<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\StockIssueRequest;
use App\Models\StockIssueRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockIssueApplicationService
{
    public function __construct(
        private readonly WorkshopInventoryAvailabilityService $availability
    ) {
    }

    public function generateReference(): string
    {
        return 'BK-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
    }

    public function apply(StockIssueRequest $request, User $reviewer): int
    {
        return DB::transaction(function () use ($request): int {
            $locked = StockIssueRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if (! $locked->canBeApproved()) {
                throw ValidationException::withMessages([
                    'request' => 'Pengajuan ini tidak dapat diproses.',
                ]);
            }

            $workshop = $locked->workshop;
            $reference = $locked->reference_number ?? $this->generateReference();
            $userId = $locked->requested_by_user_id;
            $processed = 0;

            foreach ($locked->items as $row) {
                $item = Item::query()
                    ->withoutGlobalScopes()
                    ->with('unit')
                    ->lockForUpdate()
                    ->findOrFail($row->item_id);

                if (! $item->is_active) {
                    throw ValidationException::withMessages([
                        'item' => "Barang {$item->code} sudah tidak aktif.",
                    ]);
                }

                $available = $this->availability->availableQuantity(
                    $item,
                    (int) $workshop->id,
                    true
                );

                $globalBefore = round(max(0, (float) $item->stock), 3);
                $assetNumbers = [];

                if ($item->isTool()) {
                    $assetIds = array_values(array_unique(
                        array_map('intval', $row->asset_ids ?? [])
                    ));

                    if ($assetIds === []) {
                        throw ValidationException::withMessages([
                            'item' => "Pilih minimal satu unit alat untuk {$item->code}.",
                        ]);
                    }

                    $assets = ItemAsset::query()
                        ->withoutGlobalScopes()
                        ->where('item_id', $item->id)
                        ->where('workshop_id', $workshop->id)
                        ->whereIn('id', $assetIds)
                        ->lockForUpdate()
                        ->get();

                    if ($assets->count() !== count($assetIds)) {
                        throw ValidationException::withMessages([
                            'item' => "Ada unit yang bukan milik jurusan ini untuk {$item->code}.",
                        ]);
                    }

                    foreach ($assets as $asset) {
                        if (! $asset->is_active || $asset->status !== ItemAsset::STATUS_AVAILABLE) {
                            throw ValidationException::withMessages([
                                'item' => "Unit {$asset->asset_number} tidak tersedia untuk Barang Keluar.",
                            ]);
                        }
                    }

                    $quantity = (float) $assets->count();

                    if ($quantity > $available) {
                        throw ValidationException::withMessages([
                            'item' => "Jumlah unit melebihi stok alat {$item->code} yang tersedia.",
                        ]);
                    }

                    foreach ($assets as $asset) {
                        $assetNumbers[] = $asset->asset_number;

                        $asset->fill([
                            'status' => ItemAsset::STATUS_RETIRED,
                            'is_active' => false,
                            'notes' => $this->assetNotes(
                                $asset->notes,
                                $reference,
                                $row->notes
                            ),
                        ])->save();
                    }
                } else {
                    $quantity = round((float) ($row->quantity ?? 0), 3);

                    if ($quantity <= 0) {
                        throw ValidationException::withMessages([
                            'item' => "Jumlah bahan {$item->code} wajib lebih dari nol.",
                        ]);
                    }

                    if (
                        $item->unit !== null
                        && ! $item->unit->allows_decimal
                        && abs($quantity - round($quantity)) > 0.000001
                    ) {
                        throw ValidationException::withMessages([
                            'item' => "Satuan bahan {$item->code} tidak mengizinkan jumlah desimal.",
                        ]);
                    }

                    if ($quantity > $available + 0.000001) {
                        throw ValidationException::withMessages([
                            'item' => "Jumlah keluar melebihi stok {$item->code}. Stok tersedia: {$available}.",
                        ]);
                    }
                }

                $globalAfter = round(max(0, $globalBefore - $quantity), 3);

                $item->fill([
                    'stock' => $globalAfter,
                    'status' => $globalAfter > 0 ? 'available' : 'out_of_stock',
                ])->save();

                $movement = new ItemStockMovement();

                $movement->fill([
                    'item_id' => $item->id,
                    'user_id' => $userId,
                    'workshop_id' => $workshop->id,
                    'type' => ItemStockMovement::TYPE_OUTGOING,
                    'quantity' => $quantity,
                    'stock_before' => $globalBefore,
                    'stock_after' => $globalAfter,
                    'transaction_date' => $locked->transaction_date,
                    'reference_number' => $reference,
                    'destination' => $locked->destination,
                    'purpose' => $locked->purpose,
                    'description' => $this->movementNotes(
                        $locked,
                        $row,
                        $assetNumbers,
                        $workshop->code
                    ),
                ])->save();

                $processed++;
            }

            $locked->fill([
                'status' => StockIssueRequest::STATUS_APPROVED,
                'reference_number' => $reference,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            return $processed;
        }, attempts: 3);
    }

    private function movementNotes(
        StockIssueRequest $header,
        StockIssueRequestItem $row,
        array $assetNumbers,
        string $workshopCode
    ): ?string {
        $parts = array_filter([
            'Jurusan: ' . $workshopCode,
            $header->description,
            $row->notes,
            $assetNumbers !== []
                ? 'Unit alat: ' . implode(', ', $assetNumbers)
                : null,
        ]);

        return $parts === [] ? null : implode(' | ', $parts);
    }

    private function assetNotes(?string $oldNotes, string $reference, ?string $rowNotes): string
    {
        return implode(' | ', array_filter([
            $oldNotes,
            'Barang keluar permanen ' . $reference,
            $rowNotes,
        ]));
    }
}
