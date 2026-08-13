<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Workshop;
use App\Services\WorkshopLoanInventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA LOAN ITEM AVAILABILITY CHECK\n";
echo "==================================\n\n";

echo "WAKTU APLIKASI\n";
echo "--------------\n";
echo 'Timezone config : '.
    config('app.timezone').
    PHP_EOL;
echo 'Waktu aplikasi  : '.
    now()->format('d-m-Y H:i:s').
    PHP_EOL;

if (
    config('app.timezone')
    !== 'Asia/Jakarta'
) {
    echo "WARN: timezone belum Asia/Jakarta.\n";
    $warnings++;
}

echo "\nROUTE DAN VIEW\n";
echo "--------------\n";

$route =
    Route::getRoutes()
        ->getByName(
            'loans.create'
        );

$action =
    $route?->getActionName();

$routeValid =
    is_string($action)
    && str_contains(
        $action,
        'WorkshopLoanController@create'
    );

echo str_pad(
    'loans.create',
    48
).
    ': '.
    (
        $routeValid
            ? 'OK '
            : 'GAGAL '
    ).
    ($action ?? '-').
    PHP_EOL;

if (! $routeValid) {
    $failed = true;
}

$viewFile =
    $root.
    '/resources/views/loans/create.blade.php';

$view =
    is_file($viewFile)
        ? file_get_contents(
            $viewFile
        )
        : false;

$scheduledView =
    is_string($view)
    && str_contains(
        $view,
        'Tanggal Peminjaman'
    )
    && str_contains(
        $view,
        'Kirim Pengajuan ke Toolman'
    );

echo str_pad(
    'View peminjaman terjadwal terbaru',
    48
).
    ': '.
    (
        $scheduledView
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $scheduledView) {
    $failed = true;
}

echo "\nSTRUKTUR DATA\n";
echo "-------------\n";

foreach (
    [
        'item_assets.receipt_code' =>
            Schema::hasColumn(
                'item_assets',
                'receipt_code'
            ),

        'item_assets.workshop_id' =>
            Schema::hasColumn(
                'item_assets',
                'workshop_id'
            ),

        'item_stock_movements.workshop_id' =>
            Schema::hasColumn(
                'item_stock_movements',
                'workshop_id'
            ),

        'loans.scheduled_at' =>
            Schema::hasColumn(
                'loans',
                'scheduled_at'
            ),
    ]
    as $label => $valid
) {
    echo str_pad(
        $label,
        48
    ).
        ': '.
        (
            $valid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nSTOK PER JURUSAN\n";
echo "----------------\n";

$service =
    app(
        WorkshopLoanInventoryService::class
    );

$workshops =
    Workshop::query()
        ->withoutGlobalScopes()
        ->where(
            'is_active',
            true
        )
        ->orderBy('code')
        ->get();

foreach ($workshops as $workshop) {
    $items =
        $service->items(
            (int) $workshop->id
        );

    $summary =
        $service
            ->inventorySummary(
                (int) $workshop->id
            );

    echo "\n[".
        $workshop->code.
        '] '.
        $workshop->name.
        PHP_EOL;

    echo 'Pilihan barang    : '.
        $items->count().
        PHP_EOL;

    echo 'Unit available    : '.
        $summary[
            'available_asset_units'
        ].
        PHP_EOL;

    echo 'Jenis bahan aktif : '.
        $summary[
            'available_material_items'
        ].
        PHP_EOL;

    echo 'Movement jurusan  : '.
        $summary[
            'movement_rows'
        ].
        PHP_EOL;

    foreach ($items as $item) {
        echo '  - '.
            $item->code.
            ' '.
            $item->name.
            ' ['.
            $item->type.
            '] stok='.
            $item
                ->workshop_available_stock.
            PHP_EOL;
    }

    if (
        $items->isEmpty()
        && (
            $summary[
                'available_asset_units'
            ] > 0
            || $summary[
                'available_material_items'
            ] > 0
        )
    ) {
        echo "  GAGAL: ada stok tetapi pilihan barang kosong.\n";
        $failed = true;
    }
}

echo "\nBARANG MASUK ALAT VS UNIT FISIK\n";
echo "-------------------------------\n";

$receipts =
    ItemStockMovement::query()
        ->withoutGlobalScopes()
        ->with([
            'item',
            'workshop',
        ])
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
        ->orderByDesc('id')
        ->get();

foreach ($receipts as $receipt) {
    $expected =
        (int)
        round(
            (float) $receipt->quantity
        );

    $assets =
        $receipt->receipt_code
            ? ItemAsset::query()
                ->withoutGlobalScopes()
                ->where(
                    'receipt_code',
                    $receipt
                        ->receipt_code
                )
                ->get()
            : collect();

    $available =
        $assets
            ->where(
                'is_active',
                true
            )
            ->where(
                'status',
                ItemAsset::
                    STATUS_AVAILABLE
            )
            ->where(
                'workshop_id',
                $receipt
                    ->workshop_id
            )
            ->count();

    $valid =
        $receipt->receipt_code
        && $assets->count()
            === $expected;

    echo '#'.
        $receipt->id.
        ' '.
        (
            $receipt->receipt_code
            ?: 'TANPA-KODE'
        ).
        ' | '.
        (
            $receipt->item?->name
            ?: '-'
        ).
        ' | workshop='.
        (
            $receipt->workshop?->code
            ?: (
                $receipt->workshop_id
                ?: 'NULL'
            )
        ).
        ' | diterima='.
        $expected.
        ' | unit='.
        $assets->count().
        ' | available sesuai jurusan='.
        $available.
        ' | '.
        (
            $valid
                ? 'OK'
                : 'WARN'
        ).
        PHP_EOL;

    if (! $valid) {
        $warnings++;
    }
}

echo "\nSTATUS UNIT FISIK\n";
echo "-----------------\n";

$statusRows =
    ItemAsset::query()
        ->withoutGlobalScopes()
        ->selectRaw(
            'workshop_id, status, is_active, COUNT(*) AS total'
        )
        ->groupBy(
            'workshop_id',
            'status',
            'is_active'
        )
        ->orderBy(
            'workshop_id'
        )
        ->orderBy('status')
        ->get();

foreach ($statusRows as $row) {
    echo 'workshop_id='.
        (
            $row->workshop_id
            ?: 'NULL'
        ).
        ', status='.
        (
            $row->status
            ?: 'NULL'
        ).
        ', aktif='.
        (
            $row->is_active
                ? '1'
                : '0'
        ).
        ', total='.
        $row->total.
        PHP_EOL;
}

echo "\nSTATUS\n";
echo "------\n";
echo 'FAIL: '.
    (
        $failed
            ? 'YA'
            : 'TIDAK'
    ).
    PHP_EOL;
echo "WARN: {$warnings}\n";

echo "\n".
    (
        $failed
            ? 'LOAN ITEM AVAILABILITY BELUM VALID.'
            : (
                $warnings > 0
                    ? 'KODE VALID, TETAPI UNIT BARANG MASUK LAMA PERLU DIPERIKSA/DIPERBAIKI.'
                    : 'LOAN ITEM AVAILABILITY SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit(
    $failed
        ? 1
        : 0
);
