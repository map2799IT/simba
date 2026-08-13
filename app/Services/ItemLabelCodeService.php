<?php

namespace App\Services;

use App\Models\Item;
use App\Support\BarcodeDataUriGenerator;
use App\Support\QrCodeDataUriGenerator;
use Illuminate\Support\Facades\Route;

class ItemLabelCodeService
{
    public function forItem(
        Item $item
    ): array {
        $url =
            $this->itemUrl($item);

        return [
            'code' =>
                (string) $item->code,

            'url' =>
                $url,

            'payload' =>
                $url,

            'qr_svg' =>
                $this->qrSvg(
                    $item
                ),

            'qr_data_uri' =>
                $this->qrDataUri(
                    $item
                ),

            'barcode_svg' =>
                $this->barcodeSvg(
                    $item
                ),

            'barcode_data_uri' =>
                $this->barcodeDataUri(
                    $item
                ),
        ];
    }

    public function itemUrl(
        Item $item
    ): string {
        if (
            Route::has(
                'items.show'
            )
        ) {
            return route(
                'items.show',
                [
                    'item' =>
                        $item->getRouteKey(),
                ]
            );
        }

        return url(
            '/items/'.
            $item->getRouteKey()
        );
    }

    public function qrDataUri(
        mixed $value,
        int $size = 300,
        int $margin = 10
    ): string {
        $dataUri =
            QrCodeDataUriGenerator::
                dataUri(
                    $value,
                    $size,
                    $margin
                );

        if ($dataUri !== null) {
            return $dataUri;
        }

        return $this->svgDataUri(
            $this->qrFallbackSvg(
                QrCodeDataUriGenerator::
                    payload($value),
                $size
            )
        );
    }

    public function qrSvg(
        mixed $value,
        int $size = 300,
        int $margin = 10
    ): string {
        $svg =
            QrCodeDataUriGenerator::
                svg(
                    $value,
                    $size,
                    $margin
                );

        if ($svg !== null) {
            return $this->cleanSvg(
                $svg
            );
        }

        return $this->qrFallbackSvg(
            QrCodeDataUriGenerator::
                payload($value),
            $size
        );
    }

    public function barcodeDataUri(
        mixed $value,
        int $widthFactor = 2,
        int $height = 70
    ): string {
        $dataUri =
            BarcodeDataUriGenerator::
                dataUri(
                    $value,
                    $widthFactor,
                    $height
                );

        if ($dataUri !== null) {
            return $dataUri;
        }

        return $this->svgDataUri(
            $this->barcodeFallbackSvg(
                BarcodeDataUriGenerator::
                    payload($value),
                $height
            )
        );
    }

    public function barcodeSvg(
        mixed $value,
        int $widthFactor = 2,
        int $height = 70
    ): string {
        $svg =
            BarcodeDataUriGenerator::
                svg(
                    $value,
                    $widthFactor,
                    $height
                );

        if ($svg !== null) {
            return $this->cleanSvg(
                $svg
            );
        }

        return $this->barcodeFallbackSvg(
            BarcodeDataUriGenerator::
                payload($value),
            $height
        );
    }

    private function svgDataUri(
        string $svg
    ): string {
        return 'data:image/svg+xml;base64,'.
            base64_encode($svg);
    }

    private function cleanSvg(
        string $svg
    ): string {
        return trim(
            (string) preg_replace(
                '/<\?xml[^>]*\?>/i',
                '',
                $svg
            )
        );
    }

    private function qrFallbackSvg(
        string $payload,
        int $size
    ): string {
        $size =
            max(
                120,
                min(
                    $size,
                    600
                )
            );

        $safePayload =
            htmlspecialchars(
                mb_strimwidth(
                    $payload,
                    0,
                    44,
                    '...'
                ),
                ENT_QUOTES
                | ENT_SUBSTITUTE,
                'UTF-8'
            );

        $right =
            max(
                18,
                $size - 66
            );

        $bottom =
            max(
                18,
                $size - 66
            );

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}" role="img" aria-label="QR Code">
    <rect x="1" y="1" width="{$size}" height="{$size}" rx="8" fill="#ffffff" stroke="#111827" stroke-width="2"/>
    <rect x="18" y="18" width="40" height="40" fill="none" stroke="#111827" stroke-width="8"/>
    <rect x="{$right}" y="18" width="40" height="40" fill="none" stroke="#111827" stroke-width="8"/>
    <rect x="18" y="{$bottom}" width="40" height="40" fill="none" stroke="#111827" stroke-width="8"/>
    <text x="50%" y="48%" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="#111827">QR CODE</text>
    <text x="50%" y="60%" text-anchor="middle" font-family="Arial, sans-serif" font-size="7" fill="#4b5563">{$safePayload}</text>
</svg>
SVG;
    }

    private function barcodeFallbackSvg(
        string $code,
        int $height
    ): string {
        $code =
            trim($code);

        if ($code === '') {
            $code = '-';
        }

        $height =
            max(
                35,
                min(
                    $height,
                    160
                )
            );

        $safeCode =
            htmlspecialchars(
                $code,
                ENT_QUOTES
                | ENT_SUBSTITUTE,
                'UTF-8'
            );

        $bars = '';
        $x = 12;

        foreach (
            str_split($code)
            as $index => $character
        ) {
            $ascii =
                ord($character);

            for (
                $bit = 0;
                $bit < 7;
                $bit++
            ) {
                $barWidth =
                    (
                        (
                            $ascii >> $bit
                        ) & 1
                    ) === 1
                        ? 3
                        : 1;

                $barHeight =
                    $height
                    - (
                        (
                            $index + $bit
                        ) % 3
                    ) * 4;

                $bars .= sprintf(
                    '<rect x="%d" y="5" width="%d" height="%d" fill="#111827"/>',
                    $x,
                    $barWidth,
                    $barHeight
                );

                $x +=
                    $barWidth + 2;
            }

            $x += 2;
        }

        $width =
            max(
                240,
                $x + 12
            );

        $totalHeight =
            $height + 28;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$totalHeight}" viewBox="0 0 {$width} {$totalHeight}" role="img" aria-label="Barcode {$safeCode}">
    <rect width="100%" height="100%" fill="#ffffff"/>
    {$bars}
    <text x="50%" y="{$totalHeight}" dy="-6" text-anchor="middle" font-family="DejaVu Sans Mono, monospace" font-size="13" font-weight="700" fill="#111827">{$safeCode}</text>
</svg>
SVG;
    }
}
