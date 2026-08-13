<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.
    DIRECTORY_SEPARATOR.
    'vendor'.
    DIRECTORY_SEPARATOR.
    'autoload.php';

$app = require $root.
    DIRECTORY_SEPARATOR.
    'bootstrap'.
    DIRECTORY_SEPARATOR.
    'app.php';

$app->make(Kernel::class)->bootstrap();

$minimums = [
    'users' => 5,
    'workshops' => 3,
    'storage_locations' => 11,
    'item_categories' => 10,
    'units' => 7,
    'items' => 24,
    'item_assets' => 14,
    'item_stock_movements' => 44,
    'loans' => 5,
    'loan_items' => 7,
    'damage_reports' => 4,
];

$failed = false;

echo "SIMBA SEED VERIFICATION\n";
echo "=======================\n\n";

foreach ($minimums as $table => $minimum) {
    if (! Schema::hasTable($table)) {
        echo str_pad($table, 28).
            ": GAGAL - tabel tidak ada\n";

        $failed = true;
        continue;
    }

    $count = DB::table($table)
        ->count();

    $passed = $count >= $minimum;

    echo str_pad($table, 28).
        ': '.
        $count.
        ' baris - '.
        ($passed ? 'OK' : "GAGAL, minimal {$minimum}").
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\nKONSISTENSI UNIT ALAT\n";
echo "---------------------\n";

if (
    Schema::hasTable('items')
    && Schema::hasTable('item_assets')
) {
    $tools = DB::table('items')
        ->where('type', 'tool')
        ->orderBy('code')
        ->get([
            'id',
            'code',
            'name',
            'stock',
        ]);

    foreach ($tools as $tool) {
        $assetCount = DB::table(
            'item_assets'
        )
            ->where(
                'item_id',
                $tool->id
            )
            ->count();

        $consistent =
            abs(
                (float) $tool->stock
                - $assetCount
            ) < 0.000001;

        echo str_pad(
            (string) $tool->code,
            24
        ).
            ': stock='.
            $tool->stock.
            ', unit='.
            $assetCount.
            ' - '.
            (
                $consistent
                    ? 'OK'
                    : 'GAGAL'
            ).
            PHP_EOL;

        if (! $consistent) {
            $failed = true;
        }
    }
}

echo "\nAKUN LOGIN\n";
echo "-----------\n";

$accounts = [
    'admin@simba.local',
    'kepala@simba.local',
    'toolman@simba.local',
    'guru@simba.local',
    'siswa@simba.local',
];

foreach ($accounts as $email) {
    $exists = Schema::hasTable('users')
        && DB::table('users')
            ->where('email', $email)
            ->exists();

    echo str_pad($email, 30).
        ': '.
        ($exists ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $exists) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'VERIFIKASI GAGAL.'
            : 'SEMUA DATA UTAMA VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
