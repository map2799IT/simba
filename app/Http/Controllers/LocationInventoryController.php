<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\StorageLocation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationInventoryController extends Controller
{
    public function menu(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && in_array((string) $user->role, [
                'admin',
                'wakil_sarpras',
                'kepala_bengkel',
                'toolman',
            ], true),
            403,
            'Anda tidak memiliki akses inventaris lokasi.'
        );

        $locations = StorageLocation::query()
            ->withoutGlobalScopes()
            ->with(['workshop', 'parent'])
            ->where('is_active', true)
            ->when(
                ! in_array(
                    (string) $user->role,
                    [
                        'admin',
                        'wakil_sarpras',
                    ],
                    true
                ),
                fn ($query) => $query->where(
                    'workshop_id',
                    $user->workshop_id
                )
            )
            ->orderBy('workshop_id')
            ->orderBy('code')
            ->get();

        $parents = $locations
            ->whereNull('parent_id')
            ->values();

        $children = $locations
            ->whereNotNull('parent_id')
            ->sortBy(fn (StorageLocation $location): string =>
                ($location->parent?->code ?? '').'-'.$location->code
            )
            ->values();

        $childCounts = $children->countBy(
            fn (StorageLocation $location): int =>
                (int) $location->parent_id
        );

        return view('locations.inventory-menu', [
            'parents' => $parents,
            'children' => $children,
            'childCounts' => $childCounts,
        ]);
    }

    public function summary(
        Request $request,
        StorageLocation $storageLocation
    ): View {
        $this->authorizeLocation(
            $request,
            $storageLocation
        );

        $includeChildren =
            $this->includeChildren(
                $request,
                $storageLocation
            );

        $data =
            $this->inventoryData(
                $storageLocation,
                $includeChildren
            );

        return view(
            'locations.inventory-summary',
            array_merge(
                $data,
                [
                    'location' =>
                        $storageLocation,

                    'includeChildren' =>
                        $includeChildren,
                ]
            )
        );
    }

    public function summaryPrint(
        Request $request,
        StorageLocation $storageLocation
    ): View {
        $this->authorizeLocation($request, $storageLocation);

        $includeChildren = $this->includeChildren(
            $request,
            $storageLocation
        );

        return view(
            'locations.inventory-summary-print',
            $this->documentData(
                $request,
                $storageLocation,
                $includeChildren,
                false
            )
        );
    }

    public function summaryPdf(
        Request $request,
        StorageLocation $storageLocation
    ): mixed {
        $this->authorizeLocation($request, $storageLocation);

        $includeChildren = $this->includeChildren(
            $request,
            $storageLocation
        );

        $data = $this->documentData(
            $request,
            $storageLocation,
            $includeChildren,
            true
        );

        if (! class_exists(Pdf::class)) {
            return response()->view(
                'locations.inventory-summary-print',
                array_merge($data, [
                    'pdfMode' => false,
                    'pdfFallback' => true,
                ])
            );
        }

        return Pdf::loadView(
            'locations.inventory-summary-print',
            $data
        )
            ->setPaper('a4', 'landscape')
            ->download(
                'ringkasan-inventaris-lokasi-'.
                $storageLocation->code.'-'.
                now()->format('Ymd-His').
                '.pdf'
            );
    }

    public function complete(
        Request $request,
        StorageLocation $storageLocation
    ): View {
        $this->authorizeLocation(
            $request,
            $storageLocation
        );

        $includeChildren =
            $this->includeChildren(
                $request,
                $storageLocation
            );

        return view(
            'locations.inventory-complete',
            $this->documentData(
                $request,
                $storageLocation,
                $includeChildren,
                false
            )
        );
    }

    public function pdf(
        Request $request,
        StorageLocation $storageLocation
    ): mixed {
        $this->authorizeLocation(
            $request,
            $storageLocation
        );

        $includeChildren =
            $this->includeChildren(
                $request,
                $storageLocation
            );

        $data =
            $this->documentData(
                $request,
                $storageLocation,
                $includeChildren,
                true
            );

        if (! class_exists(Pdf::class)) {
            return response()->view(
                'locations.inventory-complete',
                array_merge(
                    $data,
                    [
                        'pdfMode' =>
                            false,

                        'pdfFallback' =>
                            true,
                    ]
                )
            );
        }

        return Pdf::loadView(
            'locations.inventory-complete',
            $data
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->download(
                'inventaris-lengkap-lokasi-'.
                $storageLocation->code.
                '-'.
                now()->format(
                    'Ymd-His'
                ).
                '.pdf'
            );
    }

    private function documentData(
        Request $request,
        StorageLocation $location,
        bool $includeChildren,
        bool $pdfMode
    ): array {
        $data =
            $this->inventoryData(
                $location,
                $includeChildren
            );

        $user =
            $request->user();

        $toolman =
            User::query()
                ->withoutGlobalScopes()
                ->where(
                    'workshop_id',
                    $location->workshop_id
                )
                ->where(
                    'role',
                    'toolman'
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->first();

        $head =
            User::query()
                ->withoutGlobalScopes()
                ->where(
                    'workshop_id',
                    $location->workshop_id
                )
                ->where(
                    'role',
                    'kepala_bengkel'
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->first();

        return array_merge(
            $data,
            [
                'location' =>
                    $location,

                'includeChildren' =>
                    $includeChildren,

                'generatedAt' =>
                    now(),

                'printedBy' =>
                    $user?->name
                    ?: $user?->username
                    ?: 'Petugas SIMBA',

                'printedUsername' =>
                    $user?->username
                    ?: $user?->email
                    ?: '-',

                'toolmanName' =>
                    $toolman?->name
                    ?: 'Toolman '.
                        (
                            $location
                                ->workshop
                                ?->code
                            ?: ''
                        ),

                'toolmanUsername' =>
                    $toolman?->username
                    ?: $toolman?->email
                    ?: '-',

                'headName' =>
                    $head?->name
                    ?: 'Kepala Bengkel '.
                        (
                            $location
                                ->workshop
                                ?->code
                            ?: ''
                        ),

                'headUsername' =>
                    $head?->username
                    ?: $head?->email
                    ?: '-',

                'pdfMode' =>
                    $pdfMode,
            ]
        );
    }

    private function inventoryData(
        StorageLocation $location,
        bool $includeChildren
    ): array {
        $location->loadMissing([
            'workshop',
            'parent.parent.parent',
        ]);

        $locationIds =
            $includeChildren
                ? $this->locationIds(
                    $location
                )
                : [
                    (int) $location->id,
                ];

        $coveredLocations =
            StorageLocation::query()
                ->withoutGlobalScopes()
                ->whereIn(
                    'id',
                    $locationIds
                )
                ->orderByRaw(
                    'CASE WHEN id = ? THEN 0 ELSE 1 END',
                    [
                        $location->id,
                    ]
                )
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                    'type',
                    'parent_id',
                ]);

        $materials =
            Item::query()
                ->withoutGlobalScopes()
                ->with([
                    'category',
                    'unit',
                    'workshop',
                    'location',
                ])
                ->where(
                    'type',
                    'material'
                )
                ->whereIn(
                    'storage_location_id',
                    $locationIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->orderBy('code')
                ->get();

        $assets =
            Schema::hasTable(
                'item_assets'
            )
                ? ItemAsset::query()
                    ->withoutGlobalScopes()
                    ->with([
                        'item.category',
                        'item.unit',
                        'item.workshop',
                        'workshop',
                        'storageLocation',
                    ])
                    ->whereIn(
                        'storage_location_id',
                        $locationIds
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy('item_id')
                    ->orderBy('asset_number')
                    ->get()
                : collect();

        $toolGroups =
            $assets
                ->filter(
                    static fn (
                        ItemAsset $asset
                    ): bool =>
                        $asset->item !== null
                )
                ->groupBy('item_id');

        $toolSummaries =
            $toolGroups
                ->map(
                    function (
                        Collection $group
                    ): array {
                        $first =
                            $group->first();

                        $item =
                            $first->item;

                        $locations =
                            $group
                                ->map(
                                    static fn (
                                        ItemAsset $asset
                                    ): ?string =>
                                        $asset
                                            ->storageLocation
                                            ?->name
                                )
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();

                        return [
                            'item' =>
                                $item,

                            'code' =>
                                $item?->code
                                ?: '-',

                            'name' =>
                                $item?->name
                                ?: '-',

                            'category' =>
                                $item
                                    ?->category
                                    ?->name
                                ?: '-',

                            'unit_count' =>
                                $group->count(),

                            'unit_name' =>
                                $item
                                    ?->unit
                                    ?->name
                                ?: 'Unit',

                            'locations' =>
                                $locations,

                            'condition_summary' =>
                                $this
                                    ->distributionLabel(
                                        $group,
                                        'condition',
                                        true
                                    ),

                            'status_summary' =>
                                $this
                                    ->distributionLabel(
                                        $group,
                                        'status',
                                        false
                                    ),
                        ];
                    }
                )
                ->sortBy('name')
                ->values();

        $materialSummaries =
            $materials
                ->map(
                    function (
                        Item $item
                    ): array {
                        return [
                            'item' =>
                                $item,

                            'code' =>
                                $item->code,

                            'name' =>
                                $item->name,

                            'category' =>
                                $item
                                    ->category
                                    ?->name
                                ?: '-',

                            'stock' =>
                                (float) $item->stock,

                            'unit_name' =>
                                $item
                                    ->unit
                                    ?->name
                                ?: '-',

                            'location' =>
                                $item
                                    ->location
                                    ?->name
                                ?: '-',

                            'condition' =>
                                $this
                                    ->conditionLabel(
                                        $item->condition
                                    ),

                            'status' =>
                                $this
                                    ->statusLabel(
                                        $item->status
                                    ),
                        ];
                    }
                );

        $completeRows =
            collect();

        foreach (
            $toolGroups
            as $group
        ) {
            $first =
                $group->first();

            $item =
                $first->item;

            if ($item === null) {
                continue;
            }

            $locations =
                $group
                    ->map(
                        static fn (
                            ItemAsset $asset
                        ): ?string =>
                            $asset
                                ->storageLocation
                                ?->name
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

            $assetPrices =
                $group
                    ->map(
                        static fn (
                            ItemAsset $asset
                        ): ?float =>
                            $asset->unit_price
                                !== null
                                ? (float)
                                    $asset
                                        ->unit_price
                                : null
                    )
                    ->filter(
                        static fn (
                            ?float $value
                        ): bool =>
                            $value !== null
                    );

            $unitPrice =
                $item->unit_price
                    !== null
                    ? (float)
                        $item->unit_price
                    : (
                        $assetPrices
                            ->first()
                        ?? 0.0
                    );

            $totalValue =
                $assetPrices->isNotEmpty()
                    ? (float)
                        $assetPrices->sum()
                    : $unitPrice
                        * $group->count();

            /*
             * TODO 1-2: Nama tampilan diambil dari label Barang Masuk
             * (brand + model unit/aset) sebagai sumber utama; master
             * hanya acuan. Fallback ke master bila label kosong.
             */
            $firstAsset = $group->first();
            $txBrand = $firstAsset?->brand;
            $txModel = $firstAsset?->model;
            $txLabel = collect([$txBrand, $txModel])
                ->filter()
                ->implode(' / ');

            $displayName = $txLabel !== ''
                ? $txLabel
                : $item->name;

            $completeRows->push([
                'code' =>
                    $item->code,

                'name' =>
                    $displayName,

                'brand_model' =>
                    $txLabel !== ''
                        ? $item->name
                        : collect([
                            $item->brand,
                            $item->model,
                        ])
                            ->filter()
                            ->implode(' / '),

                'type' =>
                    'Alat',

                'category' =>
                    $item
                        ->category
                        ?->name
                    ?: '-',

                'workshop' =>
                    $item
                        ->workshop
                        ?->code
                    ?: '-',

                'location' =>
                    $locations
                    ?: '-',

                'condition' =>
                    $this
                        ->distributionLabel(
                            $group,
                            'condition',
                            true
                        ),

                'status' =>
                    $this
                        ->distributionLabel(
                            $group,
                            'status',
                            false
                        ),

                'stock' =>
                    (float) $group->count(),

                'unit_name' =>
                    $item
                        ->unit
                        ?->name
                    ?: 'Unit',

                'unit_price' =>
                    $unitPrice,

                'total_value' =>
                    $totalValue,
            ]);
        }

        foreach (
            $materials
            as $item
        ) {
            $unitPrice =
                $item->unit_price
                    !== null
                    ? (float)
                        $item->unit_price
                    : 0.0;

            $stock =
                (float) $item->stock;

            $completeRows->push([
                'code' =>
                    $item->code,

                'name' =>
                    $item->name,

                'brand_model' =>
                    collect([
                        $item->brand,
                        $item->model,
                    ])
                        ->filter()
                        ->implode(' / '),

                'type' =>
                    'Bahan',

                'category' =>
                    $item
                        ->category
                        ?->name
                    ?: '-',

                'workshop' =>
                    $item
                        ->workshop
                        ?->code
                    ?: '-',

                'location' =>
                    $item
                        ->location
                        ?->name
                    ?: '-',

                'condition' =>
                    $this
                        ->conditionLabel(
                            $item->condition
                        ),

                'status' =>
                    $this
                        ->statusLabel(
                            $item->status
                        ),

                'stock' =>
                    $stock,

                'unit_name' =>
                    $item
                        ->unit
                        ?->name
                    ?: '-',

                'unit_price' =>
                    $unitPrice,

                'total_value' =>
                    $unitPrice
                    * $stock,
            ]);
        }

        $completeRows =
            $completeRows
                ->sortBy([
                    [
                        'type',
                        'asc',
                    ],
                    [
                        'name',
                        'asc',
                    ],
                ])
                ->values();

        $assetDetails =
            $assets
                ->map(
                    function (
                        ItemAsset $asset
                    ): array {
                        $item =
                            $asset->item;

                        return [
                            'asset_number' =>
                                $asset
                                    ->asset_number
                                ?: '-',

                            'serial_number' =>
                                $asset
                                    ->serial_number
                                ?: '-',

                            'item_code' =>
                                $item?->code
                                ?: '-',

                            'item_name' =>
                                $item?->name
                                ?: '-',

                            'location' =>
                                $asset
                                    ->storageLocation
                                    ?->name
                                ?: '-',

                            'condition' =>
                                $this
                                    ->conditionLabel(
                                        $asset
                                            ->condition
                                    ),

                            'status' =>
                                $this
                                    ->statusLabel(
                                        $asset
                                            ->status
                                    ),

                            'received_date' =>
                                $asset
                                    ->received_date
                                ?? $item
                                    ?->received_date,

                            'unit_price' =>
                                $asset
                                    ->unit_price
                                ?? $item
                                    ?->unit_price
                                ?? 0,
                        ];
                    }
                )
                ->values();

        return [
            'coveredLocations' =>
                $coveredLocations,

            'locationIds' =>
                $locationIds,

            'materials' =>
                $materials,

            'assets' =>
                $assets,

            'toolGroups' =>
                $toolGroups,

            'toolSummaries' =>
                $toolSummaries,

            'materialSummaries' =>
                $materialSummaries,

            'completeRows' =>
                $completeRows,

            'assetDetails' =>
                $assetDetails,

            'summary' => [
                'tool_types' =>
                    $toolGroups->count(),

                'tool_units' =>
                    $assets->count(),

                'material_types' =>
                    $materials->count(),

                'material_stock' =>
                    (float)
                        $materials
                            ->sum('stock'),

                'location_count' =>
                    $coveredLocations
                        ->count(),

                'master_count' =>
                    $completeRows
                        ->count(),

                'total_value' =>
                    (float)
                        $completeRows
                            ->sum(
                                'total_value'
                            ),
            ],
        ];
    }

    private function distributionLabel(
        Collection $rows,
        string $field,
        bool $condition
    ): string {
        $groups =
            $rows
                ->groupBy(
                    static fn (
                        object $row
                    ): string =>
                        (string)
                        (
                            $row->{$field}
                            ?? '-'
                        )
                )
                ->map(
                    static fn (
                        Collection $group
                    ): int =>
                        $group->count()
                )
                ->sortDesc();

        return $groups
            ->map(
                function (
                    int $count,
                    string $value
                ) use (
                    $condition
                ): string {
                    $label =
                        $condition
                            ? $this
                                ->conditionLabel(
                                    $value
                                )
                            : $this
                                ->statusLabel(
                                    $value
                                );

                    return $label.
                        ' ('.
                        $count.
                        ')';
                }
            )
            ->implode(', ');
    }

    private function conditionLabel(
        mixed $condition
    ): string {
        return match (
            (string) $condition
        ) {
            'good' =>
                'Baik',

            'minor_damage' =>
                'Rusak Ringan',

            'major_damage' =>
                'Rusak Berat',

            'damaged' =>
                'Rusak',

            'maintenance',
            'under_repair' =>
                'Dalam Perawatan',

            'unfit' =>
                'Tidak Layak Pakai',

            default =>
                $condition
                    ? ucwords(
                        str_replace(
                            '_',
                            ' ',
                            (string)
                            $condition
                        )
                    )
                    : '-',
        };
    }

    private function statusLabel(
        mixed $status
    ): string {
        return match (
            (string) $status
        ) {
            'available' =>
                'Tersedia',

            'reserved' =>
                'Dipesan',

            'borrowed' =>
                'Dipinjam',

            'damaged' =>
                'Rusak',

            'maintenance',
            'under_repair' =>
                'Dalam Perawatan',

            'lost' =>
                'Hilang',

            'retired' =>
                'Dihapuskan',

            'out_of_stock' =>
                'Stok Habis',

            default =>
                $status
                    ? ucwords(
                        str_replace(
                            '_',
                            ' ',
                            (string)
                            $status
                        )
                    )
                    : '-',
        };
    }

    private function includeChildren(
        Request $request,
        StorageLocation $location
    ): bool {
        if ($request->has('include_children')) {
            return $request->boolean('include_children');
        }

        // Lokasi induk mencakup seluruh turunan.
        // Lokasi turunan hanya mencakup lokasi itu sendiri.
        return $location->parent_id === null;
    }

    private function locationIds(
        StorageLocation $location
    ): array {
        $ids = [
            (int) $location->id,
        ];

        $frontier =
            $ids;

        while ($frontier !== []) {
            $children =
                DB::table(
                    'storage_locations'
                )
                    ->whereIn(
                        'parent_id',
                        $frontier
                    )
                    ->pluck('id')
                    ->map(
                        static fn (
                            mixed $id
                        ): int =>
                            (int) $id
                    )
                    ->all();

            $children =
                array_values(
                    array_diff(
                        $children,
                        $ids
                    )
                );

            if ($children === []) {
                break;
            }

            $ids =
                array_merge(
                    $ids,
                    $children
                );

            $frontier =
                $children;
        }

        return $ids;
    }

    private function authorizeLocation(
        Request $request,
        StorageLocation $location
    ): void {
        $user =
            $request->user();

        abort_unless(
            $user !== null
            && in_array(
                (string) $user->role,
                [
                    'admin',
                    'wakil_sarpras',
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            ),
            403,
            'Anda tidak memiliki akses inventaris lokasi.'
        );

        if (
            in_array(
                (string) $user->role,
                [
                    'admin',
                    'wakil_sarpras',
                ],
                true
            )
        ) {
            return;
        }

        abort_unless(
            $user->workshop_id !== null
            && (int) $user->workshop_id
                ===
                (int) $location
                    ->workshop_id,
            403,
            'Lokasi tersebut berada di jurusan lain.'
        );
    }
}
