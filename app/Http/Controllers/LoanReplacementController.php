<?php

namespace App\Http\Controllers;

use App\Models\ItemAsset;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\LoanItemReplacementRequest;
use App\Services\WorkshopLoanTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanReplacementController extends Controller
{
    public function __construct(
        private readonly WorkshopLoanTransactionService $transactions
    ) {
    }

    // Siswa/Guru: ajukan penggantian dari halaman loan show
    public function requestReplacement(Request $request, Loan $loan, LoanItem $loanItem): RedirectResponse
    {
        $user = $request->user();
        $role = (string) $user->role;

        abort_unless(
            $loan->borrower_id === $user->id
            || in_array($role, ['admin', 'toolman', 'kepala_bengkel'], true),
            403
        );

        abort_unless($loanItem->loan_id === $loan->id, 404);
        abort_unless(! $loanItem->is_consumable && ! $loanItem->returned_at && $loanItem->issued_at, 422);

        $data = $request->validate([
            'damage_description' => ['required', 'string', 'max:1000'],
        ]);

        $existing = LoanItemReplacementRequest::query()
            ->where('loan_item_id', $loanItem->id)
            ->where('status', LoanItemReplacementRequest::STATUS_PENDING)
            ->exists();

        if ($existing) {
            return back()->with('warning', 'Pengajuan penggantian untuk item ini sudah ada dan sedang menunggu.');
        }

        LoanItemReplacementRequest::create([
            'loan_id' => $loan->id,
            'loan_item_id' => $loanItem->id,
            'item_id' => $loanItem->item_id,
            'old_asset_id' => $loanItem->item_asset_id,
            'requested_by' => $user->id,
            'status' => LoanItemReplacementRequest::STATUS_PENDING,
            'damage_description' => $data['damage_description'],
        ]);

        return back()->with('success', 'Pengajuan penggantian alat berhasil dikirim. Toolman akan memproses penggantian segera.');
    }

    // Toolman: lihat daftar pengajuan penggantian
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = (string) $user->role;

        abort_unless(in_array($role, ['admin', 'toolman', 'kepala_bengkel'], true), 403);

        $query = LoanItemReplacementRequest::query()
            ->with([
                'loan.borrower',
                'loanItem',
                'item.unit',
                'oldAsset',
                'newAsset',
                'requester',
                'handler',
            ])
            ->when($role === 'toolman', fn (Builder $q) => $q->whereHas('loan', fn (Builder $l) => $l->where('workshop_id', $user->workshop_id)))
            ->when($role === 'kepala_bengkel', fn (Builder $q) => $q->whereHas('loan', fn (Builder $l) => $l->where('workshop_id', $user->workshop_id)))
            ->when($request->input('status', 'pending') === 'pending', fn (Builder $q) => $q->where('status', LoanItemReplacementRequest::STATUS_PENDING))
            ->when($request->input('status') === 'all', fn (Builder $q) => $q)
            ->orderByDesc('created_at');

        return view('loans.replacement-requests', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $request->input('status', 'pending'),
            'pendingCount' => LoanItemReplacementRequest::query()
                ->where('status', LoanItemReplacementRequest::STATUS_PENDING)
                ->when($role === 'toolman', fn (Builder $q) => $q->whereHas('loan', fn (Builder $l) => $l->where('workshop_id', $user->workshop_id)))
                ->count(),
        ]);
    }

    // Toolman: proses penggantian dengan kode unit tertentu
    public function fulfill(Request $request, LoanItemReplacementRequest $replacementRequest): RedirectResponse
    {
        $user = $request->user();
        $role = (string) $user->role;

        abort_unless(in_array($role, ['admin', 'toolman', 'kepala_bengkel'], true), 403);

        if ($role === 'toolman') {
            $loan = $replacementRequest->loan()->withoutGlobalScopes()->first();
            abort_if($loan?->workshop_id !== $user->workshop_id, 403);
        }

        abort_unless($replacementRequest->isPending(), 409, 'Pengajuan ini sudah diproses.');

        $data = $request->validate([
            'replacement_asset_code' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newAsset = ItemAsset::query()
            ->withoutGlobalScopes()
            ->where('asset_number', trim($data['replacement_asset_code']))
            ->where('item_id', $replacementRequest->item_id)
            ->first();

        if (! $newAsset) {
            return back()->withErrors([
                'replacement_asset_code' => "Kode unit \"{$data['replacement_asset_code']}\" tidak ditemukan untuk barang ini.",
            ])->withInput();
        }

        if (! $newAsset->is_active || $newAsset->status !== ItemAsset::STATUS_AVAILABLE) {
            return back()->withErrors([
                'replacement_asset_code' => "Unit {$newAsset->asset_number} tidak tersedia (status: {$newAsset->status}).",
            ])->withInput();
        }

        if ($newAsset->condition !== ItemAsset::CONDITION_GOOD) {
            return back()->withErrors([
                'replacement_asset_code' => "Unit {$newAsset->asset_number} tidak dalam kondisi baik ({$newAsset->condition}).",
            ])->withInput();
        }

        DB::transaction(function () use ($replacementRequest, $newAsset, $data, $user): void {
            $loan = $replacementRequest->loan()->withoutGlobalScopes()->lockForUpdate()->firstOrFail();
            $loanItem = $replacementRequest->loanItem()->lockForUpdate()->firstOrFail();

            $oldAsset = $replacementRequest->oldAsset()->lockForUpdate()->first();
            if ($oldAsset) {
                $oldAsset->fill(['status' => ItemAsset::STATUS_DAMAGED])->save();
            }

            $newAsset->fill(['status' => ItemAsset::STATUS_BORROWED])->save();

            $loanItem->fill([
                'item_asset_id' => $newAsset->id,
                'condition_out' => $newAsset->condition,
            ])->save();

            $replacementRequest->fill([
                'status' => LoanItemReplacementRequest::STATUS_FULFILLED,
                'new_asset_id' => $newAsset->id,
                'replacement_asset_code' => $newAsset->asset_number,
                'handled_by' => $user->id,
                'handled_at' => now(),
                'notes' => $data['notes'] ?? null,
            ])->save();
        }, attempts: 3);

        return redirect()
            ->route('loans.replacement-requests.index')
            ->with('success', "Unit berhasil diganti dengan {$newAsset->asset_number}.");
    }

    // Siswa/Requester: batalkan pengajuan
    public function cancel(Request $request, LoanItemReplacementRequest $replacementRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $replacementRequest->requested_by === $user->id
            || in_array((string) $user->role, ['admin', 'toolman'], true),
            403
        );

        abort_unless($replacementRequest->isPending(), 409);

        $replacementRequest->fill(['status' => LoanItemReplacementRequest::STATUS_CANCELLED])->save();

        return back()->with('success', 'Pengajuan penggantian dibatalkan.');
    }
}
