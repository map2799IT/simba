<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [
    'users.workshop_id' =>
        Schema::hasColumn(
            'users',
            'workshop_id'
        ),

    'Scoped controller' =>
        class_exists(
            \App\Http\Controllers\ScopedInventoryReportController::class
        ),

    'Inventory service' =>
        class_exists(
            \App\Services\InventoryAccessService::class
        ),

    'View reports.inventory' =>
        View::exists(
            'reports.inventory'
        ),

    'View inventory PDF' =>
        View::exists(
            'reports.inventory-pdf'
        ),

    'Route reports.index' =>
        Route::has(
            'reports.index'
        ),

    'Route reports.export.pdf' =>
        Route::has(
            'reports.export.pdf'
        ),

    'Route reports.export.excel' =>
        Route::has(
            'reports.export.excel'
        ),

    'Route locations.inventory.pdf' =>
        Route::has(
            'locations.inventory.pdf'
        ),
];

$failed = false;

echo "SIMBA INVENTORY SCOPE CHECK\n";
echo "===========================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 36).
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
    'reports.index',
    'reports.inventory',
    'reports.export.pdf',
    'reports.export.excel',
    'locations.inventory.pdf',
] as $name) {
    $route = Route::getRoutes()
        ->getByName($name);

    echo str_pad($name, 36).
        ': '.
        ($route?->getActionName() ?? 'TIDAK ADA').
        PHP_EOL;
}

exit($failed ? 1 : 0);
