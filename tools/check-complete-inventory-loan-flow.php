<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use App\Services\WorkshopLoanInventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA COMPLETE INVENTORY & LOAN FLOW CHECK\n";
echo "==========================================\n\n";

$checks = [
    'Tabel loans' => Schema::hasTable('loans'),
    'Tabel loan_items' => Schema::hasTable('loan_items'),
    'loans.workshop_id' => Schema::hasColumn('loans', 'workshop_id'),
    'loans.assigned_toolman_id' => Schema::hasColumn('loans', 'assigned_toolman_id'),
    'loan_items.item_asset_id' => Schema::hasColumn('loan_items', 'item_asset_id'),
    'loan_items.workshop_id' => Schema::hasColumn('loan_items', 'workshop_id'),
    'loan_items.quantity' => Schema::hasColumn('loan_items', 'quantity'),
    'loan_items.is_consumable' => Schema::hasColumn('loan_items', 'is_consumable'),
    'loan_items.issued_at' => Schema::hasColumn('loan_items', 'issued_at'),
    'Model Loan' => class_exists(Loan::class),
    'Model LoanItem' => class_exists(LoanItem::class),
    'Inventory service' => class_exists(WorkshopLoanInventoryService::class),
    'Controller loan' => class_exists(\App\Http\Controllers\WorkshopLoanController::class),
    'Controller return' => class_exists(\App\Http\Controllers\WorkshopLoanReturnController::class),
    'View loan index' => View::exists('loans.index'),
    'View loan create' => View::exists('loans.create'),
    'View loan show' => View::exists('loans.show'),
    'View return index' => View::exists('loans.returns.index'),
    'View return form' => View::exists('loans.returns.form'),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 46).': '.($valid ? 'OK' : 'GAGAL').PHP_EOL;
    if (! $valid) $failed = true;
}

echo "\nROUTE ACTION PEMINJAMAN\n";
echo "-----------------------\n";

$routeActions = [
    'loans.index' => 'WorkshopLoanController@index',
    'loans.create' => 'WorkshopLoanController@create',
    'loans.store' => 'WorkshopLoanController@store',
    'loans.approve' => 'WorkshopLoanController@approve',
    'loans.checkout' => 'WorkshopLoanController@checkout',
    'loans.show' => 'WorkshopLoanController@show',
    'loans.returns.index' => 'WorkshopLoanReturnController@index',
    'loans.return-form' => 'WorkshopLoanReturnController@form',
    'loans.return' => 'WorkshopLoanReturnController@process',
];

foreach ($routeActions as $name => $expected) {
    $action = Route::getRoutes()->getByName($name)?->getActionName();
    $valid = is_string($action) && str_contains($action, $expected);

    echo str_pad($name, 46).': '.($valid ? 'OK ' : 'GAGAL ').($action ?? '-').PHP_EOL;
    if (! $valid) $failed = true;
}

echo "\nROUTE ACTION BARANG KELUAR\n";
echo "--------------------------\n";

foreach (['stock-issues.index', 'stock-issues.create', 'stock-issues.store'] as $name) {
    $action = Route::getRoutes()->getByName($name)?->getActionName();
    $valid = is_string($action) && str_contains($action, 'WorkshopStockIssueController@');

    echo str_pad($name, 46).': '.($valid ? 'OK ' : 'WARN ').($action ?? '-').PHP_EOL;
    if (! $valid) $warnings++;
}

echo "\nSTOK TERSEDIA PER TOOLMAN\n";
echo "-------------------------\n";

$service = app(WorkshopLoanInventoryService::class);

foreach (
    User::query()->withoutGlobalScopes()
        ->where('role', 'toolman')->whereNotNull('workshop_id')
        ->orderBy('workshop_id')->get()
    as $toolman
) {
    $request = Request::create('/loans/create', 'GET');
    $request->setUserResolver(static fn () => $toolman);

    try {
        $workshop = $service->selectedWorkshop($request);
        $items = $service->items((int) $workshop->id);
        $assets = $service->assets((int) $workshop->id);

        echo str_pad($toolman->username.' / '.$workshop->code, 30)
            .': barang='.$items->count().', unit tersedia='.$assets->count().PHP_EOL;
    } catch (Throwable $exception) {
        echo str_pad($toolman->username, 30).': GAGAL '.$exception->getMessage().PHP_EOL;
        $failed = true;
    }
}

echo "\nKONSISTENSI STOK MASTER\n";
echo "-----------------------\n";

$toolMismatch = [];

foreach (Item::query()->withoutGlobalScopes()->where('type', 'tool')->get() as $item) {
    $available = ItemAsset::query()->withoutGlobalScopes()
        ->where('item_id', $item->id)
        ->where('is_active', true)
        ->where('status', ItemAsset::STATUS_AVAILABLE)
        ->count();

    if (abs((float) $item->stock - $available) > 0.000001) {
        $toolMismatch[] = "{$item->code} {$item->name}: master={$item->stock}, unit={$available}";
    }
}

echo str_pad('Alat: stock = unit tersedia', 46).': '
    .($toolMismatch === [] ? 'OK' : 'WARN')
    .' selisih='.count($toolMismatch).PHP_EOL;

if ($toolMismatch !== []) {
    $warnings++;
    foreach (array_slice($toolMismatch, 0, 20) as $line) echo "  - {$line}\n";
}

$materialMismatch = [];

foreach (Item::query()->withoutGlobalScopes()->where('type', 'material')->get() as $item) {
    $ledger = ItemStockMovement::query()->withoutGlobalScopes()
        ->where('item_id', $item->id)->get()
        ->sum(function (ItemStockMovement $movement): float {
            $quantity = abs((float) $movement->quantity);

            return match ((string) $movement->type) {
                'initial', 'incoming', 'adjustment_in', 'return' => $quantity,
                'outgoing', 'adjustment_out', 'loan' => -$quantity,
                default => (float) $movement->stock_after
                    - (float) $movement->stock_before,
            };
        });

    $ledger = round(max(0, $ledger), 3);

    if (abs((float) $item->stock - $ledger) > 0.000001) {
        $materialMismatch[] = "{$item->code} {$item->name}: master={$item->stock}, ledger={$ledger}";
    }
}

echo str_pad('Bahan: stock = ledger movement', 46).': '
    .($materialMismatch === [] ? 'OK' : 'WARN')
    .' selisih='.count($materialMismatch).PHP_EOL;

if ($materialMismatch !== []) {
    $warnings++;
    foreach (array_slice($materialMismatch, 0, 20) as $line) echo "  - {$line}\n";
}

echo "\nKONSISTENSI PEMINJAMAN\n";
echo "----------------------\n";

$legacy = LoanItem::query()
    ->where('is_consumable', false)
    ->whereNull('item_asset_id')
    ->whereNull('returned_at')->count();

echo str_pad('Loan alat aktif tanpa item_asset_id', 46).': '
    .($legacy === 0 ? 'OK' : 'WARN').' total='.$legacy.PHP_EOL;

if ($legacy > 0) $warnings++;

$wrongBorrowed = LoanItem::query()
    ->join('loans', 'loans.id', '=', 'loan_items.loan_id')
    ->join('item_assets', 'item_assets.id', '=', 'loan_items.item_asset_id')
    ->where('loan_items.is_consumable', false)
    ->whereNull('loan_items.returned_at')
    ->whereIn('loans.status', [Loan::STATUS_BORROWED, Loan::STATUS_PARTIAL])
    ->where('item_assets.status', '!=', ItemAsset::STATUS_BORROWED)
    ->count();

echo str_pad('Unit loan aktif berstatus borrowed', 46).': '
    .($wrongBorrowed === 0 ? 'OK' : 'GAGAL').' salah='.$wrongBorrowed.PHP_EOL;
if ($wrongBorrowed > 0) $failed = true;

$wrongReserved = LoanItem::query()
    ->join('loans', 'loans.id', '=', 'loan_items.loan_id')
    ->join('item_assets', 'item_assets.id', '=', 'loan_items.item_asset_id')
    ->where('loan_items.is_consumable', false)
    ->where('loans.status', Loan::STATUS_APPROVED)
    ->where('item_assets.status', '!=', ItemAsset::STATUS_RESERVED)
    ->count();

echo str_pad('Unit approved berstatus reserved', 46).': '
    .($wrongReserved === 0 ? 'OK' : 'GAGAL').' salah='.$wrongReserved.PHP_EOL;
if ($wrongReserved > 0) $failed = true;

$badConsumable = LoanItem::query()
    ->where('is_consumable', true)
    ->whereNotNull('issued_at')
    ->where(function ($query): void {
        $query->whereNull('returned_at')->orWhere('return_status', '!=', 'consumed');
    })->count();

echo str_pad('Bahan issued ditandai consumed', 46).': '
    .($badConsumable === 0 ? 'OK' : 'GAGAL').' salah='.$badConsumable.PHP_EOL;
if ($badConsumable > 0) $failed = true;

$duplicates = DB::query()->fromSub(
    LoanItem::query()
        ->select('item_asset_id', DB::raw('COUNT(*) AS total'))
        ->whereNotNull('item_asset_id')
        ->whereNull('returned_at')
        ->groupBy('item_asset_id')
        ->havingRaw('COUNT(*) > 1'),
    'duplicates'
)->count();

echo str_pad('Unit tidak dipinjam ganda', 46).': '
    .($duplicates === 0 ? 'OK' : 'GAGAL').' duplikat='.$duplicates.PHP_EOL;
if ($duplicates > 0) $failed = true;

echo "\nRINGKASAN ALUR\n";
echo "---------------\n";
echo "Alat approve   : available -> reserved, stok tetap\n";
echo "Alat checkout  : reserved -> borrowed, stok berkurang\n";
echo "Alat return    : borrowed -> available, stok bertambah\n";
echo "Bahan checkout : movement outgoing, stok berkurang permanen\n";
echo "Barang Keluar  : alat/bahan berkurang permanen\n";

echo "\nSTATUS\n";
echo "------\n";
echo "FAIL : ".($failed ? 'YA' : 'TIDAK').PHP_EOL;
echo "WARN : {$warnings}\n\n";

echo $failed
    ? "COMPLETE INVENTORY & LOAN FLOW BELUM VALID.\n"
    : ($warnings > 0
        ? "FLOW UTAMA VALID, TETAPI ADA DATA LAMA/SELISIH YANG PERLU DITINJAU.\n"
        : "COMPLETE INVENTORY & LOAN FLOW SUDAH VALID.\n");

exit($failed ? 1 : 0);
