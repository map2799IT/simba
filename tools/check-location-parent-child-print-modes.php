<?php

declare(strict_types=1);

use App\Http\Controllers\LocationInventoryController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [
    'Controller' => class_exists(LocationInventoryController::class),
    'Method menu' => method_exists(LocationInventoryController::class, 'menu'),
    'Method summary' => method_exists(LocationInventoryController::class, 'summary'),
    'Method summaryPrint' => method_exists(LocationInventoryController::class, 'summaryPrint'),
    'Method summaryPdf' => method_exists(LocationInventoryController::class, 'summaryPdf'),
    'Method complete' => method_exists(LocationInventoryController::class, 'complete'),
    'Method detail PDF' => method_exists(LocationInventoryController::class, 'pdf'),
    'Route menu' => Route::has('locations.inventory.menu'),
    'Route ringkasan' => Route::has('locations.inventory.summary'),
    'Route print ringkasan' => Route::has('locations.inventory.summary.print'),
    'Route PDF ringkasan' => Route::has('locations.inventory.summary.pdf'),
    'Route detail' => Route::has('locations.inventory.complete'),
    'Route PDF detail' => Route::has('locations.inventory.complete.pdf'),
    'View menu' => View::exists('locations.inventory-menu'),
    'View ringkasan' => View::exists('locations.inventory-summary'),
    'View print ringkasan' => View::exists('locations.inventory-summary-print'),
    'View detail' => View::exists('locations.inventory-complete'),
    'Partial tombol aksi' => View::exists('locations._inventory-action-buttons'),
    'Partial menu link' => View::exists('locations._inventory-menu-link'),
];

$failed = false;
echo "SIMBA LOCATION PRINT MODES CHECK
";
echo "================================

";

foreach ($checks as $label => $valid) {
    echo str_pad($label, 38).': '.($valid ? 'OK' : 'GAGAL').PHP_EOL;
    if (! $valid) $failed = true;
}

$index = $root.'/resources/views/locations/index.blade.php';
$indexValid = is_file($index)
    && str_contains((string) file_get_contents($index), 'locations._inventory-menu-link');

echo str_pad('Menu link pada daftar lokasi', 38).': '.($indexValid ? 'OK' : 'GAGAL').PHP_EOL;
if (! $indexValid) $failed = true;

echo "
".($failed
    ? 'MENU DAN DUA OPSI PRINT BELUM VALID.'
    : 'MENU INDUK/TURUNAN DAN DUA OPSI PRINT SUDAH VALID.'
).PHP_EOL;

exit($failed ? 1 : 0);
