<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA PAGINATION UI CHECK\n";
echo "========================\n\n";

$views = [
    'vendor.pagination.tailwind',
    'vendor.pagination.bootstrap-5',
    'vendor.pagination.bootstrap-4',
    'vendor.pagination.default',
    'vendor.pagination.simple-tailwind',
    'vendor.pagination.simple-bootstrap-5',
    'vendor.pagination.simple-bootstrap-4',
    'vendor.pagination.simple-default',
];

foreach ($views as $view) {
    $available = View::exists($view);

    echo str_pad($view, 48).
        ': '.
        ($available ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $available) {
        $failed = true;
    }
}

$files = glob(
    $root.'/resources/views/vendor/pagination/*.blade.php'
) ?: [];

$badFiles = [];

foreach ($files as $file) {
    $contents = file_get_contents($file);

    if (! is_string($contents)) {
        $badFiles[] = basename($file).' tidak dapat dibaca';
        continue;
    }

    $isValid =
        str_contains($contents, 'class="pagination')
        && str_contains($contents, 'class="page-link"')
        && ! str_contains($contents, '<svg')
        && ! str_contains($contents, 'w-5')
        && ! str_contains($contents, 'h-5');

    if (! $isValid) {
        $badFiles[] = basename($file);
    }
}

echo "\n".
    str_pad('Template tanpa SVG Tailwind besar', 48).
    ': '.
    ($badFiles === [] ? 'OK' : 'GAGAL').
    PHP_EOL;

if ($badFiles !== []) {
    echo 'File bermasalah: '.
        implode(', ', $badFiles).
        PHP_EOL;

    $failed = true;
}

$fullFile =
    $root.
    '/resources/views/vendor/pagination/tailwind.blade.php';

$fullContents =
    is_file($fullFile)
        ? file_get_contents($fullFile)
        : '';

$localized =
    is_string($fullContents)
    && str_contains($fullContents, 'Menampilkan')
    && str_contains($fullContents, 'Sebelumnya')
    && str_contains($fullContents, 'Berikutnya')
    && str_contains($fullContents, 'data');

echo str_pad('Teks pagination Bahasa Indonesia', 48).
    ': '.
    ($localized ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $localized) {
    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'PAGINATION UI BELUM VALID.'
            : 'PAGINATION UI SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
