<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class StorageInventoryPrintController extends Controller
{
    public function __invoke(
        StorageLocation $location
    ): Response {
        $location->load([
            'workshop',
            'parent.parent.parent.parent',
        ]);

        $locationIds = [
            $location->id,
            ...$this->descendantIds(
                $location->id
            ),
        ];

        $locationColumn = $this->itemLocationColumn();

        $items = DB::table('items as items')
            ->leftJoin(
                'item_categories as categories',
                'categories.id',
                '=',
                'items.item_category_id'
            )
            ->leftJoin(
                'units as units',
                'units.id',
                '=',
                'items.unit_id'
            )
            ->leftJoin(
                'storage_locations as locations',
                'locations.id',
                '=',
                "items.{$locationColumn}"
            )
            ->whereIn(
                "items.{$locationColumn}",
                $locationIds
            )
            ->where(
                'items.is_active',
                true
            )
            ->select([
                'items.id',
                'items.code',
                'items.name',
                'items.type',
                'items.brand',
                'items.model',
                'items.serial_number',
                'items.condition',
                'items.status',
                'items.stock',
                'items.minimum_stock',
                'items.is_borrowable',
                'categories.name as category_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'locations.name as location_name',
            ])
            ->orderBy('items.type')
            ->orderBy('items.name')
            ->orderBy('items.code')
            ->get();

        $summary = [
            'total_rows' => $items->count(),

            'total_stock' => $items->sum(
                fn (object $item): float =>
                    (float) ($item->stock ?? 0)
            ),

            'tools' => $items
                ->where('type', 'tool')
                ->count(),

            'materials' => $items
                ->where('type', 'material')
                ->count(),

            'low_stock' => $items
                ->filter(
                    fn (object $item): bool =>
                        (float) ($item->stock ?? 0)
                        <=
                        (float) ($item->minimum_stock ?? 0)
                )
                ->count(),
        ];

        $filename = sprintf(
            'inventaris-%s-%s.pdf',
            strtolower($location->code),
            now()->format('Ymd-His')
        );

        return Pdf::loadView(
            'locations.inventory-pdf',
            [
                'location' => $location,
                'items' => $items,
                'summary' => $summary,
                'generatedAt' => now(),
            ]
        )
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }

    private function itemLocationColumn(): string
    {
        if (
            Schema::hasColumn(
                'items',
                'storage_location_id'
            )
        ) {
            return 'storage_location_id';
        }

        if (
            Schema::hasColumn(
                'items',
                'location_id'
            )
        ) {
            return 'location_id';
        }

        abort(
            500,
            'Tabel items tidak memiliki kolom storage_location_id atau location_id.'
        );
    }

    private function descendantIds(
        int $locationId
    ): array {
        $result = [];
        $parentIds = [$locationId];

        do {
            $childIds = StorageLocation::query()
                ->whereIn(
                    'parent_id',
                    $parentIds
                )
                ->pluck('id')
                ->map(
                    fn (mixed $id): int => (int) $id
                )
                ->all();

            if ($childIds !== []) {
                $result = [
                    ...$result,
                    ...$childIds,
                ];
            }

            $parentIds = $childIds;
        } while ($parentIds !== []);

        return array_values(
            array_unique($result)
        );
    }
}