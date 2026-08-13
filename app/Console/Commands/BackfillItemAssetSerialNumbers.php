<?php

namespace App\Console\Commands;

use App\Models\ItemAsset;
use App\Services\AssetSerialNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillItemAssetSerialNumbers
    extends Command
{
    protected $signature =
        'item-assets:backfill-serials
        {--dry-run : Hanya menghitung tanpa mengubah database}';

    protected $description =
        'Mengisi nomor seri internal pada unit alat yang serialnya kosong.';

    public function handle(
        AssetSerialNumberService
            $serialNumberService
    ): int {
        $query = ItemAsset::query()
            ->withoutGlobalScopes()
            ->where(
                function ($query): void {
                    $query
                        ->whereNull(
                            'serial_number'
                        )
                        ->orWhere(
                            'serial_number',
                            ''
                        );
                }
            )
            ->orderBy('id');

        $total = (clone $query)->count();

        $this->line(
            "Unit dengan nomor seri kosong: {$total}"
        );

        if ($total === 0) {
            $this->info(
                'Semua unit sudah memiliki nomor seri.'
            );

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn(
                'Mode dry-run: database tidak diubah.'
            );

            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(
            function () use (
                $query,
                $serialNumberService,
                &$updated
            ): void {
                $query->chunkById(
                    200,
                    function ($assets) use (
                        $serialNumberService,
                        &$updated
                    ): void {
                        foreach ($assets as $asset) {
                            $asset->fill([
                                'serial_number' =>
                                    $serialNumberService
                                        ->fromAssetNumber(
                                            $asset->asset_number,
                                            $asset->id
                                        ),
                            ])->saveQuietly();

                            $updated++;
                        }
                    }
                );
            },
            3
        );

        $this->info(
            "Nomor seri berhasil diisi: {$updated} unit."
        );

        return self::SUCCESS;
    }
}
