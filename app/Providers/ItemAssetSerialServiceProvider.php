<?php

namespace App\Providers;

use App\Models\ItemAsset;
use App\Services\AssetSerialNumberService;
use Illuminate\Support\ServiceProvider;

class ItemAssetSerialServiceProvider
    extends ServiceProvider
{
    public function boot(
        AssetSerialNumberService
            $serialNumberService
    ): void {
        ItemAsset::creating(
            static function (
                ItemAsset $asset
            ) use (
                $serialNumberService
            ): void {
                if (
                    trim(
                        (string)
                        $asset->serial_number
                    ) !== ''
                ) {
                    return;
                }

                if (
                    trim(
                        (string)
                        $asset->asset_number
                    ) === ''
                ) {
                    return;
                }

                $asset->serial_number =
                    $serialNumberService
                        ->fromAssetNumber(
                            $asset->asset_number
                        );
            }
        );
    }
}
