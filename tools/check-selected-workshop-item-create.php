<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA SELECTED WORKSHOP ITEM CREATE CHECK\n";
echo "=========================================\n\n";

$controllerFile =
    $root.
    '/app/Http/Controllers/ItemController.php';

$viewFile =
    $root.
    '/resources/views/items/create.blade.php';

$checks = [
    'ItemController tersedia' =>
        is_file($controllerFile),

    'Form item create tersedia' =>
        is_file($viewFile),

    'Route items.create' =>
        Route::has(
            'items.create'
        ),

    'Route items.store' =>
        Route::has(
            'items.store'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 42).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$controller =
    is_file($controllerFile)
        ? file_get_contents(
            $controllerFile
        )
        : '';

$view =
    is_file($viewFile)
        ? file_get_contents(
            $viewFile
        )
        : '';

$controllerSendsVariable =
    is_string($controller)
    && str_contains(
        $controller,
        "'selectedWorkshopId'"
    )
    && str_contains(
        $controller,
        'resolveMasterWorkshopId'
    );

echo str_pad(
    'Controller mengirim selectedWorkshopId',
    42
).
    ': '.
    (
        $controllerSendsVariable
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $controllerSendsVariable) {
    $failed = true;
}

$viewHasFallback =
    is_string($view)
    && str_contains(
        $view,
        '$selectedWorkshopId ='
    )
    && str_contains(
        $view,
        '$selectedWorkshopId'
    )
    && str_contains(
        $view,
        "auth()->user()?->workshop_id"
    );

echo str_pad(
    'Form memiliki nilai cadangan',
    42
).
    ': '.
    (
        $viewHasFallback
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $viewHasFallback) {
    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'SELECTED WORKSHOP ITEM CREATE BELUM VALID.'
            : 'SELECTED WORKSHOP ITEM CREATE SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
