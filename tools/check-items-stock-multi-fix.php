<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.
    DIRECTORY_SEPARATOR.
    'vendor'.
    DIRECTORY_SEPARATOR.
    'autoload.php';

$app = require $root.
    DIRECTORY_SEPARATOR.
    'bootstrap'.
    DIRECTORY_SEPARATOR.
    'app.php';

$app->make(Kernel::class)->bootstrap();

$checks = [
    'View items.create' =>
        View::exists('items.create'),

    'View items.bulk-create' =>
        View::exists('items.bulk-create'),

    'View stock-receipts.create' =>
        View::exists(
            'stock-receipts.create'
        ),

    'View stock-receipts.index' =>
        View::exists(
            'stock-receipts.index'
        ),

    'View stock-issues.create' =>
        View::exists(
            'stock-issues.create'
        ),

    'View stock-issues.index' =>
        View::exists(
            'stock-issues.index'
        ),

    'ItemController@bulkCreate' =>
        method_exists(
            \App\Http\Controllers\ItemController::class,
            'bulkCreate'
        ),

    'ItemController@bulkStore' =>
        method_exists(
            \App\Http\Controllers\ItemController::class,
            'bulkStore'
        ),

    'StockReceiptController@store' =>
        method_exists(
            \App\Http\Controllers\StockReceiptController::class,
            'store'
        ),

    'StockIssueController@store' =>
        method_exists(
            \App\Http\Controllers\StockIssueController::class,
            'store'
        ),

    'BulkItemAssetService' =>
        class_exists(
            \App\Services\BulkItemAssetService::class
        ),

    'AssetNumberService' =>
        class_exists(
            \App\Services\AssetNumberService::class
        ),

    'Route items.create' =>
        Route::has('items.create'),

    'Route items.bulk.create' =>
        Route::has('items.bulk.create'),

    'Route items.bulk.store' =>
        Route::has('items.bulk.store'),

    'Route stock-receipts.create' =>
        Route::has(
            'stock-receipts.create'
        ),

    'Route stock-receipts.store' =>
        Route::has(
            'stock-receipts.store'
        ),

    'Route stock-issues.create' =>
        Route::has(
            'stock-issues.create'
        ),

    'Route stock-issues.store' =>
        Route::has(
            'stock-issues.store'
        ),

    'Table item_assets' =>
        Schema::hasTable('item_assets'),

    'Table item_stock_movements' =>
        Schema::hasTable(
            'item_stock_movements'
        ),
];

$failed = false;

echo "SIMBA ITEMS & STOK MULTI CHECK\n";
echo "==============================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 38).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo PHP_EOL;

if ($failed) {
    echo "Masih ada komponen yang belum aktif.\n";
    exit(1);
}

echo "Semua komponen perbaikan sudah aktif.\n";
exit(0);
