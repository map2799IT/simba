<?php

declare(strict_types=1);

use App\Models\Loan;
use App\Models\User;
use App\Services\WorkshopLoanInventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA STUDENT LOAN FLOW CHECK\n";
echo "=============================\n\n";

$checks = [
    'loans.workshop_id' =>
        Schema::hasColumn(
            'loans',
            'workshop_id'
        ),

    'loans.scheduled_at' =>
        Schema::hasColumn(
            'loans',
            'scheduled_at'
        ),

    'Loan create view' =>
        View::exists(
            'loans.create'
        ),

    'Loan detail view' =>
        View::exists(
            'loans.show'
        ),

    'Route loans.create' =>
        Route::has(
            'loans.create'
        ),

    'Route loans.store' =>
        Route::has(
            'loans.store'
        ),

    'Route loans.show' =>
        Route::has(
            'loans.show'
        ),

    'Route loans.cancel' =>
        Route::has(
            'loans.cancel'
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

echo "\nROUTE ACTION\n";
echo "------------\n";

foreach (
    [
        'loans.create' =>
            'WorkshopLoanController@create',

        'loans.store' =>
            'WorkshopLoanController@store',

        'loans.show' =>
            'WorkshopLoanController@show',
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

echo "\nAKUN SISWA DAN TOOLMAN\n";
echo "----------------------\n";

$inventory =
    app(
        WorkshopLoanInventoryService::class
    );

$students =
    User::query()
        ->withoutGlobalScopes()
        ->where(
            'role',
            'siswa'
        )
        ->with('workshop')
        ->orderBy('id')
        ->get();

if ($students->isEmpty()) {
    echo "WARN: akun siswa tidak ditemukan.\n";
    $warnings++;
}

foreach ($students as $student) {
    echo str_pad(
        $student->username,
        30
    );

    if ($student->workshop_id === null) {
        echo ': WARN workshop_id NULL'.
            PHP_EOL;

        $warnings++;
        continue;
    }

    $toolmen =
        $inventory
            ->toolmenForWorkshop(
                (int)
                $student->workshop_id
            );

    $items =
        $inventory
            ->items(
                (int)
                $student->workshop_id
            );

    echo ': workshop='.
        ($student->workshop?->code ?? $student->workshop_id).
        ', barang='.
        $items->count().
        ', toolman='.
        $toolmen->count().
        PHP_EOL;

    if ($items->isEmpty()) {
        echo "  WARN: siswa tidak mempunyai pilihan barang.\n";
        $warnings++;
    }

    if ($toolmen->isEmpty()) {
        echo "  WARN: tidak ada Toolman aktif pada jurusan siswa.\n";
        $warnings++;
    }
}

echo "\nPENGAJUAN SISWA\n";
echo "---------------\n";

$studentLoans =
    Loan::query()
        ->withoutGlobalScopes()
        ->whereHas(
            'borrower',
            fn ($query) =>
                $query
                    ->withoutGlobalScopes()
                    ->where(
                        'role',
                        'siswa'
                    )
        )
        ->with([
            'borrower',
            'workshop',
            'assignedToolman',
        ])
        ->orderByDesc('id')
        ->limit(20)
        ->get();

if ($studentLoans->isEmpty()) {
    echo "Belum ada pengajuan siswa.\n";
} else {
    foreach ($studentLoans as $loan) {
        echo $loan->code.
            ' | siswa='.
            ($loan->borrower?->username ?? '-').
            ' | workshop='.
            ($loan->workshop?->code ?? 'NULL').
            ' | status='.
            $loan->status.
            ' | toolman='.
            ($loan->assignedToolman?->username ?? 'menunggu').
            ' | item='.
            $loan->items()->count().
            PHP_EOL;
    }
}

echo "\nVIEW ERROR FEEDBACK\n";
echo "-------------------\n";

$create =
    file_get_contents(
        $root.
        '/resources/views/loans/create.blade.php'
    );

$row =
    file_get_contents(
        $root.
        '/resources/views/loans/_row.blade.php'
    );

$feedbackChecks = [
    'Global validation errors' =>
        is_string($create)
        && str_contains(
            $create,
            '$errors->any()'
        ),

    'Error barang per baris' =>
        is_string($row)
        && str_contains(
            $row,
            'items.{$index}.item_id'
        ),

    'Error jumlah per baris' =>
        is_string($row)
        && str_contains(
            $row,
            'items.{$index}.quantity'
        ),

    'Status Mengirim pengajuan' =>
        is_string($create)
        && str_contains(
            $create,
            'Mengirim pengajuan...'
        ),

    'Informasi antrean Toolman' =>
        is_string($create)
        && str_contains(
            $create,
            'workshopToolmen'
        ),
];

foreach (
    $feedbackChecks
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

echo "\nSTATUS\n";
echo "------\n";
echo 'FAIL: '.
    ($failed ? 'YA' : 'TIDAK').
    PHP_EOL;
echo "WARN: {$warnings}\n";

echo "\n".
    (
        $failed
            ? 'STUDENT LOAN FLOW BELUM VALID.'
            : (
                $warnings > 0
                    ? 'KODE VALID, TETAPI DATA SISWA/TOOLMAN/STOK PERLU DIPERIKSA.'
                    : 'STUDENT LOAN FLOW SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
