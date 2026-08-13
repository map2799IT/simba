<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\Workshop;
use App\Services\ItemAssetQrCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItemAssetBulkQrController extends Controller
{
    /**
     * Daftar QR massal dikelompokkan berdasarkan:
     *
     * - master alat;
     * - jurusan unit fisik.
     *
     * Master Item merupakan katalog umum sehingga items.workshop_id
     * tidak boleh dipakai untuk menentukan kepemilikan unit.
     */
    public function index(Request $request): View
    {
        $this->authorizeRole($request);

        $workshopId = $this->effectiveWorkshopId($request);
        $search = trim((string) $request->input('search'));

        $query = DB::table('item_assets')
            ->join(
                'items',
                'items.id',
                '=',
                'item_assets.item_id'
            )
            ->join(
                'workshops',
                'workshops.id',
                '=',
                'item_assets.workshop_id'
            )
            ->leftJoin(
                'storage_locations',
                'storage_locations.id',
                '=',
                'item_assets.storage_location_id'
            )
            ->where('item_assets.is_active', true)
            ->where('items.type', 'tool')
            ->where('items.is_active', true)
            ->when(
                $workshopId !== null,
                fn ($builder) => $builder->where(
                    'item_assets.workshop_id',
                    $workshopId
                )
            )
            ->when(
                $search !== '',
                function ($builder) use ($search): void {
                    $builder->where(
                        function ($searchQuery) use ($search): void {
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
                                    'item_assets.asset_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'item_assets.serial_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'item_assets.brand',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'item_assets.model',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->select([
                'items.id as item_id',
                'items.code as item_code',
                'items.name as item_name',
                'workshops.id as workshop_id',
                'workshops.code as workshop_code',
                'workshops.name as workshop_name',
            ])
            ->selectRaw(
                'COUNT(item_assets.id) AS asset_units_count'
            )
            ->selectRaw(
                'COUNT(DISTINCT item_assets.storage_location_id) AS location_count'
            )
            ->selectRaw(
                'SUM(CASE WHEN item_assets.storage_location_id IS NULL THEN 1 ELSE 0 END) AS missing_location_count'
            )
            ->selectRaw(
                'MIN(storage_locations.name) AS first_location_name'
            )
            ->groupBy([
                'items.id',
                'items.code',
                'items.name',
                'workshops.id',
                'workshops.code',
                'workshops.name',
            ])
            ->orderBy('items.name')
            ->orderBy('workshops.code');

        $groups = $query
            ->paginate(20)
            ->withQueryString();

        return view('item-assets.bulk-qr-index', [
            'groups' => $groups,
            'workshops' => $this->visibleWorkshops($request),
            'selectedWorkshopId' => $workshopId,
            'isAdmin' => (string) $request->user()->role === 'admin',
        ]);
    }

    public function print(
        Request $request,
        Item $item,
        ItemAssetQrCodeService $qrCodeService
    ): View {
        $workshopId = $this->printWorkshopId($request);

        $this->authorizeItem(
            $request,
            $item,
            $workshopId
        );

        return view(
            'item-assets.bulk-qr-print',
            array_merge(
                $this->printData(
                    $item,
                    $qrCodeService,
                    $workshopId
                ),
                [
                    'pdfMode' => false,
                ]
            )
        );
    }

    public function download(
        Request $request,
        Item $item,
        ItemAssetQrCodeService $qrCodeService
    ): mixed {
        $workshopId = $this->printWorkshopId($request);

        $this->authorizeItem(
            $request,
            $item,
            $workshopId
        );

        $data = array_merge(
            $this->printData(
                $item,
                $qrCodeService,
                $workshopId
            ),
            [
                'pdfMode' => true,
            ]
        );

        $workshopCode =
            $data['printWorkshop']?->code
            ?? 'SEMUA-JURUSAN';

        $filename =
            'qr-'.
            $workshopCode.
            '-'.
            $item->code.
            '-'.
            now()->format('Ymd-His').
            '.pdf';

        if (
            class_exists(
                \Barryvdh\DomPDF\Facade\Pdf::class
            )
        ) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'item-assets.bulk-qr-print',
                $data
            )
                ->setPaper('a4', 'portrait')
                ->download($filename);
        }

        return response()->view(
            'item-assets.bulk-qr-print',
            array_merge(
                $data,
                [
                    'pdfMode' => false,
                    'pdfFallback' => true,
                ]
            )
        );
    }

    private function printData(
        Item $item,
        ItemAssetQrCodeService $qrCodeService,
        ?int $workshopId
    ): array {
        $item->load([
            'category',
            'unit',
        ]);

        $assets = ItemAsset::query()
            ->withoutGlobalScopes()
            ->with([
                'storageLocation',
                'workshop',
            ])
            ->where('item_id', $item->id)
            ->where('is_active', true)
            ->when(
                $workshopId !== null,
                fn ($query) => $query->where(
                    'workshop_id',
                    $workshopId
                )
            )
            ->orderBy('workshop_id')
            ->orderBy('asset_number')
            ->get();

        $printWorkshop = null;

        if ($workshopId !== null) {
            $printWorkshop = Workshop::query()
                ->withoutGlobalScopes()
                ->find($workshopId);
        } else {
            $workshopIds = $assets
                ->pluck('workshop_id')
                ->filter()
                ->unique()
                ->values();

            if ($workshopIds->count() === 1) {
                $printWorkshop = Workshop::query()
                    ->withoutGlobalScopes()
                    ->find($workshopIds->first());
            }
        }

        $locationNames = $assets
            ->map(
                fn (ItemAsset $asset): ?string =>
                    $asset->storageLocation?->name
            )
            ->filter()
            ->unique()
            ->values();

        $hasMissingLocation = $assets->contains(
            fn (ItemAsset $asset): bool =>
                $asset->storage_location_id === null
        );

        $locationSummary = match (true) {
            $assets->isEmpty() =>
                '-',

            $locationNames->isEmpty() =>
                'Lokasi belum ditentukan',

            $locationNames->count() === 1
            && ! $hasMissingLocation =>
                (string) $locationNames->first(),

            default =>
                'Beberapa lokasi',
        };

        $labels = $assets->map(
            function (
                ItemAsset $asset
            ) use (
                $qrCodeService
            ): array {
                $pngDataUri = method_exists(
                    $qrCodeService,
                    'pngDataUri'
                )
                    ? $qrCodeService->pngDataUri(
                        $asset,
                        240
                    )
                    : null;

                $svgDataUri = method_exists(
                    $qrCodeService,
                    'dataUri'
                )
                    ? $qrCodeService->dataUri(
                        $asset,
                        240
                    )
                    : null;

                return [
                    'asset' => $asset,
                    'qrPngDataUri' => $pngDataUri,
                    'qrSvgDataUri' => $svgDataUri,
                ];
            }
        );

        return [
            'item' => $item,
            'assets' => $assets,
            'labels' => $labels,
            'printWorkshop' => $printWorkshop,
            'selectedWorkshopId' => $workshopId,
            'locationSummary' => $locationSummary,
            'generatedAt' => now(),
        ];
    }

    private function authorizeRole(Request $request): void
    {
        abort_unless(
            in_array(
                (string) $request->user()->role,
                [
                    'admin',
                    'toolman',
                ],
                true
            ),
            403,
            'Cetak QR massal hanya tersedia untuk Administrator dan Toolman.'
        );
    }

    /**
     * Hak akses ditentukan dari item_assets.workshop_id,
     * bukan items.workshop_id.
     */
    private function authorizeItem(
        Request $request,
        Item $item,
        ?int $workshopId
    ): void {
        $this->authorizeRole($request);

        abort_unless(
            $item->type === 'tool',
            404
        );

        if (
            (string) $request->user()->role
            === 'toolman'
        ) {
            $workshopId =
                $this->requiredWorkshopId(
                    $request
                );
        }

        $query = ItemAsset::query()
            ->withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->where('is_active', true);

        if ($workshopId !== null) {
            $query->where(
                'workshop_id',
                $workshopId
            );
        }

        abort_unless(
            $query->exists(),
            404,
            'Tidak ada unit alat aktif yang dapat dicetak.'
        );
    }

    private function effectiveWorkshopId(
        Request $request
    ): ?int {
        if (
            (string) $request->user()->role
            === 'toolman'
        ) {
            return $this->requiredWorkshopId(
                $request
            );
        }

        if (
            ! $request->filled(
                'workshop_id'
            )
        ) {
            return null;
        }

        return $request->integer(
            'workshop_id'
        );
    }

    private function printWorkshopId(
        Request $request
    ): ?int {
        if (
            (string) $request->user()->role
            === 'toolman'
        ) {
            return $this->requiredWorkshopId(
                $request
            );
        }

        if (
            ! $request->filled(
                'workshop_id'
            )
        ) {
            return null;
        }

        return $request->integer(
            'workshop_id'
        );
    }

    private function visibleWorkshops(
        Request $request
    ): Collection {
        $query = Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('code');

        if (
            (string) $request->user()->role
            === 'toolman'
        ) {
            $query->whereKey(
                $this->requiredWorkshopId(
                    $request
                )
            );
        }

        return $query->get([
            'id',
            'code',
            'name',
        ]);
    }

    private function requiredWorkshopId(
        Request $request
    ): int {
        $value =
            $request->user()->workshop_id;

        abort_if(
            $value === null
            || $value === '',
            403,
            'Akun Toolman belum mempunyai jurusan.'
        );

        return (int) $value;
    }
}
