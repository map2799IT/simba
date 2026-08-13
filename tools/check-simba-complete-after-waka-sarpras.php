<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SimbaRoleAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$options =
    getopt(
        '',
        [
            'run-existing',
            'strict-warnings',
        ]
    );

$runExisting =
    array_key_exists(
        'run-existing',
        $options
    );

$strictWarnings =
    array_key_exists(
        'strict-warnings',
        $options
    );

$failed = false;
$warnings = 0;

$section =
    static function (
        string $title
    ): void {
        echo "\n{$title}\n";
        echo str_repeat(
            '-',
            strlen($title)
        ).
            "\n";
    };

$result =
    static function (
        string $label,
        bool $valid,
        string $detail = ''
    ) use (
        &$failed
    ): void {
        echo str_pad($label, 56).
            ': '.
            ($valid ? 'OK' : 'GAGAL').
            (
                $detail !== ''
                    ? ' '.$detail
                    : ''
            ).
            PHP_EOL;

        if (! $valid) {
            $failed = true;
        }
    };

$warn =
    static function (
        string $message
    ) use (
        &$warnings
    ): void {
        echo "WARN: {$message}\n";
        $warnings++;
    };

echo "SIMBA COMPLETE SYSTEM CHECK AFTER WAKA SARPRAS\n";
echo "==============================================\n";
echo 'Waktu     : '.
    now()->format(
        'd-m-Y H:i:s'
    ).
    PHP_EOL;
echo 'Timezone  : '.
    config(
        'app.timezone'
    ).
    PHP_EOL;
echo 'Laravel   : '.
    app()->version().
    PHP_EOL;
echo 'PHP       : '.
    PHP_VERSION.
    PHP_EOL;

$section('1. FILE DAN CLASS UTAMA');

$files = [
    'app/Support/SimbaRoleAccess.php',
    'app/Http/Middleware/EnforceSimbaRoleAccess.php',
    'app/Http/Controllers/BorrowerAwareDashboardController.php',
    'app/Http/Controllers/LocationInventoryController.php',
    'app/Services/InventoryAccessService.php',
    'app/Services/InventoryPlacementReportService.php',
    'resources/views/layouts/sidebar.blade.php',
    'resources/views/dashboard/waka-sarpras.blade.php',
    'routes/location-inventory-two-modes.php',
];

foreach ($files as $file) {
    $result(
        $file,
        is_file(
            $root.'/'.$file
        )
    );
}

$section('2. DATABASE DAN MIGRATION');

$tables = [
    'users',
    'roles',
    'workshops',
    'storage_locations',
    'items',
    'item_assets',
    'item_stock_movements',
    'loans',
    'loan_items',
    'damage_reports',
    'stock_receipt_change_requests',
];

foreach ($tables as $table) {
    $exists =
        Schema::hasTable(
            $table
        );

    if (
        $table
        === 'roles'
        || $table
        === 'stock_receipt_change_requests'
    ) {
        echo str_pad(
            "Tabel {$table}",
            56
        ).
            ': '.
            ($exists ? 'OK' : 'WARN OPSIONAL').
            PHP_EOL;

        if (! $exists) {
            $warnings++;
        }

        continue;
    }

    $result(
        "Tabel {$table}",
        $exists
    );
}

$columns = [
    ['users', 'role'],
    ['users', 'workshop_id'],
    ['items', 'asset_prefix'],
    ['item_assets', 'receipt_code'],
    ['item_assets', 'workshop_id'],
    ['item_assets', 'storage_location_id'],
    ['item_stock_movements', 'workshop_id'],
    ['loans', 'workshop_id'],
    ['loans', 'scheduled_at'],
    ['loan_items', 'item_asset_id'],
    ['loan_items', 'is_consumable'],
];

foreach ($columns as [$table, $column]) {
    $result(
        "{$table}.{$column}",
        Schema::hasTable($table)
        && Schema::hasColumn(
            $table,
            $column
        )
    );
}

$section('3. ROLE DAN AKUN');

$roles = [
    'admin',
    'wakil_sarpras',
    'kepala_bengkel',
    'toolman',
    'guru',
    'siswa',
];

if (Schema::hasTable('users')) {
    foreach ($roles as $role) {
        $count =
            DB::table('users')
                ->where(
                    'role',
                    $role
                )
                ->count();

        echo str_pad(
            "Akun {$role}",
            56
        ).
            ': '.
            $count.
            PHP_EOL;

        if (
            in_array(
                $role,
                [
                    'admin',
                    'wakil_sarpras',
                    'toolman',
                    'guru',
                    'siswa',
                ],
                true
            )
            && $count === 0
        ) {
            $warn(
                "belum ada akun {$role}."
            );
        }
    }

    $invalidWaka =
        DB::table('users')
            ->where(
                'role',
                'wakil_sarpras'
            )
            ->whereNotNull(
                'workshop_id'
            )
            ->count();

    $result(
        'Akun Waka workshop_id NULL/global',
        $invalidWaka === 0,
        "invalid={$invalidWaka}"
    );

    $studentsWithoutWorkshop =
        DB::table('users')
            ->where(
                'role',
                'siswa'
            )
            ->whereNull(
                'workshop_id'
            )
            ->count();

    if ($studentsWithoutWorkshop > 0) {
        $warn(
            "{$studentsWithoutWorkshop} siswa belum mempunyai jurusan."
        );
    }

    $toolmenWithoutWorkshop =
        DB::table('users')
            ->where(
                'role',
                'toolman'
            )
            ->whereNull(
                'workshop_id'
            )
            ->count();

    if ($toolmenWithoutWorkshop > 0) {
        $warn(
            "{$toolmenWithoutWorkshop} Toolman belum mempunyai jurusan."
        );
    }
}

$section('4. MATRKS AKSES ROLE');

$access =
    app(
        SimbaRoleAccess::class
    );

$makeUser =
    static function (
        string $role,
        ?int $workshopId = null
    ): User {
        $user = new User();

        $user->forceFill([
            'id' => random_int(
                100000,
                999999
            ),
            'name' => 'Checker '.$role,
            'role' => $role,
            'workshop_id' => $workshopId,
        ]);

        return $user;
    };

$waka =
    $makeUser(
        'wakil_sarpras'
    );

$result(
    'Waka dapat laporan inventaris GET',
    $access->canRoute(
        $waka,
        'reports.inventory',
        'GET',
        'reports/inventory'
    )
);

$result(
    'Waka dapat lokasi print GET',
    $access->canRoute(
        $waka,
        'locations.inventory.summary.print',
        'GET',
        'locations/1/inventory/summary/print'
    )
);

$result(
    'Waka diblokir Master Barang',
    ! $access->canRoute(
        $waka,
        'items.index',
        'GET',
        'items'
    )
);

$result(
    'Waka diblokir Barang Masuk',
    ! $access->canRoute(
        $waka,
        'stock-receipts.index',
        'GET',
        'stock-receipts'
    )
);

$result(
    'Waka diblokir POST laporan',
    ! $access->canRoute(
        $waka,
        'reports.inventory',
        'POST',
        'reports/inventory'
    )
);

foreach (
    [
        'guru',
        'siswa',
    ]
    as $borrowerRole
) {
    $borrower =
        $makeUser(
            $borrowerRole,
            1
        );

    $result(
        ucfirst($borrowerRole).
            ' dapat loans.create',
        $access->canRoute(
            $borrower,
            'loans.create',
            'GET',
            'loans/create'
        )
    );

    $result(
        ucfirst($borrowerRole).
            ' diblokir items.index',
        ! $access->canRoute(
            $borrower,
            'items.index',
            'GET',
            'items'
        )
    );

    $result(
        ucfirst($borrowerRole).
            ' diblokir reports.inventory',
        ! $access->canRoute(
            $borrower,
            'reports.inventory',
            'GET',
            'reports/inventory'
        )
    );
}

$section('5. ROUTE UTAMA');

$routeNames = [
    'dashboard',
    'items.index',
    'item-assets.index',
    'stock-receipts.index',
    'stock-issues.index',
    'stock-movements.index',
    'loans.index',
    'loans.create',
    'loans.store',
    'loans.approve',
    'loans.checkout',
    'loans.returns.index',
    'damage-reports.index',
    'reports.inventory',
    'reports.stock',
    'reports.loans',
    'reports.damages',
    'reports.stock-movements',
    'locations.inventory.menu',
    'locations.inventory.summary',
    'locations.inventory.summary.print',
    'locations.inventory.summary.pdf',
    'locations.inventory.complete',
    'locations.inventory.complete.pdf',
];

foreach ($routeNames as $name) {
    $route =
        Route::getRoutes()
            ->getByName($name);

    echo str_pad($name, 56).
        ': '.
        (
            $route
                ? 'OK '.
                    $route->getActionName()
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $route) {
        $failed = true;
    }
}

$duplicates =
    collect(
        Route::getRoutes()
    )
        ->filter(
            fn ($route) =>
                $route->getName()
                !== null
        )
        ->groupBy(
            fn ($route) =>
                $route->getName()
        )
        ->filter(
            fn ($routes) =>
                $routes->count()
                > 1
        );

if ($duplicates->isNotEmpty()) {
    $warn(
        'terdapat nama route ganda: '.
        $duplicates
            ->keys()
            ->take(10)
            ->implode(', ')
    );
}

$section('6. NOMOR INVENTARIS DAN UNIT FISIK');

if (Schema::hasTable('item_assets')) {
    $duplicateNumbers =
        DB::table('item_assets')
            ->select(
                'asset_number',
                DB::raw(
                    'COUNT(*) AS total'
                )
            )
            ->groupBy(
                'asset_number'
            )
            ->havingRaw(
                'COUNT(*) > 1'
            )
            ->count();

    $result(
        'Nomor inventaris unik',
        $duplicateNumbers === 0,
        "ganda={$duplicateNumbers}"
    );

    $missingWorkshop =
        DB::table('item_assets')
            ->whereNull(
                'workshop_id'
            )
            ->count();

    $result(
        'Unit alat mempunyai workshop_id',
        $missingWorkshop === 0,
        "kosong={$missingWorkshop}"
    );

    $missingLocation =
        DB::table('item_assets')
            ->whereNull(
                'storage_location_id'
            )
            ->count();

    if ($missingLocation > 0) {
        $warn(
            "{$missingLocation} unit alat belum mempunyai lokasi."
        );
    }

    $invalidStatus =
        DB::table('item_assets')
            ->whereNotIn(
                'status',
                [
                    'available',
                    'reserved',
                    'borrowed',
                    'damaged',
                    'under_repair',
                    'maintenance',
                    'lost',
                    'retired',
                ]
            )
            ->count();

    $result(
        'Status unit dikenal',
        $invalidStatus === 0,
        "invalid={$invalidStatus}"
    );

    if (
        Schema::hasColumn(
            'items',
            'asset_prefix'
        )
    ) {
        $toolWithoutPrefix =
            DB::table('items')
                ->where(
                    'type',
                    'tool'
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    fn ($query) =>
                        $query
                            ->whereNull(
                                'asset_prefix'
                            )
                            ->orWhere(
                                'asset_prefix',
                                ''
                            )
                )
                ->count();

        if ($toolWithoutPrefix > 0) {
            $warn(
                "{$toolWithoutPrefix} master alat belum mempunyai asset_prefix."
            );
        }
    }
}

$section('7. BARANG MASUK DAN STOK');

if (
    Schema::hasTable(
        'item_stock_movements'
    )
) {
    $movementWithoutWorkshop =
        Schema::hasColumn(
            'item_stock_movements',
            'workshop_id'
        )
            ? DB::table(
                'item_stock_movements'
            )
                ->whereNull(
                    'workshop_id'
                )
                ->count()
            : 0;

    if ($movementWithoutWorkshop > 0) {
        $warn(
            "{$movementWithoutWorkshop} movement belum mempunyai workshop_id."
        );
    }

    $negativeStock =
        DB::table(
            'item_stock_movements'
        )
            ->where(
                'stock_after',
                '<',
                0
            )
            ->count();

    $result(
        'Movement tidak menghasilkan stok negatif',
        $negativeStock === 0,
        "negatif={$negativeStock}"
    );
}

if (
    Schema::hasTable(
        'item_stock_movements'
    )
    && Schema::hasTable(
        'item_assets'
    )
    && Schema::hasColumn(
        'item_stock_movements',
        'receipt_code'
    )
    && Schema::hasColumn(
        'item_assets',
        'receipt_code'
    )
) {
    $receiptRows =
        DB::table(
            'item_stock_movements as m'
        )
            ->join(
                'items as i',
                'i.id',
                '=',
                'm.item_id'
            )
            ->where(
                'm.type',
                'incoming'
            )
            ->where(
                'i.type',
                'tool'
            )
            ->whereNotNull(
                'm.receipt_code'
            )
            ->select([
                'm.id',
                'm.receipt_code',
                'm.quantity',
            ])
            ->get();

    $receiptMismatch = 0;

    foreach ($receiptRows as $receipt) {
        $assets =
            DB::table(
                'item_assets'
            )
                ->where(
                    'receipt_code',
                    $receipt->receipt_code
                )
                ->count();

        if (
            $assets !== (int)
                round(
                    (float)
                    $receipt->quantity
                )
        ) {
            $receiptMismatch++;
        }
    }

    if ($receiptMismatch > 0) {
        $warn(
            "{$receiptMismatch} penerimaan alat tidak sama dengan jumlah unit fisik."
        );
    }
}

$section('8. PEMINJAMAN DAN PENGEMBALIAN');

if (
    Schema::hasTable('loans')
    && Schema::hasTable('loan_items')
) {
    $scheduledMissing =
        Schema::hasColumn(
            'loans',
            'scheduled_at'
        )
            ? DB::table('loans')
                ->whereNull(
                    'scheduled_at'
                )
                ->count()
            : 0;

    if ($scheduledMissing > 0) {
        $warn(
            "{$scheduledMissing} loan belum mempunyai scheduled_at."
        );
    }

    $activeDuplicateAssets =
        DB::table(
            'loan_items as li'
        )
            ->join(
                'loans as l',
                'l.id',
                '=',
                'li.loan_id'
            )
            ->whereNotNull(
                'li.item_asset_id'
            )
            ->whereNull(
                'li.returned_at'
            )
            ->whereIn(
                'l.status',
                [
                    'pending',
                    'approved',
                    'borrowed',
                    'partially_returned',
                    'active',
                    'checked_out',
                ]
            )
            ->select(
                'li.item_asset_id',
                DB::raw(
                    'COUNT(*) AS total'
                )
            )
            ->groupBy(
                'li.item_asset_id'
            )
            ->havingRaw(
                'COUNT(*) > 1'
            )
            ->count();

    $result(
        'Tidak ada unit dipakai dua loan aktif',
        $activeDuplicateAssets === 0,
        "ganda={$activeDuplicateAssets}"
    );

    $toolLoanWithoutAsset =
        DB::table(
            'loan_items as li'
        )
            ->join(
                'items as i',
                'i.id',
                '=',
                'li.item_id'
            )
            ->where(
                'i.type',
                'tool'
            )
            ->whereNull(
                'li.item_asset_id'
            )
            ->count();

    if ($toolLoanWithoutAsset > 0) {
        $warn(
            "{$toolLoanWithoutAsset} loan item alat belum mempunyai item_asset_id."
        );
    }

    $borrowedStatusMismatch =
        DB::table(
            'loan_items as li'
        )
            ->join(
                'loans as l',
                'l.id',
                '=',
                'li.loan_id'
            )
            ->join(
                'item_assets as a',
                'a.id',
                '=',
                'li.item_asset_id'
            )
            ->whereNull(
                'li.returned_at'
            )
            ->whereIn(
                'l.status',
                [
                    'borrowed',
                    'partially_returned',
                    'active',
                    'checked_out',
                ]
            )
            ->where(
                'a.status',
                '!=',
                'borrowed'
            )
            ->count();

    if ($borrowedStatusMismatch > 0) {
        $warn(
            "{$borrowedStatusMismatch} unit loan aktif tidak berstatus borrowed."
        );
    }
}

$section('9. JURUSAN, TOOLMAN, DAN LOKASI');

if (Schema::hasTable('workshops')) {
    $workshops =
        DB::table('workshops')
            ->when(
                Schema::hasColumn(
                    'workshops',
                    'is_active'
                ),
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->orderBy('code')
            ->get();

    foreach ($workshops as $workshop) {
        $toolmen =
            Schema::hasTable('users')
                ? DB::table('users')
                    ->where(
                        'role',
                        'toolman'
                    )
                    ->where(
                        'workshop_id',
                        $workshop->id
                    )
                    ->when(
                        Schema::hasColumn(
                            'users',
                            'is_active'
                        ),
                        fn ($query) =>
                            $query->where(
                                'is_active',
                                true
                            )
                    )
                    ->count()
                : 0;

        $locations =
            Schema::hasTable(
                'storage_locations'
            )
                ? DB::table(
                    'storage_locations'
                )
                    ->where(
                        'workshop_id',
                        $workshop->id
                    )
                    ->count()
                : 0;

        $assets =
            Schema::hasTable(
                'item_assets'
            )
                ? DB::table(
                    'item_assets'
                )
                    ->where(
                        'workshop_id',
                        $workshop->id
                    )
                    ->count()
                : 0;

        echo str_pad(
            $workshop->code.
            ' — '.
            $workshop->name,
            42
        ).
            ' | toolman='.
            $toolmen.
            ' lokasi='.
            $locations.
            ' unit='.
            $assets.
            PHP_EOL;

        if ($toolmen === 0) {
            $warn(
                "jurusan {$workshop->code} belum mempunyai Toolman aktif."
            );
        }

        if ($locations === 0) {
            $warn(
                "jurusan {$workshop->code} belum mempunyai lokasi."
            );
        }
    }
}

$section('10. CHECKER MODUL TERPASANG');

$existingCheckers = [
    'tools/check-waka-sarpras-access.php',
    'tools/check-asset-number-per-item.php',
    'tools/check-stock-receipt-crud-approval.php',
    'tools/check-stock-receipt-photo-view.php',
    'tools/check-toolman-stock-out-and-receipt-edit.php',
    'tools/check-complete-inventory-loan-flow.php',
    'tools/check-student-loan-flow.php',
    'tools/check-loan-auto-unit-sequence.php',
    'tools/check-inventory-placement-report.php',
    'tools/check-pagination-ui.php',
    'tools/check-bulk-qr-workshop-scope.php',
    'tools/check-simba-all-features.php',
];

foreach ($existingCheckers as $checker) {
    $exists =
        is_file(
            $root.'/'.$checker
        );

    echo str_pad($checker, 56).
        ': '.
        ($exists ? 'ADA' : 'TIDAK ADA').
        PHP_EOL;

    if (! $exists) {
        $warnings++;
    }
}

if ($runExisting) {
    $section('11. MENJALANKAN CHECKER MODUL');

    $skip = [
        'tools/check-simba-all-features.php',
    ];

    foreach ($existingCheckers as $checker) {
        if (
            in_array(
                $checker,
                $skip,
                true
            )
            || ! is_file(
                $root.'/'.$checker
            )
            || $checker
                === 'tools/check-simba-complete-after-waka-sarpras.php'
        ) {
            continue;
        }

        echo "\n>>> {$checker}\n";

        passthru(
            escapeshellarg(
                PHP_BINARY
            ).
            ' '.
            escapeshellarg(
                $root.'/'.$checker
            ),
            $exitCode
        );

        if ($exitCode !== 0) {
            $failed = true;
            echo "CHECKER GAGAL: {$checker} exit={$exitCode}\n";
        }
    }
}

$section('RINGKASAN AKHIR');

echo 'FAIL : '.
    ($failed ? 'YA' : 'TIDAK').
    PHP_EOL;
echo "WARN : {$warnings}\n";

if (
    $strictWarnings
    && $warnings > 0
) {
    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'SIMBA COMPLETE CHECK BELUM VALID.'
            : (
                $warnings > 0
                    ? 'FITUR UTAMA VALID, TETAPI ADA PERINGATAN DATA/FILE OPSIONAL.'
                    : 'SIMBA COMPLETE CHECK SUDAH VALID.'
            )
    ).
    PHP_EOL;

echo "\nHTTP smoke test opsional:\n";
echo PHP_BINARY.
    " tools/check-simba-all-features.php --http --all-roles --existing-checkers\n";

exit($failed ? 1 : 0);
