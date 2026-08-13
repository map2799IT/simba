<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetNumberService
{
    public function __construct(
        private readonly
        AssetPrefixService
            $prefixService
    ) {
    }

    /**
     * Format:
     *
     * ALT-{JURUSAN}-{PREFIX_BARANG}-{TAHUN}-{URUTAN_6_DIGIT}
     *
     * Contoh:
     * ALT-TKJ-ROUTER-2026-000001
     */
    public function next(
        Item $item,
        ?int $year = null,
        ?int $workshopId = null
    ): string {
        $year ??=
            (int)
            now()->format('Y');

        $workshopId ??=
            $item->workshop_id
                ? (int)
                    $item->workshop_id
                : null;

        if ($workshopId === null) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Jurusan tujuan wajib ditentukan untuk membuat nomor inventaris.',
                ]);
        }

        $workshopCode =
            Workshop::query()
                ->withoutGlobalScopes()
                ->whereKey(
                    $workshopId
                )
                ->value('code');

        $workshopCode =
            strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/i',
                    '',
                    (string)
                    $workshopCode
                )
                ?: 'GEN'
            );

        $itemPrefix =
            $this->prefixService
                ->prefixFor(
                    $item
                );

        $prefix =
            sprintf(
                'ALT-%s-%s-%d-',
                $workshopCode,
                $itemPrefix,
                $year
            );

        return DB::transaction(
            function () use (
                $prefix
            ): string {
                $lastNumber =
                    ItemAsset::query()
                        ->withoutGlobalScopes()
                        ->where(
                            'asset_number',
                            'like',
                            $prefix.'%'
                        )
                        ->lockForUpdate()
                        ->orderByDesc(
                            'asset_number'
                        )
                        ->value(
                            'asset_number'
                        );

                $sequence = 1;

                if (
                    is_string(
                        $lastNumber
                    )
                    && preg_match(
                        '/(\d{6})$/',
                        $lastNumber,
                        $matches
                    )
                ) {
                    $sequence =
                        ((int)
                            $matches[1]
                        ) + 1;
                }

                do {
                    $number =
                        $prefix.
                        str_pad(
                            (string)
                            $sequence,
                            6,
                            '0',
                            STR_PAD_LEFT
                        );

                    $exists =
                        ItemAsset::query()
                            ->withoutGlobalScopes()
                            ->where(
                                'asset_number',
                                $number
                            )
                            ->exists();

                    $sequence++;
                } while ($exists);

                return $number;
            },
            attempts: 3
        );
    }
}
