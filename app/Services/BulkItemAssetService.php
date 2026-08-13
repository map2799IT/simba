<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkItemAssetService
{
    public function __construct(
        private readonly AssetNumberService $numberService
    ) {
    }

    public function generate(
        Item $item,
        int $quantity,
        array $attributes = []
    ): Collection {
        if (! $item->isTool()) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Nomor unit hanya dibuat untuk barang berjenis alat.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah unit alat minimal 1.',
            ]);
        }

        $workshopId = isset($attributes['workshop_id'])
            ? (int) $attributes['workshop_id']
            : null;

        if ($workshopId === null) {
            throw ValidationException::withMessages([
                'workshop_id' => 'Bengkel tujuan wajib dipilih.',
            ]);
        }

        return DB::transaction(
            function () use (
                $item,
                $quantity,
                $attributes,
                $workshopId
            ): Collection {
                $created = collect();

                $year = isset($attributes['received_date'])
                    ? (int) date(
                        'Y',
                        strtotime((string) $attributes['received_date'])
                    )
                    : (int) now()->format('Y');

                for ($index = 0; $index < $quantity; $index++) {
                    $assetNumber = $this->numberService->next(
                        $item,
                        $year,
                        $workshopId
                    );

                    $asset = new ItemAsset();

                    $asset->fill([
                        'item_id' => $item->id,
                        'asset_number' => $assetNumber,
                        'barcode_value' => $assetNumber,
                        'receipt_code' => $attributes['receipt_code'] ?? null,
                        'serial_number' => null,
                        'brand' => $attributes['brand'] ?? null,
                        'model' => $attributes['model'] ?? null,
                        'specification' => $attributes['specification'] ?? null,
                        'acquisition_source' =>
                            $attributes['acquisition_source'] ?? null,
                        'fund_source' => $attributes['fund_source'] ?? null,
                        'workshop_id' => $workshopId,
                        'storage_location_id' =>
                            $attributes['storage_location_id'] ?? null,
                        'condition' =>
                            $attributes['condition']
                            ?? ItemAsset::CONDITION_GOOD,
                        'status' =>
                            $attributes['status']
                            ?? ItemAsset::STATUS_AVAILABLE,
                        'received_date' =>
                            $attributes['received_date']
                            ?? now()->toDateString(),
                        'unit_price' => $attributes['unit_price'] ?? null,
                        'photo_path' => $attributes['photo_path'] ?? null,
                        'notes' =>
                            $attributes['notes']
                            ?? 'Dibuat otomatis dari transaksi Barang Masuk.',
                        'is_active' => $attributes['is_active'] ?? true,
                    ]);

                    $asset->save();
                    $created->push($asset);
                }

                return $created;
            },
            3
        );
    }
}
