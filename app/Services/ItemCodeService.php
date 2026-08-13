<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Workshop;
use InvalidArgumentException;
use RuntimeException;

class ItemCodeService
{
    /**
     * Kode master tanpa tahun:
     * ALT-0001 / BHN-0001.
     *
     * Workshop dan kategori tetap opsional agar pemanggil lama kompatibel.
     */
    public function generate(
        string $type,
        ?Workshop $workshop = null,
        ?ItemCategory $category = null
    ): string {
        $prefix = match (strtolower(trim($type))) {
            'tool' => 'ALT',
            'material' => 'BHN',
            default => throw new InvalidArgumentException(
                "Jenis barang {$type} tidak didukung."
            ),
        };

        $last = Item::query()
            ->withoutGlobalScopes()
            ->where('code', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->pluck('code')
            ->map(static function (mixed $code): int {
                return preg_match('/(\d+)$/', (string) $code, $matches) === 1
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        for ($number = $last + 1; $number <= $last + 10000; $number++) {
            $candidate = $prefix.'-'.str_pad(
                (string) $number,
                4,
                '0',
                STR_PAD_LEFT
            );

            if (! Item::query()
                ->withoutGlobalScopes()
                ->where('code', $candidate)
                ->exists()
            ) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Nomor kode master berikutnya tidak dapat ditentukan.'
        );
    }
}
