<?php

namespace App\Services;

use App\Models\ItemAsset;
use Illuminate\Support\Str;

class AssetSerialNumberService
{
    /**
     * Membuat nomor seri internal dari nomor inventaris.
     *
     * Nomor seri pabrik tetap dipertahankan bila memang tersedia.
     * Nomor ini hanya mengisi unit yang belum memiliki serial pabrik.
     */
    public function fromAssetNumber(
        string $assetNumber,
        ?int $ignoreAssetId = null
    ): string {
        $assetNumber = strtoupper(
            trim($assetNumber)
        );

        $candidate = $this->baseCandidate(
            $assetNumber
        );

        $serial = $candidate;
        $counter = 1;

        while (
            $this->alreadyUsed(
                $serial,
                $ignoreAssetId
            )
        ) {
            $serial = $candidate.
                '-'.
                str_pad(
                    (string) $counter,
                    2,
                    '0',
                    STR_PAD_LEFT
                );

            $counter++;
        }

        return $serial;
    }

    private function baseCandidate(
        string $assetNumber
    ): string {
        if (
            preg_match(
                '/^ALT-([A-Z0-9]+)-(\d{4})-(\d+)$/',
                $assetNumber,
                $matches
            ) === 1
        ) {
            return sprintf(
                'SN-%s-%s-%06d',
                $matches[1],
                $matches[2],
                (int) $matches[3]
            );
        }

        return 'SN-'.
            strtoupper(
                Str::substr(
                    sha1($assetNumber),
                    0,
                    14
                )
            );
    }

    private function alreadyUsed(
        string $serial,
        ?int $ignoreAssetId
    ): bool {
        $query = ItemAsset::query()
            ->withoutGlobalScopes()
            ->where(
                'serial_number',
                $serial
            );

        if ($ignoreAssetId !== null) {
            $query->whereKeyNot(
                $ignoreAssetId
            );
        }

        return $query->exists();
    }
}
