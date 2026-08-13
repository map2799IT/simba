<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

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

$viewRoot = $root.
    DIRECTORY_SEPARATOR.
    'resources'.
    DIRECTORY_SEPARATOR.
    'views';

$usedRoutes = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $viewRoot,
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

    if ($contents === false) {
        continue;
    }

    preg_match_all(
        '/\broute\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        $contents,
        $matches
    );

    foreach (
        array_unique(
            $matches[1] ?? []
        )
        as $routeName
    ) {
        $usedRoutes[$routeName][] =
            str_replace(
                $root.
                    DIRECTORY_SEPARATOR,
                '',
                $file->getPathname()
            );
    }
}

ksort($usedRoutes);

$missing = [];

foreach ($usedRoutes as $name => $files) {
    if (! Route::has($name)) {
        $missing[$name] = $files;
    }
}

echo "SIMBA BLADE ROUTE CHECK\n";
echo "=======================\n\n";

echo 'Route literal pada Blade: '.
    count($usedRoutes).
    PHP_EOL;

echo 'Route belum terdaftar: '.
    count($missing).
    PHP_EOL.
    PHP_EOL;

if ($missing === []) {
    echo "Semua route literal Blade sudah terdaftar.\n";
    exit(0);
}

foreach ($missing as $name => $files) {
    echo "[MISSING] {$name}\n";

    foreach (
        array_unique($files)
        as $file
    ) {
        echo "  - {$file}\n";
    }

    echo PHP_EOL;
}

exit(1);
