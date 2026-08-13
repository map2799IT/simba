<?php

namespace App\Services;

use App\Models\ItemAsset;

class ItemAssetBarcodeService
{
    /**
     * Membuat barcode Code 128 berbentuk SVG.
     *
     * Mengembalikan null ketika package barcode belum terpasang.
     */
    public function svg(
        ItemAsset $asset
    ): ?string {
        if (
            ! class_exists(
                \Picqer\Barcode\BarcodeGeneratorSVG::class
            )
        ) {
            return null;
        }

        $generator =
            new \Picqer\Barcode\BarcodeGeneratorSVG();

        return $generator->getBarcode(
            $asset->barcode_value,
            $generator::TYPE_CODE_128,
            2,
            70
        );
    }
}
