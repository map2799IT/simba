<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA INVENTORY PLACEMENT REPORT CHECK\n";
echo "======================================\n\n";

$checks = [
    'Service laporan tersedia' =>
        class_exists(
            \App\Services\InventoryPlacementReportService::class
        ),

    'Controller laporan tersedia' =>
        class_exists(
            \App\Http\Controllers\ScopedInventoryReportController::class
        ),

    'Controller export tersedia' =>
        class_exists(
            \App\Http\Controllers\WorkshopAwareInventoryReportExportController::class
        ),

    'View laporan tersedia' =>
        View::exists('reports.index'),

    'View PDF tersedia' =>
        View::exists('reports.inventory-pdf'),

    'Route reports.index' =>
        Route::has('reports.index'),

    'Route reports.inventory' =>
        Route::has('reports.inventory'),

    'Route export PDF' =>
        Route::has('reports.export.pdf'),

    'Route export Excel' =>
        Route::has('reports.export.excel'),

    'item_assets.workshop_id' =>
        Schema::hasColumn(
            'item_assets',
            'workshop_id'
        ),

    'item_assets.storage_location_id' =>
        Schema::hasColumn(
            'item_assets',
            'storage_location_id'
        ),

    'movement.workshop_id' =>
        Schema::hasColumn(
            'item_stock_movements',
            'workshop_id'
        ),

    'movement.storage_location_id' =>
        Schema::hasColumn(
            'item_stock_movements',
            'storage_location_id'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 47).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$routeAction =
    Route::getRoutes()
        ->getByName('reports.index')
        ?->getActionName();

$routeValid =
    is_string($routeAction)
    && str_contains(
        $routeAction,
        'ScopedInventoryReportController'
    );

echo str_pad(
    'reports.index memakai controller baru',
    47
).
    ': '.
    ($routeValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $routeValid) {
    $failed = true;
}

$admin = DB::table('users')
    ->where('role', 'admin')
    ->first();

if ($admin !== null) {
    $user =
        \App\Models\User::query()
            ->withoutGlobalScopes()
            ->find($admin->id);

    $request = Request::create(
        '/reports',
        'GET'
    );

    $request->setUserResolver(
        fn () => $user
    );

    $service = app(
        \App\Services\InventoryPlacementReportService::class
    );

    try {
        $rows = $service->all($request);

        $withWorkshop = $rows->filter(
            fn (object $row): bool =>
                isset($row->report_workshop_code)
                && $row->report_workshop_code !== '-'
        )->count();

        echo "\nDATA LAPORAN\n";
        echo "------------\n";
        echo "Total baris       : ".
            $rows->count().
            "\n";
        echo "Memiliki jurusan  : ".
            $withWorkshop.
            "\n";

        foreach ($rows->take(15) as $row) {
            echo str_pad(
                $row->code.
                ' '.
                $row->name,
                38
            ).
                ': '.
                $row->report_workshop_code.
                ' / '.
                $row->report_location_name.
                ' / stok '.
                $row->report_stock.
                "\n";
        }

        if (
            $rows->isNotEmpty()
            && $withWorkshop === 0
        ) {
            $failed = true;
        }
    } catch (Throwable $exception) {
        echo "Query laporan GAGAL: ".
            $exception->getMessage().
            "\n";

        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'INVENTORY PLACEMENT REPORT BELUM VALID.'
            : 'INVENTORY PLACEMENT REPORT SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
