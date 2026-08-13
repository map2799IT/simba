<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\InventoryAccessService;
use App\Services\InventoryPlacementReportService;
use App\Support\SimbaRoleAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA WAKA SARPRAS ACCESS CHECK\n";
echo "===============================\n\n";

$access =
    app(
        SimbaRoleAccess::class
    );

$user =
    new User();

$user->forceFill([
    'id' => 999999,
    'name' => 'Checker Waka Sarpras',
    'role' => 'wakil_sarpras',
    'workshop_id' => null,
]);

echo "KOMPONEN\n";
echo "--------\n";

$components = [
    'SimbaRoleAccess' =>
        class_exists(
            SimbaRoleAccess::class
        ),

    'Middleware role' =>
        class_exists(
            \App\Http\Middleware\EnforceSimbaRoleAccess::class
        ),

    'Dashboard controller' =>
        class_exists(
            \App\Http\Controllers\BorrowerAwareDashboardController::class
        ),

    'Location controller' =>
        class_exists(
            \App\Http\Controllers\LocationInventoryController::class
        ),

    'Dashboard view' =>
        View::exists(
            'dashboard.waka-sarpras'
        ),

    'Sidebar view' =>
        View::exists(
            'layouts.sidebar'
        ),

    'InventoryAccessService' =>
        class_exists(
            InventoryAccessService::class
        ),

    'InventoryPlacementReportService' =>
        class_exists(
            InventoryPlacementReportService::class
        ),
];

foreach ($components as $label => $valid) {
    echo str_pad($label, 48).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nAKSES YANG WAJIB DIIZINKAN\n";
echo "--------------------------\n";

$allowed = [
    ['dashboard', 'GET', 'dashboard'],
    ['profile.edit', 'GET', 'profile'],
    ['reports.inventory', 'GET', 'reports/inventory'],
    ['reports.inventory.pdf', 'GET', 'reports/inventory/pdf'],
    ['reports.inventory.excel', 'GET', 'reports/inventory/excel'],
    ['reports.stock', 'GET', 'reports/stock'],
    ['reports.loans', 'GET', 'reports/loans'],
    ['reports.damages', 'GET', 'reports/damages'],
    ['reports.stock-movements', 'GET', 'reports/stock-movements'],
    ['locations.inventory.menu', 'GET', 'locations/inventory-menu'],
    ['locations.inventory.summary', 'GET', 'locations/1/inventory/summary'],
    ['locations.inventory.summary.print', 'GET', 'locations/1/inventory/summary/print'],
    ['locations.inventory.summary.pdf', 'GET', 'locations/1/inventory/summary/pdf'],
    ['locations.inventory.complete', 'GET', 'locations/1/inventory/complete'],
    ['locations.inventory.complete.pdf', 'GET', 'locations/1/inventory/complete/pdf'],
];

foreach ($allowed as [$routeName, $method, $path]) {
    $valid =
        $access->canRoute(
            $user,
            $routeName,
            $method,
            $path
        );

    echo str_pad(
        "{$method} {$routeName}",
        48
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nAKSES YANG WAJIB DIBLOKIR\n";
echo "-------------------------\n";

$blocked = [
    ['items.index', 'GET', 'items'],
    ['items.create', 'GET', 'items/create'],
    ['items.store', 'POST', 'items'],
    ['item-assets.index', 'GET', 'item-assets'],
    ['item-assets.qr-bulk.index', 'GET', 'item-assets/qr-bulk'],
    ['stock-receipts.index', 'GET', 'stock-receipts'],
    ['stock-receipts.store', 'POST', 'stock-receipts'],
    ['stock-issues.index', 'GET', 'stock-issues'],
    ['stock-movements.index', 'GET', 'stock-movements'],
    ['loans.index', 'GET', 'loans'],
    ['loans.approve', 'POST', 'loans/1/approve'],
    ['damage-reports.index', 'GET', 'damage-reports'],
    ['locations.index', 'GET', 'locations'],
    ['locations.create', 'GET', 'locations/create'],
    ['locations.store', 'POST', 'locations'],
    ['item-categories.index', 'GET', 'item-categories'],
    ['units.index', 'GET', 'units'],
    ['workshops.index', 'GET', 'workshops'],
    ['admin.users.index', 'GET', 'admin/users'],
    ['admin.audit-logs.index', 'GET', 'admin/audit-logs'],
    ['reports.inventory', 'POST', 'reports/inventory'],
];

foreach ($blocked as [$routeName, $method, $path]) {
    $denied =
        ! $access->canRoute(
            $user,
            $routeName,
            $method,
            $path
        );

    echo str_pad(
        "{$method} {$routeName}",
        48
    ).
        ': '.
        ($denied ? 'OK BLOKIR' : 'GAGAL TERBUKA').
        PHP_EOL;

    if (! $denied) {
        $failed = true;
    }
}

echo "\nROUTE WAJIB\n";
echo "-----------\n";

$routeNames = [
    'dashboard',
    'reports.inventory',
    'reports.stock',
    'reports.loans',
    'reports.damages',
    'reports.stock-movements',
    'locations.inventory.menu',
    'locations.inventory.summary',
    'locations.inventory.summary.print',
    'locations.inventory.summary.pdf',
    'locations.inventory.complete',
    'locations.inventory.complete.pdf',
];

foreach ($routeNames as $name) {
    $route =
        Route::getRoutes()
            ->getByName($name);

    echo str_pad($name, 48).
        ': '.
        ($route ? 'OK' : 'GAGAL').
        (
            $route
                ? ' '.$route->getActionName()
                : ''
        ).
        PHP_EOL;

    if (! $route) {
        $failed = true;
    }
}

echo "\nSCOPE GLOBAL LAPORAN\n";
echo "--------------------\n";

$inventoryAccess =
    app(
        InventoryAccessService::class
    );

$scopeValid =
    ! $inventoryAccess->isRestricted(
        $user
    );

echo str_pad(
    'InventoryAccessService tidak restricted',
    48
).
    ': '.
    ($scopeValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $scopeValid) {
    $failed = true;
}

$request =
    Request::create(
        '/reports/inventory',
        'GET'
    );

$request->setUserResolver(
    static fn () =>
        $user
);

$placement =
    app(
        InventoryPlacementReportService::class
    );

$placementValid =
    ! $placement->isWorkshopRestricted(
        $request
    )
    && $placement->effectiveWorkshopId(
        $request
    ) === null;

echo str_pad(
    'Placement report global seluruh jurusan',
    48
).
    ': '.
    ($placementValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $placementValid) {
    $failed = true;
}

echo "\nAKUN DATABASE\n";
echo "-------------\n";

if (
    Schema::hasTable('users')
    && Schema::hasColumn(
        'users',
        'role'
    )
) {
    $accounts =
        User::query()
            ->withoutGlobalScopes()
            ->where(
                'role',
                'wakil_sarpras'
            )
            ->get();

    echo str_pad(
        'Jumlah akun wakil_sarpras',
        48
    ).
        ': '.
        $accounts->count().
        PHP_EOL;

    if ($accounts->isEmpty()) {
        echo "WARN: jalankan tools/create-waka-sarpras-account.php.\n";
        $warnings++;
    }

    foreach ($accounts as $account) {
        $valid =
            $account->workshop_id
            === null;

        echo str_pad(
            $account->username
            ?? ('user-'.$account->id),
            48
        ).
            ': '.
            ($valid ? 'OK global' : 'GAGAL workshop_id harus NULL').
            PHP_EOL;

        if (! $valid) {
            $failed = true;
        }
    }
}

echo "\nSIDEBAR\n";
echo "-------\n";

$sidebarFile =
    $root.
    '/resources/views/layouts/sidebar.blade.php';

$sidebar =
    is_file($sidebarFile)
        ? file_get_contents(
            $sidebarFile
        )
        : false;

$sidebarChecks = [
    'Label Wakil Sarana dan Prasarana' =>
        is_string($sidebar)
        && str_contains(
            $sidebar,
            "'wakil_sarpras'"
        ),

    'Flag isWakaSarpras' =>
        is_string($sidebar)
        && str_contains(
            $sidebar,
            '$isWakaSarpras'
        ),

    'Menu lokasi read/print' =>
        is_string($sidebar)
        && str_contains(
            $sidebar,
            'locations.inventory.menu'
        ),

    'Badge Lihat/Print' =>
        is_string($sidebar)
        && str_contains(
            $sidebar,
            'Lihat/Print'
        ),

    'Laporan Stok untuk Waka' =>
        is_string($sidebar)
        && str_contains(
            $sidebar,
            "'show' => \$canViewStock || \$isWakaSarpras"
        ),

    'Transaksi stok tidak dibuka untuk Waka' =>
        is_string($sidebar)
        && ! str_contains(
            $sidebar,
            "'route' => 'stock-receipts.index',\n                    'label' => 'Barang Masuk',\n                    'icon' => 'bi-box-arrow-in-down',\n                    'active' => ['stock-receipts.*'],\n                    'show' => \$canViewStock || \$isWakaSarpras"
        ),
];

foreach ($sidebarChecks as $label => $valid) {
    echo str_pad($label, 48).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nSTATUS\n";
echo "------\n";
echo 'FAIL: '.
    ($failed ? 'YA' : 'TIDAK').
    PHP_EOL;
echo "WARN: {$warnings}\n";

echo "\n".
    (
        $failed
            ? 'WAKA SARPRAS ACCESS BELUM VALID.'
            : (
                $warnings > 0
                    ? 'KODE WAKA SARPRAS VALID, TETAPI AKUN BELUM DIBUAT/DIPERIKSA.'
                    : 'WAKA SARPRAS ACCESS SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
