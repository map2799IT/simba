<?php

declare(strict_types=1);

use App\Models\Item;
use App\Services\ItemLabelCodeService;
use App\Support\BarcodeDataUriGenerator;
use App\Support\QrCodeDataUriGenerator;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA ITEM LABEL DATA URI CHECK\n";
echo "===============================\n\n";

$methods = [
    'forItem',
    'itemUrl',
    'qrSvg',
    'qrDataUri',
    'barcodeSvg',
    'barcodeDataUri',
];

foreach ($methods as $method) {
    $valid =
        method_exists(
            ItemLabelCodeService::class,
            $method
        );

    echo str_pad(
        'ItemLabelCodeService::'.$method,
        48
    ).
        ': '.
        (
            $valid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

foreach (
    [
        QrCodeDataUriGenerator::class,
        BarcodeDataUriGenerator::class,
    ]
    as $class
) {
    $valid =
        class_exists($class);

    echo str_pad(
        $class,
        48
    ).
        ': '.
        (
            $valid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

try {
    $service =
        app(
            ItemLabelCodeService::class
        );

    $item =
        Item::query()
            ->first();

    $value =
        $item
        ?? 'ALT-TEST-0001';

    $qr =
        $service->qrDataUri(
            $value
        );

    $barcode =
        $service->barcodeDataUri(
            $value
        );

    $qrValid =
        str_starts_with(
            $qr,
            'data:image/svg+xml;base64,'
        );

    $barcodeValid =
        str_starts_with(
            $barcode,
            'data:image/svg+xml;base64,'
        );

    echo str_pad(
        'Generate QR Data URI',
        48
    ).
        ': '.
        (
            $qrValid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    echo str_pad(
        'Generate Barcode Data URI',
        48
    ).
        ': '.
        (
            $barcodeValid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (
        ! $qrValid
        || ! $barcodeValid
    ) {
        $failed = true;
    }
} catch (Throwable $exception) {
    echo str_pad(
        'Tes service',
        48
    ).
        ': GAGAL'.
        PHP_EOL;

    echo $exception->getMessage().
        PHP_EOL;

    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'ITEM LABEL DATA URI BELUM VALID.'
            : 'ITEM LABEL DATA URI SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
