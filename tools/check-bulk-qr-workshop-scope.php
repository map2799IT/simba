<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA BULK QR WORKSHOP SCOPE CHECK\n";
echo "=================================\n\n";

$checks = [
    'Controller tersedia' =>
        class_exists(
            \App\Http\Controllers\ItemAssetBulkQrController::class
        ),

    'View index tersedia' =>
        View::exists('item-assets.bulk-qr-index'),

    'View print tersedia' =>
        View::exists('item-assets.bulk-qr-print'),

    'Route index tersedia' =>
        Route::has('item-assets.qr-bulk.index'),

    'Route print tersedia' =>
        Route::has('item-assets.qr-bulk.print'),

    'Route download tersedia' =>
        Route::has('item-assets.qr-bulk.download'),

    'Tabel item_assets tersedia' =>
        Schema::hasTable('item_assets'),

    'Kolom item_assets.workshop_id' =>
        Schema::hasColumn(
            'item_assets',
            'workshop_id'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 45).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$controllerFile =
    $root.
    '/app/Http/Controllers/ItemAssetBulkQrController.php';

$controller =
    is_file($controllerFile)
        ? file_get_contents($controllerFile)
        : '';

$sourceChecks = [
    'Index memakai item_assets.workshop_id' =>
        is_string($controller)
        && str_contains(
            $controller,
            "'item_assets.workshop_id'"
        ),

    'Tidak memfilter items.workshop_id' =>
        is_string($controller)
        && ! str_contains(
            $controller,
            "'items.workshop_id'"
        ),

    'Print tidak memakai item workshop' =>
        is_string($controller)
        && ! str_contains(
            $controller,
            '$item->workshop_id'
        ),

    'Aksi membawa workshop_id' =>
        str_contains(
            (string) file_get_contents(
                $root.
                '/resources/views/item-assets/bulk-qr-index.blade.php'
            ),
            "'workshop_id' => \$group->workshop_id"
        ),
];

foreach ($sourceChecks as $label => $valid) {
    echo str_pad($label, 45).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

if (
    Schema::hasTable('item_assets')
    && Schema::hasTable('items')
    && Schema::hasTable('workshops')
) {
    $groups = DB::table('item_assets')
        ->join(
            'items',
            'items.id',
            '=',
            'item_assets.item_id'
        )
        ->join(
            'workshops',
            'workshops.id',
            '=',
            'item_assets.workshop_id'
        )
        ->where('item_assets.is_active', true)
        ->where('items.type', 'tool')
        ->select([
            'items.id as item_id',
            'items.code as item_code',
            'workshops.id as workshop_id',
            'workshops.code as workshop_code',
        ])
        ->selectRaw(
            'COUNT(item_assets.id) AS unit_count'
        )
        ->groupBy([
            'items.id',
            'items.code',
            'workshops.id',
            'workshops.code',
        ])
        ->orderByDesc('unit_count')
        ->get();

    echo "\nUNIT AKTIF PER MASTER/JURUSAN\n";
    echo "-----------------------------\n";

    if ($groups->isEmpty()) {
        echo "Tidak ada unit alat aktif.\n";
    }

    foreach ($groups as $group) {
        echo str_pad(
            $group->item_code.
            ' / '.
            $group->workshop_code,
            32
        ).
            ': '.
            $group->unit_count.
            " unit\n";
    }

    $toolmanWorkshopIds = DB::table('users')
        ->where('role', 'toolman')
        ->whereNotNull('workshop_id')
        ->pluck('workshop_id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    foreach ($toolmanWorkshopIds as $workshopId) {
        $activeCount = DB::table('item_assets')
            ->where('workshop_id', $workshopId)
            ->where('is_active', true)
            ->count();

        $groupCount = $groups
            ->where('workshop_id', $workshopId)
            ->sum('unit_count');

        $valid =
            (int) $activeCount
            === (int) $groupCount;

        echo str_pad(
            "Toolman workshop {$workshopId}",
            45
        ).
            ': '.
            ($valid ? 'OK' : 'GAGAL').
            " unit={$activeCount}, QR={$groupCount}\n";

        if (! $valid) {
            $failed = true;
        }
    }
}

echo "\n".
    (
        $failed
            ? 'BULK QR WORKSHOP SCOPE BELUM VALID.'
            : 'BULK QR WORKSHOP SCOPE SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
