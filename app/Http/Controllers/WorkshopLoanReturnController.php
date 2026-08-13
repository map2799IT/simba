<?php

namespace App\Http\Controllers;

use App\Models\ItemAsset;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Services\WorkshopLoanTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkshopLoanReturnController extends Controller
{
    public function __construct(
        private readonly WorkshopLoanTransactionService $transactions
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeManager($request);
        $user = $request->user();

        $loans = Loan::query()->withoutGlobalScopes()
            ->with(['borrower', 'workshop'])
            ->withCount([
                'items as open_tool_count' => fn (Builder $query): Builder =>
                    $query->where('is_consumable', false)->whereNull('returned_at'),
            ])
            ->whereIn('status', [Loan::STATUS_BORROWED, Loan::STATUS_PARTIAL])
            ->when(
                (string) $user?->role !== 'admin',
                fn (Builder $query): Builder => $query->where(
                    'workshop_id',
                    $user?->workshop_id
                )
            )
            ->orderByRaw('CASE WHEN due_at < NOW() THEN 0 ELSE 1 END')
            ->orderBy('due_at')
            ->paginate(20);

        return view('loans.returns.index', compact('loans'));
    }

    public function form(Request $request, Loan $loan): View
    {
        $this->authorizeManager($request, $loan);

        $loan->load([
            'borrower',
            'workshop',
            'items' => fn ($query) => $query
                ->where('is_consumable', false)
                ->whereNull('returned_at')
                ->with(['item', 'itemAsset.storageLocation']),
        ]);

        abort_if(
            $loan->items->isEmpty(),
            404,
            'Tidak ada unit alat yang menunggu pengembalian.'
        );

        return view('loans.returns.form', [
            'loan' => $loan,
            'conditions' => ItemAsset::conditionOptions(),
        ]);
    }

    public function process(Request $request, Loan $loan): RedirectResponse
    {
        $this->authorizeManager($request, $loan);

        $data = $request->validate([
            'returns' => ['required', 'array'],
            'returns.*.selected' => ['nullable', 'boolean'],
            'returns.*.condition' => [
                'nullable',
                'string',
                'in:good,minor_damage,major_damage',
            ],
            'returns.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->transactions->returnItems(
            $loan,
            $data['returns'],
            $request->user()
        );

        return redirect()->route('loans.show', $updated)->with(
            'success',
            $updated->status === Loan::STATUS_RETURNED
                ? 'Seluruh alat dikembalikan. Stok bertambah kembali.'
                : 'Sebagian alat dikembalikan. Stok unit yang kembali sudah bertambah.'
        );
    }

    public function returnItem(
        Request $request,
        Loan $loan,
        LoanItem $loanItem
    ): RedirectResponse {
        $this->authorizeManager($request, $loan);
        abort_unless((int) $loanItem->loan_id === (int) $loan->id, 404);

        $data = $request->validate([
            'condition' => [
                'required',
                'string',
                'in:good,minor_damage,major_damage',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->transactions->returnItems(
            $loan,
            [
                $loanItem->id => [
                    'selected' => true,
                    'condition' => $data['condition'],
                    'notes' => $data['notes'] ?? null,
                ],
            ],
            $request->user()
        );

        return back()->with(
            'success',
            'Unit dikembalikan dan stok bertambah kembali.'
        );
    }

    private function authorizeManager(Request $request, ?Loan $loan = null): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && in_array((string) $user->role, ['admin', 'toolman'], true),
            403
        );

        if ($loan !== null && (string) $user->role !== 'admin') {
            abort_unless(
                $user->workshop_id !== null
                && (int) $user->workshop_id === (int) $loan->workshop_id,
                403
            );
        }
    }
}
