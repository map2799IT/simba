<?php

namespace App\Support;

final class QuantityFormatter
{
    /**
     * Menentukan apakah satuan mengizinkan nilai desimal.
     *
     * Parameter dapat berupa:
     * - boolean;
     * - Unit;
     * - Item yang mempunyai relation unit;
     * - array dengan key allows_decimal atau unit.
     */
    public static function allowsDecimal(
        mixed $source
    ): bool {
        if ($source === null) {
            return false;
        }

        if (is_bool($source)) {
            return $source;
        }

        if (is_int($source) || is_float($source)) {
            return (int) $source === 1;
        }

        if (is_string($source)) {
            return in_array(
                strtolower(
                    trim($source)
                ),
                [
                    '1',
                    'true',
                    'yes',
                    'on',
                ],
                true
            );
        }

        if (is_array($source)) {
            if (
                array_key_exists(
                    'allows_decimal',
                    $source
                )
            ) {
                return self::allowsDecimal(
                    $source[
                        'allows_decimal'
                    ]
                );
            }

            if (
                array_key_exists(
                    'unit',
                    $source
                )
            ) {
                return self::allowsDecimal(
                    $source['unit']
                );
            }

            return false;
        }

        if (is_object($source)) {
            if (
                isset(
                    $source->allows_decimal
                )
                || property_exists(
                    $source,
                    'allows_decimal'
                )
            ) {
                return self::allowsDecimal(
                    $source->allows_decimal
                );
            }

            if (
                method_exists(
                    $source,
                    'getRelationValue'
                )
            ) {
                $unit =
                    $source
                        ->getRelationValue(
                            'unit'
                        );

                if ($unit !== null) {
                    return self::allowsDecimal(
                        $unit
                    );
                }
            }

            if (
                isset($source->unit)
                || property_exists(
                    $source,
                    'unit'
                )
            ) {
                return self::allowsDecimal(
                    $source->unit
                );
            }
        }

        return false;
    }

    /**
     * Format tampilan Indonesia.
     *
     * Satuan bulat:
     * 20.000 -> 20
     *
     * Satuan desimal:
     * 20.000 -> 20
     * 20.500 -> 20,5
     * 20.125 -> 20,125
     */
    public static function format(
        mixed $value,
        mixed $unitOrAllowsDecimal = false,
        int $maximumDecimals = 3
    ): string {
        $number =
            is_numeric($value)
                ? (float) $value
                : 0.0;

        if (
            ! self::allowsDecimal(
                $unitOrAllowsDecimal
            )
        ) {
            return number_format(
                round($number),
                0,
                ',',
                '.'
            );
        }

        $maximumDecimals =
            max(
                0,
                min(
                    $maximumDecimals,
                    6
                )
            );

        $formatted =
            number_format(
                $number,
                $maximumDecimals,
                ',',
                '.'
            );

        if ($maximumDecimals === 0) {
            return $formatted;
        }

        return rtrim(
            rtrim(
                $formatted,
                '0'
            ),
            ','
        );
    }

    /**
     * Format untuk value input type=number.
     * Pemisah desimal wajib titik.
     */
    public static function inputValue(
        mixed $value,
        mixed $unitOrAllowsDecimal = false,
        int $maximumDecimals = 3
    ): string {
        $number =
            is_numeric($value)
                ? (float) $value
                : 0.0;

        if (
            ! self::allowsDecimal(
                $unitOrAllowsDecimal
            )
        ) {
            return (string)
                ((int) round($number));
        }

        $maximumDecimals =
            max(
                0,
                min(
                    $maximumDecimals,
                    6
                )
            );

        $formatted =
            number_format(
                $number,
                $maximumDecimals,
                '.',
                ''
            );

        return rtrim(
            rtrim(
                $formatted,
                '0'
            ),
            '.'
        );
    }

    public static function inputStep(
        mixed $unitOrAllowsDecimal = false
    ): string {
        return self::allowsDecimal(
            $unitOrAllowsDecimal
        )
            ? '0.001'
            : '1';
    }

    public static function inputMinimum(
        mixed $unitOrAllowsDecimal = false,
        bool $allowZero = false
    ): string {
        if ($allowZero) {
            return '0';
        }

        return self::inputStep(
            $unitOrAllowsDecimal
        );
    }
}
