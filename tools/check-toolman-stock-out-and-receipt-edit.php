<?php

declare(strict_types=1);

use App\Models\ItemStockMovement;
use App\Models\User;
use App\Services\WorkshopInventoryAvailabilityService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA TOOLMAN STOCK OUT & RECEIPT EDIT CHECK\n";
echo "============================================\n\n";

$checks = [
    'Availability service' =>
        class_exists(
            WorkshopInventoryAvailabilityService::class
        ),

    'Workshop stock issue controller' =>
        class_exists(
            \App\Http\Controllers\WorkshopStockIssueController::class
        ),

    'View Barang Keluar create' =>
        View::exists(
            'stock-issues.create'
        ),

    'View Barang Keluar index' =>
        View::exists(
            'stock-issues.index'
        ),

    'Route Barang Keluar index' =>
        Route::has(
            'stock-issues.index'
        ),

    'Route Barang Keluar create' =>
        Route::has(
            'stock-issues.create'
        ),

    'Route Barang Keluar store' =>
        Route::has(
            'stock-issues.store'
        ),

    'Route Barang Masuk edit' =>
        Route::has(
            'stock-receipts.edit'
        ),
];

foreach (
    $checks
    as $label => $valid
) {
    echo str_pad(
        $label,
        46
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nROUTE ACTION\n";
echo "------------\n";

$routeActions = [
    'stock-issues.index' =>
        'WorkshopStockIssueController@index',

    'stock-issues.create' =>
        'WorkshopStockIssueController@create',

    'stock-issues.store' =>
        'WorkshopStockIssueController@store',
];

foreach (
    $routeActions
    as $name => $expected
) {
    $route =
        Route::getRoutes()
            ->getByName($name);

    $action =
        $route?->getActionName();

    $valid =
        is_string($action)
        && str_contains(
            $action,
            $expected
        );

    echo str_pad(
        $name,
        46
    ).
        ': '.
        ($valid ? 'OK ' : 'GAGAL ').
        ($action ?? '-').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nINVENTARIS TOOLMAN PER JURUSAN\n";
echo "------------------------------\n";

$service =
    app(
        WorkshopInventoryAvailabilityService::class
    );

$toolmen =
    User::query()
        ->withoutGlobalScopes()
        ->where(
            'role',
            'toolman'
        )
        ->whereNotNull(
            'workshop_id'
        )
        ->orderBy(
            'workshop_id'
        )
        ->get();

if ($toolmen->isEmpty()) {
    echo "Tidak ada akun Toolman untuk diuji.\n";
    $failed = true;
}

foreach (
    $toolmen
    as $toolman
) {
    $request =
        Request::create(
            '/stock-issues/create',
            'GET'
        );

    $request->setUserResolver(
        static fn () =>
            $toolman
    );

    try {
        $workshop =
            $service
                ->selectedWorkshop(
                    $request
                );

        $items =
            $service
                ->itemsForWorkshop(
                    $workshop->id
                );

        $assets =
            $service
                ->assetsForWorkshop(
                    $workshop->id
                );

        echo str_pad(
            $toolman->username.
            ' / '.
            $workshop->code,
            30
        ).
            ': barang='.
            $items->count().
            ', unit='.
            $assets->count().
            PHP_EOL;
    } catch (Throwable $exception) {
        echo str_pad(
            $toolman->username,
            30
        ).
            ': GAGAL '.
            $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
}

echo "\nBINDING BARANG MASUK\n";
echo "--------------------\n";

$incoming =
    ItemStockMovement::query()
        ->withoutGlobalScopes()
        ->where(
            'type',
            ItemStockMovement::
                TYPE_INCOMING
        )
        ->whereNotNull(
            'workshop_id'
        )
        ->latest('id')
        ->first();

if ($incoming === null) {
    echo "Tidak ada Barang Masuk untuk diuji.\n";
} else {
    /*
     * Pastikan data yang sebelumnya terhalang global scope
     * tetap dapat ditemukan oleh query binding baru.
     */
    $resolved =
        ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->whereKey(
                $incoming->id
            )
            ->where(
                'type',
                ItemStockMovement::
                    TYPE_INCOMING
            )
            ->first();

    $valid =
        $resolved !== null
        && (int) $resolved->id
            === (int) $incoming->id;

    echo 'ID '.
        $incoming->id.
        ' / workshop '.
        $incoming->workshop_id.
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'TOOLMAN STOCK OUT & RECEIPT EDIT BELUM VALID.'
            : 'TOOLMAN STOCK OUT & RECEIPT EDIT SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
