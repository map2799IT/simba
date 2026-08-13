<?php

declare(strict_types=1);

use App\Models\Loan;
use App\Models\User;
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

echo "SIMBA GURU SCHEDULED LOAN & WORKSHOP APPROVAL CHECK\n";
echo "===================================================\n\n";

$checks = [
    'loans.scheduled_at' =>
        Schema::hasColumn(
            'loans',
            'scheduled_at'
        ),

    'Loan model scheduled_at fillable' =>
        in_array(
            'scheduled_at',
            (new Loan())->getFillable(),
            true
        ),

    'Loan scheduled_at cast datetime' =>
        (new Loan())->getCasts()[
            'scheduled_at'
        ] ?? null
        === 'datetime',

    'View create' =>
        View::exists(
            'loans.create'
        ),

    'View index' =>
        View::exists(
            'loans.index'
        ),

    'View show' =>
        View::exists(
            'loans.show'
        ),

    'Route create' =>
        Route::has(
            'loans.create'
        ),

    'Route approve' =>
        Route::has(
            'loans.approve'
        ),

    'Route checkout' =>
        Route::has(
            'loans.checkout'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 50).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nKODE WORKFLOW\n";
echo "-------------\n";

$controllerFile =
    file_get_contents(
        $root.
        '/app/Http/Controllers/WorkshopLoanController.php'
    );

$serviceFile =
    file_get_contents(
        $root.
        '/app/Services/WorkshopLoanTransactionService.php'
    );

$workflowChecks = [
    'Guru memilih jadwal peminjaman' =>
        is_string($controllerFile)
        && str_contains(
            $controllerFile,
            'resolveScheduledAt('
        ),

    'Jadwal disimpan ke scheduled_at' =>
        is_string($controllerFile)
        && str_contains(
            $controllerFile,
            "'scheduled_at' =>"
        ),

    'Guru jatuh tempo +3 hari' =>
        is_string($controllerFile)
        && str_contains(
            $controllerFile,
            '->addDays(3)'
        ),

    'Pending belum ditetapkan ke Toolman' =>
        is_string($controllerFile)
        && str_contains(
            $controllerFile,
            "'assigned_toolman_id' =>"
        )
        && str_contains(
            $controllerFile,
            'null,'
        ),

    'Toolman approve dicatat' =>
        is_string($serviceFile)
        && str_contains(
            $serviceFile,
            "'assigned_toolman_id' =>"
        ),

    'Toolman dibatasi workshop' =>
        is_string($serviceFile)
        && str_contains(
            $serviceFile,
            'Toolman hanya dapat menyetujui peminjaman pada jurusannya.'
        ),

    'Checkout menunggu scheduled_at' =>
        is_string($serviceFile)
        && str_contains(
            $serviceFile,
            'Serah terima belum dapat dilakukan.'
        ),
];

foreach (
    $workflowChecks
    as $label => $valid
) {
    echo str_pad($label, 50).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nAKUN DAN CAKUPAN TOOLMAN\n";
echo "------------------------\n";

$toolmen =
    User::query()
        ->withoutGlobalScopes()
        ->where(
            'role',
            'toolman'
        )
        ->with('workshop')
        ->orderBy('workshop_id')
        ->get();

if ($toolmen->isEmpty()) {
    echo "WARN: tidak ada akun Toolman.\n";
    $warnings++;
} else {
    foreach ($toolmen as $toolman) {
        echo str_pad(
            $toolman->username,
            30
        ).
            ': workshop='.
            (
                $toolman
                    ->workshop
                    ?->code
                ?? 'NULL'
            ).
            PHP_EOL;

        if (
            $toolman->workshop_id
            === null
        ) {
            $warnings++;
        }
    }
}

echo "\nPENGAJUAN TERJADWAL\n";
echo "-------------------\n";

$totalScheduled =
    Loan::query()
        ->withoutGlobalScopes()
        ->whereNotNull(
            'scheduled_at'
        )
        ->count();

$pendingByWorkshop =
    Loan::query()
        ->withoutGlobalScopes()
        ->where(
            'status',
            Loan::STATUS_PENDING
        )
        ->whereNotNull(
            'workshop_id'
        )
        ->selectRaw(
            'workshop_id, COUNT(*) AS total'
        )
        ->groupBy(
            'workshop_id'
        )
        ->get();

echo str_pad(
    'Loan mempunyai scheduled_at',
    50
).
    ': total='.
    $totalScheduled.
    PHP_EOL;

if ($pendingByWorkshop->isEmpty()) {
    echo "Belum ada pengajuan pending untuk diuji.\n";
} else {
    foreach ($pendingByWorkshop as $row) {
        echo 'workshop_id='.
            $row->workshop_id.
            ', pending='.
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

        'loans.approve' =>
            'WorkshopLoanController@approve',

        'loans.checkout' =>
            'WorkshopLoanController@checkout',
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

    echo str_pad($name, 50).
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
            ? 'GURU SCHEDULED LOAN & WORKSHOP APPROVAL BELUM VALID.'
            : (
                $warnings > 0
                    ? 'WORKFLOW VALID, TETAPI ADA AKUN/DATA YANG PERLU DIPERIKSA.'
                    : 'GURU SCHEDULED LOAN & WORKSHOP APPROVAL SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
