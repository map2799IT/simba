<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Throwable;

class QrCodeDataUriGenerator
{
    public static function payload(
        mixed $value
    ): string {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            if (
                isset($value->asset_number)
                && isset($value->id)
                && Route::has(
                    'item-assets.show'
                )
            ) {
                try {
                    /*
                     * Route SIMBA:
                     * item-assets/{itemAsset}
                     *
                     * Nama parameter harus itemAsset,
                     * bukan item_asset.
                     */
                    return route(
                        'item-assets.show',
                        [
                            'itemAsset' =>
                                method_exists(
                                    $value,
                                    'getRouteKey'
                                )
                                    ? $value
                                        ->getRouteKey()
                                    : $value->id,
                        ]
                    );
                } catch (Throwable) {
                    /*
                     * QR tetap dapat dibuat walaupun
                     * konfigurasi route berubah.
                     */
                    return trim(
                        (string)
                        $value->asset_number
                    );
                }
            }

            if (
                isset($value->code)
                && isset($value->id)
                && Route::has(
                    'items.show'
                )
            ) {
                return route(
                    'items.show',
                    [
                        'item' =>
                            method_exists(
                                $value,
                                'getRouteKey'
                            )
                                ? $value
                                    ->getRouteKey()
                                : $value->id,
                    ]
                );
            }

            foreach (
                [
                    'url',
                    'barcode_value',
                    'asset_number',
                    'code',
                    'id',
                ]
                as $property
            ) {
                if (
                    isset(
                        $value->{$property}
                    )
                    && $value->{$property}
                        !== ''
                ) {
                    return trim(
                        (string)
                        $value->{$property}
                    );
                }
            }
        }

        if (is_array($value)) {
            foreach (
                [
                    'url',
                    'barcode_value',
                    'asset_number',
                    'code',
                    'id',
                ]
                as $key
            ) {
                if (
                    isset($value[$key])
                    && $value[$key] !== ''
                ) {
                    return trim(
                        (string)
                        $value[$key]
                    );
                }
            }

            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            ) ?: '';
        }

        return trim(
            (string) $value
        );
    }

    public static function svg(
        mixed $value,
        int $size = 300,
        int $margin = 10
    ): ?string {
        $payload =
            self::payload($value);

        if ($payload === '') {
            return null;
        }

        $size =
            self::normalizeSize($size);

        $margin =
            self::normalizeMargin(
                $margin
            );

        if (
            class_exists(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::class
            )
        ) {
            try {
                return (string)
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::
                        format('svg')
                        ->size($size)
                        ->margin(
                            max(
                                0,
                                (int) round(
                                    $margin / 5
                                )
                            )
                        )
                        ->generate($payload);
            } catch (Throwable) {
                // Lanjut mencoba Endroid.
            }
        }

        if (
            ! class_exists(
                \Endroid\QrCode\Builder\Builder::class
            )
            || ! class_exists(
                \Endroid\QrCode\Writer\SvgWriter::class
            )
        ) {
            return null;
        }

        try {
            return self::endroidString(
                payload: $payload,
                writer:
                    new \Endroid\QrCode\Writer\SvgWriter(),
                size: $size,
                margin: $margin
            );
        } catch (Throwable) {
            return null;
        }
    }

    public static function dataUri(
        mixed $value,
        int $size = 300,
        int $margin = 10
    ): ?string {
        $svg =
            self::svg(
                $value,
                $size,
                $margin
            );

        if ($svg === null) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.
            base64_encode($svg);
    }

    /**
     * Data URI PNG dipakai oleh DomPDF pada cetak QR massal.
     *
     * Urutan renderer:
     * 1. SimpleSoftwareIO PNG;
     * 2. Endroid PngWriter;
     * 3. konversi SVG melalui Imagick.
     */
    public static function pngDataUri(
        mixed $value,
        int $size = 300,
        int $margin = 10
    ): ?string {
        $payload =
            self::payload($value);

        if ($payload === '') {
            return null;
        }

        $size =
            self::normalizeSize($size);

        $margin =
            self::normalizeMargin(
                $margin
            );

        if (
            class_exists(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::class
            )
        ) {
            try {
                $png =
                    (string)
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::
                        format('png')
                        ->size($size)
                        ->margin(
                            max(
                                0,
                                (int) round(
                                    $margin / 5
                                )
                            )
                        )
                        ->generate($payload);

                if (self::isPng($png)) {
                    return self::pngUri(
                        $png
                    );
                }
            } catch (Throwable) {
                // Lanjut mencoba Endroid.
            }
        }

        if (
            class_exists(
                \Endroid\QrCode\Builder\Builder::class
            )
            && class_exists(
                \Endroid\QrCode\Writer\PngWriter::class
            )
        ) {
            try {
                $png =
                    self::endroidString(
                        payload: $payload,
                        writer:
                            new \Endroid\QrCode\Writer\PngWriter(),
                        size: $size,
                        margin: $margin
                    );

                if (self::isPng($png)) {
                    return self::pngUri(
                        $png
                    );
                }
            } catch (Throwable) {
                // Lanjut mencoba Imagick.
            }
        }

        if (class_exists(\Imagick::class)) {
            try {
                $svg =
                    self::svg(
                        $value,
                        $size,
                        $margin
                    );

                if ($svg === null) {
                    return null;
                }

                $image =
                    new \Imagick();

                $image->setBackgroundColor(
                    new \ImagickPixel(
                        'white'
                    )
                );

                $image->readImageBlob(
                    $svg
                );

                $image->setImageFormat(
                    'png'
                );

                $image->resizeImage(
                    $size,
                    $size,
                    \Imagick::FILTER_LANCZOS,
                    1,
                    true
                );

                $png =
                    $image->getImageBlob();

                $image->clear();
                $image->destroy();

                if (self::isPng($png)) {
                    return self::pngUri(
                        $png
                    );
                }
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private static function endroidString(
        string $payload,
        object $writer,
        int $size,
        int $margin
    ): string {
        $builderClass =
            \Endroid\QrCode\Builder\Builder::class;

        if (
            method_exists(
                $builderClass,
                'create'
            )
        ) {
            $builder =
                $builderClass::create()
                    ->writer($writer)
                    ->data($payload)
                    ->encoding(
                        new \Endroid\QrCode\Encoding\Encoding(
                            'UTF-8'
                        )
                    )
                    ->errorCorrectionLevel(
                        \Endroid\QrCode\ErrorCorrectionLevel::
                            Medium
                    )
                    ->size($size)
                    ->margin($margin);

            if (
                class_exists(
                    \Endroid\QrCode\RoundBlockSizeMode::class
                )
                && method_exists(
                    $builder,
                    'roundBlockSizeMode'
                )
            ) {
                $builder =
                    $builder->roundBlockSizeMode(
                        \Endroid\QrCode\RoundBlockSizeMode::
                            Margin
                    );
            }

            return $builder
                ->build()
                ->getString();
        }

        $arguments = [
            'writer' =>
                $writer,

            'writerOptions' =>
                [],

            'validateResult' =>
                false,

            'data' =>
                $payload,

            'encoding' =>
                new \Endroid\QrCode\Encoding\Encoding(
                    'UTF-8'
                ),

            'errorCorrectionLevel' =>
                \Endroid\QrCode\ErrorCorrectionLevel::
                    Medium,

            'size' =>
                $size,

            'margin' =>
                $margin,
        ];

        if (
            class_exists(
                \Endroid\QrCode\RoundBlockSizeMode::class
            )
        ) {
            $arguments[
                'roundBlockSizeMode'
            ] =
                \Endroid\QrCode\RoundBlockSizeMode::
                    Margin;
        }

        $reflection =
            new \ReflectionClass(
                $builderClass
            );

        $builder =
            $reflection
                ->newInstanceArgs(
                    $arguments
                );

        return $builder
            ->build()
            ->getString();
    }

    private static function normalizeSize(
        int $size
    ): int {
        return max(
            120,
            min(
                $size,
                1200
            )
        );
    }

    private static function normalizeMargin(
        int $margin
    ): int {
        return max(
            0,
            min(
                $margin,
                100
            )
        );
    }

    private static function pngUri(
        string $png
    ): string {
        return 'data:image/png;base64,'.
            base64_encode($png);
    }

    private static function isPng(
        string $value
    ): bool {
        return str_starts_with(
            $value,
            "\x89PNG\r\n\x1a\n"
        );
    }
}
