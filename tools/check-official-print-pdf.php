<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$checks = [
    'OfficialDocumentService' =>
        class_exists(
            \App\Services\OfficialDocumentService::class
        ),

    'QR massal resmi' =>
        View::exists(
            'item-assets.bulk-qr-print'
        ),

    'QR tunggal resmi' =>
        View::exists(
            'item-assets.label'
        ),

    'Inventaris lokasi resmi' =>
        View::exists(
            'locations.inventory-print'
        ),

    'Laporan inventaris resmi' =>
        View::exists(
            'reports.inventory-pdf'
        ),

    'Laporan stok resmi' =>
        View::exists(
            'reports.stock-pdf'
        ),

    'Laporan peminjaman resmi' =>
        View::exists(
            'reports.loans-pdf'
        ),

    'Laporan kerusakan resmi' =>
        View::exists(
            'reports.damages-pdf'
        ),

    'Laporan pergerakan resmi' =>
        View::exists(
            'reports.stock-movements-pdf'
        ),

    'Partial tanda tangan' =>
        View::exists(
            'prints.official-signatures'
        ),
];

$failed = false;

echo "SIMBA OFFICIAL PRINT/PDF CHECK\n";
echo "==============================\n\n";

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
            ? 'TEMPLATE RESMI BELUM LENGKAP.'
            : 'SEMUA TEMPLATE PRINT/PDF RESMI SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
