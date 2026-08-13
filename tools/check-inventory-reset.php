<?php

declare(strict_types=1);

use App\Services\InventoryResetService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$service =
    app(
        InventoryResetService::class
    );

$failed = false;

echo "SIMBA INVENTORY RESET CHECK\n";
echo "===========================\n\n";

echo "TABEL INVENTARIS\n";
echo "----------------\n";

foreach (
    $service->inventoryTables()
    as $table
) {
    if (! Schema::hasTable($table)) {
        continue;
    }

    $count =
        DB::table($table)
            ->count();

    $valid =
        $count === 0;

    echo str_pad($table, 36).
        ': '.
        $count.
        (
            $valid
                ? ' - OK'
                : ' - MASIH BERISI'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nMASTER YANG DIPERTAHANKAN\n";
echo "-------------------------\n";

foreach (
    $service->preservedTables()
    as $table
) {
    if (! Schema::hasTable($table)) {
        echo str_pad($table, 36).
            ": TABEL TIDAK ADA\n";
        continue;
    }

    echo str_pad($table, 36).
        ': '.
        DB::table($table)
            ->count().
        " baris\n";
}

echo "\nAKUN KABENG & TOOLMAN PER JURUSAN\n";
echo "---------------------------------\n";

if (
    Schema::hasTable('workshops')
    && Schema::hasTable('users')
) {
    $workshops =
        DB::table('workshops')
            ->orderBy('code')
            ->get();

    foreach ($workshops as $workshop) {
        $headCount =
            DB::table('users')
                ->where(
                    'role',
                    'kepala_bengkel'
                )
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->count();

        $toolmanCount =
            DB::table('users')
                ->where(
                    'role',
                    'toolman'
                )
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->count();

        $valid =
            $headCount >= 1
            && $toolmanCount >= 1;

        echo str_pad(
            (string) $workshop->code,
            10
        ).
            ': kabeng='.
            $headCount.
            ', toolman='.
            $toolmanCount.
            (
                $valid
                    ? ' - OK'
                    : ' - GAGAL'
            ).
            PHP_EOL;

        if (! $valid) {
            $failed = true;
        }
    }
}

echo "\n".
    (
        $failed
            ? 'RESET INVENTARIS BELUM VALID.'
            : 'INVENTARIS KOSONG, USER DAN JURUSAN TETAP SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
