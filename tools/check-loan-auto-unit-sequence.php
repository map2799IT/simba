<?php

declare(strict_types=1);

use App\Models\Loan;
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

echo "SIMBA LOAN AUTO UNIT SEQUENCE CHECK\n";
echo "===================================\n\n";

$checks = [
    'LoanItem model' =>
        class_exists(
            \App\Models\LoanItem::class
        ),

    'LoanItem.loan relation' =>
        method_exists(
            \App\Models\LoanItem::class,
            'loan'
        ),

    'Inventory service' =>
        class_exists(
            WorkshopLoanInventoryService::class
        ),

    'View row' =>
        View::exists(
            'loans._row'
        ),

    'View create' =>
        View::exists(
            'loans.create'
        ),

    'Route loans.store' =>
        Route::has(
            'loans.store'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 52).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nKODE PEMILIHAN OTOMATIS\n";
echo "-----------------------\n";

$controller =
    file_get_contents(
        $root.
        '/app/Http/Controllers/WorkshopLoanController.php'
    );

$service =
    file_get_contents(
        $root.
        '/app/Services/WorkshopLoanInventoryService.php'
    );

$rowView =
    file_get_contents(
        $root.
        '/resources/views/loans/_row.blade.php'
    );

$createView =
    file_get_contents(
        $root.
        '/resources/views/loans/create.blade.php'
    );

$codeChecks = [
    'Form tidak mengirim asset_ids' =>
        is_string($rowView)
        && ! str_contains(
            $rowView,
            'asset_ids'
        ),

    'Form memakai input jumlah' =>
        is_string($rowView)
        && str_contains(
            $rowView,
            '[quantity]'
        ),

    'Preview unit otomatis' =>
        is_string($rowView)
        && str_contains(
            $rowView,
            'auto-unit-preview'
        ),

    'JavaScript memilih urutan pertama' =>
        is_string($createView)
        && str_contains(
            $createView,
            'available.slice('
        ),

    'Backend tidak membaca asset_ids' =>
        is_string($controller)
        && ! str_contains(
            $controller,
            "'asset_ids'"
        ),

    'Backend memakai quantity alat' =>
        is_string($controller)
        && str_contains(
            $controller,
            'Jumlah alat wajib berupa bilangan bulat.'
        ),

    'Backend panggil selector urutan' =>
        is_string($controller)
        && str_contains(
            $controller,
            'selectToolAssetsBySequence('
        ),

    'Urutan berdasarkan asset_number' =>
        is_string($service)
        && str_contains(
            $service,
            "->orderBy(\n                    'asset_number'"
        ),

    'Unit pengajuan aktif dikecualikan' =>
        is_string($service)
        && str_contains(
            $service,
            'ACTIVE_ALLOCATION_STATUSES'
        )
        && str_contains(
            $service,
            'whereDoesntHave('
        ),

    'Pemilihan memakai lockForUpdate' =>
        is_string($service)
        && str_contains(
            $service,
            'lockForUpdate()'
        ),
];

foreach (
    $codeChecks
    as $label => $valid
) {
    echo str_pad($label, 52).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nSIMULASI URUTAN PER JURUSAN\n";
echo "---------------------------\n";

$inventory =
    app(
        WorkshopLoanInventoryService::class
    );

$users =
    User::query()
        ->withoutGlobalScopes()
        ->whereIn(
            'role',
            [
                'guru',
                'siswa',
                'toolman',
            ]
        )
        ->orderBy('role')
        ->orderBy('id')
        ->get();

foreach ($users as $user) {
    if (
        (string) $user->role
        === 'siswa'
        && $user->workshop_id
            === null
    ) {
        continue;
    }

    $request =
        Request::create(
            '/loans/create',
            'GET'
        );

    $request->setUserResolver(
        static fn () =>
            $user
    );

    try {
        $workshop =
            $inventory
                ->selectedWorkshop(
                    $request
                );

        $items =
            $inventory
                ->items(
                    (int) $workshop->id
                );

        $tool =
            $items->first(
                fn ($item) =>
                    $item->isTool()
            );

        echo str_pad(
            $user->role.
            ' '.
            $user->username.
            ' / '.
            $workshop->code,
            38
        ).
            ': barang='.
            $items->count();

        if ($tool === null) {
            echo ', alat=0'.
                PHP_EOL;

            continue;
        }

        $selected =
            $inventory
                ->selectToolAssetsBySequence(
                    (int) $tool->id,
                    (int) $workshop->id,
                    min(
                        3,
                        (int)
                        $tool
                            ->workshop_available_stock
                    ),
                    false
                );

        $numbers =
            $selected
                ->pluck(
                    'asset_number'
                )
                ->implode(', ');

        echo ', contoh='.
            (
                $numbers !== ''
                    ? $numbers
                    : '-'
            ).
            PHP_EOL;

        $sorted =
            $selected
                ->pluck(
                    'asset_number'
                )
                ->values();

        $expected =
            $sorted
                ->sort()
                ->values();

        if (
            $sorted->all()
            !== $expected->all()
        ) {
            $failed = true;

            echo "  GAGAL: urutan asset_number tidak menaik.\n";
        }
    } catch (Throwable $exception) {
        echo ' GAGAL '.
            $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
}

echo "\nROUTE ACTION\n";
echo "------------\n";

$route =
    Route::getRoutes()
        ->getByName(
            'loans.store'
        );

$action =
    $route?->getActionName();

$routeValid =
    is_string($action)
    && str_contains(
        $action,
        'WorkshopLoanController@store'
    );

echo str_pad(
    'loans.store',
    52
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

echo "\nALUR\n";
echo "----\n";
echo "1. Pengguna memilih barang.\n";
echo "2. Pengguna mengisi jumlah.\n";
echo "3. UI menampilkan nomor unit terkecil sebagai pratinjau.\n";
echo "4. Server mengunci dan memilih ulang unit berdasarkan asset_number.\n";
echo "5. Unit pada pengajuan aktif tidak ditawarkan ke pengajuan lain.\n";
echo "6. Reject/cancel membuat unit tersedia untuk alokasi berikutnya.\n";

echo "\n".
    (
        $failed
            ? 'LOAN AUTO UNIT SEQUENCE BELUM VALID.'
            : (
                $warnings > 0
                    ? 'KODE VALID DENGAN PERINGATAN DATA.'
                    : 'LOAN AUTO UNIT SEQUENCE SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
