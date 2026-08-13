<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SIMBA Safe Routes
|--------------------------------------------------------------------------
|
| File ini menggantikan routes/web.php lama.
|
| Prinsip:
| 1. Route hanya memakai controller method jika class dan method tersedia.
| 2. Route yang fiturnya belum tersedia menggunakan fallback Closure,
|    sehingga artisan route:list dan routes:health tidak error.
| 3. Route statis seperti /create selalu didaftarkan sebelum parameter
|    dinamis seperti /{stockReceipt}.
| 4. Parameter ID diberi whereNumber agar "create" tidak dianggap sebagai ID.
| 5. File route tambahan lama tidak di-require untuk mencegah route ganda.
|
*/

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

$resolveController = static function (
    array $candidates
): ?string {
    foreach ($candidates as $candidate) {
        if (class_exists($candidate)) {
            return $candidate;
        }
    }

    return null;
};

$safeRoute = static function (
    array|string $methods,
    string $uri,
    string $name,
    ?string $controller,
    string $controllerMethod,
    ?string $fallbackRoute = null,
    array $whereNumbers = []
) {
    $methods = is_array($methods)
        ? $methods
        : [$methods];

    $implemented = $controller !== null
        && method_exists(
            $controller,
            $controllerMethod
        );

    $action = $implemented
        ? [$controller, $controllerMethod]
        : static function () use (
            $name,
            $controller,
            $controllerMethod,
            $fallbackRoute
        ) {
            $target = $controller
                ? $controller.'@'.$controllerMethod
                : 'controller yang belum tersedia';

            $message =
                'Fitur "'.$name.'" belum aktif karena '.
                $target.' belum tersedia.';

            if (request()->expectsJson()) {
                return response()->json(
                    [
                        'message' => $message,
                    ],
                    501
                );
            }

            if (
                request()->isMethod('get')
                || request()->isMethod('head')
            ) {
                if (
                    $fallbackRoute
                    && $fallbackRoute !== $name
                    && Route::has($fallbackRoute)
                ) {
                    return redirect()
                        ->route($fallbackRoute)
                        ->with('warning', $message);
                }

                if (
                    $name !== 'dashboard'
                    && Route::has('dashboard')
                ) {
                    return redirect()
                        ->route('dashboard')
                        ->with('warning', $message);
                }

                return response(
                    '<!doctype html>'.
                    '<html lang="id"><head>'.
                    '<meta charset="utf-8">'.
                    '<meta name="viewport" content="width=device-width,initial-scale=1">'.
                    '<title>Fitur Belum Tersedia</title>'.
                    '<style>'.
                    'body{font-family:Arial,sans-serif;background:#f3f4f6;'.
                    'color:#111827;margin:0;padding:40px}'.
                    '.card{max-width:700px;margin:auto;background:white;'.
                    'border-radius:12px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.08)}'.
                    'h1{font-size:24px;margin-top:0}'.
                    'p{line-height:1.6}'.
                    'a{display:inline-block;margin-top:10px;color:#2563eb}'.
                    '</style></head><body><div class="card">'.
                    '<h1>Fitur belum tersedia</h1>'.
                    '<p>'.e($message).'</p>'.
                    '<a href="'.url('/').'">Kembali ke aplikasi</a>'.
                    '</div></body></html>',
                    501
                );
            }

            return back()->with(
                'warning',
                $message
            );
        };

    $route = Route::match(
        $methods,
        $uri,
        $action
    )->name($name);

    foreach ($whereNumbers as $parameter) {
        $route->whereNumber($parameter);
    }

    return $route;
};

/*
|--------------------------------------------------------------------------
| Controller Resolver
|--------------------------------------------------------------------------
*/

$controllers = [
    'dashboard' => $resolveController([
        \App\Http\Controllers\DashboardController::class,
    ]),

    'profile' => $resolveController([
        \App\Http\Controllers\ProfileController::class,
    ]),

    'users' => $resolveController([
        \App\Http\Controllers\Admin\UserController::class,
        \App\Http\Controllers\UserController::class,
    ]),

    'access' => $resolveController([
        \App\Http\Controllers\Admin\AccessController::class,
    ]),

    'auditLogs' => $resolveController([
        \App\Http\Controllers\Admin\AuditLogController::class,
    ]),

    'items' => $resolveController([
        \App\Http\Controllers\ItemController::class,
    ]),

    'itemLabels' => $resolveController([
        \App\Http\Controllers\ItemLabelController::class,
    ]),

    'itemCategories' => $resolveController([
        \App\Http\Controllers\ItemCategoryController::class,
    ]),

    'units' => $resolveController([
        \App\Http\Controllers\UnitController::class,
    ]),

    'workshops' => $resolveController([
        \App\Http\Controllers\WorkshopController::class,
    ]),

    'locations' => $resolveController([
        \App\Http\Controllers\StorageLocationController::class,
        \App\Http\Controllers\LocationController::class,
    ]),

    'stockReceipts' => $resolveController([
        \App\Http\Controllers\StockReceiptController::class,
    ]),

    'stockIssues' => $resolveController([
        \App\Http\Controllers\StockIssueController::class,
    ]),

    'stockMovements' => $resolveController([
        \App\Http\Controllers\StockMovementController::class,
    ]),

    'loans' => $resolveController([
        \App\Http\Controllers\LoanController::class,
    ]),

    'loanReturns' => $resolveController([
        \App\Http\Controllers\LoanReturnController::class,
    ]),

    'damageReports' => $resolveController([
        \App\Http\Controllers\DamageReportController::class,
    ]),

    'reports' => $resolveController([
        \App\Http\Controllers\ReportController::class,
    ]),

    'inventoryExports' => $resolveController([
        \App\Http\Controllers\InventoryReportExportController::class,
    ]),

    'itemAssets' => $resolveController([
        \App\Http\Controllers\ItemAssetController::class,
    ]),
];

/*
|--------------------------------------------------------------------------
| Halaman Awal dan Dashboard
|--------------------------------------------------------------------------
*/

Route::get(
    '/',
    static function () {
        if (auth()->check()) {
            return redirect()->route(
                'dashboard'
            );
        }

        if (Route::has('login')) {
            return redirect()->route(
                'login'
            );
        }

        return response(
            'SIMBA siap. Route login belum tersedia.',
            200
        );
    }
)->name('home');

Route::middleware('auth')->group(
    static function () use (
        $safeRoute,
        $controllers
    ): void {
        $safeRoute(
            'get',
            '/dashboard',
            'dashboard',
            $controllers['dashboard'],
            'index'
        );

        /*
        |--------------------------------------------------------------------------
        | Profil
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/profile',
            'profile.edit',
            $controllers['profile'],
            'edit',
            'dashboard'
        );

        $safeRoute(
            'patch',
            '/profile',
            'profile.update',
            $controllers['profile'],
            'update',
            'profile.edit'
        );

        $safeRoute(
            'delete',
            '/profile',
            'profile.destroy',
            $controllers['profile'],
            'destroy',
            'profile.edit'
        );

        /*
        |--------------------------------------------------------------------------
        | Data Alat dan Bahan
        |--------------------------------------------------------------------------
        |
        | Route statis harus berada sebelum /items/{item}.
        |
        */

        $safeRoute(
            'get',
            '/items',
            'items.index',
            $controllers['items'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/items/create',
            'items.create',
            $controllers['items'],
            'create',
            'items.index'
        );

        $safeRoute(
            'post',
            '/items',
            'items.store',
            $controllers['items'],
            'store',
            'items.index'
        );

        $safeRoute(
            'get',
            '/items/bulk/create',
            'items.bulk.create',
            $controllers['items'],
            'bulkCreate',
            'items.index'
        );

        $safeRoute(
            'post',
            '/items/bulk',
            'items.bulk.store',
            $controllers['items'],
            'bulkStore',
            'items.index'
        );

        /*
         * Nama route utama mengikuti pemanggilan view:
         * items.label.bulk
         */
        $safeRoute(
            'get',
            '/items/labels/bulk',
            'items.label.bulk',
            $controllers['itemLabels'],
            'bulk',
            'items.index'
        );

        /*
         * Alias kompatibilitas untuk kode lama yang masih memakai:
         * items.labels.bulk
         */
        $safeRoute(
            'get',
            '/items/labels/bulk/legacy',
            'items.labels.bulk',
            $controllers['itemLabels'],
            'bulk',
            'items.index'
        );

        $safeRoute(
            'get',
            '/items/{item}/history',
            'items.history',
            $controllers['items'],
            'history',
            'items.index',
            ['item']
        );

        /*
         * Nama route utama yang dipakai items/show.blade.php:
         * items.label.single
         */
        $safeRoute(
            'get',
            '/items/{item}/label',
            'items.label.single',
            $controllers['itemLabels'],
            'single',
            'items.index',
            ['item']
        );

        /*
         * Alias kompatibilitas untuk kode lama yang masih memakai:
         * items.labels.single
         */
        $safeRoute(
            'get',
            '/items/{item}/labels/single',
            'items.labels.single',
            $controllers['itemLabels'],
            'single',
            'items.index',
            ['item']
        );

        $safeRoute(
            'get',
            '/items/{item}/edit',
            'items.edit',
            $controllers['items'],
            'edit',
            'items.index',
            ['item']
        );

        $safeRoute(
            'put',
            '/items/{item}',
            'items.update',
            $controllers['items'],
            'update',
            'items.index',
            ['item']
        );

        $safeRoute(
            'delete',
            '/items/{item}',
            'items.destroy',
            $controllers['items'],
            'destroy',
            'items.index',
            ['item']
        );

        $safeRoute(
            'post',
            '/items/bulk/toggle-status',
            'items.bulk.toggle-status',
            $controllers['items'],
            'bulkToggleStatus',
            'items.index'
        );

        $safeRoute(
            'get',
            '/items/{item}',
            'items.show',
            $controllers['items'],
            'show',
            'items.index',
            ['item']
        );

        /*
        |--------------------------------------------------------------------------
        | Unit Alat Fisik dan QR Code
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/item-assets',
            'item-assets.index',
            $controllers['itemAssets'],
            'index',
            'items.index'
        );

        $safeRoute(
            'get',
            '/item-assets/{itemAsset}/edit',
            'item-assets.edit',
            $controllers['itemAssets'],
            'edit',
            'item-assets.index',
            ['itemAsset']
        );

        $safeRoute(
            'put',
            '/item-assets/{itemAsset}',
            'item-assets.update',
            $controllers['itemAssets'],
            'update',
            'item-assets.index',
            ['itemAsset']
        );

        $safeRoute(
            'get',
            '/item-assets/{itemAsset}/label',
            'item-assets.label',
            $controllers['itemAssets'],
            'label',
            'item-assets.index',
            ['itemAsset']
        );

        $safeRoute(
            'get',
            '/item-assets/{itemAsset}',
            'item-assets.show',
            $controllers['itemAssets'],
            'show',
            'item-assets.index',
            ['itemAsset']
        );

        /*
        |--------------------------------------------------------------------------
        | Master Kategori
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/item-categories',
            'item-categories.index',
            $controllers['itemCategories'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/item-categories/create',
            'item-categories.create',
            $controllers['itemCategories'],
            'create',
            'item-categories.index'
        );

        $safeRoute(
            'post',
            '/item-categories',
            'item-categories.store',
            $controllers['itemCategories'],
            'store',
            'item-categories.index'
        );

        $safeRoute(
            'get',
            '/item-categories/{itemCategory}/edit',
            'item-categories.edit',
            $controllers['itemCategories'],
            'edit',
            'item-categories.index',
            ['itemCategory']
        );

        $safeRoute(
            'put',
            '/item-categories/{itemCategory}',
            'item-categories.update',
            $controllers['itemCategories'],
            'update',
            'item-categories.index',
            ['itemCategory']
        );

        $safeRoute(
            'delete',
            '/item-categories/{itemCategory}',
            'item-categories.destroy',
            $controllers['itemCategories'],
            'destroy',
            'item-categories.index',
            ['itemCategory']
        );

        $safeRoute(
            'post',
            '/categories/bulk/toggle-status',
            'categories.bulk.toggle-status',
            $controllers['itemCategories'],
            'bulkToggleStatus',
            'item-categories.index'
        );

        /*
        |--------------------------------------------------------------------------
        | Master Satuan
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/units',
            'units.index',
            $controllers['units'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/units/create',
            'units.create',
            $controllers['units'],
            'create',
            'units.index'
        );

        $safeRoute(
            'post',
            '/units',
            'units.store',
            $controllers['units'],
            'store',
            'units.index'
        );

        $safeRoute(
            'get',
            '/units/{unit}/edit',
            'units.edit',
            $controllers['units'],
            'edit',
            'units.index',
            ['unit']
        );

        $safeRoute(
            'put',
            '/units/{unit}',
            'units.update',
            $controllers['units'],
            'update',
            'units.index',
            ['unit']
        );

        $safeRoute(
            'delete',
            '/units/{unit}',
            'units.destroy',
            $controllers['units'],
            'destroy',
            'units.index',
            ['unit']
        );

        $safeRoute(
            'post',
            '/units/bulk/toggle-status',
            'units.bulk.toggle-status',
            $controllers['units'],
            'bulkToggleStatus',
            'units.index'
        );

        /*
        |--------------------------------------------------------------------------
        | Bengkel
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/workshops',
            'workshops.index',
            $controllers['workshops'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/workshops/create',
            'workshops.create',
            $controllers['workshops'],
            'create',
            'workshops.index'
        );

        $safeRoute(
            'post',
            '/workshops',
            'workshops.store',
            $controllers['workshops'],
            'store',
            'workshops.index'
        );

        $safeRoute(
            'get',
            '/workshops/{workshop}/edit',
            'workshops.edit',
            $controllers['workshops'],
            'edit',
            'workshops.index',
            ['workshop']
        );

        $safeRoute(
            'put',
            '/workshops/{workshop}',
            'workshops.update',
            $controllers['workshops'],
            'update',
            'workshops.index',
            ['workshop']
        );

        $safeRoute(
            'delete',
            '/workshops/{workshop}',
            'workshops.destroy',
            $controllers['workshops'],
            'destroy',
            'workshops.index',
            ['workshop']
        );

        $safeRoute(
            'post',
            '/workshops/bulk/toggle-status',
            'workshops.bulk.toggle-status',
            $controllers['workshops'],
            'bulkToggleStatus',
            'workshops.index'
        );

        /*
        |--------------------------------------------------------------------------
        | Lokasi Penyimpanan
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/locations',
            'locations.index',
            $controllers['locations'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/locations/create',
            'locations.create',
            $controllers['locations'],
            'create',
            'locations.index'
        );

        $safeRoute(
            'post',
            '/locations',
            'locations.store',
            $controllers['locations'],
            'store',
            'locations.index'
        );

        $safeRoute(
            'get',
            '/locations/{location}/inventory/pdf',
            'locations.inventory.pdf',
            \App\Http\Controllers\ScopedInventoryReportController::class, 'locationPdf',
            'locations.index',
            ['location']
        );

        $safeRoute(
            'get',
            '/locations/{location}/edit',
            'locations.edit',
            $controllers['locations'],
            'edit',
            'locations.index',
            ['location']
        );

        $safeRoute(
            'put',
            '/locations/{location}',
            'locations.update',
            $controllers['locations'],
            'update',
            'locations.index',
            ['location']
        );

        $safeRoute(
            'delete',
            '/locations/{location}',
            'locations.destroy',
            $controllers['locations'],
            'destroy',
            'locations.index',
            ['location']
        );

        $safeRoute(
            'post',
            '/locations/bulk/toggle-status',
            'locations.bulk.toggle-status',
            $controllers['locations'],
            'bulkToggleStatus',
            'locations.index'
        );

        $safeRoute(
            'get',
            '/locations/{location}',
            'locations.show',
            $controllers['locations'],
            'show',
            'locations.index',
            ['location']
        );

        /*
        |--------------------------------------------------------------------------
        | Barang Masuk
        |--------------------------------------------------------------------------
        |
        | Route create berada sebelum route {stockReceipt}.
        | Semua route dinamis memakai whereNumber().
        |
        */

        $safeRoute(
            'get',
            '/stock-receipts',
            'stock-receipts.index',
            $controllers['stockReceipts'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/stock-receipts/create',
            'stock-receipts.create',
            $controllers['stockReceipts'],
            'create',
            'stock-receipts.index'
        );

        $safeRoute(
            'post',
            '/stock-receipts',
            'stock-receipts.store',
            $controllers['stockReceipts'],
            'store',
            'stock-receipts.index'
        );

        $safeRoute(
            'get',
            '/stock-receipts/{stockReceipt}/edit',
            'stock-receipts.edit',
            $controllers['stockReceipts'],
            'edit',
            'stock-receipts.index',
            ['stockReceipt']
        );

        $safeRoute(
            'put',
            '/stock-receipts/{stockReceipt}',
            'stock-receipts.update',
            $controllers['stockReceipts'],
            'update',
            'stock-receipts.index',
            ['stockReceipt']
        );

        $safeRoute(
            'post',
            '/stock-receipts/{stockReceipt}/post',
            'stock-receipts.post',
            $controllers['stockReceipts'],
            'post',
            'stock-receipts.index',
            ['stockReceipt']
        );

        $safeRoute(
            'post',
            '/stock-receipts/{stockReceipt}/cancel',
            'stock-receipts.cancel',
            $controllers['stockReceipts'],
            'cancel',
            'stock-receipts.index',
            ['stockReceipt']
        );

        $safeRoute(
            'get',
            '/stock-receipts/{stockReceipt}/print',
            'stock-receipts.print',
            $controllers['stockReceipts'],
            'printPdf',
            'stock-receipts.index',
            ['stockReceipt']
        );

        $safeRoute(
            'get',
            '/stock-receipts/{stockReceipt}',
            'stock-receipts.show',
            $controllers['stockReceipts'],
            'show',
            'stock-receipts.index',
            ['stockReceipt']
        );

        /*
        |--------------------------------------------------------------------------
        | Barang Keluar
        |--------------------------------------------------------------------------
        |
        | Route index/create/store didaftarkan oleh web.php lalu di-override
        | oleh routes/toolman-stock-out-and-receipt-binding-fix.php.
        | Route show/edit/update/approve/reject/cancel/pending didaftarkan
        | langsung oleh file override tersebut.
        |
        */

        $safeRoute(
            'get',
            '/stock-issues',
            'stock-issues.index',
            $controllers['stockIssues'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/stock-issues/create',
            'stock-issues.create',
            $controllers['stockIssues'],
            'create',
            'stock-issues.index'
        );

        $safeRoute(
            'post',
            '/stock-issues',
            'stock-issues.store',
            $controllers['stockIssues'],
            'store',
            'stock-issues.index'
        );

        /*
        |--------------------------------------------------------------------------
        | Pergerakan Stok
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/stock-movements',
            'stock-movements.index',
            $controllers['stockMovements'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/stock-movements/{stockMovement}',
            'stock-movements.show',
            $controllers['stockMovements'],
            'show',
            'stock-movements.index',
            ['stockMovement']
        );

        /*
        |--------------------------------------------------------------------------
        | Peminjaman
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/loans',
            'loans.index',
            $controllers['loans'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/loans/create',
            'loans.create',
            $controllers['loans'],
            'create',
            'loans.index'
        );

        $safeRoute(
            'post',
            '/loans',
            'loans.store',
            $controllers['loans'],
            'store',
            'loans.index'
        );

        $safeRoute(
            'post',
            '/loans/{loan}/approve',
            'loans.approve',
            $controllers['loans'],
            'approve',
            'loans.index',
            ['loan']
        );

        $safeRoute(
            'post',
            '/loans/{loan}/reject',
            'loans.reject',
            $controllers['loans'],
            'reject',
            'loans.index',
            ['loan']
        );

        $safeRoute(
            'post',
            '/loans/{loan}/checkout',
            'loans.checkout',
            $controllers['loans'],
            'checkout',
            'loans.index',
            ['loan']
        );

        $safeRoute(
            'post',
            '/loans/{loan}/complete',
            'loans.complete',
            $controllers['loans'],
            'complete',
            'loans.index',
            ['loan']
        );

        $safeRoute(
            'post',
            '/loans/{loan}/cancel',
            'loans.cancel',
            $controllers['loans'],
            'cancel',
            'loans.index',
            ['loan']
        );

        /*
         * Pengembalian harus sebelum /loans/{loan}.
         */
        $safeRoute(
            'get',
            '/loan-returns',
            'loans.returns.index',
            $controllers['loanReturns'],
            'index',
            'loans.index'
        );

        $safeRoute(
            'get',
            '/loans/{loan}/return',
            'loans.return-form',
            $controllers['loanReturns'],
            'form',
            'loans.index',
            ['loan']
        );

        $safeRoute(
            'post',
            '/loans/{loan}/return',
            'loans.return',
            $controllers['loanReturns'],
            'process',
            'loans.index',
            ['loan']
        );

        $safeRoute(
            'post',
            '/loans/{loan}/items/{loanItem}/return',
            'loans.items.return',
            $controllers['loanReturns'],
            'returnItem',
            'loans.index',
            ['loan', 'loanItem']
        );

        $safeRoute(
            'get',
            '/loans/{loan}',
            'loans.show',
            $controllers['loans'],
            'show',
            'loans.index',
            ['loan']
        );

        /*
        |--------------------------------------------------------------------------
        | Laporan Kerusakan
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/damage-reports',
            'damage-reports.index',
            $controllers['damageReports'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/damage-reports/create',
            'damage-reports.create',
            $controllers['damageReports'],
            'create',
            'damage-reports.index'
        );

        $safeRoute(
            'post',
            '/damage-reports',
            'damage-reports.store',
            $controllers['damageReports'],
            'store',
            'damage-reports.index'
        );

        $safeRoute(
            'get',
            '/damage-reports/{damageReport}/edit',
            'damage-reports.edit',
            $controllers['damageReports'],
            'edit',
            'damage-reports.index',
            ['damageReport']
        );

        $safeRoute(
            'put',
            '/damage-reports/{damageReport}',
            'damage-reports.update',
            $controllers['damageReports'],
            'update',
            'damage-reports.index',
            ['damageReport']
        );

        $safeRoute(
            'post',
            '/damage-reports/{damageReport}/verify',
            'damage-reports.verify',
            $controllers['damageReports'],
            'verify',
            'damage-reports.index',
            ['damageReport']
        );

        $safeRoute(
            'post',
            '/damage-reports/{damageReport}/start-repair',
            'damage-reports.start-repair',
            $controllers['damageReports'],
            'startRepair',
            'damage-reports.index',
            ['damageReport']
        );

        $safeRoute(
            'post',
            '/damage-reports/{damageReport}/complete-repair',
            'damage-reports.complete-repair',
            $controllers['damageReports'],
            'completeRepair',
            'damage-reports.index',
            ['damageReport']
        );

        $safeRoute(
            'post',
            '/damage-reports/{damageReport}/close',
            'damage-reports.close',
            $controllers['damageReports'],
            'close',
            'damage-reports.index',
            ['damageReport']
        );

        $safeRoute(
            'get',
            '/damage-reports/{damageReport}',
            'damage-reports.show',
            $controllers['damageReports'],
            'show',
            'damage-reports.index',
            ['damageReport']
        );

        /*
        |--------------------------------------------------------------------------
        | Laporan
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/reports',
            'reports.index',
            \App\Http\Controllers\ScopedInventoryReportController::class,
            'index',
            'dashboard'
        );

        /*
         * Halaman laporan inventaris menggunakan index bila method inventory
         * belum ada. Ini bukan missing method karena pemilihan action dilakukan
         * sebelum route didaftarkan.
         */
        $inventoryReportMethod =
            $controllers['reports']
            && method_exists(
                $controllers['reports'],
                'inventory'
            )
                ? 'inventory'
                : 'index';

        $safeRoute(
            'get',
            '/reports/inventory',
            'reports.inventory',
            \App\Http\Controllers\ScopedInventoryReportController::class, 'inventory',
            'reports.index'
        );

        /*
         * Route export inventaris diarahkan ke controller export khusus.
         */
        $safeRoute(
            'get',
            '/reports/export/pdf',
            'reports.export.pdf',
            \App\Http\Controllers\ScopedInventoryReportController::class,
            'pdf',
            'reports.inventory'
        );

        $safeRoute(
            'get',
            '/reports/export/excel',
            'reports.export.excel',
            \App\Http\Controllers\ScopedInventoryReportController::class,
            'excel',
            'reports.inventory'
        );

        /*
         * Alias nama lama agar tombol lama tetap bekerja.
         */
        $safeRoute(
            'get',
            '/reports/inventory/pdf',
            'reports.inventory.pdf',
            \App\Http\Controllers\ScopedInventoryReportController::class,
            'pdf',
            'reports.inventory'
        );

        $safeRoute(
            'get',
            '/reports/inventory/excel',
            'reports.inventory.excel',
            \App\Http\Controllers\ScopedInventoryReportController::class,
            'excel',
            'reports.inventory'
        );

        $safeRoute(
            'get',
            '/reports/stock',
            'reports.stock',
            $controllers['reports'],
            'stock',
            'reports.index'
        );

        $safeRoute(
            'get',
            '/reports/stock/pdf',
            'reports.stock.pdf',
            $controllers['reports'],
            'stockPdf',
            'reports.stock'
        );

        $safeRoute(
            'get',
            '/reports/stock/excel',
            'reports.stock.excel',
            $controllers['reports'],
            'stockExcel',
            'reports.stock'
        );

        $safeRoute(
            'get',
            '/reports/loans',
            'reports.loans',
            $controllers['reports'],
            'loans',
            'reports.index'
        );

        $safeRoute(
            'get',
            '/reports/loans/pdf',
            'reports.loans.pdf',
            $controllers['reports'],
            'loansPdf',
            'reports.loans'
        );

        $safeRoute(
            'get',
            '/reports/loans/excel',
            'reports.loans.excel',
            $controllers['reports'],
            'loansExcel',
            'reports.loans'
        );

        $safeRoute(
            'get',
            '/reports/damages',
            'reports.damages',
            $controllers['reports'],
            'damages',
            'reports.index'
        );

        $safeRoute(
            'get',
            '/reports/damages/pdf',
            'reports.damages.pdf',
            $controllers['reports'],
            'damagesPdf',
            'reports.damages'
        );

        $safeRoute(
            'get',
            '/reports/damages/excel',
            'reports.damages.excel',
            $controllers['reports'],
            'damagesExcel',
            'reports.damages'
        );

        $safeRoute(
            'get',
            '/reports/stock-movements',
            'reports.stock-movements',
            $controllers['reports'],
            'stockMovements',
            'reports.index'
        );

        $safeRoute(
            'get',
            '/reports/stock-movements/pdf',
            'reports.stock-movements.pdf',
            $controllers['reports'],
            'stockMovementsPdf',
            'reports.stock-movements'
        );

        $safeRoute(
            'get',
            '/reports/stock-movements/excel',
            'reports.stock-movements.excel',
            $controllers['reports'],
            'stockMovementsExcel',
            'reports.stock-movements'
        );
    }
);

/*
|--------------------------------------------------------------------------
| Administrasi
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin',
])->prefix('admin')->name('admin.')->group(
    static function () use (
        $safeRoute,
        $controllers
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Pengguna
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/users',
            'users.index',
            $controllers['users'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/users/create',
            'users.create',
            $controllers['users'],
            'create',
            'admin.users.index'
        );

        $safeRoute(
            'post',
            '/users',
            'users.store',
            $controllers['users'],
            'store',
            'admin.users.index'
        );

        $safeRoute(
            'get',
            '/users/{user}/edit',
            'users.edit',
            $controllers['users'],
            'edit',
            'admin.users.index',
            ['user']
        );

        $safeRoute(
            'put',
            '/users/{user}',
            'users.update',
            $controllers['users'],
            'update',
            'admin.users.index',
            ['user']
        );

        $safeRoute(
            'delete',
            '/users/{user}',
            'users.destroy',
            $controllers['users'],
            'destroy',
            'admin.users.index',
            ['user']
        );

        /*
        |--------------------------------------------------------------------------
        | Hak Akses
        |--------------------------------------------------------------------------
        */

        $safeRoute(
            'get',
            '/access',
            'access.index',
            $controllers['access'],
            'index',
            'dashboard'
        );

        $safeRoute(
            ['put', 'patch'],
            '/access',
            'access.update',
            $controllers['access'],
            'update',
            'admin.access.index'
        );

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        |
        | Route export diletakkan sebelum /audit-logs/{auditLog}.
        |
        */

        $safeRoute(
            'get',
            '/audit-logs',
            'audit-logs.index',
            $controllers['auditLogs'],
            'index',
            'dashboard'
        );

        $safeRoute(
            'get',
            '/audit-logs/export',
            'audit-logs.export',
            $controllers['auditLogs'],
            'export',
            'admin.audit-logs.index'
        );

        $safeRoute(
            'get',
            '/audit-logs/{auditLog}',
            'audit-logs.show',
            $controllers['auditLogs'],
            'show',
            'admin.audit-logs.index',
            ['auditLog']
        );
    }
);

/*
|--------------------------------------------------------------------------
| Admin — Error Logs
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/error-logs')
    ->name('admin.error-logs.')
    ->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Admin\ErrorLogController::class, 'index'])
            ->name('index');
        Route::get('/{errorLog}', [\App\Http\Controllers\Admin\ErrorLogController::class, 'show'])
            ->whereNumber('errorLog')
            ->name('show');
        Route::post('/{errorLog}/resolve', [\App\Http\Controllers\Admin\ErrorLogController::class, 'resolve'])
            ->whereNumber('errorLog')
            ->name('resolve');
        Route::post('/{errorLog}/unresolve', [\App\Http\Controllers\Admin\ErrorLogController::class, 'unresolve'])
            ->whereNumber('errorLog')
            ->name('unresolve');
        Route::delete('/{errorLog}', [\App\Http\Controllers\Admin\ErrorLogController::class, 'destroy'])
            ->whereNumber('errorLog')
            ->name('destroy');
        Route::delete('/bulk/clear-resolved', [\App\Http\Controllers\Admin\ErrorLogController::class, 'clearResolved'])
            ->name('clear-resolved');
        Route::get('/export/pdf', [\App\Http\Controllers\Admin\ErrorLogController::class, 'exportPdf'])
            ->name('export-pdf');
        Route::get('/export/excel', [\App\Http\Controllers\Admin\ErrorLogController::class, 'exportExcel'])
            ->name('export-excel');
    });

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| auth.php tetap boleh dipakai karena berisi route login, logout,
| reset password, dan verifikasi email. File hanya dimuat bila tersedia.
|
*/

if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}

/*
|--------------------------------------------------------------------------
| Status Toggle Routes
|--------------------------------------------------------------------------
*/

if (
    file_exists(
        __DIR__.'/status-toggles.php'
    )
) {
    require __DIR__.'/status-toggles.php';
}

/*
|--------------------------------------------------------------------------
| Blade Route Compatibility Aliases
|--------------------------------------------------------------------------
*/

if (
    file_exists(
        __DIR__.'/blade-route-aliases.php'
    )
) {
    require __DIR__.'/blade-route-aliases.php';
}

/* SIMBA_STUDENT_MODULE_START */
Route::middleware('guest')->group(function (): void {
    Route::get(
        '/student-register',
        [\App\Http\Controllers\Auth\StudentRegistrationController::class, 'create']
    )->name('student-register.create');

    Route::post(
        '/student-register',
        [\App\Http\Controllers\Auth\StudentRegistrationController::class, 'store']
    )->name('student-register.store');
});

Route::middleware('auth')
    ->prefix('students')
    ->name('students.')
    ->group(function (): void {
        Route::get('/', [\App\Http\Controllers\StudentController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\StudentController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\StudentController::class, 'store'])
            ->name('store');

        Route::get('/import', [\App\Http\Controllers\StudentController::class, 'importCreate'])
            ->name('import.create');
        Route::post('/import', [\App\Http\Controllers\StudentController::class, 'importStore'])
            ->name('import.store');
        Route::get('/template', [\App\Http\Controllers\StudentController::class, 'template'])
            ->name('template');
        Route::get('/export', [\App\Http\Controllers\StudentController::class, 'export'])
            ->name('export');

        Route::get('/{student}/reset-password', [\App\Http\Controllers\StudentController::class, 'resetPasswordEdit'])
            ->whereNumber('student')
            ->name('reset-password.edit');
        Route::put('/{student}/reset-password', [\App\Http\Controllers\StudentController::class, 'resetPasswordUpdate'])
            ->whereNumber('student')
            ->name('reset-password.update');

        Route::get('/{student}/edit', [\App\Http\Controllers\StudentController::class, 'edit'])
            ->whereNumber('student')
            ->name('edit');
        Route::put('/{student}', [\App\Http\Controllers\StudentController::class, 'update'])
            ->whereNumber('student')
            ->name('update');
        Route::delete('/{student}', [\App\Http\Controllers\StudentController::class, 'destroy'])
            ->whereNumber('student')
            ->name('destroy');
    });
/* SIMBA_STUDENT_MODULE_END */

require __DIR__.'/profile.php';

require __DIR__.'/storage-location-qr.php';

require __DIR__.'/auth-modern.php';



// Final override workflow Barang Masuk.
require __DIR__.'/stock-receipt-workflow-override.php';

// Route foto Barang Masuk terlindungi.
require __DIR__.'/stock-receipt-photo.php';

// Final workflow peminjaman dan stok jurusan.
require __DIR__.'/workshop-loan-inventory-flow.php';

// Final fix Toolman Barang Keluar dan edit Barang Masuk.
require __DIR__.'/toolman-stock-out-and-receipt-binding-fix.php';

// Final access Guru/Siswa hanya Peminjaman.

// Final access Guru/Siswa hanya Peminjaman.

// Final access Guru/Siswa hanya Peminjaman.

// Final access Guru/Siswa hanya Peminjaman.

// Final access Guru/Siswa hanya Peminjaman.

// Final access Guru/Siswa hanya Peminjaman.
require __DIR__.'/guru-siswa-loan-only.php';

// Inventaris lokasi read-only dan print Waka Sarpras.
require __DIR__.'/location-inventory-two-modes.php';

/*
|--------------------------------------------------------------------------
| Import Barang Masuk & Barang Keluar
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,toolman,kepala_bengkel'])
    ->prefix('stock-import')
    ->name('stock-import.')
    ->group(function (): void {
        Route::get('/', [\App\Http\Controllers\StockImportController::class, 'create'])
            ->name('index');
        Route::get('/template', [\App\Http\Controllers\StockImportController::class, 'template'])
            ->name('template');
        Route::get('/reference', [\App\Http\Controllers\StockImportController::class, 'reference'])
            ->name('reference');
        Route::post('/', [\App\Http\Controllers\StockImportController::class, 'store'])
            ->name('store');
    });
