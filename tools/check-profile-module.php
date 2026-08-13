<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$checks = [
    'ProfileController' =>
        class_exists(
            \App\Http\Controllers\ProfileController::class
        ),

    'Method edit' =>
        method_exists(
            \App\Http\Controllers\ProfileController::class,
            'edit'
        ),

    'Method update' =>
        method_exists(
            \App\Http\Controllers\ProfileController::class,
            'update'
        ),

    'Method updatePassword' =>
        method_exists(
            \App\Http\Controllers\ProfileController::class,
            'updatePassword'
        ),

    'View profile.edit' =>
        View::exists(
            'profile.edit'
        ),

    'Route profile.edit' =>
        Route::has(
            'profile.edit'
        ),

    'Route profile.update' =>
        Route::has(
            'profile.update'
        ),

    'Route password update' =>
        Route::has(
            'profile.password.update'
        ),
];

$failed = false;

echo "SIMBA PROFILE MODULE CHECK\n";
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

echo "\nROUTE ACTIONS\n";
echo "-------------\n";

foreach ([
    'profile.edit',
    'profile.update',
    'profile.password.update',
] as $name) {
    $route = Route::getRoutes()
        ->getByName($name);

    echo str_pad($name, 34).
        ': '.
        ($route?->getActionName()
            ?? 'TIDAK ADA').
        PHP_EOL;
}

echo "\n".
    (
        $failed
            ? 'MODUL PROFIL BELUM VALID.'
            : 'MODUL PROFIL SIAP DIGUNAKAN.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
