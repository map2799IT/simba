<?php

use App\Http\Controllers\StockReceiptPhotoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->group(
        function (): void {
            Route::get(
                '/stock-receipts/{stockReceipt}/photo',
                [
                    StockReceiptPhotoController::class,
                    'active',
                ]
            )
                ->whereNumber('stockReceipt')
                ->name(
                    'stock-receipts.photo.active'
                );

            Route::get(
                '/stock-receipts/{stockReceipt}/photo/proposed',
                [
                    StockReceiptPhotoController::class,
                    'proposed',
                ]
            )
                ->whereNumber('stockReceipt')
                ->name(
                    'stock-receipts.photo.proposed'
                );
        }
    );
