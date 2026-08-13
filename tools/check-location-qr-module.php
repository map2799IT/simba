<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$checks = [
    'StorageLocation model' =>
        class_exists(
            \App\Models\StorageLocation::class
        ),

    'StorageLocationController' =>
        class_exists(
            \App\Http\Controllers\StorageLocationController::class
        ),

    'Method inventoryPdf' =>
        method_exists(
            \App\Http\Controllers\StorageLocationController::class,
            'inventoryPdf'
        ),

    'Bulk QR controller' =>
        class_exists(
            \App\Http\Controllers\ItemAssetBulkQrController::class
        ),

    'Tabel storage_locations' =>
        Schema::hasTable(
            'storage_locations'
        ),

    'Kolom workshop_id' =>
        Schema::hasColumn(
            'storage_locations',
            'workshop_id'
        ),

    'Kolom parent_id' =>
        Schema::hasColumn(
            'storage_locations',
            'parent_id'
        ),

    'View locations.index' =>
        View::exists(
            'locations.index'
        ),

    'View locations.print' =>
        View::exists(
            'locations.inventory-print'
        ),

    'View bulk QR index' =>
        View::exists(
            'item-assets.bulk-qr-index'
        ),

    'View bulk QR print' =>
        View::exists(
            'item-assets.bulk-qr-print'
        ),

    'Route locations.index' =>
        Route::has(
            'locations.index'
        ),

    'Route location print' =>
        Route::has(
            'locations.inventory.print'
        ),

    'Route location PDF' =>
        Route::has(
            'locations.inventory.pdf'
        ),

    'Route bulk QR index' =>
        Route::has(
            'item-assets.qr-bulk.index'
        ),

    'Route bulk QR download' =>
        Route::has(
            'item-assets.qr-bulk.download'
        ),
];

$failed = false;

echo "SIMBA LOCATION & BULK QR CHECK\n";
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

echo "\nROUTE ACTIONS\n";
echo "-------------\n";

foreach ([
    'locations.index',
    'locations.inventory.pdf',
    'locations.inventory.print',
    'item-assets.qr-bulk.index',
    'item-assets.qr-bulk.download',
] as $name) {
    $route = Route::getRoutes()
        ->getByName($name);

    echo str_pad($name, 38).
        ': '.
        ($route?->getActionName()
            ?? 'TIDAK ADA').
        PHP_EOL;
}

echo "\n".
    (
        $failed
            ? 'MODUL BELUM VALID.'
            : 'MODUL LOKASI DAN QR MASSAL SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
