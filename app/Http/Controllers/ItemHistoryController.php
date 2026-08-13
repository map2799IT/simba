<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ItemHistoryController extends Controller
{
    public function __invoke(
        Request $request,
        Item $item
    ): View {
        abort_unless(
            Schema::hasTable('item_stock_movements'),
            500,
            'Tabel item_stock_movements belum tersedia.'
        );

        $columns = Schema::getColumnListing(
            'item_stock_movements'
        );

        abort_unless(
            in_array('item_id', $columns, true),
            500,
            'Kolom item_id tidak ditemukan pada tabel item_stock_movements.'
        );

        $query = DB::table(
            'item_stock_movements'
        )->where(
            'item_id',
            $item->id
        );

        $this->applyTypeFilter(
            $query,
            $request,
            $columns
        );

        $this->applySearchFilter(
            $query,
            $request,
            $columns
        );

        $dateColumn = $this->firstExistingColumn(
            $columns,
            [
                'transaction_date',
                'movement_date',
                'date',
                'created_at',
            ]
        );

        if ($dateColumn !== null) {
            $query->orderByDesc($dateColumn);
        }

        if (in_array('id', $columns, true)) {
            $query->orderByDesc('id');
        }

        $movements = $query
            ->paginate(20)
            ->withQueryString();

        $userColumn = $this->firstExistingColumn(
            $columns,
            [
                'user_id',
                'created_by',
                'performed_by',
                'operator_id',
            ]
        );

        $userNames = $this->userNames(
            $movements->items(),
            $userColumn
        );

        $availableTypes = collect(
            $movements->items()
        )
            ->pluck('type')
            ->filter()
            ->map(
                fn (mixed $type): string =>
                    (string) $type
            )
            ->unique()
            ->sort()
            ->values();

        return view(
            'items.history',
            [
                'item' => $item,
                'movements' => $movements,
                'columns' => $columns,
                'userColumn' => $userColumn,
                'userNames' => $userNames,
                'availableTypes' => $availableTypes,
            ]
        );
    }

    private function applyTypeFilter(
        Builder $query,
        Request $request,
        array $columns
    ): void {
        if (
            ! in_array('type', $columns, true)
            || ! $request->filled('type')
        ) {
            return;
        }

        $query->where(
            'type',
            (string) $request->input('type')
        );
    }

    private function applySearchFilter(
        Builder $query,
        Request $request,
        array $columns
    ): void {
        $search = trim(
            (string) $request->input('search')
        );

        if ($search === '') {
            return;
        }

        $searchableColumns = array_values(
            array_intersect(
                [
                    'reference_number',
                    'reference_no',
                    'transaction_number',
                    'document_number',
                    'notes',
                    'note',
                    'description',
                    'remarks',
                ],
                $columns
            )
        );

        if ($searchableColumns === []) {
            return;
        }

        $query->where(
            function (
                Builder $subquery
            ) use (
                $searchableColumns,
                $search
            ): void {
                foreach (
                    $searchableColumns
                    as $index => $column
                ) {
                    if ($index === 0) {
                        $subquery->where(
                            $column,
                            'like',
                            "%{$search}%"
                        );

                        continue;
                    }

                    $subquery->orWhere(
                        $column,
                        'like',
                        "%{$search}%"
                    );
                }
            }
        );
    }

    private function userNames(
        array $movements,
        ?string $userColumn
    ): Collection {
        if (
            $userColumn === null
            || ! Schema::hasTable('users')
        ) {
            return collect();
        }

        $userIds = collect($movements)
            ->map(
                fn (object $movement): mixed =>
                    data_get(
                        $movement,
                        $userColumn
                    )
            )
            ->filter()
            ->map(
                fn (mixed $id): int => (int) $id
            )
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return DB::table('users')
            ->whereIn('id', $userIds->all())
            ->pluck('name', 'id');
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