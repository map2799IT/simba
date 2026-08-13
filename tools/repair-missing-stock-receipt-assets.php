<?php

declare(strict_types=1);

use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Services\BulkItemAssetService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$apply =
    in_array(
        '--apply',
        $argv,
        true
    );

echo "SIMBA REPAIR MISSING STOCK RECEIPT ASSETS\n";
echo "=========================================\n";
echo 'Mode: '.
    (
        $apply
            ? 'APPLY'
            : 'DRY RUN'
    ).
    "\n\n";

if (
    ! Schema::hasColumn(
        'item_assets',
        'receipt_code'
    )
) {
    fwrite(
        STDERR,
        "GAGAL: item_assets.receipt_code belum tersedia. Jalankan migration refactor terlebih dahulu.\n"
    );

    exit(1);
}

$service =
    app(
        BulkItemAssetService::class
    );

$receipts =
    ItemStockMovement::query()
        ->withoutGlobalScopes()
        ->with('item')
        ->where(
            'type',
            ItemStockMovement::
                TYPE_INCOMING
        )
        ->whereHas(
            'item',
            fn ($query) =>
                $query
                    ->withoutGlobalScopes()
                    ->where(
                        'type',
                        'tool'
                    )
        )
        ->orderBy('id')
        ->get();

$planned = 0;
$created = 0;
$warnings = 0;

foreach ($receipts as $receipt) {
    $expected =
        (int)
        round(
            (float)
            $receipt->quantity
        );

    if ($expected < 1) {
        continue;
    }

    $code =
        trim(
            (string)
            $receipt->receipt_code
        );

    if ($code === '') {
        echo '#'.
            $receipt->id.
            ' '.
            (
                $receipt->item?->name
                ?: '-'
            ).
            ": WARN tanpa receipt_code; tidak diperbaiki otomatis.\n";

        $warnings++;
        continue;
    }

    $duplicateMovements =
        ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where(
                'receipt_code',
                $code
            )
            ->where(
                'type',
                ItemStockMovement::
                    TYPE_INCOMING
            )
            ->count();

    if ($duplicateMovements !== 1) {
        echo "{$code}: WARN receipt_code dipakai {$duplicateMovements} movement; dilewati.\n";
        $warnings++;
        continue;
    }

    $assets =
        ItemAsset::query()
            ->withoutGlobalScopes()
            ->where(
                'receipt_code',
                $code
            )
            ->get();

    $existing =
        $assets->count();

    if ($existing > $expected) {
        echo "{$code}: WARN unit={$existing} lebih besar dari penerimaan={$expected}; tidak ada unit dihapus.\n";
        $warnings++;
        continue;
    }

    $missing =
        $expected
        - $existing;

    $wrongWorkshop =
        $assets
            ->whereNotNull(
                'workshop_id'
            )
            ->where(
                'workshop_id',
                '!=',
                $receipt
                    ->workshop_id
            )
            ->count();

    $nullWorkshop =
        $assets
            ->whereNull(
                'workshop_id'
            )
            ->count();

    echo "{$code} | ".
        (
            $receipt->item?->name
            ?: '-'
        ).
        " | diterima={$expected}".
        " | unit={$existing}".
        " | kurang={$missing}".
        " | workshop-null={$nullWorkshop}".
        " | workshop-salah={$wrongWorkshop}".
        PHP_EOL;

    if ($missing > 0) {
        $planned += $missing;
    }

    if (! $apply) {
        continue;
    }

    if (
        $receipt->workshop_id
        === null
        || $receipt
            ->storage_location_id
            === null
    ) {
        echo "  SKIP: workshop/lokasi movement belum lengkap.\n";
        $warnings++;
        continue;
    }

    DB::transaction(
        function () use (
            $assets,
            $receipt,
            $service,
            $missing,
            &$created
        ): void {
            /*
             * Hanya memperbaiki placement yang NULL.
             * Placement berbeda tidak ditimpa karena mungkin hasil
             * pemindahan unit yang sah.
             */
            ItemAsset::query()
                ->withoutGlobalScopes()
                ->where(
                    'receipt_code',
                    $receipt
                        ->receipt_code
                )
                ->whereNull(
                    'workshop_id'
                )
                ->update([
                    'workshop_id' =>
                        $receipt
                            ->workshop_id,

                    'storage_location_id' =>
                        $receipt
                            ->storage_location_id,
                ]);

            if ($missing < 1) {
                return;
            }

            $generated =
                $service->generate(
                    $receipt->item,
                    $missing,
                    [
                        'receipt_code' =>
                            $receipt
                                ->receipt_code,

                        'workshop_id' =>
                            $receipt
                                ->workshop_id,

                        'storage_location_id' =>
                            $receipt
                                ->storage_location_id,

                        'received_date' =>
                            $receipt
                                ->transaction_date
                                ?->format('Y-m-d'),

                        'brand' =>
                            $receipt->brand,

                        'model' =>
                            $receipt->model,

                        'specification' =>
                            $receipt
                                ->specification,

                        'acquisition_source' =>
                            $receipt->source,

                        'fund_source' =>
                            $receipt
                                ->fund_source,

                        'unit_price' =>
                            $receipt
                                ->unit_price,

                        'condition' =>
                            $receipt->condition
                            ?: ItemAsset::
                                CONDITION_GOOD,

                        'photo_path' =>
                            $receipt
                                ->photo_path,

                        'status' =>
                            ItemAsset::
                                STATUS_AVAILABLE,

                        'notes' =>
                            'Dibuat oleh repair unit fisik dari Barang Masuk '.$receipt->receipt_code,
                    ]
                );

            $created +=
                $generated->count();
        },
        attempts: 3
    );
}

echo "\nRINGKASAN\n";
echo "---------\n";
echo "Unit kurang terdeteksi : {$planned}\n";
echo "Unit baru dibuat       : {$created}\n";
echo "Peringatan             : {$warnings}\n";

if (! $apply) {
    echo "\nDRY RUN: belum ada data yang diubah.\n";
    echo "Setelah backup database, tambahkan --apply untuk menerapkan.\n";
} else {
    echo "\nREPAIR SELESAI.\n";
}

exit(0);
