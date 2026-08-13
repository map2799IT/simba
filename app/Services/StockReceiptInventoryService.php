<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockReceiptInventoryService
{
    public function __construct(
        private readonly BulkItemAssetService
            $assetService
    ) {
    }

    /**
     * Proses persediaan untuk satu baris Barang Masuk.
     *
     * Ganti increment stock lama dengan pemanggilan method ini.
     * Jangan menjalankan keduanya karena stok akan bertambah dua kali.
     */
    public function receive(
        Item $item,
        float $quantity,
        array $attributes = []
    ): Collection {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Jumlah Barang Masuk harus lebih dari nol.',
            ]);
        }

        return DB::transaction(
            function () use (
                $item,
                $quantity,
                $attributes
            ): Collection {
                $lockedItem = Item::query()
                    ->lockForUpdate()
                    ->findOrFail($item->id);

                if ($lockedItem->type === 'tool') {
                    if (
                        abs(
                            $quantity
                            - round($quantity)
                        ) > 0.000001
                    ) {
                        throw ValidationException::withMessages([
                            'quantity' =>
                                'Jumlah alat harus berupa bilangan bulat.',
                        ]);
                    }

                    $integerQuantity =
                        (int) round($quantity);

                    $assets =
                        $this->assetService
                            ->generate(
                                $lockedItem,
                                $integerQuantity,
                                $attributes
                            );

                    $lockedItem->increment(
                        'stock',
                        $integerQuantity
                    );

                    return $assets;
                }

                $lockedItem->increment(
                    'stock',
                    $quantity
                );

                return collect();
            },
            3
        );
    }
}
