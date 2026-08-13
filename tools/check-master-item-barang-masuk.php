<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA MASTER VS BARANG MASUK CHECK\n";
echo "==================================\n\n";

$files = [
    'StoreItemRequest' =>
        'app/Http/Requests/StoreItemRequest.php',

    'ItemController' =>
        'app/Http/Controllers/ItemController.php',

    'StoreStockReceiptRequest' =>
        'app/Http/Requests/StoreStockReceiptRequest.php',

    'StockReceiptController' =>
        'app/Http/Controllers/StockReceiptController.php',

    'Form master barang' =>
        'resources/views/items/create.blade.php',

    'Form Barang Masuk' =>
        'resources/views/stock-receipts/create.blade.php',
];

foreach ($files as $label => $relative) {
    $file = $root.'/'.$relative;

    $valid = is_file($file)
        && filesize($file) > 0;

    echo str_pad($label, 39).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

foreach (
    [
        'items.create',
        'items.store',
        'stock-receipts.create',
        'stock-receipts.store',
    ]
    as $routeName
) {
    $valid = Route::has($routeName);

    echo str_pad(
        'Route '.$routeName,
        39
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$masterFile =
    $root.
    '/resources/views/items/create.blade.php';

$receiptFile =
    $root.
    '/resources/views/stock-receipts/create.blade.php';

$master = is_file($masterFile)
    ? (string) file_get_contents($masterFile)
    : '';

$receipt = is_file($receiptFile)
    ? (string) file_get_contents($receiptFile)
    : '';

$notInMaster = [
    'name="received_date"',
    'name="acquisition_source"',
    'name="fund_source"',
    'name="unit_price"',
    'name="minimum_stock"',
    'name="condition"',
    'name="photo"',
    'name="storage_location_id"',
];

foreach ($notInMaster as $needle) {
    $valid = ! str_contains(
        $master,
        $needle
    );

    echo str_pad(
        'Master tanpa '.$needle,
        39
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$inReceipt = [
    'name="workshop_id"',
    'name="receipt_date"',
    'name="source"',
    'name="fund_source"',
    '[storage_location_id]',
    '[unit_price]',
    '[minimum_stock]',
    '[condition]',
    '[photo]',
];

foreach ($inReceipt as $needle) {
    $valid = str_contains(
        $receipt,
        $needle
    );

    echo str_pad(
        'Barang Masuk '.$needle,
        39
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

foreach (
    [
        'workshop_id',
        'storage_location_id',
        'received_date',
        'acquisition_source',
        'fund_source',
        'unit_price',
        'condition',
        'stock',
        'minimum_stock',
        'photo_path',
    ]
    as $column
) {
    $valid = Schema::hasColumn(
        'items',
        $column
    );

    echo str_pad(
        'items.'.$column,
        39
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'PEMISAHAN MASTER DAN BARANG MASUK BELUM VALID.'
            : 'MASTER BARANG DAN BARANG MASUK SUDAH TERPISAH.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
