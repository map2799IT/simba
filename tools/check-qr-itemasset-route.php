<?php

declare(strict_types=1);

use App\Models\ItemAsset;
use App\Services\ItemAssetQrCodeService;
use App\Support\QrCodeDataUriGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA QR ITEM ASSET ROUTE CHECK\n";
echo "===============================\n\n";

$checks = [
    'Route item-assets.show' =>
        Route::has(
            'item-assets.show'
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

    'Endroid PngWriter' =>
        class_exists(
            \Endroid\QrCode\Writer\PngWriter::class
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 40).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

if (
    Route::has(
        'item-assets.show'
    )
) {
    $route =
        Route::getRoutes()
            ->getByName(
                'item-assets.show'
            );

    $parameterNames =
        $route?->parameterNames()
        ?? [];

    $parameterValid =
        in_array(
            'itemAsset',
            $parameterNames,
            true
        );

    echo str_pad(
        'Parameter route itemAsset',
        40
    ).
        ': '.
        (
            $parameterValid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    echo str_pad(
        'Parameter route terdeteksi',
        40
    ).
        ': '.
        (
            $parameterNames !== []
                ? implode(
                    ', ',
                    $parameterNames
                )
                : '-'
        ).
        PHP_EOL;

    if (! $parameterValid) {
        $failed = true;
    }
}

$file =
    $root.
    '/app/Support/QrCodeDataUriGenerator.php';

$fileContents =
    is_file($file)
        ? file_get_contents($file)
        : '';

$sourceValid =
    is_string($fileContents)
    && str_contains(
        $fileContents,
        "'itemAsset' =>"
    )
    && ! str_contains(
        $fileContents,
        "'item_asset' =>"
    );

echo str_pad(
    'Source memakai itemAsset',
    40
).
    ': '.
    (
        $sourceValid
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $sourceValid) {
    $failed = true;
}

$asset =
    ItemAsset::query()
        ->withoutGlobalScopes()
        ->where(
            'is_active',
            true
        )
        ->first();

if ($asset === null) {
    echo str_pad(
        'Tes unit alat',
        40
    ).
        ": DILEWATI - unit alat kosong\n";
} else {
    try {
        $payload =
            QrCodeDataUriGenerator::
                payload($asset);

        $payloadValid =
            $payload !== ''
            && (
                str_contains(
                    $payload,
                    '/item-assets/'
                )
                || $payload
                    === (string)
                        $asset
                            ->asset_number
            );

        echo str_pad(
            'Payload unit alat',
            40
        ).
            ': '.
            (
                $payloadValid
                    ? 'OK'
                    : 'GAGAL'
            ).
            PHP_EOL;

        echo "Payload: {$payload}\n";

        $service =
            app(
                ItemAssetQrCodeService::class
            );

        $png =
            $service->pngDataUri(
                $asset,
                180
            );

        $pngValid =
            is_string($png)
            && str_starts_with(
                $png,
                'data:image/png;base64,'
            );

        echo str_pad(
            'Generate PNG Data URI',
            40
        ).
            ': '.
            (
                $pngValid
                    ? 'OK'
                    : 'GAGAL'
            ).
            PHP_EOL;

        if (
            ! $payloadValid
            || ! $pngValid
        ) {
            $failed = true;
        }
    } catch (Throwable $exception) {
        echo str_pad(
            'Tes QR unit alat',
            40
        ).
            ": GAGAL\n";

        echo $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'QR ITEM ASSET ROUTE BELUM VALID.'
            : 'QR ITEM ASSET ROUTE DAN PNG SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
