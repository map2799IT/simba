<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = realpath(dirname(__DIR__));

if ($root === false) {
    fwrite(STDERR, "Root project tidak dapat ditemukan.\n");
    exit(1);
}

$output = $root.
    DIRECTORY_SEPARATOR.
    'storage'.
    DIRECTORY_SEPARATOR.
    'app'.
    DIRECTORY_SEPARATOR.
    'simba-debug-bundle';

$zipPath = $root.
    DIRECTORY_SEPARATOR.
    'storage'.
    DIRECTORY_SEPARATOR.
    'app'.
    DIRECTORY_SEPARATOR.
    'simba-debug-bundle.zip';

require $root.
    DIRECTORY_SEPARATOR.
    'vendor'.
    DIRECTORY_SEPARATOR.
    'autoload.php';

$app = require $root.
    DIRECTORY_SEPARATOR.
    'bootstrap'.
    DIRECTORY_SEPARATOR.
    'app.php';

$app->make(Kernel::class)->bootstrap();

/**
 * Menghapus folder secara rekursif.
 */
function removeDirectory(
    string $directory
): void {
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }

    @rmdir($directory);
}

/**
 * Membuat path relatif yang aman untuk Windows dan Linux.
 */
function relativePath(
    string $root,
    string $absolutePath
): string {
    $normalizedRoot = rtrim(
        str_replace('\\', '/', $root),
        '/'
    );

    $normalizedPath = str_replace(
        '\\',
        '/',
        $absolutePath
    );

    if (
        str_starts_with(
            strtolower($normalizedPath),
            strtolower($normalizedRoot).'/'
        )
    ) {
        return ltrim(
            substr(
                $normalizedPath,
                strlen($normalizedRoot)
            ),
            '/'
        );
    }

    return basename($absolutePath);
}

/**
 * Menyalin file sambil mempertahankan struktur folder relatif.
 */
function copyRelativeFile(
    string $root,
    string $output,
    string $source
): void {
    if (! is_file($source)) {
        return;
    }

    $relative = relativePath(
        $root,
        $source
    );

    $target = $output.
        DIRECTORY_SEPARATOR.
        str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relative
        );

    $targetDirectory = dirname($target);

    if (! is_dir($targetDirectory)) {
        mkdir(
            $targetDirectory,
            0777,
            true
        );
    }

    if (! copy($source, $target)) {
        throw new RuntimeException(
            "Gagal menyalin {$source}"
        );
    }
}

removeDirectory($output);

if (is_file($zipPath)) {
    @unlink($zipPath);
}

mkdir(
    $output,
    0777,
    true
);

$coreFiles = [
    'routes/web.php',

    'app/Http/Controllers/StockReceiptController.php',
    'app/Http/Controllers/ItemAssetController.php',
    'app/Http/Controllers/ItemController.php',

    'app/Models/StockReceipt.php',
    'app/Models/StockReceiptItem.php',
    'app/Models/StockReceiptDetail.php',
    'app/Models/StockMovement.php',
    'app/Models/ItemAsset.php',
    'app/Models/Item.php',

    'app/Services/StockReceiptInventoryService.php',
    'app/Services/BulkItemAssetService.php',
    'app/Services/AssetNumberService.php',

    'resources/views/stock-receipts/index.blade.php',
    'resources/views/stock-receipts/create.blade.php',

    'resources/views/items/index.blade.php',
    'resources/views/items/show.blade.php',

    'resources/views/item-assets/index.blade.php',
    'resources/views/item-assets/show.blade.php',
    'resources/views/item-assets/edit.blade.php',
    'resources/views/item-assets/label.blade.php',

    'resources/views/layouts/app.blade.php',
];

foreach ($coreFiles as $relative) {
    copyRelativeFile(
        $root,
        $output,
        $root.
            DIRECTORY_SEPARATOR.
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            )
    );
}

/*
|--------------------------------------------------------------------------
| Cari file relevan tambahan
|--------------------------------------------------------------------------
*/

$searchRoots = [
    $root.
        DIRECTORY_SEPARATOR.
        'app',

    $root.
        DIRECTORY_SEPARATOR.
        'resources'.
        DIRECTORY_SEPARATOR.
        'views',

    $root.
        DIRECTORY_SEPARATOR.
        'database'.
        DIRECTORY_SEPARATOR.
        'migrations',
];

$patterns = [
    'StockReceipt',
    'stock_receipt',
    'stock-receipt',
    'Barang Masuk',
    'item_assets',
    'ItemAsset',
];

foreach ($searchRoots as $searchRoot) {
    if (! is_dir($searchRoot)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $searchRoot,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $extension = strtolower(
            $file->getExtension()
        );

        if (
            ! in_array(
                $extension,
                [
                    'php',
                    'blade.php',
                ],
                true
            )
            && ! str_ends_with(
                strtolower(
                    $file->getFilename()
                ),
                '.blade.php'
            )
        ) {
            continue;
        }

        $contents = @file_get_contents(
            $file->getPathname()
        );

        if ($contents === false) {
            continue;
        }

        $matched = false;

        foreach ($patterns as $pattern) {
            if (
                stripos(
                    $contents,
                    $pattern
                ) !== false
            ) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            continue;
        }

        copyRelativeFile(
            $root,
            $output,
            $file->getPathname()
        );
    }
}

/*
|--------------------------------------------------------------------------
| Ringkasan view dan controller
|--------------------------------------------------------------------------
*/

$summary = '';

$summary .=
    "SIMBA DEBUG SUMMARY\n".
    "===================\n\n";

$summary .=
    "Project: {$root}\n".
    'PHP: '.PHP_VERSION."\n".
    'Environment: '.app()->environment()."\n\n";

$summary .=
    "VIEWS\n".
    "-----\n";

foreach (
    [
        'stock-receipts.index',
        'stock-receipts.create',
        'item-assets.index',
        'items.index',
    ]
    as $view
) {
    $summary .=
        str_pad($view, 34).
        ': '.
        (
            View::exists($view)
                ? 'ADA'
                : 'TIDAK ADA'
        ).
        "\n";
}

$summary .=
    "\nCONTROLLER METHODS\n".
    "------------------\n";

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
    $summary .= $controller."\n";

    if (! class_exists($controller)) {
        $summary .= "  CLASS TIDAK ADA\n";
        continue;
    }

    foreach ($methods as $method) {
        $summary .=
            '  '.
            str_pad($method, 18).
            ': '.
            (
                method_exists(
                    $controller,
                    $method
                )
                    ? 'ADA'
                    : 'TIDAK ADA'
            ).
            "\n";
    }
}

file_put_contents(
    $output.
        DIRECTORY_SEPARATOR.
        'diagnostic-summary.txt',
    $summary
);

/*
|--------------------------------------------------------------------------
| Daftar tabel dan schema database
|--------------------------------------------------------------------------
*/

$databaseName = DB::connection()
    ->getDatabaseName();

$tableRows = DB::select(
    'SHOW TABLES'
);

$tableKey = 'Tables_in_'.$databaseName;

$tables = [];

foreach ($tableRows as $row) {
    $array = (array) $row;

    $tableName =
        $array[$tableKey]
        ?? array_values($array)[0]
        ?? null;

    if (is_string($tableName)) {
        $tables[] = $tableName;
    }
}

sort($tables);

file_put_contents(
    $output.
        DIRECTORY_SEPARATOR.
        'database-tables.txt',
    implode(PHP_EOL, $tables).
    PHP_EOL
);

$schemaText = '';

foreach ($tables as $table) {
    if (
        preg_match(
            '/stock|receipt|movement|item_asset|inventory|transaction|incoming/i',
            $table
        ) !== 1
    ) {
        continue;
    }

    $schemaText .=
        "TABLE {$table}\n".
        str_repeat('=', 72).
        "\n";

    try {
        $schemaText .=
            'ROWS: '.
            DB::table($table)->count().
            "\n";
    } catch (Throwable $exception) {
        $schemaText .=
            'ROWS ERROR: '.
            $exception->getMessage().
            "\n";
    }

    foreach (
        Schema::getColumns($table)
        as $column
    ) {
        $schemaText .=
            ($column['name'] ?? '-').
            ' | '.
            ($column['type'] ?? '-').
            ' | nullable='.
            (
                ($column['nullable'] ?? false)
                    ? 'yes'
                    : 'no'
            ).
            ' | default='.
            (
                array_key_exists(
                    'default',
                    $column
                )
                    ? var_export(
                        $column['default'],
                        true
                    )
                    : '-'
            ).
            "\n";
    }

    $schemaText .= "\n";
}

if ($schemaText === '') {
    $schemaText =
        "Tidak ada tabel dengan nama yang cocok dengan ".
        "stock/receipt/movement/item_asset/inventory/transaction/incoming.\n";
}

file_put_contents(
    $output.
        DIRECTORY_SEPARATOR.
        'database-relevant-schema.txt',
    $schemaText
);

/*
|--------------------------------------------------------------------------
| Daftar route relevan
|--------------------------------------------------------------------------
*/

$routeText = '';

foreach (
    Route::getRoutes()
    as $route
) {
    $name = $route->getName() ?? '';

    if (
        ! str_contains(
            $name,
            'stock-receipts'
        )
        && ! str_contains(
            $name,
            'item-assets'
        )
        && ! str_contains(
            $route->uri(),
            'stock-receipts'
        )
        && ! str_contains(
            $route->uri(),
            'item-assets'
        )
    ) {
        continue;
    }

    $routeText .=
        implode(
            '|',
            $route->methods()
        ).
        ' | '.
        $route->uri().
        ' | '.
        ($name !== '' ? $name : '-').
        ' | '.
        $route->getActionName().
        "\n";
}

file_put_contents(
    $output.
        DIRECTORY_SEPARATOR.
        'relevant-routes.txt',
    $routeText
);

/*
|--------------------------------------------------------------------------
| Buat ZIP otomatis
|--------------------------------------------------------------------------
*/

if (! class_exists(ZipArchive::class)) {
    echo "Folder debug berhasil dibuat:\n{$output}\n";
    echo "Extension ZipArchive tidak tersedia.\n";
    echo "Zip folder tersebut secara manual.\n";
    exit(0);
}

$zip = new ZipArchive();

if (
    $zip->open(
        $zipPath,
        ZipArchive::CREATE
        | ZipArchive::OVERWRITE
    ) !== true
) {
    fwrite(
        STDERR,
        "Gagal membuat ZIP: {$zipPath}\n"
    );

    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $output,
        FilesystemIterator::SKIP_DOTS
    )
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relative = relativePath(
        $output,
        $file->getPathname()
    );

    $zip->addFile(
        $file->getPathname(),
        $relative
    );
}

$zip->close();

echo "Debug bundle berhasil dibuat:\n";
echo "{$zipPath}\n";
