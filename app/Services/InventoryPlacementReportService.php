<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryPlacementReportService
{
    public function paginate(
        Request $request,
        int $perPage = 25
    ): LengthAwarePaginator {
        return $this->query($request)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(Request $request): Collection
    {
        return $this->query($request)->get();
    }

    public function summary(Request $request): array
    {
        $rows = $this->all($request);

        return [
            'total_items' => $rows->count(),

            'tools' => $rows
                ->where('type', 'tool')
                ->count(),

            'materials' => $rows
                ->where('type', 'material')
                ->count(),

            'low_stock' => $rows
                ->where('type', 'material')
                ->filter(
                    fn (object $row): bool =>
                        (float) $row->report_stock
                        <= (float) $row->minimum_stock
                )
                ->count(),

            'total_value' => $rows->sum(
                fn (object $row): float =>
                    (float) $row->report_inventory_value
            ),
        ];
    }

    public function effectiveWorkshopId(
        Request $request
    ): ?int {
        $user = $request->user();

        if (
            $user !== null
            && ! in_array(
                (string) $user->role,
                [
                    'admin',
                    'wakil_sarpras',
                ],
                true
            )
        ) {
            return $user->workshop_id !== null
                ? (int) $user->workshop_id
                : null;
        }

        return $request->filled('workshop_id')
            ? $request->integer('workshop_id')
            : null;
    }

    public function isWorkshopRestricted(
        Request $request
    ): bool {
        return $request->user() !== null
            && ! in_array(
                (string) $request->user()->role,
                [
                    'admin',
                    'wakil_sarpras',
                ],
                true
            );
    }

    public function accessWarning(
        Request $request
    ): ?string {
        if (
            ! $this->isWorkshopRestricted($request)
        ) {
            return null;
        }

        if ($request->user()?->workshop_id === null) {
            return 'Akun belum mempunyai jurusan. Hubungi Administrator.';
        }

        return null;
    }

    public function visibleWorkshops(
        Request $request
    ): Collection {
        $query = DB::table('workshops')
            ->where('is_active', true)
            ->orderBy('code');

        if (
            $this->isWorkshopRestricted($request)
            && $request->user()?->workshop_id !== null
        ) {
            $query->where(
                'id',
                $request->user()->workshop_id
            );
        }

        return $query->get([
            'id',
            'code',
            'name',
        ]);
    }

    public function categories(): Collection
    {
        return DB::table('item_categories')
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);
    }

    private function query(Request $request): Builder
    {
        $workshopId =
            $this->effectiveWorkshopId($request);

        $toolAggregate =
            $this->toolAggregate($workshopId);

        $materialAggregate =
            $this->materialAggregate($workshopId);

        $query = DB::table('items')
            ->leftJoin(
                'item_categories',
                'item_categories.id',
                '=',
                'items.item_category_id'
            )
            ->leftJoin(
                'units',
                'units.id',
                '=',
                'items.unit_id'
            )
            ->leftJoinSub(
                $toolAggregate,
                'tool_report',
                fn ($join) =>
                    $join->on(
                        'tool_report.item_id',
                        '=',
                        'items.id'
                    )
            )
            ->leftJoinSub(
                $materialAggregate,
                'material_report',
                fn ($join) =>
                    $join->on(
                        'material_report.item_id',
                        '=',
                        'items.id'
                    )
            )
            ->select([
                'items.id',
                'items.code',
                'items.name',
                'items.type',
                'items.item_category_id',
                'items.unit_id',
                'items.minimum_stock',
                'items.is_active',
                'item_categories.name as category_name',
                'units.name as unit_name',
            ])
            ->addSelect(
                DB::raw(
                    $this->unitSymbolExpression()
                    .' AS unit_symbol'
                )
            )
            ->addSelect(
                DB::raw(
                    'COALESCE(units.allows_decimal, 0) AS allows_decimal'
                )
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.workshop_codes,
                            '-'
                        )
                    ELSE COALESCE(
                        material_report.workshop_codes,
                        '-'
                    )
                END AS report_workshop_code
                "
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.workshop_names,
                            '-'
                        )
                    ELSE COALESCE(
                        material_report.workshop_names,
                        '-'
                    )
                END AS report_workshop_name
                "
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.location_names,
                            '-'
                        )
                    ELSE COALESCE(
                        material_report.location_names,
                        '-'
                    )
                END AS report_location_name
                "
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.brands,
                            '-'
                        )
                    ELSE COALESCE(
                        material_report.brands,
                        '-'
                    )
                END AS report_brand
                "
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.models,
                            '-'
                        )
                    ELSE COALESCE(
                        material_report.models,
                        '-'
                    )
                END AS report_model
                "
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.condition_value,
                            'good'
                        )
                    ELSE COALESCE(
                        material_report.condition_value,
                        'good'
                    )
                END AS report_condition
                "
            )
            ->selectRaw(
                $this->stockExpression($workshopId)
                .' AS report_stock'
            )
            ->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.average_unit_price,
                            0
                        )
                    ELSE COALESCE(
                        material_report.average_unit_price,
                        0
                    )
                END AS report_unit_price
                "
            )
            ->selectRaw(
                $this->inventoryValueExpression($workshopId)
                .' AS report_inventory_value'
            )
            ->selectRaw(
                "
                CASE
                    WHEN ".
                    $this->stockExpression($workshopId).
                    " > 0
                        THEN 'available'
                    ELSE 'out_of_stock'
                END AS report_status
                "
            );

        if ($workshopId !== null) {
            $query->where(
                function (Builder $scope): void {
                    $scope
                        ->where(
                            function (Builder $tool): void {
                                $tool
                                    ->where(
                                        'items.type',
                                        'tool'
                                    )
                                    ->whereNotNull(
                                        'tool_report.item_id'
                                    );
                            }
                        )
                        ->orWhere(
                            function (Builder $material): void {
                                $material
                                    ->where(
                                        'items.type',
                                        'material'
                                    )
                                    ->whereNotNull(
                                        'material_report.item_id'
                                    );
                            }
                        );
                }
            );
        }

        $search = trim(
            (string) $request->input('search')
        );

        if ($search !== '') {
            $query->where(
                function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where(
                            'items.code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'items.name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'tool_report.brands',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'tool_report.models',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'material_report.brands',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'material_report.models',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        if ($request->filled('item_category_id')) {
            $query->where(
                'items.item_category_id',
                $request->integer(
                    'item_category_id'
                )
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'items.type',
                (string) $request->input('type')
            );
        }

        if ($request->filled('condition')) {
            $condition =
                (string) $request->input('condition');

            $query->whereRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.condition_value,
                            'good'
                        )
                    ELSE COALESCE(
                        material_report.condition_value,
                        'good'
                    )
                END = ?
                ",
                [$condition]
            );
        }

        if ($request->filled('status')) {
            $status =
                (string) $request->input('status');

            if ($status === 'available') {
                $query->whereRaw(
                    $this->stockExpression($workshopId)
                    .' > 0'
                );
            }

            if ($status === 'out_of_stock') {
                $query->whereRaw(
                    $this->stockExpression($workshopId)
                    .' <= 0'
                );
            }
        }

        return $query
            ->orderBy('items.type')
            ->orderBy('items.name')
            ->orderBy('items.code');
    }

    private function toolAggregate(
        ?int $workshopId
    ): Builder {
        $query = DB::table('item_assets as ia')
            ->join(
                'workshops as iw',
                'iw.id',
                '=',
                'ia.workshop_id'
            )
            ->leftJoin(
                'storage_locations as isl',
                'isl.id',
                '=',
                'ia.storage_location_id'
            )
            ->where('ia.is_active', true)
            ->when(
                $workshopId !== null,
                fn (Builder $builder): Builder =>
                    $builder->where(
                        'ia.workshop_id',
                        $workshopId
                    )
            )
            ->groupBy('ia.item_id')
            ->selectRaw(
                'ia.item_id AS item_id'
            )
            ->selectRaw(
                'COUNT(ia.id) AS stock_qty'
            )
            ->selectRaw(
                "
                GROUP_CONCAT(
                    DISTINCT iw.code
                    ORDER BY iw.code
                    SEPARATOR ', '
                ) AS workshop_codes
                "
            )
            ->selectRaw(
                "
                GROUP_CONCAT(
                    DISTINCT iw.name
                    ORDER BY iw.code
                    SEPARATOR ', '
                ) AS workshop_names
                "
            )
            ->selectRaw(
                "
                GROUP_CONCAT(
                    DISTINCT isl.name
                    ORDER BY isl.name
                    SEPARATOR ', '
                ) AS location_names
                "
            );

        $query->selectRaw(
            $this->aggregateTextColumn(
                'item_assets',
                'brand',
                'ia.brand'
            )
            .' AS brands'
        );

        $query->selectRaw(
            $this->aggregateTextColumn(
                'item_assets',
                'model',
                'ia.model'
            )
            .' AS models'
        );

        $query->selectRaw(
            $this->conditionAggregate(
                'ia.condition'
            )
            .' AS condition_value'
        );

        if (
            Schema::hasColumn(
                'item_assets',
                'unit_price'
            )
        ) {
            $query
                ->selectRaw(
                    'AVG(COALESCE(ia.unit_price, 0)) AS average_unit_price'
                )
                ->selectRaw(
                    'SUM(COALESCE(ia.unit_price, 0)) AS inventory_value'
                );
        } else {
            $query
                ->selectRaw(
                    '0 AS average_unit_price'
                )
                ->selectRaw(
                    '0 AS inventory_value'
                );
        }

        return $query;
    }

    private function materialAggregate(
        ?int $workshopId
    ): Builder {
        $hasWorkshop =
            Schema::hasColumn(
                'item_stock_movements',
                'workshop_id'
            );

        $hasLocation =
            Schema::hasColumn(
                'item_stock_movements',
                'storage_location_id'
            );

        $query = DB::table(
            'item_stock_movements as ism'
        );

        if ($hasWorkshop) {
            $query->leftJoin(
                'workshops as mw',
                'mw.id',
                '=',
                'ism.workshop_id'
            );
        }

        if ($hasLocation) {
            $query->leftJoin(
                'storage_locations as msl',
                'msl.id',
                '=',
                'ism.storage_location_id'
            );
        }

        $query
            ->when(
                $workshopId !== null
                && $hasWorkshop,
                fn (Builder $builder): Builder =>
                    $builder->where(
                        'ism.workshop_id',
                        $workshopId
                    )
            )
            ->groupBy('ism.item_id')
            ->selectRaw(
                'ism.item_id AS item_id'
            )
            ->selectRaw(
                $this->signedMovementExpression()
                .' AS stock_qty'
            )
            ->selectRaw(
                $hasWorkshop
                    ? "
                        GROUP_CONCAT(
                            DISTINCT mw.code
                            ORDER BY mw.code
                            SEPARATOR ', '
                        ) AS workshop_codes
                    "
                    : 'NULL AS workshop_codes'
            )
            ->selectRaw(
                $hasWorkshop
                    ? "
                        GROUP_CONCAT(
                            DISTINCT mw.name
                            ORDER BY mw.code
                            SEPARATOR ', '
                        ) AS workshop_names
                    "
                    : 'NULL AS workshop_names'
            )
            ->selectRaw(
                $hasLocation
                    ? "
                        GROUP_CONCAT(
                            DISTINCT msl.name
                            ORDER BY msl.name
                            SEPARATOR ', '
                        ) AS location_names
                    "
                    : 'NULL AS location_names'
            );

        $query->selectRaw(
            $this->aggregateTextColumn(
                'item_stock_movements',
                'brand',
                'ism.brand'
            )
            .' AS brands'
        );

        $query->selectRaw(
            $this->aggregateTextColumn(
                'item_stock_movements',
                'model',
                'ism.model'
            )
            .' AS models'
        );

        if (
            Schema::hasColumn(
                'item_stock_movements',
                'condition'
            )
        ) {
            $query->selectRaw(
                $this->conditionAggregate(
                    'ism.condition'
                )
                .' AS condition_value'
            );
        } else {
            $query->selectRaw(
                "'good' AS condition_value"
            );
        }

        if (
            Schema::hasColumn(
                'item_stock_movements',
                'unit_price'
            )
        ) {
            $query->selectRaw(
                "
                CASE
                    WHEN SUM(
                        CASE
                            WHEN ism.type IN (
                                'initial',
                                'incoming',
                                'adjustment_in',
                                'return'
                            )
                            THEN ABS(ism.quantity)
                            ELSE 0
                        END
                    ) > 0
                    THEN
                        SUM(
                            CASE
                                WHEN ism.type IN (
                                    'initial',
                                    'incoming',
                                    'adjustment_in',
                                    'return'
                                )
                                THEN
                                    COALESCE(
                                        ism.unit_price,
                                        0
                                    )
                                    * ABS(ism.quantity)
                                ELSE 0
                            END
                        )
                        /
                        SUM(
                            CASE
                                WHEN ism.type IN (
                                    'initial',
                                    'incoming',
                                    'adjustment_in',
                                    'return'
                                )
                                THEN ABS(ism.quantity)
                                ELSE 0
                            END
                        )
                    ELSE 0
                END AS average_unit_price
                "
            );
        } else {
            $query->selectRaw(
                '0 AS average_unit_price'
            );
        }

        return $query;
    }

    private function stockExpression(
        ?int $workshopId
    ): string {
        if ($workshopId === null) {
            return "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            tool_report.stock_qty,
                            0
                        )
                    ELSE COALESCE(
                        items.stock,
                        0
                    )
                END
            ";
        }

        return "
            CASE
                WHEN items.type = 'tool'
                    THEN COALESCE(
                        tool_report.stock_qty,
                        0
                    )
                ELSE COALESCE(
                    material_report.stock_qty,
                    0
                )
            END
        ";
    }

    private function inventoryValueExpression(
        ?int $workshopId
    ): string {
        $stock =
            $this->stockExpression($workshopId);

        return "
            CASE
                WHEN items.type = 'tool'
                    THEN COALESCE(
                        tool_report.inventory_value,
                        0
                    )
                ELSE
                    ({$stock})
                    *
                    COALESCE(
                        material_report.average_unit_price,
                        0
                    )
            END
        ";
    }

    private function signedMovementExpression(): string
    {
        return "
            SUM(
                CASE
                    WHEN ism.type IN (
                        'initial',
                        'incoming',
                        'adjustment_in',
                        'return'
                    )
                        THEN ABS(ism.quantity)

                    WHEN ism.type IN (
                        'outgoing',
                        'adjustment_out',
                        'loan'
                    )
                        THEN -ABS(ism.quantity)

                    ELSE
                        ism.stock_after
                        - ism.stock_before
                END
            )
        ";
    }

    private function aggregateTextColumn(
        string $table,
        string $column,
        string $qualifiedColumn
    ): string {
        if (! Schema::hasColumn($table, $column)) {
            return 'NULL';
        }

        return "
            GROUP_CONCAT(
                DISTINCT NULLIF(
                    TRIM({$qualifiedColumn}),
                    ''
                )
                ORDER BY {$qualifiedColumn}
                SEPARATOR ', '
            )
        ";
    }

    private function conditionAggregate(
        string $qualifiedColumn
    ): string {
        return "
            CASE
                WHEN COUNT(
                    DISTINCT NULLIF(
                        {$qualifiedColumn},
                        ''
                    )
                ) > 1
                    THEN 'mixed'

                ELSE COALESCE(
                    MIN(
                        NULLIF(
                            {$qualifiedColumn},
                            ''
                        )
                    ),
                    'good'
                )
            END
        ";
    }

    private function unitSymbolExpression(): string
    {
        foreach (
            [
                'symbol',
                'abbreviation',
                'short_name',
                'short_code',
                'code',
            ]
            as $column
        ) {
            if (Schema::hasColumn('units', $column)) {
                return "units.`{$column}`";
            }
        }

        return 'NULL';
    }
}
