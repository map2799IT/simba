<?php

namespace App\Http\Controllers;
use App\Traits\SortsIndex;
use App\Models\DamageReport;
use App\Http\Requests\RejectLoanRequest;
use App\Http\Requests\ReturnLoanItemRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @deprecated Route sudah di-override ke WorkshopLoanController.
 *             Controller ini tidak boleh dipakai dan akan throw
 *             exception jika diakses secara langsung.
 */
class LoanController extends Controller
{
    use SortsIndex;

    public function __construct()
    {
        abort(403, 'LoanController tidak aktif. Gunakan WorkshopLoanController.');
    }
    public function index(
        Request $request
    ): View {
        $user = $request->user();

        [$sort, $direction, $perPage] = $this->indexSortParams(['code', 'purpose', 'request_date', 'due_at']);

        $canViewAll = $user->hasRole(
            'admin',
            'kepala_bengkel',
            'toolman'
        );

        $search = trim(
            (string) $request->input('search')
        );

        $loans = Loan::query()
            ->with([
                'borrower',
                'approver',
                'items.item.category',
                'items.item.workshop',
            ])
            ->when(
                ! $canViewAll,
                fn (Builder $query): Builder =>
                    $query->where(
                        'borrower_id',
                        $user->id
                    )
            )
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $subquery
                        ) use ($search): void {
                            $subquery
                                ->where(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'purpose',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'borrower',
                                    fn (Builder $userQuery): Builder =>
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'items.item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('status'),
                function (
                    Builder $query
                ) use ($request): void {
                    $status = (string)
                        $request->input('status');

                    if ($status === 'overdue') {
                        $query
                            ->whereIn('status', [
                                Loan::STATUS_BORROWED,
                                Loan::STATUS_PARTIALLY_RETURNED,
                            ])
                            ->where(
                                'due_at',
                                '<',
                                now()
                            );

                        return;
                    }

                    $query->where(
                        'status',
                        $status
                    );
                }
            )
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->when($sort !== null, fn ($q) => $q->orderBy($sort, $direction))
            ->paginate($perPage)
            ->withQueryString();

        return view('loans.index', [
            'loans' => $loans,

            'statuses' =>
                Loan::filterStatusOptions(),

            'canReview' => $user->hasRole(
                'admin',
                'toolman'
            ),
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $tools = Item::query()
            ->with([
                'category',
                'workshop',
                'location.parent.parent.parent',
            ])
            ->where('type', 'tool')
            ->where('is_active', true)
            ->where('is_borrowable', true)
            ->where('status', 'available')
            ->where('condition', 'good')
            ->orderBy('workshop_id')
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        return view('loans.create', [
            'tools' => $tools,
        ]);
    }

    public function store(
        StoreLoanRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $loan = DB::transaction(
            function () use (
                $data,
                $request
            ): Loan {
                $itemIds = collect(
                    $data['items']
                )
                    ->map(
                        fn (mixed $id): int =>
                            (int) $id
                    )
                    ->unique()
                    ->values();

                $tools = Item::query()
                    ->whereIn('id', $itemIds)
                    ->lockForUpdate()
                    ->get();

                $this->validateAvailableTools(
                    $itemIds,
                    $tools
                );

                $loan = Loan::query()->create([
                    'code' => null,

                    'borrower_id' =>
                        $request->user()->id,

                    'status' =>
                        Loan::STATUS_PENDING,

                    'request_date' =>
                        now()->toDateString(),

                    'due_at' =>
                        $data['due_at'],

                    'purpose' =>
                        $data['purpose'],

                    'notes' =>
                        $data['notes'] ?? null,
                ]);

                $loan->fill([
                    'code' => sprintf(
                        'PJM-%s-%06d',
                        now()->format('Ymd'),
                        $loan->id
                    ),
                ])->save();

                foreach ($tools as $tool) {
                    LoanItem::query()->create([
                        'loan_id' => $loan->id,
                        'item_id' => $tool->id,

                        'condition_out' =>
                            $tool->condition,
                    ]);
                }

                return $loan;
            },
            attempts: 3
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Pengajuan peminjaman berhasil dibuat.'
            );
    }

    public function show(
        Request $request,
        Loan $loan
    ): View {
        $this->ensureCanViewLoan(
            $request,
            $loan
        );

        $loan->load([
            'borrower',
            'approver',
            'rejector',
            'returner',
            'items.item.category',
            'items.item.workshop',
            'items.item.location.parent.parent.parent',
            'items.returnedBy',
        ]);

        return view('loans.show', [
            'loan' => $loan,

            'conditions' =>
                Item::conditionOptions(),

            'canReview' =>
                $request->user()->hasRole(
                    'admin',
                    'toolman'
                ),
        ]);
    }

    /**
     * Cetak Surat Serah Terima / Surat Peminjaman.
     *
     * Hanya boleh dicetak oleh Admin, Kepala Bengkel, atau Toolman
     * dan hanya setelah alat benar-benar diserahkan (status borrowed).
     */
    public function permit(
        Request $request,
        Loan $loan
    ): mixed {
        if (
            ! $request->user()->hasRole(
                'admin',
                'kepala_bengkel',
                'toolman'
            )
        ) {
            abort(403, 'Hanya Admin, Kepala Bengkel, atau Toolman yang dapat mencetak surat.');
        }

        if ($loan->status !== Loan::STATUS_BORROWED) {
            abort(422, 'Surat hanya dapat dicetak setelah alat diserahkan.');
        }

        $loan->load([
            'borrower',
            'approver',
            'workshop',
            'items.item.unit',
            'items.item.workshop',
            'items.itemAsset',
            'items.returnedBy',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'loans.permit',
            [
                'loan' => $loan,
                'generatedAt' => now(),
            ]
        )->setPaper('a4', 'portrait');

        return $pdf->download(
            sprintf(
                'surat-serah-terima-%s.pdf',
                $loan->code
            )
        );
    }

    public function approve(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        DB::transaction(
            function () use (
                $loan,
                $request
            ): void {
                $lockedLoan = Loan::query()
                    ->lockForUpdate()
                    ->findOrFail($loan->id);

                if (! $lockedLoan->canBeApproved()) {
                    throw ValidationException::withMessages([
                        'loan' =>
                            'Peminjaman ini tidak dapat disetujui.',
                    ]);
                }

                if ($lockedLoan->due_at->isPast()) {
                    throw ValidationException::withMessages([
                        'loan' =>
                            'Batas waktu pengembalian sudah terlewati.',
                    ]);
                }

                $loanItems = LoanItem::query()
                    ->where(
                        'loan_id',
                        $lockedLoan->id
                    )
                    ->lockForUpdate()
                    ->get();

                $itemIds = $loanItems
                    ->pluck('item_id')
                    ->values();

                $tools = Item::query()
                    ->whereIn('id', $itemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($loanItems as $loanItem) {
                    /** @var Item|null $tool */
                    $tool = $tools->get(
                        $loanItem->item_id
                    );

                    if (
                        $tool === null
                        || ! $tool->isTool()
                        || ! $tool->is_active
                        || ! $tool->is_borrowable
                        || $tool->status !== 'available'
                    ) {
                        throw ValidationException::withMessages([
                            'loan' =>
                                'Salah satu alat sudah tidak tersedia.',
                        ]);
                    }

                    $loanItem->fill([
                        'condition_out' =>
                            $tool->condition,
                    ])->save();

                    $tool->fill([
                        'status' => 'borrowed',
                    ])->save();
                }

                $lockedLoan->fill([
                    'status' =>
                        Loan::STATUS_BORROWED,

                    'approved_by' =>
                        $request->user()->id,

                    'approved_at' => now(),
                    'borrowed_at' => now(),
                ])->save();
            },
            attempts: 3
        );

        return back()->with(
            'success',
            'Peminjaman berhasil disetujui dan alat telah diserahkan.'
        );
    }

    public function reject(
        RejectLoanRequest $request,
        Loan $loan
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(
            function () use (
                $loan,
                $request,
                $data
            ): void {
                $lockedLoan = Loan::query()
                    ->lockForUpdate()
                    ->findOrFail($loan->id);

                if (! $lockedLoan->canBeRejected()) {
                    throw ValidationException::withMessages([
                        'loan' =>
                            'Peminjaman ini tidak dapat ditolak.',
                    ]);
                }

                $lockedLoan->fill([
                    'status' =>
                        Loan::STATUS_REJECTED,

                    'rejected_by' =>
                        $request->user()->id,

                    'rejected_at' => now(),

                    'rejection_reason' =>
                        $data['rejection_reason'],
                ])->save();
            },
            attempts: 3
        );

        return back()->with(
            'success',
            'Pengajuan peminjaman berhasil ditolak.'
        );
    }

    public function returnItem(
        ReturnLoanItemRequest $request,
        Loan $loan,
        LoanItem $loanItem
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(
            function () use (
                $loan,
                $loanItem,
                $request,
                $data
            ): void {
                $lockedLoan = Loan::query()
                    ->lockForUpdate()
                    ->findOrFail($loan->id);

                if (! $lockedLoan->canReceiveReturns()) {
                    throw ValidationException::withMessages([
                        'loan' =>
                            'Peminjaman ini tidak menerima pengembalian.',
                    ]);
                }

                $lockedLoanItem = LoanItem::query()
                    ->where(
                        'loan_id',
                        $lockedLoan->id
                    )
                    ->whereKey($loanItem->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedLoanItem->isReturned()) {
                    throw ValidationException::withMessages([
                        'loan_item' =>
                            'Alat ini sudah dikembalikan.',
                    ]);
                }

                $tool = Item::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lockedLoanItem->item_id
                    );

                $condition = $data['condition_in'];

                $lockedLoanItem->fill([
                    'returned_by' =>
                        $request->user()->id,

                    'condition_in' =>
                        $condition,

                    'returned_at' => now(),

                    'return_notes' =>
                        $data['return_notes'] ?? null,
                ])->save();

                $tool->fill([
                    'condition' => $condition,

                    'status' =>
                        $this->statusFromCondition(
                            $condition
                        ),
                ])->save();
                /*
                * Membuat laporan kerusakan otomatis ketika
                * alat kembali dalam kondisi tidak baik.
                */
                if ($condition !== 'good') {
                    $existingOpenReport = DamageReport::query()
                        ->where(
                            'item_id',
                            $tool->id
                        )
                        ->whereIn(
                            'status',
                            DamageReport::openStatuses()
                        )
                        ->first();

                    if ($existingOpenReport === null) {
                        $damageReport = DamageReport::query()
                            ->create([
                                'code' => null,

                                'item_id' =>
                                    $tool->id,

                                'loan_item_id' =>
                                    $lockedLoanItem->id,

                                'reported_by' =>
                                    $request->user()->id,

                                'status' =>
                                    DamageReport::STATUS_REPORTED,

                                'severity' =>
                                    $condition,

                                'reported_at' => now(),

                                'condition_before' =>
                                    $lockedLoanItem
                                        ->condition_out,

                                'description' =>
                                    "Kerusakan ditemukan saat pengembalian peminjaman {$lockedLoan->code}.",

                                'notes' =>
                                    $data['return_notes']
                                    ?? null,
                            ]);

                        $damageReport->fill([
                            'code' => sprintf(
                                'RSK-%s-%06d',
                                now()->format('Ymd'),
                                $damageReport->id
                            ),
                        ])->save();
                    }
                }
                $remainingItems = LoanItem::query()
                    ->where(
                        'loan_id',
                        $lockedLoan->id
                    )
                    ->whereNull('returned_at')
                    ->count();

                if ($remainingItems === 0) {
                    $lockedLoan->fill([
                        'status' =>
                            Loan::STATUS_RETURNED,

                        'returned_by' =>
                            $request->user()->id,

                        'returned_at' => now(),
                    ])->save();

                    return;
                }

                $lockedLoan->fill([
                    'status' =>
                        Loan::STATUS_PARTIALLY_RETURNED,
                ])->save();
            },
            attempts: 3
        );

        return back()->with(
            'success',
            'Pengembalian alat berhasil dicatat.'
        );
    }

    private function validateAvailableTools(
        Collection $requestedIds,
        Collection $tools
    ): void {
        if (
            $tools->count()
            !== $requestedIds->count()
        ) {
            throw ValidationException::withMessages([
                'items' =>
                    'Salah satu alat tidak ditemukan.',
            ]);
        }

        foreach ($tools as $tool) {
            if (! $tool->isTool()) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Peminjaman hanya berlaku untuk alat.',
                ]);
            }

            if (
                ! $tool->is_active
                || ! $tool->is_borrowable
                || $tool->status !== 'available'
                || $tool->condition !== 'good'
            ) {
                throw ValidationException::withMessages([
                    'items' =>
                        "Alat {$tool->code} tidak tersedia untuk dipinjam.",
                ]);
            }
        }
    }

    private function ensureCanViewLoan(
        Request $request,
        Loan $loan
    ): void {
        $user = $request->user();

        if (
            $user->hasRole(
                'admin',
                'kepala_bengkel',
                'toolman'
            )
        ) {
            return;
        }

        abort_unless(
            $loan->borrower_id === $user->id,
            403
        );
    }

    private function statusFromCondition(
        string $condition
    ): string {
        return match ($condition) {
            'good' => 'available',

            'maintenance' => 'maintenance',

            'minor_damage',
            'major_damage',
            'unfit' => 'damaged',

            default => 'damaged',
        };
    }
}