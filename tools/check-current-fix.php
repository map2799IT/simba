<?php

declare(strict_types=1);

use App\Models\ItemStockMovement;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$checks = [
    'View stock-receipts.index' =>
        View::exists('stock-receipts.index'),

    'View stock-receipts.create' =>
        View::exists('stock-receipts.create'),

    'Tabel item_stock_movements' =>
        Schema::hasTable('item_stock_movements'),

    'Route stock-receipts.index' =>
        Route::has('stock-receipts.index'),

    'Route stock-receipts.create' =>
        Route::has('stock-receipts.create'),

    'Route stock-receipts.store' =>
        Route::has('stock-receipts.store'),

    'ItemAsset create dinonaktifkan' =>
        ! Route::has('item-assets.create'),

    'ItemAsset store dinonaktifkan' =>
        ! Route::has('item-assets.store'),

    'AccessController update' =>
        method_exists(
            \App\Http\Controllers\Admin\AccessController::class,
            'update'
        ),
];

$failed = false;

echo "SIMBA CURRENT FIX CHECK\n";
echo "=======================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 38).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

if (Schema::hasTable('item_stock_movements')) {
    $incoming = ItemStockMovement::query()
        ->where(
            'type',
            ItemStockMovement::TYPE_INCOMING
        )
        ->count();

    echo PHP_EOL.
        'Jumlah transaksi incoming: '.
        $incoming.
        PHP_EOL;
}

exit($failed ? 1 : 0);
