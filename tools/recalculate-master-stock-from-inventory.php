<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);

echo "SIMBA RECALCULATE MASTER STOCK\n";
echo "==============================\n";
echo "Mode: ".($apply ? 'APPLY' : 'DRY RUN')."\n\n";

$changes = [];

foreach (
    Item::query()->withoutGlobalScopes()->orderBy('type')->orderBy('code')->get()
    as $item
) {
    if ($item->isTool()) {
        $expected = ItemAsset::query()->withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->where('is_active', true)
            ->where('status', ItemAsset::STATUS_AVAILABLE)
            ->count();
    } else {
        $expected = ItemStockMovement::query()->withoutGlobalScopes()
            ->where('item_id', $item->id)->get()
            ->sum(function (ItemStockMovement $movement): float {
                $quantity = abs((float) $movement->quantity);

                return match ((string) $movement->type) {
                    'initial', 'incoming', 'adjustment_in', 'return' => $quantity,
                    'outgoing', 'adjustment_out', 'loan' => -$quantity,
                    default => (float) $movement->stock_after
                        - (float) $movement->stock_before,
                };
            });

        $expected = round(max(0, $expected), 3);
    }

    $current = round((float) $item->stock, 3);

    if (abs($current - $expected) > 0.000001) {
        $changes[] = [
            'id' => $item->id,
            'label' => $item->code.' '.$item->name,
            'current' => $current,
            'expected' => $expected,
        ];
    }
}

if ($changes === []) {
    echo "Tidak ada selisih stok.\n";
    exit(0);
}

foreach ($changes as $row) {
    echo str_pad($row['label'], 42).': '
        .$row['current'].' -> '.$row['expected'].PHP_EOL;
}

echo "\nTotal perubahan: ".count($changes)."\n";

if (! $apply) {
    echo "\nTidak ada data diubah. Backup database lalu tambah --apply bila hasil sudah benar.\n";
    exit(0);
}

DB::transaction(function () use ($changes): void {
    foreach ($changes as $row) {
        Item::query()->withoutGlobalScopes()->whereKey($row['id'])->update([
            'stock' => $row['expected'],
            'status' => $row['expected'] > 0 ? 'available' : 'out_of_stock',
        ]);
    }
});

echo "\nSTOK MASTER BERHASIL DISESUAIKAN.\n";
