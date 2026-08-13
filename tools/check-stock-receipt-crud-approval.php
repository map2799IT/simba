<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA STOCK RECEIPT CRUD & APPROVAL CHECK\n";
echo "=========================================\n\n";

$checks = [
    'Tabel permintaan perubahan' =>
        Schema::hasTable('stock_receipt_change_requests'),

    'Model permintaan perubahan' =>
        class_exists(\App\Models\StockReceiptChangeRequest::class),

    'Request update' =>
        class_exists(\App\Http\Requests\UpdateStockReceiptRequest::class),

    'Mutation service' =>
        class_exists(\App\Services\StockReceiptMutationService::class),

    'Workflow controller' =>
        class_exists(\App\Http\Controllers\StockReceiptWorkflowController::class),

    'Workflow provider' =>
        class_exists(\App\Providers\StockReceiptWorkflowServiceProvider::class),

    'View index' => View::exists('stock-receipts.index'),
    'View show' => View::exists('stock-receipts.show'),
    'View edit' => View::exists('stock-receipts.edit'),
    'View approvals' => View::exists('stock-receipts.approvals'),

    'Route index' => Route::has('stock-receipts.index'),
    'Route create' => Route::has('stock-receipts.create'),
    'Route store' => Route::has('stock-receipts.store'),
    'Route show' => Route::has('stock-receipts.show'),
    'Route edit' => Route::has('stock-receipts.edit'),
    'Route update' => Route::has('stock-receipts.update'),
    'Route delete admin' => Route::has('stock-receipts.destroy'),
    'Route approval queue' => Route::has('stock-receipts.approvals'),
    'Route approve edit' => Route::has('stock-receipts.approve-edit'),
    'Route reject edit' => Route::has('stock-receipts.reject-edit'),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 44).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nROUTE ACTION\n";
echo "------------\n";

foreach ([
    'stock-receipts.index' => 'index',
    'stock-receipts.create' => 'create',
    'stock-receipts.store' => 'store',
    'stock-receipts.show' => 'show',
    'stock-receipts.edit' => 'edit',
    'stock-receipts.update' => 'update',
    'stock-receipts.destroy' => 'destroy',
    'stock-receipts.approvals' => 'approvalIndex',
    'stock-receipts.approve-edit' => 'approveEdit',
    'stock-receipts.reject-edit' => 'rejectEdit',
] as $routeName => $method) {
    $action = Route::getRoutes()
        ->getByName($routeName)
        ?->getActionName();

    $valid = is_string($action)
        && str_contains(
            $action,
            'StockReceiptWorkflowController@'.$method
        );

    echo str_pad($routeName, 44).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        ' '.
        ($action ?? '-').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$relationValid = true;

try {
    $movement = new \App\Models\ItemStockMovement();

    $changeRelation = $movement->changeRequests();
    $pendingRelation = $movement->pendingChangeRequest();

    $relationValid =
        $changeRelation->getRelated()
            instanceof \App\Models\StockReceiptChangeRequest
        && $pendingRelation->getRelated()
            instanceof \App\Models\StockReceiptChangeRequest;
} catch (Throwable $exception) {
    $relationValid = false;
}

echo "\n".
    str_pad('Relasi approval pada movement', 44).
    ': '.
    ($relationValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $relationValid) {
    $failed = true;
}

echo "\nHAK AKSES TARGET\n";
echo "----------------\n";
echo "Toolman        : tambah + ajukan edit jurusan sendiri\n";
echo "Kepala Bengkel : lihat/edit/approve jurusan sendiri\n";
echo "Administrator  : semua + hapus\n";

echo "\n".
    (
        $failed
            ? 'STOCK RECEIPT CRUD & APPROVAL BELUM VALID.'
            : 'STOCK RECEIPT CRUD & APPROVAL SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
