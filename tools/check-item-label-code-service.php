<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$file = $root.
    '/app/Services/ItemLabelCodeService.php';

if (! is_file($file)) {
    fwrite(
        STDERR,
        "File tidak ditemukan: {$file}\n"
    );

    exit(1);
}

$source = file_get_contents($file);

if ($source === false) {
    fwrite(
        STDERR,
        "Gagal membaca file.\n"
    );

    exit(1);
}

$methods = [
    'qrSvg',
    'qrDataUri',
    'barcodeDataUri',
];

$failed = false;

foreach ($methods as $method) {
    $count = preg_match_all(
        '/function\s+'.
        preg_quote($method, '/').
        '\s*\(/i',
        $source
    );

    echo str_pad($method, 20).
        ': '.
        $count.
        PHP_EOL;

    if ($count > 1) {
        $failed = true;
    }
}

if ($failed) {
    fwrite(
        STDERR,
        "Masih ada method duplikat.\n"
    );

    exit(1);
}

echo "Tidak ada method duplikat.\n";
