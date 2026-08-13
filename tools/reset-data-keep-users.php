<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

if (PHP_SAPI !== 'cli') {
    fwrite(
        STDERR,
        "Perintah ini hanya boleh dijalankan melalui Terminal.\n"
    );

    exit(1);
}

$options = getopt(
    '',
    [
        'force',
    ]
);

$force = array_key_exists(
    'force',
    $options
);

$connection = DB::connection();
$driver = $connection->getDriverName();
$database = $connection->getDatabaseName();

if ($driver !== 'mysql') {
    fwrite(
        STDERR,
        "GAGAL: script ini khusus database MySQL.\n".
        "Driver aktif: {$driver}\n"
    );

    exit(1);
}

$rows = DB::select(
    <<<'SQL'
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = ?
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME
SQL,
    [
        $database,
    ]
);

$tables = collect($rows)
    ->map(
        static fn (
            object $row
        ): string =>
            (string) (
                $row->TABLE_NAME
                ?? $row->table_name
            )
    )
    ->filter()
    ->values();

if ($tables->isEmpty()) {
    fwrite(
        STDERR,
        "GAGAL: tidak ada tabel pada database {$database}.\n"
    );

    exit(1);
}

$preserved = collect([
    'users',
    'workshops',
    'migrations',
])
    ->filter(
        static fn (
            string $table
        ): bool =>
            $tables->contains($table)
    )
    ->values();

$foreignKeys = DB::select(
    <<<'SQL'
SELECT
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = ?
  AND REFERENCED_TABLE_NAME IS NOT NULL
SQL,
    [
        $database,
    ]
);

do {
    $changed = false;

    foreach ($foreignKeys as $foreignKey) {
        $child = (string) (
            $foreignKey->TABLE_NAME
            ?? $foreignKey->table_name
        );

        $parent = (string) (
            $foreignKey->REFERENCED_TABLE_NAME
            ?? $foreignKey->referenced_table_name
        );

        if (
            $preserved->contains($child)
            && ! $preserved->contains($parent)
        ) {
            $preserved->push($parent);
            $changed = true;
        }
    }
} while ($changed);

$preserved = $preserved
    ->unique()
    ->sort()
    ->values();

$truncateTables = $tables
    ->reject(
        static fn (
            string $table
        ): bool =>
            $preserved->contains($table)
    )
    ->values();

$userCountBefore =
    $tables->contains('users')
        ? (int) DB::table('users')->count()
        : 0;

echo "SIMBA RESET DATA - PERTAHANKAN USERS\n";
echo "====================================\n\n";

echo "Database : {$database}\n";
echo "Driver   : {$driver}\n";
echo "User     : {$userCountBefore} akun\n\n";

echo "TABEL YANG DIPERTAHANKAN\n";
echo "------------------------\n";

foreach ($preserved as $table) {
    echo "- {$table}\n";
}

echo "\nTABEL YANG AKAN DIKOSONGKAN\n";
echo "---------------------------\n";

foreach ($truncateTables as $table) {
    echo "- {$table}\n";
}

if ($truncateTables->isEmpty()) {
    echo "\nTidak ada tabel yang perlu dikosongkan.\n";
    exit(0);
}

if (! $force) {
    echo "\nPERINGATAN\n";
    echo "Semua data pada tabel di atas akan dihapus permanen.\n";
    echo "Akun pengguna dan jurusan pengguna tetap dipertahankan.\n\n";
    echo "Ketik persis: RESET DATA SIMBA\n";
    echo "> ";

    $confirmation = trim(
        (string) fgets(STDIN)
    );

    if ($confirmation !== 'RESET DATA SIMBA') {
        echo "Dibatalkan. Tidak ada data yang dihapus.\n";
        exit(1);
    }
}

$truncateSucceeded = [];
$deleteFallback = [];
$failed = [];

DB::statement(
    'SET FOREIGN_KEY_CHECKS=0'
);

try {
    foreach ($truncateTables as $table) {
        $quoted = '`'.
            str_replace(
                '`',
                '``',
                $table
            ).
            '`';

        try {
            DB::statement(
                "TRUNCATE TABLE {$quoted}"
            );

            $truncateSucceeded[] =
                $table;
        } catch (Throwable $truncateError) {
            try {
                DB::statement(
                    "DELETE FROM {$quoted}"
                );

                try {
                    DB::statement(
                        "ALTER TABLE {$quoted} AUTO_INCREMENT = 1"
                    );
                } catch (Throwable) {
                    // Reset AUTO_INCREMENT tidak wajib.
                }

                $deleteFallback[] =
                    $table;
            } catch (Throwable $deleteError) {
                $failed[$table] =
                    $deleteError->getMessage();
            }
        }
    }
} finally {
    DB::statement(
        'SET FOREIGN_KEY_CHECKS=1'
    );
}

$userCountAfter =
    $tables->contains('users')
        ? (int) DB::table('users')->count()
        : 0;

echo "\nHASIL RESET\n";
echo "-----------\n";
echo "TRUNCATE berhasil : ".
    count($truncateSucceeded).
    " tabel\n";

echo "DELETE fallback   : ".
    count($deleteFallback).
    " tabel\n";

echo "Gagal             : ".
    count($failed).
    " tabel\n";

echo "User sebelum      : {$userCountBefore}\n";
echo "User sesudah      : {$userCountAfter}\n";

if ($failed !== []) {
    echo "\nTABEL GAGAL\n";
    echo "-----------\n";

    foreach (
        $failed
        as $table => $message
    ) {
        echo "- {$table}: {$message}\n";
    }

    echo "\nRESET SEBAGIAN. Periksa tabel yang gagal.\n";
    exit(1);
}

if ($userCountAfter !== $userCountBefore) {
    echo "\nGAGAL: jumlah user berubah. Pulihkan backup database.\n";
    exit(1);
}

echo "\nRESET DATA BERHASIL. USER TETAP DIPERTAHANKAN.\n";
