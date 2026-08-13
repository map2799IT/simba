<?php

use App\Models\DamageReport;
use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Kompatibilitas Nama Route yang Dipanggil Blade
|--------------------------------------------------------------------------
|
| Beberapa view lama masih memakai nama route lama:
| - audit-logs.*
| - categories.*
| - damage-reports.start
| - damage-reports.resolve
| - item
| - register
|
| File ini mendaftarkan alias tanpa mengganti seluruh routes/web.php.
|
*/

$controllerAction = static function (
    string $controller,
    string $method,
    array $parameters = [],
    ?string $fallbackRoute = null,
    string $message = 'Fitur belum tersedia.'
): mixed {
    if (
        class_exists($controller)
        && method_exists(
            $controller,
            $method
        )
    ) {
        return app()->call(
            [
                app($controller),
                $method,
            ],
            $parameters
        );
    }

    if (
        $fallbackRoute !== null
        && Route::has($fallbackRoute)
    ) {
        return redirect()
            ->route($fallbackRoute)
            ->with(
                'warning',
                $message
            );
    }

    if (Route::has('dashboard')) {
        return redirect()
            ->route('dashboard')
            ->with(
                'warning',
                $message
            );
    }

    return redirect('/')
        ->with(
            'warning',
            $message
        );
};

$toggleActive = static function (
    Model $model,
    string $table,
    string $label
): RedirectResponse {
    if (
        ! Schema::hasTable($table)
        || ! Schema::hasColumn(
            $table,
            'is_active'
        )
    ) {
        return back()->with(
            'warning',
            "Status {$label} belum dapat diubah karena kolom is_active tidak tersedia."
        );
    }

    $newStatus = ! (bool) $model->getAttribute(
        'is_active'
    );

    $model->forceFill([
        'is_active' => $newStatus,
    ])->save();

    return back()->with(
        'success',
        "{$label} berhasil ".
        (
            $newStatus
                ? 'diaktifkan.'
                : 'dinonaktifkan.'
        )
    );
};

/*
|--------------------------------------------------------------------------
| Audit Log: alias audit-logs.* ke admin.audit-logs.*
|--------------------------------------------------------------------------
*/

if (! Route::has('audit-logs.index')) {
    Route::get(
        '/audit-logs',
        static function (): RedirectResponse {
            if (
                Route::has(
                    'admin.audit-logs.index'
                )
            ) {
                return redirect()->route(
                    'admin.audit-logs.index'
                );
            }

            return redirect()
                ->route('dashboard')
                ->with(
                    'warning',
                    'Halaman Audit Log belum tersedia.'
                );
        }
    )
        ->middleware([
            'auth',
            'role:admin,kepala_bengkel',
        ])
        ->name('audit-logs.index');
}

if (! Route::has('audit-logs.show')) {
    Route::get(
        '/audit-logs/{auditLog}',
        static function (
            int $auditLog
        ): RedirectResponse {
            if (
                Route::has(
                    'admin.audit-logs.show'
                )
            ) {
                return redirect()->route(
                    'admin.audit-logs.show',
                    [
                        'auditLog' => $auditLog,
                    ]
                );
            }

            return redirect()
                ->route('audit-logs.index')
                ->with(
                    'warning',
                    'Detail Audit Log belum tersedia.'
                );
        }
    )
        ->middleware([
            'auth',
            'role:admin,kepala_bengkel',
        ])
        ->whereNumber('auditLog')
        ->name('audit-logs.show');
}

/*
|--------------------------------------------------------------------------
| Kategori: alias categories.* ke ItemCategoryController
|--------------------------------------------------------------------------
*/

$categoryController =
    \App\Http\Controllers\ItemCategoryController::class;

if (! Route::has('categories.index')) {
    Route::get(
        '/categories',
        static fn (): mixed =>
            $controllerAction(
                $categoryController,
                'index',
                [],
                'item-categories.index',
                'Halaman kategori belum tersedia.'
            )
    )
        ->middleware('auth')
        ->name('categories.index');
}

if (! Route::has('categories.create')) {
    Route::get(
        '/categories/create',
        static fn (): mixed =>
            $controllerAction(
                $categoryController,
                'create',
                [],
                'categories.index',
                'Form tambah kategori belum tersedia.'
            )
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->name('categories.create');
}

if (! Route::has('categories.store')) {
    Route::post(
        '/categories',
        static fn (): mixed =>
            $controllerAction(
                $categoryController,
                'store',
                [],
                'categories.index',
                'Penyimpanan kategori belum tersedia.'
            )
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->name('categories.store');
}

if (! Route::has('categories.edit')) {
    Route::get(
        '/categories/{itemCategory}/edit',
        static function (
            ItemCategory $itemCategory
        ) use (
            $controllerAction,
            $categoryController
        ): mixed {
            return $controllerAction(
                $categoryController,
                'edit',
                [
                    'itemCategory' =>
                        $itemCategory,
                ],
                'categories.index',
                'Form edit kategori belum tersedia.'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('itemCategory')
        ->name('categories.edit');
}

if (! Route::has('categories.update')) {
    Route::match(
        [
            'put',
            'patch',
        ],
        '/categories/{itemCategory}',
        static function (
            ItemCategory $itemCategory
        ) use (
            $controllerAction,
            $categoryController
        ): mixed {
            return $controllerAction(
                $categoryController,
                'update',
                [

                    'itemCategory' =>
                        $itemCategory,
                ],
                'categories.index',
                'Pembaruan kategori belum tersedia.'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('itemCategory')
        ->name('categories.update');
}

if (
    ! Route::has(
        'categories.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/categories/{itemCategory}/toggle-status',
        static function (
            ItemCategory $itemCategory
        ) use (
            $controllerAction,
            $categoryController,
            $toggleActive
        ): mixed {
            if (
                class_exists(
                    $categoryController
                )
                && method_exists(
                    $categoryController,
                    'toggleStatus'
                )
            ) {
                return $controllerAction(
                    $categoryController,
                    'toggleStatus',
                    [
                        'itemCategory' =>
                            $itemCategory,
                    ]
                );
            }

            return $toggleActive(
                $itemCategory,
                'item_categories',
                'Kategori barang'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('itemCategory')
        ->name(
            'categories.toggle-status'
        );
}

/*
|--------------------------------------------------------------------------
| Laporan Kerusakan: nama workflow lama
|--------------------------------------------------------------------------
|
| Controller diprioritaskan:
| - start() lalu startRepair()
| - resolve() lalu close()
|
| Bila tidak ada, pengguna kembali ke detail dengan pesan.
|
*/

$damageController =
    \App\Http\Controllers\DamageReportController::class;

if (
    ! Route::has(
        'damage-reports.start'
    )
) {
    Route::post(
        '/damage-reports/{damageReport}/start',
        static function (
            DamageReport $damageReport
        ) use (
            $controllerAction,
            $damageController
        ): mixed {
            if (
                class_exists(
                    $damageController
                )
                && method_exists(
                    $damageController,
                    'start'
                )
            ) {
                return $controllerAction(
                    $damageController,
                    'start',
                    [
                        'damageReport' =>
                            $damageReport,
                    ]
                );
            }

            if (
                class_exists(
                    $damageController
                )
                && method_exists(
                    $damageController,
                    'startRepair'
                )
            ) {
                return $controllerAction(
                    $damageController,
                    'startRepair',
                    [
                        'damageReport' =>
                            $damageReport,
                    ]
                );
            }

            return redirect()
                ->route(
                    'damage-reports.show',
                    $damageReport
                )
                ->with(
                    'warning',
                    'Proses mulai penanganan belum tersedia pada controller.'
                );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('damageReport')
        ->name('damage-reports.start');
}

if (
    ! Route::has(
        'damage-reports.resolve'
    )
) {
    Route::post(
        '/damage-reports/{damageReport}/resolve',
        static function (
            DamageReport $damageReport
        ) use (
            $controllerAction,
            $damageController
        ): mixed {
            if (
                class_exists(
                    $damageController
                )
                && method_exists(
                    $damageController,
                    'resolve'
                )
            ) {
                return $controllerAction(
                    $damageController,
                    'resolve',
                    [
                        'damageReport' =>
                            $damageReport,
                    ]
                );
            }

            if (
                class_exists(
                    $damageController
                )
                && method_exists(
                    $damageController,
                    'close'
                )
            ) {
                return $controllerAction(
                    $damageController,
                    'close',
                    [
                        'damageReport' =>
                            $damageReport,
                    ]
                );
            }

            return redirect()
                ->route(
                    'damage-reports.show',
                    $damageReport
                )
                ->with(
                    'warning',
                    'Proses penyelesaian laporan belum tersedia pada controller.'
                );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('damageReport')
        ->name(
            'damage-reports.resolve'
        );
}

/*
|--------------------------------------------------------------------------
| Alias route "item"
|--------------------------------------------------------------------------
|
| Layout lama masih memanggil route('item').
| Alias ini mengarah ke daftar barang.
|
*/

if (! Route::has('item')) {
    Route::get(
        '/item',
        static function (): RedirectResponse {
            if (Route::has('items.index')) {
                return redirect()->route(
                    'items.index'
                );
            }

            return redirect()->route(
                'dashboard'
            );
        }
    )
        ->middleware('auth')
        ->name('item');
}

/*
|--------------------------------------------------------------------------
| Register Fallback
|--------------------------------------------------------------------------
|
| Registrasi publik tidak diaktifkan. Route hanya disediakan agar
| welcome.blade.php tidak menghasilkan RouteNotFoundException.
|
*/

if (! Route::has('register')) {
    Route::get(
        '/register',
        static function (): RedirectResponse {
            if (Route::has('login')) {
                return redirect()
                    ->route('login')
                    ->with(
                        'warning',
                        'Registrasi publik tidak diaktifkan. Hubungi administrator untuk membuat akun.'
                    );
            }

            return redirect('/');
        }
    )->name('register');
}
