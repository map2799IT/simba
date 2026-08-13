<?php

namespace App\Services;

use App\Models\ItemAsset;
use App\Support\QrCodeDataUriGenerator;

class ItemAssetQrCodeService
{
    public function payload(
        ItemAsset $asset
    ): string {
        return QrCodeDataUriGenerator::
            payload($asset);
    }

    public function svg(
        ItemAsset $asset,
        int $size = 300
    ): ?string {
        return QrCodeDataUriGenerator::
            svg(
                $asset,
                $size
            );
    }

    public function dataUri(
        ItemAsset $asset,
        int $size = 300
    ): ?string {
        return QrCodeDataUriGenerator::
            dataUri(
                $asset,
                $size
            );
    }

    public function pngDataUri(
        ItemAsset $asset,
        int $size = 300
    ): ?string {
        return QrCodeDataUriGenerator::
            pngDataUri(
                $asset,
                $size
            );
    }
}
