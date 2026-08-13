<?php

declare(strict_types=1);

use App\Http\Controllers\ItemCategoryController;
use App\Http\Requests\StoreItemCategoryRequest;
use App\Http\Requests\UpdateItemCategoryRequest;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA CATEGORY FORM REQUEST ROUTE CHECK\n";
echo "=======================================\n\n";

$routeFile =
    $root.
    '/routes/blade-route-aliases.php';

$checks = [
    'Route alias tersedia' =>
        is_file($routeFile),

    'Controller kategori tersedia' =>
        class_exists(
            ItemCategoryController::class
        ),

    'Store FormRequest tersedia' =>
        class_exists(
            StoreItemCategoryRequest::class
        ),

    'Update FormRequest tersedia' =>
        class_exists(
            UpdateItemCategoryRequest::class
        ),

    'Route categories.store' =>
        Route::has(
            'categories.store'
        ),

    'Route categories.update' =>
        Route::has(
            'categories.update'
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

$contents =
    is_file($routeFile)
        ? file_get_contents(
            $routeFile
        )
        : '';

$manualRequestExists =
    is_string($contents)
    && (
        str_contains(
            $contents,
            "'request' => request()"
        )
        || preg_match(
            '/[\'"]request[\'"]\s*=>\s*\R\s*request\(\)/',
            $contents
        ) === 1
    );

echo str_pad(
    'Request manual sudah dihapus',
    42
).
    ': '.
    (
        ! $manualRequestExists
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if ($manualRequestExists) {
    $failed = true;
}

try {
    $storeMethod =
        new ReflectionMethod(
            ItemCategoryController::class,
            'store'
        );

    $storeParameters =
        $storeMethod->getParameters();

    $storeType =
        $storeParameters[0]
            ->getType();

    $storeValid =
        $storeType instanceof ReflectionNamedType
        && $storeType->getName()
            === StoreItemCategoryRequest::class;

    echo str_pad(
        'Store memakai StoreItemCategoryRequest',
        42
    ).
        ': '.
        ($storeValid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $storeValid) {
        $failed = true;
    }
} catch (Throwable $exception) {
    echo str_pad(
        'Refleksi method store',
        42
    ).
        ': GAGAL'.
        PHP_EOL;

    echo $exception->getMessage().
        PHP_EOL;

    $failed = true;
}

try {
    $updateMethod =
        new ReflectionMethod(
            ItemCategoryController::class,
            'update'
        );

    $updateParameters =
        $updateMethod->getParameters();

    $updateType =
        $updateParameters[0]
            ->getType();

    $updateValid =
        $updateType instanceof ReflectionNamedType
        && $updateType->getName()
            === UpdateItemCategoryRequest::class;

    echo str_pad(
        'Update memakai UpdateItemCategoryRequest',
        42
    ).
        ': '.
        ($updateValid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $updateValid) {
        $failed = true;
    }
} catch (Throwable $exception) {
    echo str_pad(
        'Refleksi method update',
        42
    ).
        ': GAGAL'.
        PHP_EOL;

    echo $exception->getMessage().
        PHP_EOL;

    $failed = true;
}

$storeRoute =
    Route::getRoutes()
        ->getByName(
            'categories.store'
        );

$storeMethods =
    $storeRoute?->methods()
    ?? [];

$storeRouteValid =
    in_array(
        'POST',
        $storeMethods,
        true
    );

echo str_pad(
    'Method route store POST',
    42
).
    ': '.
    ($storeRouteValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $storeRouteValid) {
    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'CATEGORY FORM REQUEST ROUTE BELUM VALID.'
            : 'CATEGORY FORM REQUEST ROUTE SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
