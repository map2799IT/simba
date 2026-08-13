<?php

declare(strict_types=1);

use App\Services\InventoryResetService;
use Database\Seeders\WorkshopRoleAccountSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
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

$arguments =
    array_slice(
        $argv,
        1
    );

$force = in_array(
    '--force',
    $arguments,
    true
);

$defaultWorkshopsOnly = in_array(
    '--default-workshops-only',
    $arguments,
    true
);

$emptyInventory = in_array(
    '--empty-inventory',
    $arguments,
    true
);

$clearInventoryAudit = in_array(
    '--clear-inventory-audit',
    $arguments,
    true
);

if (
    app()->environment(
        'production'
    )
    && ! $force
) {
    fwrite(
        STDERR,
        "RESET DITOLAK: APP_ENV=production.\n".
        "Gunakan --force hanya bila benar-benar memahami risikonya.\n"
    );

    exit(1);
}

echo "SIMBA RESET DATABASE & SEED\n";
echo "===========================\n\n";
echo "Project     : {$root}\n";
echo "Environment : ".
    app()->environment().
    "\n";
echo "Database    : ".
    DB::connection()
        ->getDatabaseName().
    "\n\n";

echo "PERINGATAN: seluruh tabel, akun, transaksi, dan inventaris akan dihapus.\n";
echo $emptyInventory
    ? "Seeder akan membuat jurusan dan akun, kemudian seluruh barang alat/bahan dikosongkan.\n\n"
    : "Data baru akan dibuat kembali oleh seeder.\n\n";


if (! $force) {
    echo "Ketik tepat RESET SIMBA untuk melanjutkan: ";

    $confirmation =
        trim(
            (string)
            fgets(STDIN)
        );

    if (
        $confirmation
        !== 'RESET SIMBA'
    ) {
        echo "Dibatalkan. Database tidak diubah.\n";
        exit(0);
    }
}

$workshopSnapshot = [];

if (
    ! $defaultWorkshopsOnly
    && Schema::hasTable(
        'workshops'
    )
) {
    $columns =
        Schema::getColumnListing(
            'workshops'
        );

    $select = array_values(
        array_intersect(
            [
                'code',
                'name',
                'description',
                'is_active',
            ],
            $columns
        )
    );

    if (
        in_array(
            'code',
            $select,
            true
        )
        && in_array(
            'name',
            $select,
            true
        )
    ) {
        $workshopSnapshot =
            DB::table('workshops')
                ->orderBy('code')
                ->get($select)
                ->map(
                    static fn (
                        object $row
                    ): array =>
                        (array) $row
                )
                ->all();
    }
}

echo "\nJurusan tersimpan sebelum reset: ".
    count($workshopSnapshot).
    "\n";

echo "\nMenjalankan migrate:fresh --seed...\n\n";

$exitCode = Artisan::call(
    'migrate:fresh',
    [
        '--seed' => true,
        '--force' => true,
    ]
);

echo Artisan::output();

if ($exitCode !== 0) {
    fwrite(
        STDERR,
        "migrate:fresh --seed gagal dengan exit code {$exitCode}.\n"
    );

    exit($exitCode);
}

if ($workshopSnapshot !== []) {
    echo "\nMemulihkan daftar jurusan dinamis...\n";

    foreach (
        $workshopSnapshot
        as $workshop
    ) {
        $values = [
            'name' =>
                $workshop['name'],

            'description' =>
                $workshop[
                    'description'
                ]
                ?? null,

            'is_active' =>
                (bool) (
                    $workshop[
                        'is_active'
                    ]
                    ?? true
                ),
        ];

        if (
            Schema::hasColumn(
                'workshops',
                'updated_at'
            )
        ) {
            $values['updated_at'] =
                now();
        }

        $existing =
            DB::table('workshops')
                ->where(
                    'code',
                    strtoupper(
                        (string)
                        $workshop['code']
                    )
                )
                ->exists();

        if ($existing) {
            DB::table('workshops')
                ->where(
                    'code',
                    strtoupper(
                        (string)
                        $workshop['code']
                    )
                )
                ->update($values);

            continue;
        }

        $values['code'] =
            strtoupper(
                (string)
                $workshop['code']
            );

        if (
            Schema::hasColumn(
                'workshops',
                'created_at'
            )
        ) {
            $values['created_at'] =
                now();
        }

        DB::table('workshops')
            ->insert($values);
    }

    Artisan::call(
        'db:seed',
        [
            '--class' =>
                WorkshopRoleAccountSeeder::class,

            '--force' =>
                true,
        ]
    );

    echo Artisan::output();
}

if ($emptyInventory) {
    echo "\nMengosongkan seluruh tabel alat dan bahan...\n";

    app(
        InventoryResetService::class
    )->clear(
        $clearInventoryAudit
    );

    echo "Barang, unit alat, QR, stok, peminjaman, dan kerusakan telah dikosongkan.\n";
}

Artisan::call(
    'optimize:clear'
);

echo Artisan::output();

echo "\nHASIL RESET\n";
echo "-----------\n";

foreach (
    [
        'roles',
        'users',
        'workshops',
        'students',
        'storage_locations',
        'item_categories',
        'units',
        'items',
        'item_assets',
        'item_stock_movements',
        'loans',
        'loan_items',
        'damage_reports',
        'audit_logs',
    ]
    as $table
) {
    if (! Schema::hasTable($table)) {
        echo str_pad(
            $table,
            28
        ).
            ": TABEL TIDAK ADA\n";

        continue;
    }

    echo str_pad(
        $table,
        28
    ).
        ': '.
        DB::table($table)
            ->count().
        " baris\n";
}

echo "\nAKUN LOGIN UTAMA\n";
echo "-----------------\n";
echo "admin@simba.local / Admin123!\n";
echo "guru@simba.local  / Password123!\n\n";

echo "AKUN PER JURUSAN\n";
echo "-----------------\n";

$workshops = DB::table(
    'workshops'
)
    ->orderBy('code')
    ->get();

foreach ($workshops as $workshop) {
    $slug = strtolower(
        preg_replace(
            '/[^a-z0-9]+/i',
            '_',
            (string) $workshop->code
        )
    );

    echo $workshop->code.
        ":\n";

    echo "  Kepala Bengkel : kabeng.".
        $slug.
        "@simba.local / Password123!\n";

    echo "  Toolman        : toolman.".
        $slug.
        "@simba.local / Password123!\n";

    echo "  Siswa Demo     : siswa.".
        $slug.
        "@simba.local / Password123!\n";
}

echo "\nReset dan seeding selesai.\n";

if ($emptyInventory) {
    echo "Status inventaris: KOSONG dan siap input Barang Masuk baru.\n";
}

