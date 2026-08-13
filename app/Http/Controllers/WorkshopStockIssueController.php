<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\StockIssueChangeRequest;
use App\Models\StockIssueRequest;
use App\Services\StockIssueApplicationService;
use App\Services\WorkshopInventoryAvailabilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkshopStockIssueController extends Controller
{
    public function __construct(
        private readonly WorkshopInventoryAvailabilityService $availability,
        private readonly StockIssueApplicationService $applicator
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeInventoryManager($request);

        $user = $request->user();
        $isAdmin = (string) $user?->role === 'admin';
        $search = trim((string) $request->input('search'));

        $query = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->with(['item.category', 'item.unit', 'workshop', 'storageLocation', 'user', 'pendingIssueChangeRequest'])
            ->where('type', ItemStockMovement::TYPE_OUTGOING)
            ->when(! $isAdmin, fn (Builder $b): Builder => $b->where('workshop_id', $user?->workshop_id))
            ->when($isAdmin && $request->filled('workshop_id'), fn (Builder $b): Builder => $b->where('workshop_id', $request->integer('workshop_id')))
            ->when($search !== '', function (Builder $b) use ($search): void {
                $b->where(function (Builder $sq) use ($search): void {
                    $sq->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('item', function (Builder $iq) use ($search): void {
                            $iq->withoutGlobalScopes()
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('date_from'), fn (Builder $b): Builder => $b->whereDate('transaction_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $b): Builder => $b->whereDate('transaction_date', '<=', $request->input('date_to')))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $pendingQuery = StockIssueRequest::query()
            ->with(['requester', 'workshop', 'items.item'])
            ->where('status', StockIssueRequest::STATUS_PENDING)
            ->when(! $isAdmin, fn (Builder $b): Builder => $b->where('workshop_id', $user?->workshop_id));

        $canReview = in_array((string) $user?->role, ['admin', 'kepala_bengkel', 'wakil_sarpras'], true);

        return view('stock-issues.index', [
            'movements' => $query->paginate(20)->withQueryString(),
            'pendingRequests' => $canReview ? $pendingQuery->orderByDesc('id')->limit(10)->get() : collect(),
            'workshops' => $this->availability->visibleWorkshops($request),
            'isAdmin' => $isAdmin,
            'canReview' => $canReview,
        ]);
    }

    public function pendingIndex(Request $request): View
    {
        $this->authorizeReviewerAny($request);

        $user = $request->user();
        $isAdmin = (string) $user?->role === 'admin';
        $isWakaSarpras = (string) $user?->role === 'wakil_sarpras';

        $query = StockIssueRequest::query()
            ->with(['requester', 'workshop', 'items.item'])
            ->where('status', StockIssueRequest::STATUS_PENDING)
            ->when(! $isAdmin && ! $isWakaSarpras, fn (Builder $b): Builder => $b->where('workshop_id', $user?->workshop_id))
            ->when($isAdmin && $request->filled('workshop_id'), fn (Builder $b): Builder => $b->where('workshop_id', $request->integer('workshop_id')))
            ->orderByDesc('id');

        return view('stock-issues.pending', [
            'requests' => $query->paginate(20)->withQueryString(),
            'workshops' => $this->availability->visibleWorkshops($request),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeInventoryManager($request);

        $workshop = $this->availability->selectedWorkshop($request);

        return view('stock-issues.create', [
            'items' => $this->availability->itemsForWorkshop($workshop->id),
            'assets' => $this->availability->assetsForWorkshop($workshop->id),
            'workshops' => $this->availability->visibleWorkshops($request),
            'selectedWorkshop' => $workshop,
            'selectedWorkshopId' => (int) $workshop->id,
            'isAdmin' => (string) $request->user()?->role === 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeInventoryManager($request);

        $workshop = $this->availability->selectedWorkshop($request);

        $data = $request->validate([
            'workshop_id' => ['required', 'integer', 'exists:workshops,id'],
            'transaction_date' => ['required', 'date', 'after_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:150'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0', 'max:99999999999.999'],
            'items.*.asset_ids' => ['nullable', 'array'],
            'items.*.asset_ids.*' => ['integer', 'exists:item_assets,id'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((int) $data['workshop_id'] !== (int) $workshop->id) {
            throw ValidationException::withMessages([
                'workshop_id' => 'Jurusan transaksi tidak sesuai dengan hak akses akun.',
            ]);
        }

        $this->validateWorkshopOwnership($data['items'], $workshop->id);

        $reference = ! empty($data['reference_number'])
            ? strtoupper(trim($data['reference_number']))
            : $this->applicator->generateReference();

        $issueRequest = DB::transaction(function () use ($data, $reference, $request, $workshop): StockIssueRequest {
            $req = StockIssueRequest::create([
                'reference_number' => $reference,
                'workshop_id' => $workshop->id,
                'requested_by_user_id' => $request->user()->id,
                'status' => StockIssueRequest::STATUS_PENDING,
                'transaction_date' => $data['transaction_date'],
                'destination' => $data['destination'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $req->items()->create([
                    'item_id' => $row['item_id'],
                    'quantity' => $row['quantity'] ?? 0,
                    'asset_ids' => $row['asset_ids'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $req;
        }, attempts: 3);

        return redirect()
            ->route('stock-issues.show', $issueRequest)
            ->with('success', 'Pengajuan Barang Keluar berhasil dibuat. Menunggu persetujuan Kepala Bengkel / Wakil Sarpras.');
    }

    public function show(Request $request, StockIssueRequest $stockIssue): View
    {
        $this->authorizeViewer($request, $stockIssue);

        $stockIssue->load(['requester', 'reviewer', 'workshop', 'items.item.category', 'items.item.unit']);

        $user = $request->user();
        $canReview = $this->canReview($user, $stockIssue);
        $canCancel = $stockIssue->isPending() && (
            $user->id === $stockIssue->requested_by_user_id
            || (string) $user->role === 'admin'
        );
        $canEdit = $stockIssue->isPending() && (
            $user->id === $stockIssue->requested_by_user_id
            || (string) $user->role === 'admin'
        );

        return view('stock-issues.show', [
            'issueRequest' => $stockIssue,
            'canReview' => $canReview,
            'canCancel' => $canCancel,
            'canEdit' => $canEdit,
        ]);
    }

    public function edit(Request $request, StockIssueRequest $stockIssue): View
    {
        $this->authorizeEditor($request, $stockIssue);

        $stockIssue->load(['workshop', 'items.item.category', 'items.item.unit']);

        $workshop = $stockIssue->workshop;

        return view('stock-issues.edit', [
            'issueRequest' => $stockIssue,
            'items' => $this->availability->itemsForWorkshop($workshop->id),
            'assets' => $this->availability->assetsForWorkshop($workshop->id),
            'selectedWorkshop' => $workshop,
            'selectedWorkshopId' => (int) $workshop->id,
            'isAdmin' => (string) $request->user()?->role === 'admin',
        ]);
    }

    public function update(Request $request, StockIssueRequest $stockIssue): RedirectResponse
    {
        $this->authorizeEditor($request, $stockIssue);

        if (! $stockIssue->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'Hanya pengajuan dengan status menunggu yang dapat diedit.',
            ]);
        }

        $data = $request->validate([
            'transaction_date' => ['required', 'date', 'after_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:150'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0', 'max:99999999999.999'],
            'items.*.asset_ids' => ['nullable', 'array'],
            'items.*.asset_ids.*' => ['integer', 'exists:item_assets,id'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->validateWorkshopOwnership($data['items'], $stockIssue->workshop_id);

        DB::transaction(function () use ($data, $stockIssue): void {
            $reference = ! empty($data['reference_number'])
                ? strtoupper(trim($data['reference_number']))
                : $stockIssue->reference_number;

            $stockIssue->fill([
                'reference_number' => $reference,
                'transaction_date' => $data['transaction_date'],
                'destination' => $data['destination'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'description' => $data['description'] ?? null,
            ])->save();

            $stockIssue->items()->delete();

            foreach ($data['items'] as $row) {
                $stockIssue->items()->create([
                    'item_id' => $row['item_id'],
                    'quantity' => $row['quantity'] ?? 0,
                    'asset_ids' => $row['asset_ids'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }
        }, attempts: 3);

        return redirect()
            ->route('stock-issues.show', $stockIssue)
            ->with('success', 'Pengajuan Barang Keluar berhasil diperbarui.');
    }

    public function approve(Request $request, StockIssueRequest $stockIssue): RedirectResponse
    {
        $this->authorizeReviewer($request, $stockIssue);

        if (! $stockIssue->canBeApproved()) {
            throw ValidationException::withMessages([
                'request' => 'Pengajuan ini tidak dapat disetujui.',
            ]);
        }

        $count = $this->applicator->apply($stockIssue, $request->user());

        return redirect()
            ->route('stock-issues.index')
            ->with('success', "Pengajuan Barang Keluar disetujui. {$count} baris berhasil diproses.");
    }

    public function reject(Request $request, StockIssueRequest $stockIssue): RedirectResponse
    {
        $this->authorizeReviewer($request, $stockIssue);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        if (! $stockIssue->canBeApproved()) {
            throw ValidationException::withMessages([
                'request' => 'Pengajuan ini tidak dapat ditolak.',
            ]);
        }

        $stockIssue->fill([
            'status' => StockIssueRequest::STATUS_REJECTED,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ])->save();

        return redirect()
            ->route('stock-issues.index')
            ->with('success', 'Pengajuan Barang Keluar telah ditolak.');
    }

    public function cancel(Request $request, StockIssueRequest $stockIssue): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $stockIssue->canBeCancelled()
            && ($user->id === $stockIssue->requested_by_user_id || (string) $user->role === 'admin'),
            403
        );

        $stockIssue->fill([
            'status' => StockIssueRequest::STATUS_CANCELLED,
        ])->save();

        return redirect()
            ->route('stock-issues.index')
            ->with('success', 'Pengajuan Barang Keluar telah dibatalkan.');
    }

    private function validateWorkshopOwnership(array $items, int $workshopId): void
    {
        $itemIds = collect($items)->pluck('item_id')->unique()->values()->all();

        if (! empty($itemIds)) {
            $validItems = Item::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $itemIds)
                ->where('workshop_id', $workshopId)
                ->pluck('id');

            if ($validItems->count() !== count($itemIds)) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu barang bukan milik jurusan yang dipilih.',
                ]);
            }
        }

        foreach ($items as $index => $row) {
            $assetIds = $row['asset_ids'] ?? [];
            if (empty($assetIds)) {
                continue;
            }

            $validAssets = ItemAsset::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $assetIds)
                ->where('workshop_id', $workshopId)
                ->pluck('id');

            if ($validAssets->count() !== count(array_unique($assetIds))) {
                throw ValidationException::withMessages([
                    "items.{$index}.asset_ids" => 'Salah satu unit alat bukan milik jurusan yang dipilih.',
                ]);
            }
        }
    }

    private function authorizeInventoryManager(Request $request): void
    {
        abort_unless(
            in_array((string) $request->user()?->role, ['admin', 'toolman'], true),
            403
        );

        if ((string) $request->user()?->role === 'toolman') {
            abort_if($request->user()?->workshop_id === null, 403, 'Akun Toolman belum mempunyai jurusan.');
        }
    }

    private function authorizeViewer(Request $request, StockIssueRequest $stockIssue): void
    {
        $role = (string) $request->user()?->role;

        if (in_array($role, ['admin', 'wakil_sarpras'], true)) {
            return;
        }

        if ($role === 'kepala_bengkel' || $role === 'toolman') {
            abort_if(
                $request->user()->workshop_id !== $stockIssue->workshop_id,
                403
            );
            return;
        }

        abort(403);
    }

    private function authorizeReviewer(Request $request, StockIssueRequest $stockIssue): void
    {
        $user = $request->user();
        $role = (string) $user?->role;

        if ($role === 'admin' || $role === 'wakil_sarpras') {
            return;
        }

        if ($role === 'kepala_bengkel') {
            abort_if($user->workshop_id !== $stockIssue->workshop_id, 403, 'Anda hanya dapat menyetujui pengajuan jurusan Anda.');
            return;
        }

        abort(403, 'Anda tidak memiliki hak untuk menyetujui/menolak pengajuan.');
    }

    private function authorizeReviewerAny(Request $request): void
    {
        abort_unless(
            in_array((string) $request->user()?->role, ['admin', 'kepala_bengkel', 'wakil_sarpras'], true),
            403
        );
    }

    private function authorizeEditor(Request $request, StockIssueRequest $stockIssue): void
    {
        $user = $request->user();

        abort_unless(
            $stockIssue->isPending()
            && ($user->id === $stockIssue->requested_by_user_id || (string) $user->role === 'admin'),
            403,
            'Anda tidak dapat mengedit pengajuan ini.'
        );
    }

    private function canReview($user, StockIssueRequest $stockIssue): bool
    {
        $role = (string) $user?->role;

        if (in_array($role, ['admin', 'wakil_sarpras'], true)) {
            return true;
        }

        if ($role === 'kepala_bengkel') {
            return $user->workshop_id === $stockIssue->workshop_id;
        }

        return false;
    }

    // =========================================================================
    // Edit Barang Keluar yang sudah approved (movement outgoing)
    // Alur: Toolman ajukan perubahan → Kabeng/Admin approve/reject
    // =========================================================================

    public function editMovement(Request $request, ItemStockMovement $movement): View
    {
        abort_unless($movement->type === ItemStockMovement::TYPE_OUTGOING, 404);
        $this->authorizeMovementOwner($request, $movement);

        $movement->load(['item.unit', 'item.category', 'workshop', 'storageLocation', 'pendingIssueChangeRequest.requester']);

        $isToolman = (string) $request->user()?->role === 'toolman';

        return view('stock-issues.edit-movement', [
            'movement' => $movement,
            'canDirectEdit' => ! $isToolman,
            'requiresApproval' => $isToolman,
        ]);
    }

    public function requestEditMovement(Request $request, ItemStockMovement $movement): RedirectResponse
    {
        abort_unless($movement->type === ItemStockMovement::TYPE_OUTGOING, 404);
        $this->authorizeMovementOwner($request, $movement);

        $pending = StockIssueChangeRequest::query()
            ->where('item_stock_movement_id', $movement->id)
            ->where('status', StockIssueChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pending) {
            return back()->with('warning', 'Pengajuan perubahan sedang menunggu persetujuan.');
        }

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999999999.999'],
            'destination' => ['nullable', 'string', 'max:150'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'request_note' => ['required', 'string', 'max:1000'],
        ]);

        $newQty = round((float) $data['quantity'], 3);
        $oldQty = round((float) $movement->quantity, 3);

        $original = [
            'quantity' => $oldQty,
            'destination' => $movement->destination,
            'purpose' => $movement->purpose,
            'description' => $movement->description,
        ];

        $requested = [
            'quantity' => $newQty,
            'destination' => $data['destination'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        $isToolman = (string) $request->user()?->role === 'toolman';

        if ($isToolman) {
            if ($newQty > $oldQty) {
                $item = Item::withoutGlobalScopes()->lockForUpdate()->find($movement->item_id);
                $available = round((float) ($item?->stock ?? 0) + $oldQty, 3);
                if ($newQty > $available + 0.000001) {
                    return back()->withInput()->withErrors([
                        'quantity' => "Stok tidak cukup. Stok tersedia: {$available} (termasuk stok keluar saat ini).",
                    ]);
                }
            }

            StockIssueChangeRequest::create([
                'item_stock_movement_id' => $movement->id,
                'requested_by_user_id' => $request->user()->id,
                'status' => StockIssueChangeRequest::STATUS_PENDING,
                'original_payload' => $original,
                'requested_payload' => $requested,
                'request_note' => $data['request_note'],
            ]);

            return redirect()->route('stock-issues.index')
                ->with('success', 'Pengajuan perubahan Barang Keluar berhasil dikirim. Menunggu persetujuan Kepala Bengkel / Admin.');
        }

        $this->applyMovementEdit($movement, $original, $requested);

        return redirect()->route('stock-issues.index')
            ->with('success', 'Data Barang Keluar berhasil diperbarui.');
    }

    public function approveEditMovement(Request $request, StockIssueChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeIssueReviewer($request, $changeRequest);

        abort_unless($changeRequest->status === StockIssueChangeRequest::STATUS_PENDING, 409);

        DB::transaction(function () use ($changeRequest, $request): void {
            $this->applyMovementEdit(
                $changeRequest->movement,
                $changeRequest->original_payload,
                $changeRequest->requested_payload
            );

            $changeRequest->update([
                'status' => StockIssueChangeRequest::STATUS_APPROVED,
                'reviewed_by_user_id' => $request->user()->id,
                'review_note' => $request->input('review_note'),
                'reviewed_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()->route('stock-issues.change-approvals')
            ->with('success', 'Perubahan Barang Keluar disetujui dan diterapkan.');
    }

    public function rejectEditMovement(Request $request, StockIssueChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeIssueReviewer($request, $changeRequest);

        abort_unless($changeRequest->status === StockIssueChangeRequest::STATUS_PENDING, 409);

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $changeRequest->update([
            'status' => StockIssueChangeRequest::STATUS_REJECTED,
            'reviewed_by_user_id' => $request->user()->id,
            'review_note' => $data['review_note'],
            'reviewed_at' => now(),
        ]);

        return redirect()->route('stock-issues.change-approvals')
            ->with('success', 'Pengajuan perubahan Barang Keluar telah ditolak.');
    }

    private function applyMovementEdit(ItemStockMovement $movement, array $original, array $requested): void
    {
        DB::transaction(function () use ($movement, $original, $requested): void {
            $oldQty = round((float) ($original['quantity'] ?? $movement->quantity), 3);
            $newQty = round((float) ($requested['quantity'] ?? $movement->quantity), 3);

            $item = Item::withoutGlobalScopes()->lockForUpdate()->findOrFail($movement->item_id);

            $delta = round($newQty - $oldQty, 3);

            if (abs($delta) > 0.000001) {
                $currentStock = round((float) $item->stock, 3);

                $newStock = round($currentStock - $delta, 3);

                if ($newStock < -0.000001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantity' => "Perubahan tidak dapat diterapkan: stok akan menjadi negatif ({$newStock}).",
                    ]);
                }

                $newStock = max(0, $newStock);

                $item->fill([
                    'stock' => $newStock,
                    'status' => $newStock > 0 ? 'available' : 'out_of_stock',
                    'stock_after' => $newStock,
                ])->save();

                $movement->fill([
                    'quantity' => $newQty,
                    'stock_after' => $newStock,
                    'stock_before' => round($newStock + $newQty, 3),
                ])->save();
            }

            $movement->fill([
                'destination' => $requested['destination'] ?? null,
                'purpose' => $requested['purpose'] ?? null,
                'description' => $requested['description'] ?? null,
            ])->save();
        }, attempts: 3);
    }

    public function issueChangeApprovals(Request $request): View
    {
        $user = $request->user();
        $role = (string) $user?->role;

        abort_unless(in_array($role, ['admin', 'kepala_bengkel', 'wakil_sarpras'], true), 403);

        $requests = StockIssueChangeRequest::query()
            ->with(['movement.item.unit', 'movement.workshop', 'requester', 'reviewer'])
            ->where('status', StockIssueChangeRequest::STATUS_PENDING)
            ->when($role === 'kepala_bengkel', fn (Builder $q) => $q->whereHas('movement', fn (Builder $m) => $m->where('workshop_id', $user->workshop_id)))
            ->orderByDesc('id')
            ->paginate(20);

        return view('stock-issues.change-approvals', [
            'requests' => $requests,
        ]);
    }

    private function authorizeMovementOwner(Request $request, ItemStockMovement $movement): void
    {
        $role = (string) $request->user()?->role;

        if ($role === 'admin') return;

        if (in_array($role, ['kepala_bengkel', 'toolman'], true)) {
            abort_if($request->user()->workshop_id !== $movement->workshop_id, 403);
            return;
        }

        abort(403);
    }

    private function authorizeIssueReviewer(Request $request, StockIssueChangeRequest $changeRequest): void
    {
        $role = (string) $request->user()?->role;

        if (in_array($role, ['admin', 'wakil_sarpras'], true)) return;

        if ($role === 'kepala_bengkel') {
            abort_if($request->user()->workshop_id !== $changeRequest->movement?->workshop_id, 403);
            return;
        }

        abort(403);
    }
}
