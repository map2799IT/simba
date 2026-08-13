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
    'DashboardController' =>
        class_exists(
            \App\Http\Controllers\DashboardController::class
        ),

    'DashboardController@index' =>
        method_exists(
            \App\Http\Controllers\DashboardController::class,
            'index'
        ),

    'View dashboard' =>
        View::exists('dashboard'),

    'Route dashboard' =>
        Route::has('dashboard'),

    'Tabel items' =>
        Schema::hasTable('items'),

    'Tabel item_assets' =>
        Schema::hasTable(
            'item_assets'
        ),

    'Tabel loans' =>
        Schema::hasTable('loans'),

    'Tabel damage_reports' =>
        Schema::hasTable(
            'damage_reports'
        ),

    'users.workshop_id' =>
        Schema::hasColumn(
            'users',
            'workshop_id'
        ),
];

$failed = false;

echo "SIMBA ROLE DASHBOARD CHECK\n";
echo "==========================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 34).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

$route = Route::getRoutes()
    ->getByName('dashboard');

echo "\nDashboard action:\n";
echo ($route?->getActionName()
    ?? 'TIDAK ADA').
    PHP_EOL;

if (
    $route !== null
    && $route->getActionName()
        !==
        'App\\Http\\Controllers\\DashboardController@index'
) {
    echo "PERINGATAN: route masih memakai action lain.\n";
}

echo "\n".
    (
        $failed
            ? 'PEMERIKSAAN GAGAL.'
            : 'DASHBOARD SIAP DIGUNAKAN.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
