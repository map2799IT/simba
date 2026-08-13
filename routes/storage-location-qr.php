<?php

use App\Http\Controllers\ItemAssetBulkQrController;
use App\Http\Controllers\StorageLocationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->group(function (): void {
        Route::get(
            '/locations/{storageLocation}/inventory/print',
            [
                StorageLocationController::class,
                'inventoryPrint',
            ]
        )
            ->whereNumber(
                'storageLocation'
            )
            ->name(
                'locations.inventory.print'
            );

        Route::get(
            '/item-assets/qr-bulk',
            [
                ItemAssetBulkQrController::class,
                'index',
            ]
        )->name(
            'item-assets.qr-bulk.index'
        );

        Route::get(
            '/item-assets/items/{item}/qr-bulk',
            [
                ItemAssetBulkQrController::class,
                'print',
            ]
        )
            ->whereNumber('item')
            ->name(
                'item-assets.qr-bulk.print'
            );

        Route::get(
            '/item-assets/items/{item}/qr-bulk/download',
            [
                ItemAssetBulkQrController::class,
                'download',
            ]
        )
            ->whereNumber('item')
            ->name(
                'item-assets.qr-bulk.download'
            );
    });
