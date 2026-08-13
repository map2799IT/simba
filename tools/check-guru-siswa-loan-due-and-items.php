<?php

declare(strict_types=1);

use App\Models\ItemAsset;
use App\Models\User;
use App\Services\WorkshopLoanInventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA GURU/SISWA DUE DATE & ITEM CHECK\n";
echo "======================================\n\n";

$checks = [
    'Loan controller' =>
        class_exists(
            \App\Http\Controllers\WorkshopLoanController::class
        ),

    'Inventory service' =>
        class_exists(
            WorkshopLoanInventoryService::class
        ),

    'Loan create view' =>
        View::exists(
            'loans.create'
        ),

    'Loan row partial' =>
        View::exists(
            'loans._row'
        ),

    'Route loans.create' =>
        Route::has(
            'loans.create'
        ),

    'Route loans.store' =>
        Route::has(
            'loans.store'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 48).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nATURAN JATUH TEMPO\n";
echo "------------------\n";

$controllerFile =
    $root.
    '/app/Http/Controllers/WorkshopLoanController.php';

$controller =
    is_file($controllerFile)
        ? file_get_contents(
            $controllerFile
        )
        : false;

$dueChecks = [
    'Guru otomatis tambah 3 hari' =>
        is_string($controller)
        && str_contains(
            $controller,
            '->addDays(3)'
        ),

    'Siswa otomatis tambah 3 jam' =>
        is_string($controller)
        && str_contains(
            $controller,
            '->addHours(3)'
        ),

    'Siswa wajib tanggal sama' =>
        is_string($controller)
        && str_contains(
            $controller,
            '->isSameDay('
        ),

    'Backend mengabaikan due_at borrower' =>
        is_string($controller)
        && str_contains(
            $controller,
            'resolveDueAt('
        ),
];

foreach (
    $dueChecks
    as $label => $valid
) {
    echo str_pad($label, 48).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nBARANG TERSEDIA PER ROLE\n";
echo "------------------------\n";

$service =
    app(
        WorkshopLoanInventoryService::class
    );

foreach (
    [
        'guru',
        'siswa',
    ]
    as $role
) {
    $account =
        User::query()
            ->withoutGlobalScopes()
            ->where(
                'role',
                $role
            )
            ->orderBy('id')
            ->first();

    if ($account === null) {
        echo str_pad(
            "Akun {$role}",
            48
        ).
            ': WARN tidak ditemukan'.
            PHP_EOL;

        $warnings++;
        continue;
    }

    $request =
        Request::create(
            '/loans/create',
            'GET'
        );

    $request->setUserResolver(
        static fn () =>
            $account
    );

    if (
        $role === 'siswa'
        && $account->workshop_id
            === null
    ) {
        echo str_pad(
            'Siswa memiliki workshop_id',
            48
        ).
            ': WARN belum diatur'.
            PHP_EOL;

        $warnings++;
        continue;
    }

    try {
        $workshop =
            $service
                ->selectedWorkshop(
                    $request
                );

        $items =
            $service
                ->items(
                    (int)
                    $workshop->id
                );

        $assets =
            $service
                ->assets(
                    (int)
                    $workshop->id
                );

        $summary =
            $service
                ->inventorySummary(
                    (int)
                    $workshop->id
                );

        echo str_pad(
            "{$role} / {$workshop->code}",
            48
        ).
            ': barang='.
            $items->count().
            ', unit='.
            $assets->count().
            ', movement='.
            $summary[
                'movement_rows'
            ].
            PHP_EOL;

        if ($items->isEmpty()) {
            echo "  PERINGATAN: dropdown barang akan kosong pada jurusan ini.\n";
            echo "  Unit available=".
                $summary[
                    'available_asset_units'
                ].
                ', bahan positif='.
                $summary[
                    'available_material_items'
                ].
                ".\n";

            $warnings++;
        }
    } catch (Throwable $exception) {
        echo str_pad(
            $role,
            48
        ).
            ': GAGAL '.
            $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
}

echo "\nSTATUS UNIT ALAT\n";
echo "----------------\n";

$statusRows =
    ItemAsset::query()
        ->withoutGlobalScopes()
        ->selectRaw(
            'status, is_active, COUNT(*) AS total'
        )
        ->groupBy(
            'status',
            'is_active'
        )
        ->orderBy('status')
        ->get();

if ($statusRows->isEmpty()) {
    echo "WARN: item_assets kosong.\n";
    $warnings++;
} else {
    foreach ($statusRows as $row) {
        echo 'status='.
            $row->status.
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
}

echo "\nROUTE ACTION\n";
echo "------------\n";

foreach (
    [
        'loans.create' =>
            'WorkshopLoanController@create',

        'loans.store' =>
            'WorkshopLoanController@store',
    ]
    as $name => $expected
) {
    $route =
        Route::getRoutes()
            ->getByName($name);

    $action =
        $route?->getActionName();

    $valid =
        is_string($action)
        && str_contains(
            $action,
            $expected
        );

    echo str_pad($name, 48).
        ': '.
        ($valid ? 'OK ' : 'GAGAL ').
        ($action ?? '-').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nSTATUS\n";
echo "------\n";
echo 'FAIL: '.
    ($failed ? 'YA' : 'TIDAK').
    PHP_EOL;
echo "WARN: {$warnings}\n";

echo "\n".
    (
        $failed
            ? 'GURU/SISWA DUE DATE & ITEM BELUM VALID.'
            : (
                $warnings > 0
                    ? 'KODE VALID, TETAPI ADA DATA JURUSAN/STOK YANG PERLU DIPERIKSA.'
                    : 'GURU/SISWA DUE DATE & ITEM SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
