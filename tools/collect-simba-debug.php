<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);
$output = $root.'/storage/app/simba-debug-bundle';

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (is_dir($output)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $output,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($output);
}

mkdir($output, 0777, true);

$files = [
    'routes/web.php',
    'app/Http/Controllers/StockReceiptController.php',
    'app/Http/Controllers/ItemAssetController.php',
    'app/Models/StockReceipt.php',
    'app/Models/StockReceiptItem.php',
    'app/Models/ItemAsset.php',
    'resources/views/stock-receipts/index.blade.php',
    'resources/views/stock-receipts/create.blade.php',
    'resources/views/items/index.blade.php',
    'resources/views/items/show.blade.php',
    'resources/views/item-assets/index.blade.php',
    'resources/views/layouts/app.blade.php',
];

foreach ($files as $relative) {
    $source = $root.'/'.$relative;

    if (! is_file($source)) {
        continue;
    }

    $target = $output.'/'.$relative;
    @mkdir(dirname($target), 0777, true);
    copy($source, $target);
}

$sidebarIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $root.'/resources/views',
        FilesystemIterator::SKIP_DOTS
    )
);

foreach ($sidebarIterator as $file) {
    if (
        ! $file->isFile()
        || ! str_ends_with(
            $file->getFilename(),
            '.blade.php'
        )
    ) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());

    if (
        $contents === false
        || ! (
            str_contains($contents, 'Barang Masuk')
            && str_contains($contents, 'Dashboard')
        )
    ) {
        continue;
    }

    $relative = str_replace(
        $root.DIRECTORY_SEPARATOR,
        '',
        $file->getPathname()
    );

    $target = $output.'/'.$relative;
    @mkdir(dirname($target), 0777, true);
    copy($file->getPathname(), $target);
}

$schema = '';

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
    $schema .= "TABLE {$table}\n";
    $schema .= str_repeat('=', 60)."\n";

    if (! Schema::hasTable($table)) {
        $schema .= "TIDAK ADA\n\n";
        continue;
    }

    foreach (
        Schema::getColumns($table)
        as $column
    ) {
        $schema .=
            ($column['name'] ?? '-').
            ' | '.
            ($column['type'] ?? '-').
            ' | nullable='.
            (($column['nullable'] ?? false)
                ? 'yes'
                : 'no').
            "\n";
    }

    $schema .= "\n";
}

file_put_contents(
    $output.'/database-schema.txt',
    $schema
);

echo "Debug bundle dibuat di:\n{$output}\n";
echo "Zip folder tersebut lalu unggah ke chat.\n";
