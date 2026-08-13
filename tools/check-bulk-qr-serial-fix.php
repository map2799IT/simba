<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$checks = [
    'PNG QR generator' =>
        method_exists(
            \App\Support\QrCodeDataUriGenerator::class,
            'pngDataUri'
        ),

    'QR service PNG method' =>
        method_exists(
            \App\Services\ItemAssetQrCodeService::class,
            'pngDataUri'
        ),

    'Asset serial service' =>
        class_exists(
            \App\Services\AssetSerialNumberService::class
        ),

    'Serial provider' =>
        class_exists(
            \App\Providers\ItemAssetSerialServiceProvider::class
        ),

    'Backfill command' =>
        class_exists(
            \App\Console\Commands\BackfillItemAssetSerialNumbers::class
        ),

    'View QR massal' =>
        View::exists(
            'item-assets.bulk-qr-print'
        ),

    'Tabel item_assets' =>
        Schema::hasTable(
            'item_assets'
        ),

    'Kolom serial_number' =>
        Schema::hasColumn(
            'item_assets',
            'serial_number'
        ),
];

$failed = false;

echo "SIMBA QR PDF & SERIAL CHECK\n";
echo "===========================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 36).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

if (
    Schema::hasTable('item_assets')
    && Schema::hasColumn(
        'item_assets',
        'serial_number'
    )
) {
    $emptySerials = DB::table(
        'item_assets'
    )
        ->where(
            function ($query): void {
                $query
                    ->whereNull(
                        'serial_number'
                    )
                    ->orWhere(
                        'serial_number',
                        ''
                    );
            }
        )
        ->count();

    echo str_pad(
        'Unit serial kosong',
        36
    ).
        ': '.
        $emptySerials.
        (
            $emptySerials === 0
                ? ' - OK'
                : ' - JALANKAN BACKFILL'
        ).
        PHP_EOL;
}

echo "\nDUKUNGAN RENDER PNG\n";
echo "--------------------\n";

echo 'Endroid PngWriter'.
    str_repeat(' ', 18).
    ': '.
    (
        class_exists(
            \Endroid\QrCode\Writer\PngWriter::class
        )
            ? 'OK'
            : 'TIDAK ADA'
    ).
    PHP_EOL;

echo 'SimpleSoftwareIO QR'.
    str_repeat(' ', 17).
    ': '.
    (
        class_exists(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::class
        )
            ? 'OK'
            : 'TIDAK ADA'
    ).
    PHP_EOL;

echo 'Imagick'.
    str_repeat(' ', 28).
    ': '.
    (
        class_exists(\Imagick::class)
            ? 'OK'
            : 'TIDAK ADA'
    ).
    PHP_EOL;

echo "\n".
    (
        $failed
            ? 'PERBAIKAN BELUM VALID.'
            : 'PERBAIKAN QR PDF DAN SERIAL SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
