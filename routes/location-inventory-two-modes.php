<?php

use App\Http\Controllers\LocationInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/locations/inventory-menu', [
        LocationInventoryController::class,
        'menu',
    ])->name('locations.inventory.menu');

    Route::get('/locations/{storageLocation}/inventory/summary', [
        LocationInventoryController::class,
        'summary',
    ])->whereNumber('storageLocation')
      ->name('locations.inventory.summary');

    Route::get('/locations/{storageLocation}/inventory/summary/print', [
        LocationInventoryController::class,
        'summaryPrint',
    ])->whereNumber('storageLocation')
      ->name('locations.inventory.summary.print');

    Route::get('/locations/{storageLocation}/inventory/summary/pdf', [
        LocationInventoryController::class,
        'summaryPdf',
    ])->whereNumber('storageLocation')
      ->name('locations.inventory.summary.pdf');

    Route::get('/locations/{storageLocation}/inventory/complete', [
        LocationInventoryController::class,
        'complete',
    ])->whereNumber('storageLocation')
      ->name('locations.inventory.complete');

    Route::get('/locations/{storageLocation}/inventory/complete/pdf', [
        LocationInventoryController::class,
        'pdf',
    ])->whereNumber('storageLocation')
      ->name('locations.inventory.complete.pdf');
});
