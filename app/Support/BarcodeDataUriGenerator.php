<?php

namespace App\Support;

use Throwable;

class BarcodeDataUriGenerator
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
            foreach (
                [
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
                    return (string)
                        $value->{$property};
                }
            }
        }

        if (is_array($value)) {
            foreach (
                [
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
                    return (string)
                        $value[$key];
                }
            }
        }

        return '';
    }

    public static function svg(
        mixed $value,
        int $widthFactor = 2,
        int $height = 70
    ): ?string {
        $payload =
            self::payload($value);

        if ($payload === '') {
            return null;
        }

        if (
            ! class_exists(
                \Picqer\Barcode\BarcodeGeneratorSVG::class
            )
        ) {
            return null;
        }

        try {
            $generator =
                new \Picqer\Barcode\BarcodeGeneratorSVG();

            return $generator->getBarcode(
                $payload,
                $generator::TYPE_CODE_128,
                max(
                    1,
                    min(
                        $widthFactor,
                        5
                    )
                ),
                max(
                    30,
                    min(
                        $height,
                        160
                    )
                )
            );
        } catch (Throwable) {
            return null;
        }
    }

    public static function dataUri(
        mixed $value,
        int $widthFactor = 2,
        int $height = 70
    ): ?string {
        $svg =
            self::svg(
                $value,
                $widthFactor,
                $height
            );

        if ($svg === null) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.
            base64_encode($svg);
    }
}
