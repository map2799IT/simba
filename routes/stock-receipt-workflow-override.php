<?php

use App\Http\Controllers\StockReceiptWorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Final override route Barang Masuk
|--------------------------------------------------------------------------
|
| File ini wajib dimuat pada BARIS TERAKHIR routes/web.php.
| Tujuannya memastikan route Closure/StockReceiptController lama tidak
| menimpa StockReceiptWorkflowController.
|
*/

$stockReceiptWorkflowRoutes = [
    [
        ['GET'],
        '/stock-receipts',
        'stock-receipts.index',
        'index',
        ['auth'],
    ],
    [
        ['GET'],
        '/stock-receipts/create',
        'stock-receipts.create',
        'create',
        ['auth', 'role:admin,toolman'],
    ],
    [
        ['POST'],
        '/stock-receipts',
        'stock-receipts.store',
        'store',
        ['auth', 'role:admin,toolman'],
    ],
    [
        ['GET'],
        '/stock-receipts/{stockReceipt}',
        'stock-receipts.show',
        'show',
        ['auth', 'role:admin,toolman,kepala_bengkel'],
    ],
    [
        ['GET'],
        '/stock-receipts/{stockReceipt}/edit',
        'stock-receipts.edit',
        'edit',
        ['auth', 'role:admin,toolman,kepala_bengkel'],
    ],
    [
        ['PUT', 'PATCH'],
        '/stock-receipts/{stockReceipt}',
        'stock-receipts.update',
        'update',
        ['auth', 'role:admin,toolman,kepala_bengkel'],
    ],
    [
        ['DELETE'],
        '/stock-receipts/{stockReceipt}',
        'stock-receipts.destroy',
        'destroy',
        ['auth', 'role:admin'],
    ],
    [
        ['GET'],
        '/stock-receipt-change-requests',
        'stock-receipts.approvals',
        'approvalIndex',
        ['auth', 'role:admin,kepala_bengkel'],
    ],
    [
        ['POST'],
        '/stock-receipts/{stockReceipt}/approve-edit',
        'stock-receipts.approve-edit',
        'approveEdit',
        ['auth', 'role:admin,kepala_bengkel'],
    ],
    [
        ['POST'],
        '/stock-receipts/{stockReceipt}/reject-edit',
        'stock-receipts.reject-edit',
        'rejectEdit',
        ['auth', 'role:admin,kepala_bengkel'],
    ],
];

foreach (
    $stockReceiptWorkflowRoutes
    as [
        $methods,
        $uri,
        $name,
        $action,
        $middleware,
    ]
) {
    $route = Route::match(
        $methods,
        $uri,
        [
            StockReceiptWorkflowController::class,
            $action,
        ]
    )
        ->middleware($middleware)
        ->name($name);

    if (str_contains($uri, '{stockReceipt}')) {
        $route->whereNumber('stockReceipt');
    }
}

unset($stockReceiptWorkflowRoutes, $route);
