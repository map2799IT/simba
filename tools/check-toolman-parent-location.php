<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SimbaRoleAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA TOOLMAN PARENT LOCATION CHECK\n";
echo "===================================\n\n";

$files = [
    'StorageLocationController' =>
        $root.
        '/app/Http/Controllers/StorageLocationController.php',

    'StorageLocation model' =>
        $root.
        '/app/Models/StorageLocation.php',

    'SimbaRoleAccess' =>
        $root.
        '/app/Support/SimbaRoleAccess.php',

    'Location form' =>
        $root.
        '/resources/views/locations/_form.blade.php',

    'Location index' =>
        $root.
        '/resources/views/locations/index.blade.php',

    'Location create' =>
        $root.
        '/resources/views/locations/create.blade.php',

    'Location edit' =>
        $root.
        '/resources/views/locations/edit.blade.php',

    'Location show' =>
        $root.
        '/resources/views/locations/show.blade.php',
];

foreach ($files as $label => $file) {
    $valid =
        is_file($file)
        && filesize($file) > 0;

    echo str_pad($label, 38).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$routeChecks = [
    'Route locations.index' =>
        Route::has(
            'locations.index'
        ),

    'Route locations.create' =>
        Route::has(
            'locations.create'
        ),

    'Route locations.store' =>
        Route::has(
            'locations.store'
        ),

    'Route locations.show' =>
        Route::has(
            'locations.show'
        ),

    'Route locations.edit' =>
        Route::has(
            'locations.edit'
        ),

    'Route locations.update' =>
        Route::has(
            'locations.update'
        ),

    'Route locations.destroy' =>
        Route::has(
            'locations.destroy'
        ),
];

foreach (
    $routeChecks
    as $label => $valid
) {
    echo str_pad($label, 38).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nDATABASE\n";
echo "--------\n";

$tableValid =
    Schema::hasTable(
        'storage_locations'
    );

echo str_pad(
    'Table storage_locations',
    38
).
    ': '.
    (
        $tableValid
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $tableValid) {
    $failed = true;
} else {
    foreach (
        [
            'workshop_id',
            'parent_id',
            'code',
            'name',
            'type',
            'is_active',
        ]
        as $column
    ) {
        $valid =
            Schema::hasColumn(
                'storage_locations',
                $column
            );

        echo str_pad(
            'Column '.$column,
            38
        ).
            ': '.
            (
                $valid
                    ? 'OK'
                    : 'GAGAL'
            ).
            PHP_EOL;

        if (! $valid) {
            $failed = true;
        }
    }
}

echo "\nROLE ACCESS\n";
echo "-----------\n";

$access =
    app(
        SimbaRoleAccess::class
    );

$toolman =
    new User();

$toolman->forceFill([
    'role' => 'toolman',
]);

foreach (
    [
        'locations.index',
        'locations.create',
        'locations.store',
        'locations.show',
        'locations.edit',
        'locations.update',
        'locations.destroy',
    ]
    as $routeName
) {
    $valid =
        $access->canRoute(
            $toolman,
            $routeName,
            in_array(
                $routeName,
                [
                    'locations.store',
                    'locations.update',
                    'locations.destroy',
                ],
                true
            )
                ? 'POST'
                : 'GET'
        );

    echo str_pad(
        'Toolman '.$routeName,
        38
    ).
        ': '.
        (
            $valid
                ? 'DIIZINKAN'
                : 'DITOLAK'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'AKSES LOKASI INDUK TOOLMAN BELUM SIAP.'
            : 'TOOLMAN DAPAT MEMBUAT LOKASI INDUK DAN TURUNAN.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
