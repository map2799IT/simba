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

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA RESET SEED VERIFICATION\n";
echo "=============================\n\n";

$requiredTables = [
    'users',
    'workshops',
    'storage_locations',
    'item_categories',
    'units',
    'items',
    'item_assets',
    'item_stock_movements',
];

foreach ($requiredTables as $table) {
    $exists =
        Schema::hasTable($table);

    echo str_pad(
        "Tabel {$table}",
        40
    ).
        ': '.
        ($exists ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $exists) {
        $failed = true;
    }
}

if ($failed) {
    echo "\nVERIFIKASI GAGAL.\n";
    exit(1);
}

$workshops = DB::table(
    'workshops'
)
    ->orderBy('code')
    ->get();

echo "\nAKUN ROLE PER JURUSAN\n";
echo "---------------------\n";

foreach ($workshops as $workshop) {
    $headQuery =
        DB::table('users')
            ->where(
                'role',
                'kepala_bengkel'
            )
            ->where(
                'workshop_id',
                $workshop->id
            );

    $toolmanQuery =
        DB::table('users')
            ->where(
                'role',
                'toolman'
            )
            ->where(
                'workshop_id',
                $workshop->id
            );

    if (
        Schema::hasColumn(
            'users',
            'is_active'
        )
    ) {
        $headQuery->where(
            'is_active',
            true
        );

        $toolmanQuery->where(
            'is_active',
            true
        );
    }

    $headCount =
        $headQuery->count();

    $toolmanCount =
        $toolmanQuery->count();

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
        ' - '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$unassignedScopedUsers =
    DB::table('users')
        ->whereIn(
            'role',
            [
                'kepala_bengkel',
                'toolman',
                'siswa',
            ]
        )
        ->whereNull(
            'workshop_id'
        )
        ->count();

echo "\n".
    str_pad(
        'Role jurusan tanpa workshop_id',
        40
    ).
    ': '.
    $unassignedScopedUsers.
    (
        $unassignedScopedUsers === 0
            ? ' - OK'
            : ' - GAGAL'
    ).
    PHP_EOL;

if ($unassignedScopedUsers !== 0) {
    $failed = true;
}

echo "\nKONSISTENSI UNIT ALAT\n";
echo "---------------------\n";

$tools = DB::table('items')
    ->where(
        'type',
        'tool'
    )
    ->orderBy('code')
    ->get([
        'id',
        'code',
        'stock',
    ]);

foreach ($tools as $tool) {
    $assetCount =
        DB::table(
            'item_assets'
        )
            ->where(
                'item_id',
                $tool->id
            )
            ->where(
                'is_active',
                true
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

echo "\nAKUN LOGIN\n";
echo "-----------\n";

$accounts = DB::table('users')
    ->whereIn(
        'role',
        [
            'admin',
            'guru',
            'kepala_bengkel',
            'toolman',
            'siswa',
        ]
    )
    ->orderBy('role')
    ->orderBy('email')
    ->get([
        'role',
        'email',
        'workshop_id',
    ]);

foreach ($accounts as $account) {
    $workshopCode =
        $account->workshop_id
            ? DB::table(
                'workshops'
            )
                ->where(
                    'id',
                    $account
                        ->workshop_id
                )
                ->value('code')
            : 'GLOBAL';

    echo str_pad(
        (string) $account->email,
        34
    ).
        ': '.
        $account->role.
        ' / '.
        $workshopCode.
        PHP_EOL;
}

echo "\n".
    (
        $failed
            ? 'VERIFIKASI GAGAL.'
            : 'SEMUA DATA RESET DAN AKUN JURUSAN VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
