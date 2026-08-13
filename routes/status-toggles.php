<?php

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\StorageLocation;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Route Toggle Status
|--------------------------------------------------------------------------
|
| File ini melengkapi nama route yang sudah dipanggil oleh Blade.
| Bila controller mempunyai method toggleStatus(), method asli dipakai.
| Bila method belum tersedia, fallback aman hanya mengubah is_active.
|
*/

$callToggleController = static function (
    string $controller,
    string $parameterName,
    Model $model
): mixed {
    if (
        ! class_exists($controller)
        || ! method_exists(
            $controller,
            'toggleStatus'
        )
    ) {
        return null;
    }

    return app()->call(
        [
            app($controller),
            'toggleStatus',
        ],
        [
            $parameterName => $model,
        ]
    );
};

$toggleActiveFallback = static function (
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
| Pengguna
|--------------------------------------------------------------------------
*/

if (
    ! Route::has(
        'admin.users.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/admin/users/{user}/toggle-status',
        static function (
            User $user
        ) use (
            $callToggleController,
            $toggleActiveFallback
        ): mixed {
            $controllerResult =
                $callToggleController(
                    \App\Http\Controllers\Admin\UserController::class,
                    'user',
                    $user
                );

            if ($controllerResult !== null) {
                return $controllerResult;
            }

            if (
                auth()->id() === $user->id
                && (bool) $user->is_active
            ) {
                return back()->with(
                    'warning',
                    'Akun yang sedang digunakan tidak dapat dinonaktifkan.'
                );
            }

            if (
                (string) $user->role === 'admin'
                && (bool) $user->is_active
                && Schema::hasColumn(
                    'users',
                    'is_active'
                )
            ) {
                $activeAdminCount =
                    User::query()
                        ->where('role', 'admin')
                        ->where(
                            'is_active',
                            true
                        )
                        ->count();

                if ($activeAdminCount <= 1) {
                    return back()->with(
                        'warning',
                        'Administrator aktif terakhir tidak dapat dinonaktifkan.'
                    );
                }
            }

            return $toggleActiveFallback(
                $user,
                'users',
                'Pengguna'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin',
        ])
        ->whereNumber('user')
        ->name(
            'admin.users.toggle-status'
        );
}

/*
|--------------------------------------------------------------------------
| Bengkel
|--------------------------------------------------------------------------
*/

if (
    ! Route::has(
        'workshops.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/workshops/{workshop}/toggle-status',
        static function (
            Workshop $workshop
        ) use (
            $callToggleController,
            $toggleActiveFallback
        ): mixed {
            $controllerResult =
                $callToggleController(
                    \App\Http\Controllers\WorkshopController::class,
                    'workshop',
                    $workshop
                );

            if ($controllerResult !== null) {
                return $controllerResult;
            }

            return $toggleActiveFallback(
                $workshop,
                'workshops',
                'Bengkel'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('workshop')
        ->name(
            'workshops.toggle-status'
        );
}

/*
|--------------------------------------------------------------------------
| Lokasi Penyimpanan
|--------------------------------------------------------------------------
*/

if (
    ! Route::has(
        'locations.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/locations/{location}/toggle-status',
        static function (
            StorageLocation $location
        ) use (
            $callToggleController,
            $toggleActiveFallback
        ): mixed {
            $controllerClass =
                class_exists(
                    \App\Http\Controllers\StorageLocationController::class
                )
                    ? \App\Http\Controllers\StorageLocationController::class
                    : \App\Http\Controllers\LocationController::class;

            $controllerResult =
                $callToggleController(
                    $controllerClass,
                    'location',
                    $location
                );

            if ($controllerResult !== null) {
                return $controllerResult;
            }

            return $toggleActiveFallback(
                $location,
                'storage_locations',
                'Lokasi penyimpanan'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('location')
        ->name(
            'locations.toggle-status'
        );
}

/*
|--------------------------------------------------------------------------
| Satuan
|--------------------------------------------------------------------------
*/

if (
    ! Route::has(
        'units.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/units/{unit}/toggle-status',
        static function (
            Unit $unit
        ) use (
            $callToggleController,
            $toggleActiveFallback
        ): mixed {
            $controllerResult =
                $callToggleController(
                    \App\Http\Controllers\UnitController::class,
                    'unit',
                    $unit
                );

            if ($controllerResult !== null) {
                return $controllerResult;
            }

            return $toggleActiveFallback(
                $unit,
                'units',
                'Satuan'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('unit')
        ->name(
            'units.toggle-status'
        );
}

/*
|--------------------------------------------------------------------------
| Kategori Barang
|--------------------------------------------------------------------------
*/

if (
    ! Route::has(
        'item-categories.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/item-categories/{itemCategory}/toggle-status',
        static function (
            ItemCategory $itemCategory
        ) use (
            $callToggleController,
            $toggleActiveFallback
        ): mixed {
            $controllerResult =
                $callToggleController(
                    \App\Http\Controllers\ItemCategoryController::class,
                    'itemCategory',
                    $itemCategory
                );

            if ($controllerResult !== null) {
                return $controllerResult;
            }

            return $toggleActiveFallback(
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
            'item-categories.toggle-status'
        );
}

/*
|--------------------------------------------------------------------------
| Data Barang
|--------------------------------------------------------------------------
*/

if (
    ! Route::has(
        'items.toggle-status'
    )
) {
    Route::match(
        [
            'post',
            'put',
            'patch',
        ],
        '/items/{item}/toggle-status',
        static function (
            Item $item
        ) use (
            $callToggleController,
            $toggleActiveFallback
        ): mixed {
            $controllerResult =
                $callToggleController(
                    \App\Http\Controllers\ItemController::class,
                    'item',
                    $item
                );

            if ($controllerResult !== null) {
                return $controllerResult;
            }

            return $toggleActiveFallback(
                $item,
                'items',
                'Barang'
            );
        }
    )
        ->middleware([
            'auth',
            'role:admin,toolman',
        ])
        ->whereNumber('item')
        ->name(
            'items.toggle-status'
        );
}
