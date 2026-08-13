<?php

declare(strict_types=1);

use App\Http\Controllers\LocationInventoryController;
use App\Models\StorageLocation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA LOCATION INVENTORY TWO MODES CHECK\n";
echo "========================================\n\n";

$checks = [
    'Controller tersedia' =>
        class_exists(
            LocationInventoryController::class
        ),

    'Method summary' =>
        method_exists(
            LocationInventoryController::class,
            'summary'
        ),

    'Method complete' =>
        method_exists(
            LocationInventoryController::class,
            'complete'
        ),

    'Method pdf' =>
        method_exists(
            LocationInventoryController::class,
            'pdf'
        ),

    'Route ringkasan' =>
        Route::has(
            'locations.inventory.summary'
        ),

    'Route lengkap' =>
        Route::has(
            'locations.inventory.complete'
        ),

    'Route PDF lengkap' =>
        Route::has(
            'locations.inventory.complete.pdf'
        ),

    'View ringkasan' =>
        View::exists(
            'locations.inventory-summary'
        ),

    'View lengkap' =>
        View::exists(
            'locations.inventory-complete'
        ),

    'Partial tombol' =>
        View::exists(
            'locations._inventory-action-buttons'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 38).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

foreach (
    [
        'index',
        'show',
    ]
    as $view
) {
    $file =
        $root.
        '/resources/views/locations/'.
        $view.
        '.blade.php';

    $valid =
        is_file($file)
        && str_contains(
            (string)
            file_get_contents($file),
            'locations._inventory-action-buttons'
        );

    echo str_pad(
        "Tombol view {$view}",
        38
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$location =
    StorageLocation::query()
        ->withoutGlobalScopes()
        ->first();

if ($location !== null) {
    try {
        $summaryUrl =
            route(
                'locations.inventory.summary',
                [
                    'storageLocation' =>
                        $location
                            ->getRouteKey(),

                    'include_children' =>
                        1,
                ]
            );

        $completeUrl =
            route(
                'locations.inventory.complete',
                [
                    'storageLocation' =>
                        $location
                            ->getRouteKey(),

                    'include_children' =>
                        1,
                ]
            );

        echo str_pad(
            'Generate URL ringkasan',
            38
        ).
            ': OK'.
            PHP_EOL;

        echo str_pad(
            'Generate URL lengkap',
            38
        ).
            ': OK'.
            PHP_EOL;

        echo "Ringkasan: {$summaryUrl}\n";
        echo "Lengkap  : {$completeUrl}\n";
    } catch (Throwable $exception) {
        echo str_pad(
            'Generate URL',
            38
        ).
            ': GAGAL'.
            PHP_EOL;

        echo $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
} else {
    echo str_pad(
        'Generate URL',
        38
    ).
        ": DILEWATI - lokasi masih kosong\n";
}

echo "\n".
    (
        $failed
            ? 'DUA MODE INVENTARIS LOKASI BELUM VALID.'
            : 'DUA MODE INVENTARIS LOKASI SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
