<?php

declare(strict_types=1);

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\StoreStockReceiptRequest;
use App\Models\Item;
use App\Services\ItemCodeService;
use App\Services\StockReceiptCodeService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA MASTER CATALOG & RECEIPT DETAIL CHECK\n";
echo "===========================================\n\n";

$column = DB::table('information_schema.COLUMNS')
    ->where(
        'TABLE_SCHEMA',
        DB::connection()->getDatabaseName()
    )
    ->where('TABLE_NAME', 'items')
    ->where('COLUMN_NAME', 'workshop_id')
    ->first();

$checks = [
    'StoreItemRequest tersedia' =>
        class_exists(StoreItemRequest::class),

    'StoreStockReceiptRequest tersedia' =>
        class_exists(StoreStockReceiptRequest::class),

    'ItemCodeService tersedia' =>
        class_exists(ItemCodeService::class),

    'StockReceiptCodeService tersedia' =>
        class_exists(StockReceiptCodeService::class),

    'View master create' =>
        View::exists('items.create'),

    'View master edit' =>
        View::exists('items.edit'),

    'View receipt create' =>
        View::exists('stock-receipts.create'),

    'View receipt row' =>
        View::exists('stock-receipts._receipt-row'),

    'View receipt index' =>
        View::exists('stock-receipts.index'),

    'items.workshop_id nullable' =>
        $column !== null
        && ($column->IS_NULLABLE ?? null) === 'YES',
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

foreach ([
    'receipt_code',
    'workshop_id',
    'storage_location_id',
    'brand',
    'model',
    'specification',
    'fund_source',
    'unit_price',
    'condition',
    'photo_path',
] as $columnName) {
    $valid = Schema::hasColumn(
        'item_stock_movements',
        $columnName
    );

    echo str_pad(
        "movement.{$columnName}",
        44
    ).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$itemCreateFile =
    $root.'/resources/views/items/create.blade.php';

$itemCreate = is_file($itemCreateFile)
    ? file_get_contents($itemCreateFile)
    : '';

$masterFormValid =
    is_string($itemCreate)
    && str_contains($itemCreate, 'name="name"')
    && str_contains($itemCreate, 'name="item_category_id"')
    && str_contains($itemCreate, 'name="unit_id"')
    && ! str_contains($itemCreate, 'name="brand"')
    && ! str_contains($itemCreate, 'name="model"')
    && ! str_contains($itemCreate, 'name="specification"');

echo str_pad(
    'Master hanya nama/kategori/satuan',
    44
).
    ': '.
    ($masterFormValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $masterFormValid) {
    $failed = true;
}

$receiptRowFile =
    $root.
    '/resources/views/stock-receipts/_receipt-row.blade.php';

$receiptRow = is_file($receiptRowFile)
    ? file_get_contents($receiptRowFile)
    : '';

$receiptDetailsValid =
    is_string($receiptRow)
    && str_contains($receiptRow, "'brand'")
    && str_contains($receiptRow, "'model'")
    && str_contains($receiptRow, "'specification'");

echo str_pad(
    'Receipt punya merek/model/spesifikasi',
    44
).
    ': '.
    ($receiptDetailsValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $receiptDetailsValid) {
    $failed = true;
}

try {
    $masterCode = app(ItemCodeService::class)
        ->generate('tool');

    $masterValid =
        preg_match('/^ALT-\d{4,}$/', $masterCode) === 1
        && preg_match('/^ALT-\d{4}-\d+$/', $masterCode) !== 1;

    echo str_pad('Format kode master', 44).
        ': '.
        ($masterValid
            ? $masterCode.' - OK'
            : $masterCode.' - GAGAL').
        PHP_EOL;

    if (! $masterValid) {
        $failed = true;
    }
} catch (Throwable $exception) {
    echo str_pad('Format kode master', 44).
        ": GAGAL\n";

    echo $exception->getMessage().PHP_EOL;
    $failed = true;
}

try {
    $receiptCode = app(StockReceiptCodeService::class)
        ->generate('tool', now());

    $year = now()->format('Y');

    $receiptValid = preg_match(
        '/^ALT-'.preg_quote($year, '/').'-\d{4,}$/',
        $receiptCode
    ) === 1;

    echo str_pad('Format kode Barang Masuk', 44).
        ': '.
        ($receiptValid
            ? $receiptCode.' - OK'
            : $receiptCode.' - GAGAL').
        PHP_EOL;

    if (! $receiptValid) {
        $failed = true;
    }
} catch (Throwable $exception) {
    echo str_pad('Format kode Barang Masuk', 44).
        ": GAGAL\n";

    echo $exception->getMessage().PHP_EOL;
    $failed = true;
}

$routeBindingValid = method_exists(
    Item::class,
    'resolveRouteBindingQuery'
);

echo str_pad('Global master route binding', 44).
    ': '.
    ($routeBindingValid ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $routeBindingValid) {
    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'MASTER CATALOG & RECEIPT DETAIL BELUM VALID.'
            : 'MASTER CATALOG & RECEIPT DETAIL SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
