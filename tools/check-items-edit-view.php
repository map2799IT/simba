<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA ITEMS EDIT VIEW CHECK\n";
echo "===========================\n\n";

$checks = [
    'View items.edit tersedia' =>
        View::exists('items.edit'),

    'Route items.edit tersedia' =>
        Route::has('items.edit'),

    'Route items.update tersedia' =>
        Route::has('items.update'),

    'Route items.index tersedia' =>
        Route::has('items.index'),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 38).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$file =
    $root.
    '/resources/views/items/edit.blade.php';

$validFile =
    is_file($file)
    && filesize($file) > 0;

echo str_pad(
    'File edit.blade.php',
    38
).
    ': '.
    ($validFile ? 'OK' : 'GAGAL').
    PHP_EOL;

if (! $validFile) {
    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'ITEMS EDIT VIEW BELUM VALID.'
            : 'ITEMS EDIT VIEW SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
