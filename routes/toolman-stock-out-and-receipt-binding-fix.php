<?php

use App\Http\Controllers\WorkshopStockIssueController;
use App\Models\ItemStockMovement;
use App\Models\StockIssueRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Final fix Barang Keluar Toolman dan binding Barang Masuk
|--------------------------------------------------------------------------
|
| File ini dimuat pada baris terakhir routes/web.php.
|
*/

/*
 * Perbaiki implicit model binding Barang Masuk.
 * Data dicari tanpa global scope, lalu controller tetap memeriksa
 * role dan kesamaan jurusan.
 */
Route::bind(
    'stockReceipt',
    function (string $value): ItemStockMovement {
        return ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->whereKey($value)
            ->where('type', ItemStockMovement::TYPE_INCOMING)
            ->firstOrFail();
    }
);

Route::bind(
    'stockIssue',
    function (string $value): StockIssueRequest {
        return StockIssueRequest::query()
            ->whereKey($value)
            ->firstOrFail();
    }
);

$definitions = [
    ['name' => 'stock-issues.index', 'uri' => 'stock-issues', 'methods' => ['GET', 'HEAD'], 'action' => 'index'],
    ['name' => 'stock-issues.create', 'uri' => 'stock-issues/create', 'methods' => ['GET', 'HEAD'], 'action' => 'create'],
    ['name' => 'stock-issues.store', 'uri' => 'stock-issues', 'methods' => ['POST'], 'action' => 'store'],
];

$matched = [];

foreach (Route::getRoutes() as $route) {
    foreach ($definitions as $definition) {
        $sameName = $route->getName() === $definition['name'];
        $sameUri = trim($route->uri(), '/') === $definition['uri'];
        $sameMethod = array_intersect($definition['methods'], $route->methods()) !== [];

        if (! $sameName && ! ($sameUri && $sameMethod)) {
            continue;
        }

        $uses = WorkshopStockIssueController::class . '@' . $definition['action'];
        $action = $route->getAction();
        $action['uses'] = $uses;
        $action['controller'] = $uses;
        $route->setAction($action);

        $matched[$definition['name']] = true;
    }
}

foreach ($definitions as $definition) {
    if (isset($matched[$definition['name']])) {
        continue;
    }

    Route::match(
        array_values(array_filter($definition['methods'], static fn (string $m): bool => $m !== 'HEAD')),
        '/' . $definition['uri'],
        [WorkshopStockIssueController::class, $definition['action']]
    )
        ->middleware('auth')
        ->name($definition['name']);
}

unset($definitions, $matched, $route, $definition);

/*
|--------------------------------------------------------------------------
| Approval Routes untuk Barang Keluar
|--------------------------------------------------------------------------
|
| Toolman membuat pengajuan, Kepala Bengkel / Wakil Sarpras menyetujui.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::get('/stock-issues/pending', [WorkshopStockIssueController::class, 'pendingIndex'])
        ->name('stock-issues.pending');

    Route::get('/stock-issues/{stockIssue}', [WorkshopStockIssueController::class, 'show'])
        ->whereNumber('stockIssue')
        ->name('stock-issues.show');

    Route::get('/stock-issues/{stockIssue}/edit', [WorkshopStockIssueController::class, 'edit'])
        ->whereNumber('stockIssue')
        ->name('stock-issues.edit');

    Route::put('/stock-issues/{stockIssue}', [WorkshopStockIssueController::class, 'update'])
        ->whereNumber('stockIssue')
        ->name('stock-issues.update');

    Route::post('/stock-issues/{stockIssue}/approve', [WorkshopStockIssueController::class, 'approve'])
        ->whereNumber('stockIssue')
        ->name('stock-issues.approve');

    Route::post('/stock-issues/{stockIssue}/reject', [WorkshopStockIssueController::class, 'reject'])
        ->whereNumber('stockIssue')
        ->name('stock-issues.reject');

    Route::post('/stock-issues/{stockIssue}/cancel', [WorkshopStockIssueController::class, 'cancel'])
        ->whereNumber('stockIssue')
        ->name('stock-issues.cancel');

    Route::bind('issueMovement', function (string $value): \App\Models\ItemStockMovement {
        return \App\Models\ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->whereKey($value)
            ->where('type', \App\Models\ItemStockMovement::TYPE_OUTGOING)
            ->firstOrFail();
    });

    Route::bind('issueChangeRequest', function (string $value): \App\Models\StockIssueChangeRequest {
        return \App\Models\StockIssueChangeRequest::query()
            ->whereKey($value)
            ->firstOrFail();
    });

    Route::get('/stock-issues/movement/{issueMovement}/edit', [WorkshopStockIssueController::class, 'editMovement'])
        ->whereNumber('issueMovement')
        ->name('stock-issues.movement.edit');

    Route::post('/stock-issues/movement/{issueMovement}/request-edit', [WorkshopStockIssueController::class, 'requestEditMovement'])
        ->whereNumber('issueMovement')
        ->name('stock-issues.movement.request-edit');

    Route::get('/stock-issues/change-approvals', [WorkshopStockIssueController::class, 'issueChangeApprovals'])
        ->name('stock-issues.change-approvals');

    Route::post('/stock-issues/change-request/{issueChangeRequest}/approve', [WorkshopStockIssueController::class, 'approveEditMovement'])
        ->whereNumber('issueChangeRequest')
        ->name('stock-issues.change-request.approve');

    Route::post('/stock-issues/change-request/{issueChangeRequest}/reject', [WorkshopStockIssueController::class, 'rejectEditMovement'])
        ->whereNumber('issueChangeRequest')
        ->name('stock-issues.change-request.reject');
});

