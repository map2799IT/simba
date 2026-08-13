<?php

namespace App\Observers;

use App\Models\Item;
use App\Models\ItemStockMovement;
use Illuminate\Support\Facades\Schema;

class ItemObserver
{
    public function created(Item $item): void
    {
        /*
         * Menjaga agar pembuatan item tetap berjalan ketika
         * migration tabel riwayat belum dijalankan.
         */
        if (
            ! Schema::hasTable(
                'item_stock_movements'
            )
        ) {
            return;
        }

        $stockAfter = $item->isTool()
            ? 1
            : (float) $item->stock;

        ItemStockMovement::query()->create([
            'item_id' => $item->id,
            'user_id' => auth()->id(),
            'type' =>
                ItemStockMovement::TYPE_INITIAL,
            'quantity' => $stockAfter,
            'stock_before' => 0,
            'stock_after' => $stockAfter,
            'transaction_date' =>
                $item->received_date?->toDateString()
                ?? now()->toDateString(),
            'reference_number' => null,
            'source' =>
                $item->acquisition_source,
            'description' =>
                'Saldo awal saat barang didaftarkan.',
        ]);
    }
}