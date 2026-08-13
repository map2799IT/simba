<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$checks = [
    'File .env tersedia' =>
        is_file($root.'/.env'),

    'Vendor autoload tersedia' =>
        is_file(
            $root.
            '/vendor/autoload.php'
        ),

    'Vite manifest tersedia' =>
        is_file(
            $root.
            '/public/build/manifest.json'
        ),

    'Storage writable' =>
        is_writable(
            $root.'/storage'
        ),

    'Bootstrap cache writable' =>
        is_writable(
            $root.'/bootstrap/cache'
        ),
];

$failed = false;

echo "SIMBA HOSTING DEPLOYMENT CHECK\n";
echo "==============================\n\n";

foreach ($checks as $label => $valid) {
    echo str_pad($label, 34).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'DEPLOYMENT HOSTING BELUM VALID.'
            : 'DEPLOYMENT HOSTING SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
