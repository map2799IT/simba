<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

echo "SIMBA STOCK RECEIPT DIAGNOSTIC\n";
echo "==============================\n\n";

echo "Project: {$root}\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'Environment: '.app()->environment()."\n\n";

echo "VIEWS\n";
echo "-----\n";

foreach (
    [
        'stock-receipts.index',
        'stock-receipts.create',
        'item-assets.index',
        'items.index',
    ]
    as $view
) {
    echo str_pad($view, 34).
        ': '.
        (View::exists($view)
            ? 'ADA'
            : 'TIDAK ADA').
        "\n";
}

echo "\nDATABASE\n";
echo "--------\n";

foreach (
    [
        'stock_receipts',
        'stock_receipt_items',
        'stock_receipt_details',
        'stock_movements',
        'item_assets',
    ]
    as $table
) {
    $exists = Schema::hasTable($table);

    echo str_pad($table, 34).
        ': '.
        ($exists
            ? 'ADA'
            : 'TIDAK ADA');

    if ($exists) {
        try {
            echo ' | rows='.
                DB::table($table)->count();
        } catch (Throwable $exception) {
            echo ' | count error='.
                $exception->getMessage();
        }
    }

    echo "\n";
}

echo "\nCONTROLLER METHODS\n";
echo "------------------\n";

$controllers = [
    \App\Http\Controllers\StockReceiptController::class => [
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
        'post',
        'cancel',
    ],

    \App\Http\Controllers\ItemAssetController::class => [
        'index',
        'show',
        'edit',
        'update',
        'label',
        'create',
        'store',
    ],
];

foreach ($controllers as $controller => $methods) {
    echo $controller."\n";

    if (! class_exists($controller)) {
        echo "  CLASS TIDAK ADA\n";
        continue;
    }

    foreach ($methods as $method) {
        echo '  '.
            str_pad($method, 18).
            ': '.
            (method_exists($controller, $method)
                ? 'ADA'
                : 'TIDAK ADA').
            "\n";
    }
}

echo "\nROUTES\n";
echo "------\n";

foreach (
    [
        'stock-receipts.index',
        'stock-receipts.create',
        'stock-receipts.store',
        'item-assets.index',
        'item-assets.create',
        'item-assets.store',
        'admin.users.index',
        'admin.access.index',
        'admin.audit-logs.index',
    ]
    as $name
) {
    echo str_pad($name, 34).
        ': '.
        (Route::has($name)
            ? 'ADA'
            : 'TIDAK ADA').
        "\n";
}

echo "\nPOSSIBLE SIDEBAR FILES\n";
echo "----------------------\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $root.'/resources/views',
        FilesystemIterator::SKIP_DOTS
    )
);

foreach ($iterator as $file) {
    if (
        ! $file->isFile()
        || ! str_ends_with(
            $file->getFilename(),
            '.blade.php'
        )
    ) {
        continue;
    }

    $contents = file_get_contents(
        $file->getPathname()
    );

    if (
        $contents !== false
        && (
            str_contains($contents, 'Barang Masuk')
            || str_contains($contents, 'Data Alat')
            || str_contains($contents, 'Audit Log')
        )
    ) {
        echo str_replace(
            $root.DIRECTORY_SEPARATOR,
            '',
            $file->getPathname()
        )."\n";
    }
}

echo "\nSelesai.\n";
