<?php

declare(strict_types=1);

use App\Models\ItemAsset;
use App\Services\ItemAssetQrCodeService;
use App\Support\QrCodeDataUriGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA LOCATION ACTION & QR PNG CHECK\n";
echo "====================================\n\n";

$checks = [
    'Generator payload' =>
        method_exists(
            QrCodeDataUriGenerator::class,
            'payload'
        ),

    'Generator svg' =>
        method_exists(
            QrCodeDataUriGenerator::class,
            'svg'
        ),

    'Generator dataUri' =>
        method_exists(
            QrCodeDataUriGenerator::class,
            'dataUri'
        ),

    'Generator pngDataUri' =>
        method_exists(
            QrCodeDataUriGenerator::class,
            'pngDataUri'
        ),

    'Service pngDataUri' =>
        method_exists(
            ItemAssetQrCodeService::class,
            'pngDataUri'
        ),

    'Partial aksi lokasi' =>
        View::exists(
            'locations._inventory-action-buttons'
        ),

    'Route QR massal print' =>
        Route::has(
            'item-assets.qr-bulk.print'
        ),

    'Route QR massal download' =>
        Route::has(
            'item-assets.qr-bulk.download'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 39).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$partialFile =
    $root.
    '/resources/views/locations/_inventory-action-buttons.blade.php';

$partialContents =
    is_file($partialFile)
        ? file_get_contents(
            $partialFile
        )
        : '';

$compactValid =
    is_string($partialContents)
    && str_contains(
        $partialContents,
        'simba-location-inventory-actions'
    )
    && str_contains(
        $partialContents,
        'Ringkas'
    )
    && str_contains(
        $partialContents,
        'Lengkap'
    );

echo str_pad(
    'Aksi lokasi mode ringkas',
    39
).
    ': '.
    (
        $compactValid
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $compactValid) {
    $failed = true;
}

$rendererChecks = [
    'SimpleSoftwareIO PNG' =>
        class_exists(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::class
        ),

    'Endroid PngWriter' =>
        class_exists(
            \Endroid\QrCode\Writer\PngWriter::class
        ),

    'Imagick' =>
        class_exists(
            \Imagick::class
        ),
];

echo "\nRENDERER PNG HOSTING\n";
echo "--------------------\n";

$rendererAvailable = false;

foreach (
    $rendererChecks
    as $label => $valid
) {
    echo str_pad($label, 39).
        ': '.
        ($valid ? 'TERSEDIA' : 'TIDAK ADA').
        PHP_EOL;

    if ($valid) {
        $rendererAvailable = true;
    }
}

$asset =
    ItemAsset::query()
        ->withoutGlobalScopes()
        ->where(
            'is_active',
            true
        )
        ->first();

if ($asset !== null) {
    try {
        $service =
            app(
                ItemAssetQrCodeService::class
            );

        $svg =
            $service->dataUri(
                $asset,
                180
            );

        $png =
            $service->pngDataUri(
                $asset,
                180
            );

        $svgValid =
            $svg === null
            || str_starts_with(
                $svg,
                'data:image/svg+xml;base64,'
            );

        $pngValid =
            $png === null
            || str_starts_with(
                $png,
                'data:image/png;base64,'
            );

        echo str_pad(
            'Pemanggilan QR SVG',
            39
        ).
            ': '.
            ($svgValid ? 'OK' : 'GAGAL').
            PHP_EOL;

        echo str_pad(
            'Pemanggilan QR PNG',
            39
        ).
            ': '.
            (
                $pngValid
                    ? (
                        $png !== null
                            ? 'OK'
                            : 'FALLBACK NULL'
                    )
                    : 'GAGAL'
            ).
            PHP_EOL;

        if (
            ! $svgValid
            || ! $pngValid
        ) {
            $failed = true;
        }
    } catch (Throwable $exception) {
        echo str_pad(
            'Tes service QR',
            39
        ).
            ': GAGAL'.
            PHP_EOL;

        echo $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
} else {
    echo str_pad(
        'Tes QR dengan unit alat',
        39
    ).
        ": DILEWATI - unit alat kosong\n";
}

if (! $rendererAvailable) {
    echo "\nPERINGATAN: method sudah valid, tetapi hosting tidak mempunyai renderer PNG.\n";
    echo "Print browser tetap dapat memakai SVG; QR pada PDF memerlukan salah satu renderer di atas.\n";
}

echo "\n".
    (
        $failed
            ? 'AKSI LOKASI DAN QR PNG BELUM VALID.'
            : 'AKSI LOKASI RAPI DAN QR PNG SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
