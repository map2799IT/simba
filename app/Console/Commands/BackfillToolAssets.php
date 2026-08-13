<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Services\AssetNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillToolAssets extends Command
{
    protected $signature =
        'assets:backfill-tools
        {--dry-run : Hanya tampilkan rencana}
        {--force : Lewati konfirmasi}';

    protected $description =
        'Membuat unit alat fisik dari stok master alat yang sudah ada.';

    public function handle(
        AssetNumberService $numberService
    ): int {
        $tools = Item::query()
            ->where('type', 'tool')
            ->orderBy('code')
            ->get();

        if ($tools->isEmpty()) {
            $this->warn(
                'Tidak ada data alat.'
            );

            return self::SUCCESS;
        }

        $plan = [];

        foreach ($tools as $tool) {
            $stock = (float) $tool->stock;

            if (
                abs($stock - round($stock)) > 0.000001
            ) {
                $this->error(
                    "{$tool->code}: stok alat {$stock} bukan bilangan bulat."
                );

                return self::FAILURE;
            }

            $desired = max(
                0,
                (int) round($stock)
            );

            $existing = ItemAsset::query()
                ->where('item_id', $tool->id)
                ->count();

            $missing = max(
                0,
                $desired - $existing
            );

            if ($missing > 0) {
                $plan[] = [
                    'tool' => $tool,
                    'existing' => $existing,
                    'missing' => $missing,
                ];
            }
        }

        if ($plan === []) {
            $this->info(
                'Seluruh alat sudah memiliki unit fisik yang sesuai.'
            );

            return self::SUCCESS;
        }

        $this->table(
            [
                'Kode',
                'Nama',
                'Unit Ada',
                'Akan Dibuat',
            ],
            array_map(
                fn (array $row): array => [
                    $row['tool']->code,
                    $row['tool']->name,
                    $row['existing'],
                    $row['missing'],
                ],
                $plan
            )
        );

        if ($this->option('dry-run')) {
            $this->info(
                'Dry run selesai. Database tidak diubah.'
            );

            return self::SUCCESS;
        }

        if (
            ! $this->option('force')
            && ! $this->confirm(
                'Lanjutkan membuat unit alat?',
                false
            )
        ) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(
            function () use (
                $plan,
                $numberService
            ): void {
                foreach ($plan as $row) {
                    /** @var Item $tool */
                    $tool = $row['tool'];

                    for (
                        $index = 0;
                        $index < $row['missing'];
                        $index++
                    ) {
                        $assetNumber =
                            $numberService->next(
                                $tool
                            );

                        $assetCount =
                            ItemAsset::query()
                                ->where(
                                    'item_id',
                                    $tool->id
                                )
                                ->count();

                        ItemAsset::query()->create([
                            'item_id' => $tool->id,
                            'asset_number' =>
                                $assetNumber,
                            'barcode_value' =>
                                $assetNumber,

                            /*
                             * Serial master lama hanya diberikan
                             * ke unit pertama agar tetap unik.
                             */
                            'serial_number' =>
                                $assetCount === 0
                                    ? $tool->serial_number
                                    : null,

                            'workshop_id' =>
                                $tool->workshop_id,

                            'storage_location_id' =>
                                $tool->storage_location_id,

                            'condition' =>
                                $tool->condition,

                            'status' =>
                                $this->assetStatus(
                                    $tool->status
                                ),

                            'received_date' =>
                                $tool->received_date,

                            'unit_price' =>
                                $tool->unit_price,

                            'notes' =>
                                'Dibuat otomatis dari data alat lama.',

                            'is_active' =>
                                $tool->is_active,
                        ]);
                    }
                }
            }
        );

        $this->info(
            'Unit alat berhasil dibuat.'
        );

        return self::SUCCESS;
    }

    private function assetStatus(
        ?string $status
    ): string {
        return match ($status) {
            'borrowed' => 'borrowed',
            'damaged' => 'damaged',
            'maintenance' => 'under_repair',
            'lost' => 'lost',
            'retired' => 'retired',
            default => 'available',
        };
    }
}
