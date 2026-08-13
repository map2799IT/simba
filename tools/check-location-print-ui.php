<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$file = $root.
    DIRECTORY_SEPARATOR.
    'resources'.
    DIRECTORY_SEPARATOR.
    'views'.
    DIRECTORY_SEPARATOR.
    'locations'.
    DIRECTORY_SEPARATOR.
    'inventory-print.blade.php';

$contents = is_file($file)
    ? file_get_contents($file)
    : '';

$checks = [
    'View inventory print' =>
        View::exists(
            'locations.inventory-print'
        ),

    'Layout A4 landscape' =>
        is_string($contents)
        && str_contains(
            $contents,
            'size: A4 landscape'
        ),

    'Ringkasan isi lokasi' =>
        is_string($contents)
        && str_contains(
            $contents,
            'Ringkasan Isi Lokasi'
        ),

    'Detail unit alat' =>
        is_string($contents)
        && str_contains(
            $contents,
            'Detail Unit Alat'
        ),

    'Empty state' =>
        is_string($contents)
        && str_contains(
            $contents,
            'empty-state'
        ),

    'Tanda tangan' =>
        is_string($contents)
        && str_contains(
            $contents,
            'Mengetahui,'
        ),

    'Tidak menjumlah lintas satuan' =>
        is_string($contents)
        && str_contains(
            $contents,
            'Stok tidak dijumlahkan lintas satuan'
        ),
];

$failed = false;

echo "SIMBA LOCATION PRINT UI CHECK\n";
echo "=============================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 38).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'TAMPILAN PRINT BELUM VALID.'
            : 'TAMPILAN PRINT LOKASI SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
