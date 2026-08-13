<?php

declare(strict_types=1);

use App\Services\InventoryResetService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$arguments =
    array_slice(
        $argv,
        1
    );

$force =
    in_array(
        '--force',
        $arguments,
        true
    );

$dryRun =
    in_array(
        '--dry-run',
        $arguments,
        true
    );

$clearAudit =
    in_array(
        '--clear-audit',
        $arguments,
        true
    );

if (
    app()->environment('production')
    && ! $force
) {
    fwrite(
        STDERR,
        "RESET DITOLAK: APP_ENV=production.\n".
        "Gunakan --force hanya bila risikonya telah dipahami.\n"
    );

    exit(1);
}

$service =
    app(
        InventoryResetService::class
    );

$existingTables =
    array_values(
        array_filter(
            $service->inventoryTables(
                $clearAudit
            ),
            static fn (
                string $table
            ): bool =>
                \Illuminate\Support\Facades\Schema::
                    hasTable($table)
        )
    );

echo "SIMBA RESET INVENTARIS ALAT & BAHAN\n";
echo "===================================\n\n";
echo "Database : ".
    DB::connection()
        ->getDatabaseName().
    "\n";
echo "Mode     : ".
    (
        $dryRun
            ? 'SIMULASI'
            : 'EKSEKUSI'
    ).
    "\n\n";

echo "TABEL YANG AKAN DIKOSONGKAN\n";
echo "---------------------------\n";

foreach ($existingTables as $table) {
    echo str_pad($table, 34).
        ': '.
        DB::table($table)->count().
        " baris\n";
}

echo "\nTABEL YANG TETAP DIPERTAHANKAN\n";
echo "------------------------------\n";

foreach (
    $service->counts(
        $service->preservedTables()
    )
    as $table => $count
) {
    echo str_pad($table, 34).
        ': '.
        $count.
        " baris\n";
}

if ($dryRun) {
    echo "\nSimulasi selesai. Tidak ada data yang diubah.\n";
    exit(0);
}

echo "\nPERINGATAN:\n";
echo "- seluruh barang alat dan bahan akan dihapus;\n";
echo "- seluruh unit fisik/QR akan dihapus;\n";
echo "- stok masuk/keluar dan histori stok akan dihapus;\n";
echo "- peminjaman, kerusakan, perbaikan, dan maintenance terkait barang akan dihapus;\n";
echo "- jurusan, user, siswa, kategori, satuan, dan lokasi tetap ada.\n\n";

if (! $force) {
    echo "Ketik tepat RESET INVENTARIS untuk melanjutkan: ";

    $confirmation =
        trim(
            (string)
            fgets(STDIN)
        );

    if (
        $confirmation
        !== 'RESET INVENTARIS'
    ) {
        echo "Dibatalkan. Tidak ada data yang diubah.\n";
        exit(0);
    }
}

$result =
    $service->clear(
        $clearAudit
    );

Artisan::call(
    'optimize:clear'
);

echo Artisan::output();

echo "\nHASIL RESET INVENTARIS\n";
echo "----------------------\n";

foreach (
    $service->inventoryTables(
        $clearAudit
    )
    as $table
) {
    if (
        ! \Illuminate\Support\Facades\Schema::
            hasTable($table)
    ) {
        continue;
    }

    echo str_pad($table, 34).
        ': '.
        DB::table($table)->count().
        " baris\n";
}

$preservedValid = true;

foreach (
    $result['preserved_before']
    as $table => $before
) {
    $after =
        $result['preserved_after'][$table]
        ?? -1;

    if ($before !== $after) {
        $preservedValid = false;
    }
}

echo "\nMaster dan akun: ".
    (
        $preservedValid
            ? 'TETAP UTUH'
            : 'PERLU DIPERIKSA'
    ).
    "\n";

echo "\nRESET INVENTARIS SELESAI.\n";
