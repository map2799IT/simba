<?php

namespace App\Services;

use App\Models\ItemStockMovement;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class StockReceiptCodeService
{
    /**
     * Kode per baris Barang Masuk:
     * ALT-2026-0001 / BHN-2026-0001.
     */
    public function generate(
        string $type,
        mixed $receiptDate = null
    ): string {
        $prefix = match (strtolower(trim($type))) {
            'tool' => 'ALT',
            'material' => 'BHN',
            default => throw new InvalidArgumentException(
                "Jenis barang {$type} tidak didukung."
            ),
        };

        $year = Carbon::parse(
            $receiptDate ?: now()
        )->format('Y');

        $codePrefix = $prefix.'-'.$year.'-';

        $last = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where('receipt_code', 'like', $codePrefix.'%')
            ->lockForUpdate()
            ->pluck('receipt_code')
            ->map(static function (mixed $code): int {
                return preg_match('/(\d+)$/', (string) $code, $matches) === 1
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        for ($number = $last + 1; $number <= $last + 10000; $number++) {
            $candidate = $codePrefix.str_pad(
                (string) $number,
                4,
                '0',
                STR_PAD_LEFT
            );

            if (! ItemStockMovement::query()
                ->withoutGlobalScopes()
                ->where('receipt_code', $candidate)
                ->exists()
            ) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Nomor kode Barang Masuk berikutnya tidak dapat ditentukan.'
        );
    }
}
