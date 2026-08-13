<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockReceiptRequest;
use App\Models\Item;
use App\Models\ItemStockMovement;
use App\Models\StorageLocation;
use App\Models\Workshop;
use App\Services\BulkItemAssetService;
use App\Services\StockReceiptCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StockReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            in_array((string) $user?->role, ['admin', 'kepala_bengkel', 'toolman'], true),
            403
        );

        $search = trim((string) $request->input('search'));

        $movements = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->with([
                'item.category',
                'item.unit',
                'workshop',
                'storageLocation',
                'user',
            ])
            ->where(
                'type',
                ItemStockMovement::TYPE_INCOMING
            )
            ->when(
                (string) $user?->role !== 'admin',
                fn (Builder $query): Builder =>
                    $query->where(
                        'workshop_id',
                        $user?->workshop_id
                    )
            )
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(
                        function (Builder $searchQuery) use ($search): void {
                            $searchQuery
                                ->where(
                                    'receipt_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'reference_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'source',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'brand',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'model',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->withoutGlobalScopes()
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
                $request->filled('workshop_id'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'workshop_id',
                        $request->integer('workshop_id')
                    )
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('stock-receipts.index', [
            'movements' => $movements,
            'workshops' => Workshop::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            in_array((string) $user?->role, ['admin', 'toolman'], true),
            403
        );

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->when(
                (string) $user?->role !== 'admin',
                fn (Builder $query): Builder =>
                    $query->whereKey($user?->workshop_id)
            )
            ->orderBy('code')
            ->get();

        $selectedWorkshopId = old(
            'workshop_id',
            $request->integer('workshop_id')
                ?: $user?->workshop_id
                ?: $workshops->first()?->id
        );

        $visibleWorkshopIds = $workshops
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        /*
         * Master merupakan katalog umum dan tidak difilter
         * berdasarkan bengkel tujuan.
         */
        $items = Item::query()
            ->withoutGlobalScopes()
            ->with([
                'category',
                'unit',
            ])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        $locations = StorageLocation::query()
            ->withoutGlobalScopes()
            ->with([
                'workshop',
                'parent.parent.parent',
            ])
            ->where('is_active', true)
            ->whereIn('workshop_id', $visibleWorkshopIds)
            ->orderBy('workshop_id')
            ->orderBy('code')
            ->get();

        return view('stock-receipts.create', [
            'items' => $items,
            'workshops' => $workshops,
            'locations' => $locations,
            'conditions' => Item::conditionOptions(),
            'selectedWorkshopId' => $selectedWorkshopId,
            'isAdmin' => (string) $user?->role === 'admin',
        ]);
    }

    public function store(
        StoreStockReceiptRequest $request,
        BulkItemAssetService $assetService,
        StockReceiptCodeService $receiptCodeService
    ): RedirectResponse {
        $data = $request->validated();

        $reference = $data['document_number']
            ?: 'BM-'.
                now()->format('Ymd-His').
                '-'.
                Str::upper(Str::random(4));

        $userId = $request->user()?->id;
        $newPhotoPaths = [];

        foreach ($data['items'] as $index => $row) {
            $photo = $request->file(
                "items.{$index}.photo"
            );

            if ($photo !== null) {
                $newPhotoPaths[$index] = $photo->store(
                    'stock-receipts',
                    'public'
                );
            }
        }

        try {
            $result = DB::transaction(
                function () use (
                    $data,
                    $reference,
                    $userId,
                    $assetService,
                    $receiptCodeService,
                    $newPhotoPaths
                ): array {
                    $movementCount = 0;
                    $assetCount = 0;
                    $receiptCodes = [];

                    $targetWorkshop = Workshop::query()
                        ->findOrFail($data['workshop_id']);

                    foreach ($data['items'] as $index => $row) {
                        $item = Item::query()
                            ->withoutGlobalScopes()
                            ->with([
                                'unit',
                                'category',
                            ])
                            ->lockForUpdate()
                            ->findOrFail($row['item_id']);

                        $receiptCode = $receiptCodeService->generate(
                            $item->type,
                            $data['receipt_date']
                        );

                        $receiptCodes[] = $receiptCode;

                        $quantity = round(
                            (float) $row['quantity'],
                            3
                        );

                        if ($item->isTool()) {
                            $quantity = (int) round($quantity);
                        }

                        $stockBefore = round(
                            (float) $item->stock,
                            3
                        );

                        $stockAfter = round(
                            $stockBefore + $quantity,
                            3
                        );

                        $locationId = (int)
                            $row['storage_location_id'];

                        $unitPrice = $row['unit_price'] ?? null;
                        $condition = $row['condition'] ?? 'good';

                        $minimumStock = $item->isMaterial()
                            ? (
                                $row['minimum_stock']
                                ?? $item->minimum_stock
                                ?? 0
                            )
                            : 0;

                        $photoPath = $newPhotoPaths[$index] ?? null;

                        /*
                         * Master hanya menyimpan angka agregat.
                         * Detail pembelian tidak ditulis ke master.
                         */
                        $item->fill([
                            'stock' => $stockAfter,
                            'status' => $stockAfter > 0
                                ? 'available'
                                : 'out_of_stock',
                            'minimum_stock' => $minimumStock,
                        ])->save();

                        if ($item->isTool()) {
                            $assets = $assetService->generate(
                                $item,
                                (int) $quantity,
                                [
                                    'receipt_code' => $receiptCode,
                                    'workshop_id' => $targetWorkshop->id,
                                    'storage_location_id' => $locationId,
                                    'received_date' => $data['receipt_date'],
                                    'brand' => $row['brand'] ?? null,
                                    'model' => $row['model'] ?? null,
                                    'specification' =>
                                        $row['specification'] ?? null,
                                    'acquisition_source' =>
                                        $data['source'] ?? null,
                                    'fund_source' =>
                                        $data['fund_source'] ?? null,
                                    'unit_price' => $unitPrice,
                                    'condition' => $condition,
                                    'photo_path' => $photoPath,
                                    'status' => 'available',
                                    'notes' => $this->movementNotes(
                                        $data,
                                        $row,
                                        $receiptCode,
                                        $locationId
                                    ),
                                ]
                            );

                            $assetCount += $assets->count();
                        }

                        $movement = new ItemStockMovement();

                        $movement->fill([
                            'receipt_code' => $receiptCode,
                            'item_id' => $item->id,
                            'user_id' => $userId,
                            'workshop_id' => $targetWorkshop->id,
                            'storage_location_id' => $locationId,
                            'type' =>
                                ItemStockMovement::TYPE_INCOMING,
                            'quantity' => $quantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'transaction_date' =>
                                $data['receipt_date'],
                            'reference_number' => $reference,
                            'source' => $data['source'] ?? null,
                            'brand' => $row['brand'] ?? null,
                            'model' => $row['model'] ?? null,
                            'specification' =>
                                $row['specification'] ?? null,
                            'fund_source' =>
                                $data['fund_source'] ?? null,
                            'unit_price' => $unitPrice,
                            'condition' => $condition,
                            'photo_path' => $photoPath,
                            'description' => $this->movementNotes(
                                $data,
                                $row,
                                $receiptCode,
                                $locationId
                            ),
                        ]);

                        $movement->save();
                        $movementCount++;
                    }

                    return [
                        'movements' => $movementCount,
                        'assets' => $assetCount,
                        'codes' => $receiptCodes,
                    ];
                },
                attempts: 3
            );
        } catch (Throwable $exception) {
            foreach ($newPhotoPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $message =
            "{$result['movements']} baris Barang Masuk berhasil diproses. Kode: ".
            implode(', ', $result['codes']).
            '.';

        if ($result['assets'] > 0) {
            $message .=
                " {$result['assets']} unit alat beserta nomor inventaris dan QR berhasil dibuat.";
        }

        return redirect()
            ->route('stock-receipts.index', [
                'search' => $reference,
            ])
            ->with('success', $message);
    }

    public function history(Item $item): View
    {
        $movements = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->with([
                'workshop',
                'storageLocation',
                'user',
            ])
            ->where('item_id', $item->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('items.history', [
            'item' => $item,
            'movements' => $movements,
        ]);
    }

    private function movementNotes(
        array $header,
        array $row,
        string $receiptCode,
        ?int $locationId = null
    ): string {
        $location = $locationId !== null
            ? StorageLocation::query()
                ->withoutGlobalScopes()
                ->find($locationId)
            : null;

        return implode(
            ' | ',
            array_filter([
                'Barang masuk '.$receiptCode,
                ! empty($row['brand'])
                    ? 'Merek: '.$row['brand']
                    : null,
                ! empty($row['model'])
                    ? 'Model: '.$row['model']
                    : null,
                ! empty($row['specification'])
                    ? 'Spesifikasi: '.$row['specification']
                    : null,
                ! empty($header['fund_source'])
                    ? 'Sumber dana: '.$header['fund_source']
                    : null,
                $location !== null
                    ? 'Lokasi: '.$location->code.' - '.$location->name
                    : null,
                ! empty($row['condition'])
                    ? 'Kondisi: '.$row['condition']
                    : null,
                $header['notes'] ?? null,
                $row['notes'] ?? null,
            ])
        );
    }
}
