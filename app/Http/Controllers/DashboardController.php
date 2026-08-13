<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const GLOBAL_ROLES = [
        'admin',
        'guru',
    ];

    private const SINGLE_WORKSHOP_ROLES = [
        'kepala_bengkel',
        'toolman',
        'siswa',
    ];

    public function index(
        Request $request
    ): View {
        $user = $request->user();

        $role = (string) $user->role;

        $hasGlobalScope = in_array(
            $role,
            self::GLOBAL_ROLES,
            true
        );

        $assignedWorkshopId =
            $this->assignedWorkshopId(
                $user
            );

        $requestedWorkshopId =
            $request->filled('workshop_id')
                ? $request->integer(
                    'workshop_id'
                )
                : null;

        $effectiveWorkshopId =
            $hasGlobalScope
                ? $requestedWorkshopId
                : $assignedWorkshopId;

        $missingAssignment =
            in_array(
                $role,
                self::SINGLE_WORKSHOP_ROLES,
                true
            )
            && $assignedWorkshopId === null;

        $workshops =
            $this->visibleWorkshops(
                $hasGlobalScope,
                $assignedWorkshopId
            );

        $scopeLabel =
            $this->scopeLabel(
                $hasGlobalScope,
                $effectiveWorkshopId,
                $missingAssignment
            );

        $inventoryQuery =
            $this->inventoryQuery(
                $effectiveWorkshopId,
                $missingAssignment
            );

        /*
         * Jumlah master barang adalah katalog global: tidak boleh
         * disaring per jurusan (items.workshop_id umumnya NULL).
         */
        $masterQuery =
            DB::table('items')
                ->where(
                    'items.is_active',
                    true
                );

        $stats = [
            'total_items' =>
                (clone $masterQuery)
                    ->count(),

            'tool_masters' =>
                (clone $masterQuery)
                    ->where(
                        'items.type',
                        'tool'
                    )
                    ->count(),

            'material_masters' =>
                (clone $masterQuery)
                    ->where(
                        'items.type',
                        'material'
                    )
                    ->count(),

            'low_stock_materials' =>
                (clone $inventoryQuery)
                    ->where(
                        'items.type',
                        'material'
                    )
                    ->where(
                        'items.is_active',
                        true
                    )
                    ->whereColumn(
                        'items.stock',
                        '<=',
                        'items.minimum_stock'
                    )
                    ->count(),

            'inventory_value' =>
                $this->inventoryValue(
                    clone $inventoryQuery
                ),
        ];

        $assetStats =
            $this->assetStats(
                $effectiveWorkshopId,
                $missingAssignment
            );

        $stats = array_merge(
            $stats,
            $assetStats
        );

        $loanQuery =
            $this->loanQuery(
                $role,
                (int) $user->id,
                $effectiveWorkshopId,
                $missingAssignment
            );

        $stats['pending_loans'] =
            $this->countLoansByStatus(
                clone $loanQuery,
                [
                    'pending',
                    'requested',
                    'submitted',
                ]
            );

        $stats['active_loans'] =
            $this->countLoansByStatus(
                clone $loanQuery,
                [
                    'approved',
                    'borrowed',
                    'active',
                    'checked_out',
                ]
            );

        $stats['overdue_loans'] =
            $this->overdueLoanCount(
                clone $loanQuery
            );

        $stats['open_damages'] =
            $this->openDamageCount(
                $role,
                (int) $user->id,
                $effectiveWorkshopId,
                $missingAssignment
            );

        $stats['incoming_this_month'] =
            $this->movementTotal(
                'incoming',
                $effectiveWorkshopId,
                $missingAssignment
            );

        $stats['outgoing_this_month'] =
            $this->movementTotal(
                'outgoing',
                $effectiveWorkshopId,
                $missingAssignment
            );

        return view(
            'dashboard',
            [
                'role' => $role,

                'roleLabel' =>
                    $this->roleLabel(
                        $role
                    ),

                'hasGlobalScope' =>
                    $hasGlobalScope,

                'assignedWorkshopId' =>
                    $assignedWorkshopId,

                'effectiveWorkshopId' =>
                    $effectiveWorkshopId,

                'missingAssignment' =>
                    $missingAssignment,

                'scopeLabel' =>
                    $scopeLabel,

                'workshops' =>
                    $workshops,

                'stats' => $stats,

                'recentLoans' =>
                    $this->recentLoans(
                        $role,
                        (int) $user->id,
                        $effectiveWorkshopId,
                        $missingAssignment
                    ),

                'recentMovements' =>
                    $this->recentMovements(
                        $effectiveWorkshopId,
                        $missingAssignment,
                        $role
                    ),

                'lowStockItems' =>
                    $this->lowStockItems(
                        $effectiveWorkshopId,
                        $missingAssignment
                    ),

                'openDamageReports' =>
                    $this->openDamageReports(
                        $role,
                        (int) $user->id,
                        $effectiveWorkshopId,
                        $missingAssignment
                    ),
            ]
        );
    }

    private function assignedWorkshopId(
        object $user
    ): ?int {
        if (
            ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return null;
        }

        $value = $user->workshop_id;

        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return (int) $value;
    }

    private function visibleWorkshops(
        bool $hasGlobalScope,
        ?int $assignedWorkshopId
    ): Collection {
        if (
            ! Schema::hasTable(
                'workshops'
            )
        ) {
            return collect();
        }

        $query = DB::table(
            'workshops'
        )
            ->orderBy('code');

        if (
            Schema::hasColumn(
                'workshops',
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        if (
            ! $hasGlobalScope
            && $assignedWorkshopId !== null
        ) {
            $query->where(
                'id',
                $assignedWorkshopId
            );
        }

        if (
            ! $hasGlobalScope
            && $assignedWorkshopId === null
        ) {
            return collect();
        }

        return $query->get([
            'id',
            'code',
            'name',
        ]);
    }

    private function scopeLabel(
        bool $hasGlobalScope,
        ?int $effectiveWorkshopId,
        bool $missingAssignment
    ): string {
        if ($missingAssignment) {
            return 'Jurusan belum ditetapkan';
        }

        if ($effectiveWorkshopId === null) {
            return $hasGlobalScope
                ? 'Seluruh jurusan'
                : 'Jurusan sendiri';
        }

        if (
            ! Schema::hasTable(
                'workshops'
            )
        ) {
            return 'Jurusan #'.
                $effectiveWorkshopId;
        }

        $workshop = DB::table(
            'workshops'
        )
            ->where(
                'id',
                $effectiveWorkshopId
            )
            ->first([
                'code',
                'name',
            ]);

        if ($workshop === null) {
            return 'Jurusan tidak ditemukan';
        }

        return $workshop->code.
            ' — '.
            $workshop->name;
    }

    private function inventoryQuery(
        ?int $workshopId,
        bool $missingAssignment
    ): Builder {
        if (
            ! Schema::hasTable('items')
        ) {
            return DB::table('items')
                ->whereRaw('1 = 0');
        }

        $query = DB::table('items');

        if (
            Schema::hasColumn(
                'items',
                'is_active'
            )
        ) {
            $query->where(
                'items.is_active',
                true
            );
        }

        if ($missingAssignment) {
            return $query->whereRaw(
                '1 = 0'
            );
        }

        if ($workshopId !== null) {
            $query->where(
                'items.workshop_id',
                $workshopId
            );
        }

        return $query;
    }

    private function inventoryValue(
        Builder $query
    ): float {
        if (
            ! Schema::hasColumn(
                'items',
                'stock'
            )
            || ! Schema::hasColumn(
                'items',
                'unit_price'
            )
        ) {
            return 0;
        }

        $row = $query
            ->selectRaw(
                'COALESCE(SUM('.
                'COALESCE(items.stock, 0) * '.
                'COALESCE(items.unit_price, 0)'.
                '), 0) AS aggregate'
            )
            ->first();

        return (float) (
            $row->aggregate
            ?? 0
        );
    }

    private function assetStats(
        ?int $workshopId,
        bool $missingAssignment
    ): array {
        $empty = [
            'tool_units' => 0,
            'available_units' => 0,
            'borrowed_units' => 0,
            'problem_units' => 0,
        ];

        if (
            ! Schema::hasTable(
                'item_assets'
            )
        ) {
            return $empty;
        }

        $query = DB::table(
            'item_assets'
        );

        if (
            Schema::hasColumn(
                'item_assets',
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        if ($missingAssignment) {
            $query->whereRaw('1 = 0');
        } elseif ($workshopId !== null) {
            $query->where(
                'workshop_id',
                $workshopId
            );
        }

        $empty['tool_units'] =
            (clone $query)->count();

        if (
            Schema::hasColumn(
                'item_assets',
                'status'
            )
        ) {
            $empty['available_units'] =
                (clone $query)
                    ->where(
                        'status',
                        'available'
                    )
                    ->count();

            $empty['borrowed_units'] =
                (clone $query)
                    ->whereIn(
                        'status',
                        [
                            'borrowed',
                            'reserved',
                        ]
                    )
                    ->count();

            $empty['problem_units'] =
                (clone $query)
                    ->whereIn(
                        'status',
                        [
                            'damaged',
                            'under_repair',
                            'lost',
                        ]
                    )
                    ->count();
        }

        return $empty;
    }

    private function loanQuery(
        string $role,
        int $userId,
        ?int $workshopId,
        bool $missingAssignment
    ): Builder {
        if (
            ! Schema::hasTable('loans')
        ) {
            return DB::table('loans')
                ->whereRaw('1 = 0');
        }

        $query = DB::table('loans');

        if ($missingAssignment) {
            return $query->whereRaw(
                '1 = 0'
            );
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
        ) {
            $query->where(
                'loans.borrower_id',
                $userId
            );
        }

        if (
            in_array(
                $role,
                [
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            )
        ) {
            $this->applyLoanWorkshop(
                $query,
                $workshopId
            );
        }

        if (
            in_array(
                $role,
                [
                    'admin',
                    'guru',
                ],
                true
            )
            && $workshopId !== null
        ) {
            $this->applyLoanWorkshop(
                $query,
                $workshopId
            );
        }

        return $query;
    }

    private function applyLoanWorkshop(
        Builder $query,
        ?int $workshopId
    ): void {
        if ($workshopId === null) {
            return;
        }

        if (
            Schema::hasColumn(
                'loans',
                'workshop_id'
            )
        ) {
            $query->where(
                'loans.workshop_id',
                $workshopId
            );

            return;
        }

        if (
            Schema::hasTable(
                'loan_items'
            )
            && Schema::hasTable(
                'items'
            )
        ) {
            $query->whereExists(
                function (
                    Builder $subquery
                ) use ($workshopId): void {
                    $subquery
                        ->selectRaw('1')
                        ->from('loan_items')
                        ->join(
                            'items',
                            'items.id',
                            '=',
                            'loan_items.item_id'
                        )
                        ->whereColumn(
                            'loan_items.loan_id',
                            'loans.id'
                        )
                        ->where(
                            'items.workshop_id',
                            $workshopId
                        );
                }
            );
        }
    }

    private function countLoansByStatus(
        Builder $query,
        array $statuses
    ): int {
        if (
            ! Schema::hasColumn(
                'loans',
                'status'
            )
        ) {
            return 0;
        }

        return $query
            ->whereIn(
                'loans.status',
                $statuses
            )
            ->count();
    }

    private function overdueLoanCount(
        Builder $query
    ): int {
        if (
            ! Schema::hasColumn(
                'loans',
                'due_at'
            )
        ) {
            return 0;
        }

        $query
            ->where(
                'loans.due_at',
                '<',
                now()
            )
            ->whereIn(
                'loans.status',
                [
                    'approved',
                    'borrowed',
                    'active',
                    'checked_out',
                ]
            );

        if (
            Schema::hasColumn(
                'loans',
                'returned_at'
            )
        ) {
            $query->whereNull(
                'loans.returned_at'
            );
        }

        return $query->count();
    }

    private function openDamageCount(
        string $role,
        int $userId,
        ?int $workshopId,
        bool $missingAssignment
    ): int {
        return $this->damageQuery(
            $role,
            $userId,
            $workshopId,
            $missingAssignment
        )
            ->whereNotIn(
                'damage_reports.status',
                [
                    'completed',
                    'closed',
                    'resolved',
                    'cancelled',
                ]
            )
            ->count();
    }

    private function damageQuery(
        string $role,
        int $userId,
        ?int $workshopId,
        bool $missingAssignment
    ): Builder {
        if (
            ! Schema::hasTable(
                'damage_reports'
            )
        ) {
            return DB::table(
                'damage_reports'
            )->whereRaw('1 = 0');
        }

        $query = DB::table(
            'damage_reports'
        );

        if (
            Schema::hasTable('items')
        ) {
            $query->join(
                'items',
                'items.id',
                '=',
                'damage_reports.item_id'
            );
        }

        if ($missingAssignment) {
            return $query->whereRaw(
                '1 = 0'
            );
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
            && Schema::hasColumn(
                'damage_reports',
                'reported_by'
            )
        ) {
            $query->where(
                'damage_reports.reported_by',
                $userId
            );
        }

        if (
            $workshopId !== null
            && Schema::hasTable('items')
        ) {
            $query->where(
                'items.workshop_id',
                $workshopId
            );
        }

        return $query;
    }

    private function movementTotal(
        string $type,
        ?int $workshopId,
        bool $missingAssignment
    ): float {
        if (
            ! Schema::hasTable(
                'item_stock_movements'
            )
            || ! Schema::hasTable('items')
        ) {
            return 0;
        }

        $query = DB::table(
            'item_stock_movements'
        )
            ->join(
                'items',
                'items.id',
                '=',
                'item_stock_movements.item_id'
            )
            ->where(
                'item_stock_movements.type',
                $type
            );

        if (
            Schema::hasColumn(
                'item_stock_movements',
                'transaction_date'
            )
        ) {
            $query
                ->whereYear(
                    'item_stock_movements.transaction_date',
                    now()->year
                )
                ->whereMonth(
                    'item_stock_movements.transaction_date',
                    now()->month
                );
        }

        if ($missingAssignment) {
            $query->whereRaw('1 = 0');
        } elseif ($workshopId !== null) {
            $query->where(
                'items.workshop_id',
                $workshopId
            );
        }

        return (float) $query->sum(
            'item_stock_movements.quantity'
        );
    }

    private function recentLoans(
        string $role,
        int $userId,
        ?int $workshopId,
        bool $missingAssignment
    ): Collection {
        $query = $this->loanQuery(
            $role,
            $userId,
            $workshopId,
            $missingAssignment
        );

        if (
            ! Schema::hasTable('loans')
        ) {
            return collect();
        }

        if (
            Schema::hasTable('users')
        ) {
            $query->leftJoin(
                'users as borrowers',
                'borrowers.id',
                '=',
                'loans.borrower_id'
            );
        }

        if (
            Schema::hasTable('workshops')
            && Schema::hasColumn(
                'loans',
                'workshop_id'
            )
        ) {
            $query->leftJoin(
                'workshops',
                'workshops.id',
                '=',
                'loans.workshop_id'
            );
        }

        $select = [
            'loans.id',
            'loans.status',
        ];

        foreach (
            [
                'code',
                'request_date',
                'due_at',
                'created_at',
            ]
            as $column
        ) {
            if (
                Schema::hasColumn(
                    'loans',
                    $column
                )
            ) {
                $select[] =
                    "loans.{$column}";
            }
        }

        if (
            Schema::hasTable('users')
        ) {
            $select[] =
                'borrowers.name as borrower_name';
        }

        if (
            Schema::hasTable('workshops')
            && Schema::hasColumn(
                'loans',
                'workshop_id'
            )
        ) {
            $select[] =
                'workshops.code as workshop_code';

            $select[] =
                'workshops.name as workshop_name';
        }

        $orderColumn =
            Schema::hasColumn(
                'loans',
                'request_date'
            )
                ? 'loans.request_date'
                : 'loans.id';

        return $query
            ->select($select)
            ->orderByDesc($orderColumn)
            ->orderByDesc('loans.id')
            ->limit(6)
            ->get();
    }

    private function recentMovements(
        ?int $workshopId,
        bool $missingAssignment,
        string $role
    ): Collection {
        if (
            $role === 'siswa'
            || ! Schema::hasTable(
                'item_stock_movements'
            )
            || ! Schema::hasTable('items')
        ) {
            return collect();
        }

        $query = DB::table(
            'item_stock_movements'
        )
            ->join(
                'items',
                'items.id',
                '=',
                'item_stock_movements.item_id'
            );

        if (
            Schema::hasTable('units')
        ) {
            $query->leftJoin(
                'units',
                'units.id',
                '=',
                'items.unit_id'
            );
        }

        if ($missingAssignment) {
            $query->whereRaw('1 = 0');
        } elseif ($workshopId !== null) {
            $query->where(
                'items.workshop_id',
                $workshopId
            );
        }

        $select = [
            'item_stock_movements.id',
            'item_stock_movements.type',
            'item_stock_movements.quantity',
            'items.code as item_code',
            'items.name as item_name',
            'items.type as item_type',
        ];

        foreach (
            [
                'transaction_date',
                'reference_number',
            ]
            as $column
        ) {
            if (
                Schema::hasColumn(
                    'item_stock_movements',
                    $column
                )
            ) {
                $select[] =
                    "item_stock_movements.{$column}";
            }
        }

        if (
            Schema::hasTable('units')
        ) {
            $select[] =
                'units.name as unit_name';
        }

        $orderColumn =
            Schema::hasColumn(
                'item_stock_movements',
                'transaction_date'
            )
                ? 'item_stock_movements.transaction_date'
                : 'item_stock_movements.id';

        return $query
            ->select($select)
            ->orderByDesc($orderColumn)
            ->orderByDesc(
                'item_stock_movements.id'
            )
            ->limit(6)
            ->get();
    }

    private function lowStockItems(
        ?int $workshopId,
        bool $missingAssignment
    ): Collection {
        if (
            ! Schema::hasTable('items')
        ) {
            return collect();
        }

        $query = DB::table('items')
            ->where(
                'items.type',
                'material'
            )
            ->whereColumn(
                'items.stock',
                '<=',
                'items.minimum_stock'
            );

        if (
            Schema::hasColumn(
                'items',
                'is_active'
            )
        ) {
            $query->where(
                'items.is_active',
                true
            );
        }

        if (
            Schema::hasTable('units')
        ) {
            $query->leftJoin(
                'units',
                'units.id',
                '=',
                'items.unit_id'
            );
        }

        if (
            Schema::hasTable(
                'workshops'
            )
        ) {
            $query->leftJoin(
                'workshops',
                'workshops.id',
                '=',
                'items.workshop_id'
            );
        }

        if ($missingAssignment) {
            $query->whereRaw('1 = 0');
        } elseif ($workshopId !== null) {
            $query->where(
                'items.workshop_id',
                $workshopId
            );
        }

        $select = [
            'items.id',
            'items.code',
            'items.name',
            'items.stock',
            'items.minimum_stock',
        ];

        if (
            Schema::hasTable('units')
        ) {
            $select[] =
                'units.name as unit_name';
        }

        if (
            Schema::hasTable(
                'workshops'
            )
        ) {
            $select[] =
                'workshops.code as workshop_code';
        }

        return $query
            ->select($select)
            ->orderBy('items.stock')
            ->orderBy('items.name')
            ->limit(6)
            ->get();
    }

    private function openDamageReports(
        string $role,
        int $userId,
        ?int $workshopId,
        bool $missingAssignment
    ): Collection {
        $query = $this->damageQuery(
            $role,
            $userId,
            $workshopId,
            $missingAssignment
        );

        if (
            ! Schema::hasTable(
                'damage_reports'
            )
        ) {
            return collect();
        }

        if (
            Schema::hasTable('users')
        ) {
            $query->leftJoin(
                'users as reporters',
                'reporters.id',
                '=',
                'damage_reports.reported_by'
            );
        }

        if (
            Schema::hasTable(
                'workshops'
            )
            && Schema::hasTable('items')
        ) {
            $query->leftJoin(
                'workshops',
                'workshops.id',
                '=',
                'items.workshop_id'
            );
        }

        $query->whereNotIn(
            'damage_reports.status',
            [
                'completed',
                'closed',
                'resolved',
                'cancelled',
            ]
        );

        $select = [
            'damage_reports.id',
            'damage_reports.status',
            'items.code as item_code',
            'items.name as item_name',
        ];

        foreach (
            [
                'code',
                'severity',
                'reported_at',
                'description',
                'created_at',
            ]
            as $column
        ) {
            if (
                Schema::hasColumn(
                    'damage_reports',
                    $column
                )
            ) {
                $select[] =
                    "damage_reports.{$column}";
            }
        }

        if (
            Schema::hasTable('users')
        ) {
            $select[] =
                'reporters.name as reporter_name';
        }

        if (
            Schema::hasTable(
                'workshops'
            )
            && Schema::hasTable('items')
        ) {
            $select[] =
                'workshops.code as workshop_code';
        }

        $orderColumn =
            Schema::hasColumn(
                'damage_reports',
                'reported_at'
            )
                ? 'damage_reports.reported_at'
                : 'damage_reports.id';

        return $query
            ->select($select)
            ->orderByDesc($orderColumn)
            ->orderByDesc(
                'damage_reports.id'
            )
            ->limit(6)
            ->get();
    }

    private function roleLabel(
        string $role
    ): string {
        return match ($role) {
            'admin' =>
                'Administrator',

            'kepala_bengkel' =>
                'Kepala Bengkel',

            'toolman' =>
                'Toolman',

            'guru' =>
                'Guru',

            'siswa' =>
                'Siswa',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $role
                    )
                ),
        };
    }
}
