<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Services\LoanDueDateService;
use App\Services\WorkshopLoanInventoryService;
use App\Services\WorkshopLoanTransactionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkshopLoanController
    extends Controller
{
    public function __construct(
        private readonly WorkshopLoanInventoryService $inventory,
        private readonly WorkshopLoanTransactionService $transactions,
        private readonly LoanDueDateService $dueDateService
    ) {
    }

    public function index(
        Request $request
    ): View {
        $user =
            $request->user();

        $canFilterWorkshop =
            in_array(
                (string) $user?->role,
                [
                    'admin',
                    'guru',
                ],
                true
            );

        $query =
            Loan::query()
                ->withoutGlobalScopes()
                ->with([
                    'borrower',
                    'workshop',
                    'assignedToolman',
                ])
                ->withCount('items')
                ->when(
                    (string)
                    $user?->role
                    === 'admin',
                    fn (
                        Builder $builder
                    ): Builder =>
                        $builder->when(
                            $request->filled(
                                'workshop_id'
                            ),
                            fn (
                                Builder $scope
                            ): Builder =>
                                $scope->where(
                                    'workshop_id',
                                    $request
                                        ->integer(
                                            'workshop_id'
                                        )
                                )
                        ),
                    function (
                        Builder $builder
                    ) use (
                        $user
                    ): Builder {
                        if (
                            in_array(
                                (string)
                                $user?->role,
                                [
                                    'toolman',
                                    'kepala_bengkel',
                                ],
                                true
                            )
                        ) {
                            return $builder
                                ->where(
                                    'workshop_id',
                                    $user
                                        ?->workshop_id
                                );
                        }

                        /*
                         * Guru dan siswa hanya melihat
                         * peminjaman miliknya sendiri.
                         */
                        return $builder
                            ->where(
                                'borrower_id',
                                $user?->id
                            );
                    }
                )
                ->when(
                    $request->filled(
                        'status'
                    ),
                    fn (
                        Builder $builder
                    ): Builder =>
                        $builder->where(
                            'status',
                            $request
                                ->input(
                                    'status'
                                )
                        )
                )
                ->when(
                    trim(
                        (string)
                        $request->input(
                            'search'
                        )
                    ) !== '',
                    function (
                        Builder $builder
                    ) use (
                        $request
                    ): void {
                        $search =
                            trim(
                                (string)
                                $request
                                    ->input(
                                        'search'
                                    )
                            );

                        $builder->where(
                            function (
                                Builder $scope
                            ) use (
                                $search
                            ): void {
                                $scope
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
                                        fn (
                                            Builder
                                            $borrower
                                        ): Builder =>
                                            $borrower
                                                ->where(
                                                    'name',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                    );
                            }
                        );
                    }
                )
                ->orderByDesc(
                    'request_date'
                )
                ->orderByDesc('id');

        return view(
            'loans.index',
            [
                'loans' =>
                    $query
                        ->paginate(20)
                        ->withQueryString(),

                'workshops' =>
                    $this->inventory
                        ->visibleWorkshops(
                            $request
                        ),

                'statuses' =>
                    Loan::statusOptions(),

                'isAdmin' =>
                    (string)
                    $user?->role
                    === 'admin',

                'canFilterWorkshop' =>
                    $canFilterWorkshop,

                'canCreateLoan' =>
                    $this->canCreateLoan(
                        $request
                    ),

                'canManage' =>
                    $this->canManage(
                        $request
                    ),
            ]
        );
    }

    public function create(
        Request $request
    ): View {
        $user =
            $request->user();

        if (
            (string) $user?->role
                === 'siswa'
            && $user?->workshop_id
                === null
        ) {
            return view(
                'loans.workshop-required'
            );
        }

        $workshop =
            $this->inventory
                ->selectedWorkshop(
                    $request
                );

        $role =
            (string)
            $user?->role;

        $canSetCustomDue = $this->dueDateService->canSetCustomDueDate($user);
        $now = now(config('app.timezone', 'Asia/Jakarta'));

        // Preview due_at untuk ditampilkan di UI (nilai sesungguhnya ditentukan backend saat store)
        $previewDueAt = match (true) {
            $role === 'siswa' => $now->copy()->setTime(15, 0, 0),
            $role === 'guru' => $now->copy()->addDays(3),
            default => $now->copy()->addDays(3)->setTime(15, 0, 0),
        };

        $dueRuleText = match (true) {
            $role === 'siswa' => 'Batas pengembalian hari ini pukul 15.00 WIB.',
            $role === 'guru' => 'Jatuh tempo otomatis 3 hari dari waktu peminjaman.',
            $canSetCustomDue => 'Anda dapat menentukan jatuh tempo sendiri, atau biarkan kosong untuk default (3 hari, pukul 15.00 WIB).',
            default => 'Jatuh tempo otomatis 3 hari pukul 15.00 WIB.',
        };

        return view(
            'loans.create',
            [
                'workshops' =>
                    $this->inventory
                        ->visibleWorkshops(
                            $request
                        ),

                'selectedWorkshop' =>
                    $workshop,

                'selectedWorkshopId' =>
                    (int)
                    $workshop->id,

                'borrowers' =>
                    $this->inventory
                        ->borrowers(
                            $request,
                            (int)
                            $workshop->id
                        ),

                'items' =>
                    $this->inventory
                        ->items(
                            (int)
                            $workshop->id
                        ),

                'assets' =>
                    $this->inventory
                        ->assets(
                            (int)
                            $workshop->id
                        ),

                'inventorySummary' =>
                    $this->inventory
                        ->inventorySummary(
                            (int)
                            $workshop->id
                        ),

                'workshopToolmen' =>
                    $this->inventory
                        ->toolmenForWorkshop(
                            (int)
                            $workshop->id
                        ),

                'isAdmin' =>
                    $role === 'admin',

                'canSelectWorkshop' =>
                    in_array(
                        $role,
                        [
                            'admin',
                            'guru',
                        ],
                        true
                    ),

                'isBorrowerOnly' =>
                    in_array(
                        $role,
                        [
                            'guru',
                            'siswa',
                        ],
                        true
                    ),

                'borrowerRole' =>
                    $role,

                'canSetCustomDue' =>
                    $canSetCustomDue,

                'previewDueAt' =>
                    $previewDueAt,

                'dueRuleText' => $dueRuleText,
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $user =
            $request->user();

        $role =
            (string)
            $user?->role;

        if (
            $role === 'siswa'
            && $user?->workshop_id
                === null
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Akun siswa belum terhubung dengan jurusan. Hubungi Administrator.',
                ]);
        }

        $workshop =
            $this->inventory
                ->selectedWorkshop(
                    $request
                );

        if (
            $role === 'siswa'
            && (int) $user->workshop_id
                !== (int) $workshop->id
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Siswa hanya dapat mengajukan peminjaman pada jurusannya sendiri.',
                ]);
        }

        if (
            in_array(
                $role,
                [
                    'guru',
                    'siswa',
                ],
                true
            )
            && ! $this->inventory
                ->hasToolmanForWorkshop(
                    (int) $workshop->id
                )
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        "Jurusan {$workshop->code} belum mempunyai akun Toolman aktif. Pengajuan belum dapat dikirim.",
                ]);
        }

        $rules = [
            'borrower_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'workshop_id' => [
                'required',
                'integer',
                'exists:workshops,id',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            'due_time' => [
                'nullable',
                'required_with:due_date',
                'date_format:H:i',
            ],

            'purpose' => [
                'required',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'items.*.item_id' => [
                'required',
                'integer',
                'exists:items,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999999.999',
            ],
        ];

        $data =
            $request->validate(
                $rules
            );

        if (
            (int)
            $data['workshop_id']
            !== (int)
            $workshop->id
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Jurusan pengajuan tidak sesuai dengan hak akses akun.',
                ]);
        }

        /*
         * Guru dan siswa tidak boleh mengubah borrower_id lewat DevTools.
         */
        if (
            in_array(
                $role,
                ['guru', 'siswa'],
                true
            )
        ) {
            $borrowerId = (int) $user->id;
        } else {
            $borrowerId = (int) ($data['borrower_id'] ?? 0);
        }

        $borrower =
            $this->inventory
                ->borrowers(
                    $request,
                    (int) $workshop->id
                )
                ->firstWhere('id', $borrowerId);

        if ($borrower === null) {
            throw ValidationException::
                withMessages([
                    'borrower_id' =>
                        'Peminjam tidak berada dalam cakupan hak akses akun.',
                ]);
        }

        /*
         * Waktu peminjaman selalu NOW() dari server.
         * Frontend tidak dipercaya untuk menentukan waktu peminjaman.
         */
        $scheduledAt = now(config('app.timezone', 'Asia/Jakarta'));

        // Validasi khusus siswa: tidak boleh meminjam setelah pukul 15:00
        if ((string) $borrower->role === 'siswa') {
            $this->dueDateService->validateSiswaBorrowTimeForController($scheduledAt);
        }

        // Hitung due_at berdasarkan actor + borrower (bukan hanya actor.role)
        $dueAt = $this->dueDateService->calculateForController(
            actor: $user,
            borrower: $borrower,
            borrowedAt: $scheduledAt,
            dueDateStr: $data['due_date'] ?? null,
            dueTimeStr: $data['due_time'] ?? null
        );

        $availableItemIds =
            $this->inventory
                ->items(
                    (int)
                    $workshop->id
                )
                ->pluck('id')
                ->map(
                    static fn (
                        mixed $id
                    ): int =>
                        (int) $id
                )
                ->all();

        if ($availableItemIds === []) {
            throw ValidationException::
                withMessages([
                    'items' =>
                        "Tidak ada stok tersedia pada jurusan {$workshop->code}.",
                ]);
        }

        $loan =
            DB::transaction(
                function () use (
                    $data,
                    $workshop,
                    $borrowerId,
                    $scheduledAt,
                    $dueAt,
                    $availableItemIds
                ): Loan {
                    $loan =
                        Loan::query()
                            ->create([
                                'code' =>
                                    $this
                                        ->generateCode(),

                                'borrower_id' =>
                                    $borrowerId,

                                'workshop_id' =>
                                    $workshop->id,

                                /*
                                 * Semua Toolman jurusan terpilih
                                 * dapat melihat pengajuan pending.
                                 * Toolman yang approve baru dicatat.
                                 */
                                'assigned_toolman_id' =>
                                    null,

                                'status' =>
                                    Loan::
                                        STATUS_PENDING,

                                'request_date' =>
                                    $scheduledAt
                                        ->toDateString(),

                                'scheduled_at' =>
                                    $scheduledAt,

                                'due_at' =>
                                    $dueAt,

                                'purpose' =>
                                    $data[
                                        'purpose'
                                    ],

                                'notes' =>
                                    $data['notes']
                                    ?? null,
                            ]);

                    $lineCount = 0;

                    foreach (
                        $data['items']
                        as $index => $row
                    ) {
                        $itemId =
                            (int)
                            $row['item_id'];

                        if (
                            ! in_array(
                                $itemId,
                                $availableItemIds,
                                true
                            )
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.item_id" =>
                                        'Barang tidak tersedia pada jurusan yang dipilih.',
                                ]);
                        }

                        $item =
                            Item::query()
                                ->withoutGlobalScopes()
                                ->with('unit')
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->findOrFail(
                                    $itemId
                                );

                        if ($item->isTool()) {
                            $rawQuantity =
                                (float)
                                $row['quantity'];

                            if (
                                abs(
                                    $rawQuantity
                                    - round(
                                        $rawQuantity
                                    )
                                ) > 0.000001
                            ) {
                                throw ValidationException::
                                    withMessages([
                                        "items.{$index}.quantity" =>
                                            'Jumlah alat wajib berupa bilangan bulat.',
                                    ]);
                            }

                            $quantity =
                                (int)
                                round(
                                    $rawQuantity
                                );

                            if ($quantity < 1) {
                                throw ValidationException::
                                    withMessages([
                                        "items.{$index}.quantity" =>
                                            'Jumlah alat minimal satu unit.',
                                    ]);
                            }

                            /*
                             * Pemilihan unit tidak mempercayai browser.
                             * Backend mengambil nomor inventaris terkecil
                             * yang benar-benar masih tersedia.
                             */
                            $assets =
                                $this->inventory
                                    ->selectToolAssetsBySequence(
                                        (int) $item->id,
                                        (int) $workshop->id,
                                        $quantity,
                                        true
                                    );

                            if (
                                $assets->count()
                                !== $quantity
                            ) {
                                throw ValidationException::
                                    withMessages([
                                        "items.{$index}.quantity" =>
                                            "Unit {$item->name} yang dapat dialokasikan hanya {$assets->count()}. Kurangi jumlah atau muat ulang halaman.",
                                    ]);
                            }

                            foreach (
                                $assets
                                as $asset
                            ) {
                                LoanItem::query()
                                    ->create([
                                        'loan_id' =>
                                            $loan->id,

                                        'item_id' =>
                                            $item->id,

                                        'item_asset_id' =>
                                            $asset->id,

                                        'workshop_id' =>
                                            $workshop->id,

                                        'quantity' =>
                                            1,

                                        'is_consumable' =>
                                            false,

                                        'condition_out' =>
                                            $asset
                                                ->condition
                                            ?: 'good',
                                    ]);

                                $lineCount++;
                            }

                            continue;
                        }

                        $quantity =
                            round(
                                (float)
                                (
                                    $row[
                                        'quantity'
                                    ]
                                    ?? 0
                                ),
                                3
                            );

                        if ($quantity <= 0) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.quantity" =>
                                        'Jumlah bahan wajib lebih dari nol.',
                                ]);
                        }

                        if (
                            $item->unit !== null
                            && ! $item
                                ->unit
                                ->allows_decimal
                            && abs(
                                $quantity
                                - round(
                                    $quantity
                                )
                            ) > 0.000001
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.quantity" =>
                                        'Satuan bahan ini tidak mengizinkan desimal.',
                                ]);
                        }

                        $available =
                            $this->inventory
                                ->materialAvailable(
                                    $item->id,
                                    (int)
                                    $workshop->id
                                );

                        if (
                            $quantity
                            > $available
                            + 0.000001
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.quantity" =>
                                        "Stok bahan pada jurusan hanya {$available}.",
                                ]);
                        }

                        LoanItem::query()
                            ->create([
                                'loan_id' =>
                                    $loan->id,

                                'item_id' =>
                                    $item->id,

                                'item_asset_id' =>
                                    null,

                                'workshop_id' =>
                                    $workshop->id,

                                'quantity' =>
                                    $quantity,

                                'is_consumable' =>
                                    true,

                                'condition_out' =>
                                    'good',
                            ]);

                        $lineCount++;
                    }

                    if ($lineCount === 0) {
                        throw ValidationException::
                            withMessages([
                                'items' =>
                                    'Pengajuan belum memiliki barang.',
                            ]);
                    }

                    return $loan;
                },
                attempts: 3
            );

        return redirect()
            ->route(
                'loans.show',
                $loan
            )
            ->with(
                'success',
                $role === 'siswa'
                    ? "Pengajuan berhasil dikirim ke Toolman {$workshop->code}. Status sekarang Menunggu Persetujuan."
                    : 'Pengajuan terjadwal berhasil dibuat. Pengajuan dikirim ke Toolman jurusan yang dipilih dan stok belum berubah sampai serah terima.'
            );
    }

    public function show(
        Request $request,
        Loan $loan
    ): View {
        $this->authorizeLoan(
            $request,
            $loan
        );

        $loan->load([
            'borrower',
            'workshop',
            'assignedToolman',
            'approver',
            'rejecter',
            'returner',
            'extender',
            'items.item.unit',
            'items.itemAsset.storageLocation',
            'items.returnedBy',
        ]);

        return view(
            'loans.show',
            [
                'loan' =>
                    $loan,

                'canManage' =>
                    $this->canManage(
                        $request,
                        $loan
                    ),

                'canExtend' =>
                    $this->canManage(
                        $request,
                        $loan
                    )
                    && $loan->borrower !== null
                    && (string) $loan->borrower->role
                        === 'guru',

                'canCancel' =>
                    $this->canCancel(
                        $request,
                        $loan
                    ),

                'canRequestReplacement' =>
                    $request->user() !== null
                    && (
                        $request->user()->id
                            === (int) $loan->borrower_id
                        || (string) $request->user()->role
                            === 'admin'
                    ),
            ]
        );
    }

    public function approve(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeManager(
            $request,
            $loan
        );

        $this->transactions
            ->approve(
                $loan,
                $request->user()
            );

        return back()->with(
            'success',
            'Pengajuan disetujui. Unit alat dipesan; stok belum berkurang sampai serah terima.'
        );
    }

    public function reject(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeManager(
            $request,
            $loan
        );

        $data =
            $request->validate([
                'rejection_reason' => [
                    'required',
                    'string',
                    'max:2000',
                ],
            ]);

        $this->transactions
            ->reject(
                $loan,
                $request->user(),
                $data[
                    'rejection_reason'
                ]
            );

        return back()->with(
            'success',
            'Pengajuan ditolak.'
        );
    }

    public function checkout(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeManager(
            $request,
            $loan
        );

        $updated =
            $this->transactions
                ->checkout(
                    $loan,
                    $request->user()
                );

        return redirect()
            ->route(
                'loans.show',
                $updated
            )
            ->with(
                'success',
                $updated->status
                    === Loan::
                        STATUS_COMPLETED
                    ? 'Bahan diserahkan dan stok langsung berkurang. Tidak ada pengembalian.'
                    : 'Alat diserahterimakan. Stok berkurang dan unit berstatus Dipinjam.'
            );
    }

    public function complete(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeManager(
            $request,
            $loan
        );

        $openTools =
            LoanItem::query()
                ->where(
                    'loan_id',
                    $loan->id
                )
                ->where(
                    'is_consumable',
                    false
                )
                ->whereNull(
                    'returned_at'
                )
                ->count();

        if ($openTools > 0) {
            throw ValidationException::
                withMessages([
                    'loan' =>
                        'Masih ada unit alat yang belum dikembalikan.',
                ]);
        }

        $loan
            ->fill([
                'status' =>
                    Loan::
                        STATUS_COMPLETED,

                'returned_at' =>
                    $loan->returned_at
                    ?: now(),

                'returned_by' =>
                    $loan->returned_by
                    ?: $request
                        ->user()
                        ->id,
            ])
            ->save();

        return back()->with(
            'success',
            'Transaksi peminjaman diselesaikan.'
        );
    }

    public function cancel(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeLoan(
            $request,
            $loan
        );

        if (
            ! $this->canCancel(
                $request,
                $loan
            )
        ) {
            abort(
                403,
                'Peminjaman tidak dapat dibatalkan.'
            );
        }

        $this->transactions
            ->cancel(
                $loan,
                $request->user()
            );

        return back()->with(
            'success',
            'Pengajuan dibatalkan.'
        );
    }

    /**
     * Deadline default: borrowed_at + 3 hari kalender pukul 15:00 WIB.
     * Custom due date HANYA untuk Admin dan Toolman.
     * Backend-enforced — frontend tidak dipercaya.
     */
    // resolveDueAt dipindahkan ke LoanDueDateService::calculate()
    // Method ini dipertahankan sementara agar tidak ada referensi yang terputus

    private function authorizeLoan(
        Request $request,
        Loan $loan
    ): void {
        $user =
            $request->user();

        abort_if(
            $user === null,
            401
        );

        if (
            (string) $user->role
            === 'admin'
        ) {
            return;
        }

        if (
            in_array(
                (string) $user->role,
                [
                    'toolman',
                    'kepala_bengkel',
                ],
                true
            )
        ) {
            abort_unless(
                $user->workshop_id
                    !== null
                && (int)
                    $user->workshop_id
                    === (int)
                        $loan
                            ->workshop_id,
                403
            );

            return;
        }

        abort_unless(
            (int)
            $loan->borrower_id
            === (int)
            $user->id,
            403
        );
    }

    private function authorizeManager(
        Request $request,
        Loan $loan
    ): void {
        abort_unless(
            $this->canManage(
                $request,
                $loan
            ),
            403,
            'Persetujuan dan serah terima hanya dapat dilakukan Toolman jurusan atau Administrator.'
        );
    }

    private function canCreateLoan(
        Request $request
    ): bool {
        $user =
            $request->user();

        if ($user === null) {
            return false;
        }

        if (
            (string) $user->role
            === 'siswa'
        ) {
            return $user
                ->workshop_id
                !== null;
        }

        return in_array(
            (string) $user->role,
            [
                'admin',
                'toolman',
                'guru',
            ],
            true
        );
    }

    private function canManage(
        Request $request,
        ?Loan $loan = null
    ): bool {
        $user =
            $request->user();

        if (
            $user === null
            || ! in_array(
                (string) $user->role,
                [
                    'admin',
                    'toolman',
                ],
                true
            )
        ) {
            return false;
        }

        if (
            (string) $user->role
                === 'admin'
            || $loan === null
        ) {
            return true;
        }

        return $user->workshop_id
                !== null
            && (int)
                $user->workshop_id
                === (int)
                    $loan
                        ->workshop_id;
    }

    private function canCancel(
        Request $request,
        Loan $loan
    ): bool {
        if (
            ! in_array(
                $loan->status,
                [
                    Loan::STATUS_PENDING,
                    Loan::STATUS_APPROVED,
                ],
                true
            )
        ) {
            return false;
        }

        return $this->canManage(
            $request,
            $loan
        )
            || (int)
                $request->user()?->id
                === (int)
                    $loan
                        ->borrower_id;
    }

    private function generateCode(): string
    {
        do {
            $code =
                'PJM-'.
                now()
                    ->format(
                        'Ymd'
                    ).
                '-'.
                Str::upper(
                    Str::random(6)
                );
        } while (
            Loan::query()
                ->where(
                    'code',
                    $code
                )
                ->exists()
        );

        return $code;
    }

    public function replaceAsset(
        Request $request,
        Loan $loan,
        LoanItem $loanItem
    ): RedirectResponse {
        $this->authorizeManager($request, $loan);

        $data = $request->validate([
            'new_asset_id' => ['required', 'integer', 'exists:item_assets,id'],
        ]);

        $this->transactions->replaceAsset(
            $loan,
            $loanItem,
            (int) $data['new_asset_id'],
            $request->user()
        );

        return back()->with(
            'success',
            'Unit alat berhasil diganti dengan unit lain yang kondisinya baik.'
        );
    }

    /**
     * Perpanjangan waktu pengembalian khusus GURU oleh Toolman.
     * Backend memverifikasi role peminjam dan role aktor.
     */
    public function extend(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $actor = $request->user();
        $actorRole = (string) $actor?->role;

        // Hanya Toolman (scoped workshop) atau Admin yang boleh.
        if ($actorRole === 'admin') {
            // admin diizinkan
        } elseif ($actorRole === 'toolman') {
            abort_if(
                (int) $loan->workshop_id !== (int) $actor?->workshop_id,
                403,
                'Anda hanya dapat memperpanjang peminjaman pada jurusan Anda.'
            );
        } else {
            abort(403, 'Hanya Toolman yang dapat memberikan perpanjangan.');
        }

        // Hanya untuk peminjam ber-peran Guru.
        $borrower = $loan->borrower;
        abort_if(
            $borrower === null || (string) $borrower->role !== 'guru',
            422,
            'Perpanjangan hanya dapat diberikan untuk peminjaman Guru.'
        );

        $data = $request->validate([
            'extended_due_at' => ['required', 'date_format:Y-m-d H:i'],
            'extension_reason' => ['required', 'string', 'max:1000'],
        ]);

        $newDue = Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['extended_due_at'],
            config('app.timezone', 'Asia/Jakarta')
        );

        $baseDue = $loan->due_at;

        // Wajib lebih besar dari deadline default.
        abort_if(
            $baseDue !== null && $newDue->lte($baseDue),
            422,
            'Tanggal perpanjangan harus lebih besar dari deadline default (' . $baseDue->format('d-m-Y H:i') . ').'
        );

        $loan->fill([
            'extended_due_at' => $newDue,
            'extended_by' => $actor->id,
            'extension_reason' => $data['extension_reason'],
            'extended_at' => now(),
        ])->save();

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Perpanjangan waktu pengembalian berhasil diberikan kepada Guru.');
    }
}
