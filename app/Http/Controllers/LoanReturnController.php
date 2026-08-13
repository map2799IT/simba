<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Traits\SortsIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoanReturnController extends Controller
{
    use SortsIndex;

    private const OPEN_STATUSES = [
        'borrowed',
        'overdue',
        'partially_returned',
        'partial_return',
    ];

    private const PARTIAL_STATUSES = [
        'partially_returned',
        'partial_return',
    ];

    /*
    |--------------------------------------------------------------------------
    | Halaman daftar pengembalian
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        [$sort, $direction, $perPage] = $this->indexSortParams(['code', 'returned_at']);

        $this->ensureLoanTable();

        $loanColumns = Schema::getColumnListing('loans');

        $codeColumn = $this->firstExistingColumn(
            $loanColumns,
            [
                'code',
                'loan_code',
                'transaction_code',
                'reference_number',
                'reference_no',
            ]
        );

        $borrowerColumn = $this->firstExistingColumn(
            $loanColumns,
            [
                'borrower_id',
                'user_id',
                'requested_by',
                'created_by',
            ]
        );

        $purposeColumn = $this->firstExistingColumn(
            $loanColumns,
            [
                'purpose',
                'loan_purpose',
                'usage_purpose',
                'description',
                'notes',
            ]
        );

        $requestDateColumn = $this->firstExistingColumn(
            $loanColumns,
            [
                'request_date',
                'requested_at',
                'loan_date',
                'transaction_date',
                'submitted_at',
                'created_at',
            ]
        );

        $dueDateColumn = $this->detectDueDateColumn(
            $loanColumns
        );

        $query = DB::table('loans')
            ->select('loans.*');

        $borrowerJoined = $borrowerColumn !== null
            && Schema::hasTable('users');

        if ($borrowerJoined) {
            $query
                ->leftJoin(
                    'users as borrowers',
                    'borrowers.id',
                    '=',
                    "loans.{$borrowerColumn}"
                )
                ->addSelect(
                    'borrowers.name as borrower_name'
                );
        } else {
            $query->addSelect(
                DB::raw('NULL as borrower_name')
            );
        }

        $this->addItemCount($query);

        $this->applyStatusFilter(
            query: $query,
            request: $request,
            dueDateColumn: $dueDateColumn
        );

        $this->applySearch(
            query: $query,
            request: $request,
            codeColumn: $codeColumn,
            purposeColumn: $purposeColumn,
            borrowerJoined: $borrowerJoined
        );

        if ($dueDateColumn !== null) {
            $query
                ->orderByRaw(
                    "CASE
                        WHEN loans.status = 'overdue' THEN 0
                        WHEN loans.status IN (
                            'borrowed',
                            'partially_returned',
                            'partial_return'
                        )
                        AND loans.{$dueDateColumn} < ?
                        THEN 0
                        ELSE 1
                    END",
                    [now()]
                )
                ->orderByRaw(
                    "CASE
                        WHEN loans.{$dueDateColumn} IS NULL
                        THEN 1
                        ELSE 0
                    END"
                )
                ->orderBy(
                    "loans.{$dueDateColumn}"
                );
        } elseif ($requestDateColumn !== null) {
            $query->orderByDesc(
                "loans.{$requestDateColumn}"
            );
        } else {
            $query->orderByDesc('loans.id');
        }

        $query->when($sort !== null, fn ($q) => $q->orderBy($sort, $direction));

        $loans = $query
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'loans.returns.index',
            [
                'loans' => $loans,
                'summary' => $this->summary(
                    $dueDateColumn
                ),
                'codeColumn' => $codeColumn,
                'purposeColumn' => $purposeColumn,
                'requestDateColumn' => $requestDateColumn,
                'dueDateColumn' => $dueDateColumn,
                'sort' => $sort,
                'direction' => $direction,
                'perPage' => $perPage,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Form pengembalian
    |--------------------------------------------------------------------------
    */

    public function form(Loan $loan): View
    {
        $this->ensureReturnable($loan);

        $details = $this->loanDetails(
            $loan->id
        );

        $borrowerName = null;

        if (
            Schema::hasTable('users')
            && ! empty($loan->borrower_id)
        ) {
            $borrowerName = DB::table('users')
                ->where('id', $loan->borrower_id)
                ->value('name');
        }

        return view(
            'loans.returns.form',
            [
                'loan' => $loan,
                'details' => $details,
                'borrowerName' => $borrowerName,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Pengembalian satu item
    |--------------------------------------------------------------------------
    */

    public function returnItem(
        Request $request,
        Loan $loan,
        string $loanItem
    ): RedirectResponse {
        $this->ensureReturnable($loan);

        $data = $this->validateSingleReturn(
            $request
        );

        DB::transaction(
            function () use (
                $loan,
                $loanItem,
                $data
            ): void {
                $lockedLoan = DB::table('loans')
                    ->where('id', $loan->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedLoan === null) {
                    abort(404);
                }

                $this->returnOneDetail(
                    loanId: (int) $loan->id,
                    loanItemId: (int) $loanItem,
                    quantity: (float) $data['quantity'],
                    condition: $data['condition'],
                    notes: $data['notes'],
                    returnedAt: $data['returned_at']
                );

                $this->synchronizeLoanStatus(
                    (int) $loan->id,
                    $data['returned_at']
                );
            }
        );

        return redirect()
            ->route(
                'loans.return-form',
                $loan
            )
            ->with(
                'success',
                'Pengembalian item berhasil disimpan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Pengembalian beberapa atau semua item
    |--------------------------------------------------------------------------
    */

    public function process(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->ensureReturnable($loan);

        $generalData = Validator::make(
            [
                'condition' => $request->input(
                    'condition',
                    $request->input(
                        'return_condition',
                        'good'
                    )
                ),

                'notes' => $request->input(
                    'notes',
                    $request->input('return_notes')
                ),

                'returned_at' => $request->input(
                    'returned_at',
                    now()->format('Y-m-d H:i:s')
                ),

                'return_all' => $request->boolean(
                    'return_all'
                ),

                'returns' => $request->input(
                    'returns',
                    []
                ),
            ],
            [
                'condition' => [
                    'required',
                    'in:good,minor_damage,major_damage',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'returned_at' => [
                    'required',
                    'date',
                ],

                'return_all' => [
                    'boolean',
                ],

                'returns' => [
                    'array',
                ],
            ]
        )->validate();

        DB::transaction(
            function () use (
                $loan,
                $generalData
            ): void {
                DB::table('loans')
                    ->where('id', $loan->id)
                    ->lockForUpdate()
                    ->first();

                $details = $this->loanDetails(
                    (int) $loan->id,
                    lock: true
                );

                if ($details->isEmpty()) {
                    throw ValidationException::withMessages([
                        'returns' =>
                            'Detail alat peminjaman tidak ditemukan.',
                    ]);
                }

                $processed = 0;

                foreach ($details as $detail) {
                    $outstanding = max(
                        0,
                        (float) $detail->borrowed_quantity
                        - (float) $detail->returned_quantity
                    );

                    if ($outstanding <= 0) {
                        continue;
                    }

                    $returnPayload = data_get(
                        $generalData,
                        'returns.'.$detail->loan_item_id
                    );

                    if (
                        ! $generalData['return_all']
                        && ! is_array($returnPayload)
                    ) {
                        continue;
                    }

                    $quantity = $generalData['return_all']
                        ? $outstanding
                        : (float) (
                            $returnPayload['quantity']
                            ?? $returnPayload['return_quantity']
                            ?? 0
                        );

                    if ($quantity <= 0) {
                        continue;
                    }

                    $condition = is_array($returnPayload)
                        ? (
                            $returnPayload['condition']
                            ?? $returnPayload['return_condition']
                            ?? $generalData['condition']
                        )
                        : $generalData['condition'];

                    $notes = is_array($returnPayload)
                        ? (
                            $returnPayload['notes']
                            ?? $returnPayload['return_notes']
                            ?? $generalData['notes']
                        )
                        : $generalData['notes'];

                    $this->returnOneDetail(
                        loanId: (int) $loan->id,
                        loanItemId: (int) $detail->loan_item_id,
                        quantity: $quantity,
                        condition: (string) $condition,
                        notes: $notes,
                        returnedAt: (string) $generalData['returned_at']
                    );

                    $processed++;
                }

                if ($processed === 0) {
                    throw ValidationException::withMessages([
                        'returns' =>
                            'Tidak ada item yang dipilih untuk dikembalikan.',
                    ]);
                }

                $this->synchronizeLoanStatus(
                    (int) $loan->id,
                    (string) $generalData['returned_at']
                );
            }
        );

        return redirect()
            ->route('loans.returns.index')
            ->with(
                'success',
                'Pengembalian alat berhasil diproses.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Proses internal
    |--------------------------------------------------------------------------
    */

    private function returnOneDetail(
        int $loanId,
        int $loanItemId,
        float $quantity,
        string $condition,
        ?string $notes,
        string $returnedAt
    ): void {
        $schema = $this->loanItemSchema();

        $detail = DB::table($schema['table'])
            ->where(
                $schema['id'],
                $loanItemId
            )
            ->where(
                $schema['loan_id'],
                $loanId
            )
            ->lockForUpdate()
            ->first();

        if ($detail === null) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Detail alat peminjaman tidak ditemukan.',
            ]);
        }

        $borrowedQuantity = (float) data_get(
            $detail,
            $schema['quantity']
        );

        $returnedQuantity = $schema['returned_quantity']
            ? (float) data_get(
                $detail,
                $schema['returned_quantity'],
                0
            )
            : 0;

        $outstanding = max(
            0,
            $borrowedQuantity - $returnedQuantity
        );

        if ($outstanding <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Item ini sudah dikembalikan seluruhnya.',
            ]);
        }

        if (
            $quantity <= 0
            || $quantity > $outstanding
        ) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Jumlah kembali harus lebih dari 0 dan tidak boleh melebihi sisa '.$outstanding.'.',
            ]);
        }

        $newReturnedQuantity =
            $returnedQuantity + $quantity;

        $fullyReturned =
            $newReturnedQuantity >= $borrowedQuantity;

        $updates = [];

        if ($schema['returned_quantity']) {
            $updates[$schema['returned_quantity']] =
                $newReturnedQuantity;
        }

        if ($schema['return_condition']) {
            $updates[$schema['return_condition']] =
                $condition;
        }

        if ($schema['return_notes']) {
            $updates[$schema['return_notes']] =
                $notes;
        }

        if ($schema['returned_at']) {
            $updates[$schema['returned_at']] =
                $returnedAt;
        }

        if ($schema['returned_by']) {
            $updates[$schema['returned_by']] =
                auth()->id();
        }

        if ($schema['return_status']) {
            $updates[$schema['return_status']] =
                $fullyReturned
                    ? 'returned'
                    : 'partially_returned';
        }

        if ($updates !== []) {
            DB::table($schema['table'])
                ->where(
                    $schema['id'],
                    $loanItemId
                )
                ->update($updates);
        }

        $itemId = (int) data_get(
            $detail,
            $schema['item_id']
        );

        $this->restoreItemStock(
            itemId: $itemId,
            quantity: $quantity,
            condition: $condition
        );
    }

    private function restoreItemStock(
        int $itemId,
        float $quantity,
        string $condition
    ): void {
        if (
            ! Schema::hasTable('items')
            || $itemId <= 0
        ) {
            return;
        }

        $itemColumns = Schema::getColumnListing(
            'items'
        );

        $item = DB::table('items')
            ->where('id', $itemId)
            ->lockForUpdate()
            ->first();

        if ($item === null) {
            return;
        }

        $updates = [];

        if (
            in_array(
                'stock',
                $itemColumns,
                true
            )
        ) {
            $updates['stock'] =
                (float) ($item->stock ?? 0)
                + $quantity;
        }

        if (
            in_array(
                'condition',
                $itemColumns,
                true
            )
        ) {
            $updates['condition'] = $condition;
        }

        if (
            in_array(
                'status',
                $itemColumns,
                true
            )
        ) {
            $updates['status'] = $condition === 'good'
                ? 'available'
                : 'damaged';
        }

        if (
            in_array(
                'updated_at',
                $itemColumns,
                true
            )
        ) {
            $updates['updated_at'] = now();
        }

        if ($updates !== []) {
            DB::table('items')
                ->where('id', $itemId)
                ->update($updates);
        }
    }

    private function synchronizeLoanStatus(
        int $loanId,
        string $returnedAt
    ): void {
        $details = $this->loanDetails(
            $loanId,
            lock: true
        );

        if ($details->isEmpty()) {
            return;
        }

        $allReturned = $details->every(
            fn (object $detail): bool =>
                (float) $detail->returned_quantity
                >=
                (float) $detail->borrowed_quantity
        );

        $someReturned = $details->contains(
            fn (object $detail): bool =>
                (float) $detail->returned_quantity > 0
        );

        $loanColumns = Schema::getColumnListing(
            'loans'
        );

        $updates = [
            'status' => $allReturned
                ? 'completed'
                : (
                    $someReturned
                        ? 'partially_returned'
                        : 'borrowed'
                ),
        ];

        if ($allReturned) {
            if (
                in_array(
                    'returned_at',
                    $loanColumns,
                    true
                )
            ) {
                $updates['returned_at'] =
                    $returnedAt;
            }

            if (
                in_array(
                    'returned_by',
                    $loanColumns,
                    true
                )
            ) {
                $updates['returned_by'] =
                    auth()->id();
            }
        }

        if (
            in_array(
                'updated_at',
                $loanColumns,
                true
            )
        ) {
            $updates['updated_at'] = now();
        }

        DB::table('loans')
            ->where('id', $loanId)
            ->update($updates);
    }

    /*
    |--------------------------------------------------------------------------
    | Query detail peminjaman
    |--------------------------------------------------------------------------
    */

    private function loanDetails(
        int $loanId,
        bool $lock = false
    ): Collection {
        $schema = $this->loanItemSchema();

        $query = DB::table(
            $schema['table'].' as loan_items'
        )
            ->leftJoin(
                'items as items',
                'items.id',
                '=',
                'loan_items.'.$schema['item_id']
            )
            ->where(
                'loan_items.'.$schema['loan_id'],
                $loanId
            )
            ->select([
                DB::raw(
                    'loan_items.'.$schema['id'].
                    ' as loan_item_id'
                ),

                DB::raw(
                    'loan_items.'.$schema['item_id'].
                    ' as item_id'
                ),

                DB::raw(
                    'loan_items.'.$schema['quantity'].
                    ' as borrowed_quantity'
                ),

                $schema['returned_quantity']
                    ? DB::raw(
                        'COALESCE(loan_items.'.
                        $schema['returned_quantity'].
                        ', 0) as returned_quantity'
                    )
                    : DB::raw(
                        '0 as returned_quantity'
                    ),

                DB::raw(
                    'items.code as item_code'
                ),

                DB::raw(
                    'items.name as item_name'
                ),

                DB::raw(
                    'items.stock as item_stock'
                ),

                DB::raw(
                    'items.condition as item_condition'
                ),

                DB::raw(
                    'items.status as item_status'
                ),
            ]);

        if (
            Schema::hasTable('units')
            && Schema::hasColumn('items', 'unit_id')
        ) {
            $query
                ->leftJoin(
                    'units as units',
                    'units.id',
                    '=',
                    'items.unit_id'
                )
                ->addSelect([
                    DB::raw(
                        'units.name as unit_name'
                    ),

                    Schema::hasColumn('units', 'symbol')
                        ? DB::raw(
                            'units.symbol as unit_symbol'
                        )
                        : DB::raw(
                            'NULL as unit_symbol'
                        ),
                ]);
        } else {
            $query->addSelect([
                DB::raw('NULL as unit_name'),
                DB::raw('NULL as unit_symbol'),
            ]);
        }

        if ($schema['return_condition']) {
            $query->addSelect(
                DB::raw(
                    'loan_items.'.
                    $schema['return_condition'].
                    ' as return_condition'
                )
            );
        } else {
            $query->addSelect(
                DB::raw(
                    'NULL as return_condition'
                )
            );
        }

        if ($schema['return_notes']) {
            $query->addSelect(
                DB::raw(
                    'loan_items.'.
                    $schema['return_notes'].
                    ' as return_notes'
                )
            );
        } else {
            $query->addSelect(
                DB::raw(
                    'NULL as return_notes'
                )
            );
        }

        if ($schema['return_status']) {
            $query->addSelect(
                DB::raw(
                    'loan_items.'.
                    $schema['return_status'].
                    ' as return_status'
                )
            );
        } else {
            $query->addSelect(
                DB::raw(
                    'NULL as return_status'
                )
            );
        }

        $query->orderBy('items.name');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function loanItemSchema(): array
    {
        $table = $this->firstExistingTable([
            'loan_items',
            'loan_details',
        ]);

        if ($table === null) {
            abort(
                500,
                'Tabel detail peminjaman tidak ditemukan.'
            );
        }

        $columns = Schema::getColumnListing(
            $table
        );

        $idColumn = $this->firstExistingColumn(
            $columns,
            ['id']
        );

        $loanIdColumn = $this->firstExistingColumn(
            $columns,
            [
                'loan_id',
                'borrowing_id',
            ]
        );

        $itemIdColumn = $this->firstExistingColumn(
            $columns,
            [
                'item_id',
                'inventory_item_id',
            ]
        );

        $quantityColumn = $this->firstExistingColumn(
            $columns,
            [
                'quantity',
                'qty',
                'borrowed_quantity',
                'approved_quantity',
                'requested_quantity',
            ]
        );

        if (
            $idColumn === null
            || $loanIdColumn === null
            || $itemIdColumn === null
            || $quantityColumn === null
        ) {
            abort(
                500,
                'Struktur tabel detail peminjaman belum sesuai.'
            );
        }

        return [
            'table' => $table,
            'id' => $idColumn,
            'loan_id' => $loanIdColumn,
            'item_id' => $itemIdColumn,
            'quantity' => $quantityColumn,

            'returned_quantity' =>
                $this->firstExistingColumn(
                    $columns,
                    [
                        'returned_quantity',
                        'quantity_returned',
                        'returned_qty',
                    ]
                ),

            'return_condition' =>
                $this->firstExistingColumn(
                    $columns,
                    [
                        'return_condition',
                        'condition_in',
                        'returned_condition',
                    ]
                ),

            'return_notes' =>
                $this->firstExistingColumn(
                    $columns,
                    [
                        'return_notes',
                        'return_note',
                        'notes_return',
                    ]
                ),

            'return_status' =>
                $this->firstExistingColumn(
                    $columns,
                    [
                        'return_status',
                        'status',
                    ]
                ),

            'returned_at' =>
                $this->firstExistingColumn(
                    $columns,
                    [
                        'returned_at',
                        'return_date',
                    ]
                ),

            'returned_by' =>
                $this->firstExistingColumn(
                    $columns,
                    [
                        'returned_by',
                        'return_user_id',
                    ]
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi dan bantuan query
    |--------------------------------------------------------------------------
    */

    private function validateSingleReturn(
        Request $request
    ): array {
        $data = [
            'quantity' => $request->input(
                'quantity',
                $request->input(
                    'return_quantity',
                    $request->input(
                        'returned_quantity',
                        $request->input('qty')
                    )
                )
            ),

            'condition' => $request->input(
                'condition',
                $request->input(
                    'return_condition',
                    $request->input(
                        'condition_in',
                        'good'
                    )
                )
            ),

            'notes' => $request->input(
                'notes',
                $request->input('return_notes')
            ),

            'returned_at' => $request->input(
                'returned_at',
                now()->format('Y-m-d H:i:s')
            ),
        ];

        return Validator::make(
            $data,
            [
                'quantity' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'condition' => [
                    'required',
                    'in:good,minor_damage,major_damage',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'returned_at' => [
                    'required',
                    'date',
                ],
            ],
            [
                'quantity.required' =>
                    'Jumlah pengembalian wajib diisi.',

                'quantity.gt' =>
                    'Jumlah pengembalian harus lebih dari 0.',

                'condition.in' =>
                    'Kondisi pengembalian tidak valid.',
            ]
        )->validate();
    }

    private function ensureReturnable(
        Loan $loan
    ): void {
        abort_unless(
            in_array(
                (string) $loan->status,
                self::OPEN_STATUSES,
                true
            ),
            422,
            'Peminjaman ini tidak dapat diproses untuk pengembalian.'
        );
    }

    private function ensureLoanTable(): void
    {
        abort_unless(
            Schema::hasTable('loans'),
            500,
            'Tabel loans belum tersedia.'
        );

        abort_unless(
            Schema::hasColumn('loans', 'status'),
            500,
            'Kolom status tidak ditemukan pada tabel loans.'
        );
    }

    private function addItemCount(
        Builder $query
    ): void {
        $table = $this->firstExistingTable([
            'loan_items',
            'loan_details',
        ]);

        if ($table === null) {
            $query->addSelect(
                DB::raw('0 as items_count')
            );

            return;
        }

        $columns = Schema::getColumnListing(
            $table
        );

        $loanIdColumn = $this->firstExistingColumn(
            $columns,
            [
                'loan_id',
                'borrowing_id',
            ]
        );

        $quantityColumn = $this->firstExistingColumn(
            $columns,
            [
                'quantity',
                'qty',
                'borrowed_quantity',
                'approved_quantity',
                'requested_quantity',
            ]
        );

        if ($loanIdColumn === null) {
            $query->addSelect(
                DB::raw('0 as items_count')
            );

            return;
        }

        $query->selectSub(
            function (Builder $subquery) use (
                $table,
                $loanIdColumn,
                $quantityColumn
            ): void {
                $subquery
                    ->from($table)
                    ->whereColumn(
                        "{$table}.{$loanIdColumn}",
                        'loans.id'
                    );

                if ($quantityColumn !== null) {
                    $subquery->selectRaw(
                        "COALESCE(SUM({$quantityColumn}), 0)"
                    );
                } else {
                    $subquery->selectRaw('COUNT(*)');
                }
            },
            'items_count'
        );
    }

    private function applyStatusFilter(
        Builder $query,
        Request $request,
        ?string $dueDateColumn
    ): void {
        $selectedStatus = (string) $request->input(
            'status',
            'all'
        );

        if ($selectedStatus === 'borrowed') {
            $query->where(
                'loans.status',
                'borrowed'
            );

            return;
        }

        if ($selectedStatus === 'partially_returned') {
            $query->whereIn(
                'loans.status',
                self::PARTIAL_STATUSES
            );

            return;
        }

        if ($selectedStatus === 'overdue') {
            $query->where(
                function (Builder $statusQuery) use (
                    $dueDateColumn
                ): void {
                    $statusQuery->where(
                        'loans.status',
                        'overdue'
                    );

                    if ($dueDateColumn !== null) {
                        $statusQuery->orWhere(
                            function (Builder $dateQuery) use (
                                $dueDateColumn
                            ): void {
                                $dateQuery
                                    ->whereIn(
                                        'loans.status',
                                        [
                                            'borrowed',
                                            ...self::PARTIAL_STATUSES,
                                        ]
                                    )
                                    ->where(
                                        "loans.{$dueDateColumn}",
                                        '<',
                                        now()
                                    );
                            }
                        );
                    }
                }
            );

            return;
        }

        $query->whereIn(
            'loans.status',
            self::OPEN_STATUSES
        );
    }

    private function applySearch(
        Builder $query,
        Request $request,
        ?string $codeColumn,
        ?string $purposeColumn,
        bool $borrowerJoined
    ): void {
        $search = trim(
            (string) $request->input('search')
        );

        if ($search === '') {
            return;
        }

        if (
            $codeColumn === null
            && $purposeColumn === null
            && ! $borrowerJoined
        ) {
            return;
        }

        $query->where(
            function (Builder $searchQuery) use (
                $search,
                $codeColumn,
                $purposeColumn,
                $borrowerJoined
            ): void {
                $hasCondition = false;

                if ($codeColumn !== null) {
                    $searchQuery->where(
                        "loans.{$codeColumn}",
                        'like',
                        "%{$search}%"
                    );

                    $hasCondition = true;
                }

                if ($purposeColumn !== null) {
                    $method = $hasCondition
                        ? 'orWhere'
                        : 'where';

                    $searchQuery->{$method}(
                        "loans.{$purposeColumn}",
                        'like',
                        "%{$search}%"
                    );

                    $hasCondition = true;
                }

                if ($borrowerJoined) {
                    $method = $hasCondition
                        ? 'orWhere'
                        : 'where';

                    $searchQuery->{$method}(
                        'borrowers.name',
                        'like',
                        "%{$search}%"
                    );
                }
            }
        );
    }

    private function summary(
        ?string $dueDateColumn
    ): array {
        $active = DB::table('loans')
            ->whereIn(
                'status',
                self::OPEN_STATUSES
            )
            ->count();

        $partial = DB::table('loans')
            ->whereIn(
                'status',
                self::PARTIAL_STATUSES
            )
            ->count();

        $overdue = DB::table('loans')
            ->where(
                function (Builder $query) use (
                    $dueDateColumn
                ): void {
                    $query->where(
                        'status',
                        'overdue'
                    );

                    if ($dueDateColumn !== null) {
                        $query->orWhere(
                            function (Builder $dateQuery) use (
                                $dueDateColumn
                            ): void {
                                $dateQuery
                                    ->whereIn(
                                        'status',
                                        [
                                            'borrowed',
                                            ...self::PARTIAL_STATUSES,
                                        ]
                                    )
                                    ->where(
                                        $dueDateColumn,
                                        '<',
                                        now()
                                    );
                            }
                        );
                    }
                }
            )
            ->count();

        return [
            'active' => $active,
            'borrowed' => max(
                0,
                $active - $partial
            ),
            'partial' => $partial,
            'overdue' => $overdue,
        ];
    }

    private function detectDueDateColumn(
        array $columns
    ): ?string {
        $column = $this->firstExistingColumn(
            $columns,
            [
                'due_at',
                'due_date',
                'return_due_at',
                'return_due_date',
                'expected_return_at',
                'expected_return_date',
                'planned_return_at',
                'planned_return_date',
                'return_deadline_at',
                'return_deadline',
                'deadline_at',
                'deadline',
                'return_by',
            ]
        );

        if ($column !== null) {
            return $column;
        }

        foreach ($columns as $candidate) {
            $name = strtolower($candidate);

            if (
                str_contains($name, 'returned')
                || str_contains($name, 'actual_return')
            ) {
                continue;
            }

            if (
                str_contains($name, 'due')
                || str_contains($name, 'deadline')
                || (
                    str_contains($name, 'expected')
                    && str_contains($name, 'return')
                )
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function firstExistingTable(
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function firstExistingColumn(
        array $columns,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (
                in_array(
                    $candidate,
                    $columns,
                    true
                )
            ) {
                return $candidate;
            }
        }

        return null;
    }
}
