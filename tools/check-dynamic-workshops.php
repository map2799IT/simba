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
    'Workshop model' =>
        class_exists(
            \App\Models\Workshop::class
        ),

    'WorkshopController' =>
        class_exists(
            \App\Http\Controllers\WorkshopController::class
        ),

    'WorkshopDirectoryService' =>
        class_exists(
            \App\Services\WorkshopDirectoryService::class
        ),

    'Tabel workshops' =>
        Schema::hasTable(
            'workshops'
        ),

    'Kolom code' =>
        Schema::hasColumn(
            'workshops',
            'code'
        ),

    'Kolom name' =>
        Schema::hasColumn(
            'workshops',
            'name'
        ),

    'Kolom is_active' =>
        Schema::hasColumn(
            'workshops',
            'is_active'
        ),

    'View workshops.index' =>
        View::exists(
            'workshops.index'
        ),

    'View workshops.create' =>
        View::exists(
            'workshops.create'
        ),

    'View workshops.edit' =>
        View::exists(
            'workshops.edit'
        ),

    'Route workshops.index' =>
        Route::has(
            'workshops.index'
        ),

    'Route workshops.create' =>
        Route::has(
            'workshops.create'
        ),

    'Route workshops.store' =>
        Route::has(
            'workshops.store'
        ),

    'Route workshops.edit' =>
        Route::has(
            'workshops.edit'
        ),

    'Route workshops.update' =>
        Route::has(
            'workshops.update'
        ),

    'Route workshops.destroy' =>
        Route::has(
            'workshops.destroy'
        ),

    'Route toggle status' =>
        Route::has(
            'workshops.toggle-status'
        ),
];

$failed = false;

echo "SIMBA DYNAMIC WORKSHOP CHECK\n";
echo "============================\n\n";

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

foreach (
    [
        'workshops.index',
        'workshops.store',
        'workshops.update',
        'workshops.destroy',
        'workshops.toggle-status',
    ]
    as $name
) {
    $route = Route::getRoutes()
        ->getByName($name);

    echo str_pad($name, 38).
        ': '.
        (
            $route?->getActionName()
            ?? 'TIDAK ADA'
        ).
        PHP_EOL;
}

echo "\n".
    (
        $failed
            ? 'MODUL JURUSAN DINAMIS BELUM VALID.'
            : 'MODUL JURUSAN DINAMIS SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
