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

$layoutFile = $root.
    DIRECTORY_SEPARATOR.
    'resources'.
    DIRECTORY_SEPARATOR.
    'views'.
    DIRECTORY_SEPARATOR.
    'layouts'.
    DIRECTORY_SEPARATOR.
    'app.blade.php';

$layout = is_file($layoutFile)
    ? file_get_contents($layoutFile)
    : '';

$checks = [
    'View layouts.sidebar' =>
        View::exists('layouts.sidebar'),

    'Layout include sidebar' =>
        is_string($layout)
        && str_contains(
            $layout,
            "@include('layouts.sidebar')"
        ),

    'Route dashboard' =>
        Route::has('dashboard'),

    'Route items.index' =>
        Route::has('items.index'),

    'Route item-assets.index' =>
        Route::has('item-assets.index'),

    'Route loans.index' =>
        Route::has('loans.index'),

    'Route reports.inventory' =>
        Route::has('reports.inventory'),

    'Route profile.edit' =>
        Route::has('profile.edit'),
];

$failed = false;

echo "SIMBA ROLE SIDEBAR CHECK\n";
echo "========================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 34).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\nROLE MENU\n";
echo "---------\n";
echo "admin            : semua menu\n";
echo "kepala_bengkel   : monitoring satu jurusan\n";
echo "toolman          : operasional satu jurusan\n";
echo "guru             : seluruh jurusan, loan milik sendiri\n";
echo "siswa            : satu jurusan, loan milik sendiri\n";

echo "\n".
    (
        $failed
            ? 'PEMERIKSAAN SIDEBAR GAGAL.'
            : 'SIDEBAR SIAP DIGUNAKAN.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
