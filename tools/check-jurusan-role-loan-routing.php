<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA JURUSAN & LOAN ROUTING CHECK\n";
echo "==================================\n\n";

$checks = [
    'users.workshop_id' =>
        Schema::hasColumn(
            'users',
            'workshop_id'
        ),

    'loans.workshop_id' =>
        Schema::hasColumn(
            'loans',
            'workshop_id'
        ),

    'loans.assigned_toolman_id' =>
        Schema::hasColumn(
            'loans',
            'assigned_toolman_id'
        ),

    'JurusanAccessServiceProvider' =>
        class_exists(
            \App\Providers\JurusanAccessServiceProvider::class
        ),

    'LoanJurusanRoutingService' =>
        class_exists(
            \App\Services\LoanJurusanRoutingService::class
        ),

    'RouteLoanToJurusanToolman' =>
        class_exists(
            \App\Http\Middleware\RouteLoanToJurusanToolman::class
        ),
];

foreach ($checks as $label => $passed) {
    echo str_pad($label, 38).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\nASSIGNMENT ROLE\n";
echo "---------------\n";

foreach (
    [
        'kepala_bengkel',
        'toolman',
        'siswa',
    ]
    as $role
) {
    $invalid = DB::table('users')
        ->where('role', $role)
        ->whereNull('workshop_id')
        ->count();

    echo str_pad(
        "{$role} tanpa jurusan",
        38
    ).
        ': '.
        $invalid.
        (
            $invalid === 0
                ? ' - OK'
                : ' - GAGAL'
        ).
        PHP_EOL;

    if ($invalid !== 0) {
        $failed = true;
    }
}

$teacherAssigned =
    DB::table('users')
        ->where('role', 'guru')
        ->whereNotNull(
            'workshop_id'
        )
        ->count();

echo str_pad(
    'guru yang masih dikunci jurusan',
    38
).
    ': '.
    $teacherAssigned.
    (
        $teacherAssigned === 0
            ? ' - OK'
            : ' - GAGAL'
    ).
    PHP_EOL;

if ($teacherAssigned !== 0) {
    $failed = true;
}

echo "\nKEPALA BENGKEL PER JURUSAN\n";
echo "--------------------------\n";

$duplicateHeads = DB::table('users')
    ->select('workshop_id')
    ->where(
        'role',
        'kepala_bengkel'
    )
    ->whereNotNull(
        'workshop_id'
    )
    ->groupBy('workshop_id')
    ->havingRaw('COUNT(*) > 1')
    ->count();

echo str_pad(
    'Jurusan dengan kabeng ganda',
    38
).
    ': '.
    $duplicateHeads.
    (
        $duplicateHeads === 0
            ? ' - OK'
            : ' - GAGAL'
    ).
    PHP_EOL;

if ($duplicateHeads !== 0) {
    $failed = true;
}

echo "\nTOOLMAN PER JURUSAN\n";
echo "-------------------\n";

$workshops = DB::table(
    'workshops'
)
    ->orderBy('code')
    ->get([
        'id',
        'code',
    ]);

foreach ($workshops as $workshop) {
    $toolmanCount =
        DB::table('users')
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
            ->count();

    echo str_pad(
        "Toolman {$workshop->code}",
        38
    ).
        ': '.
        $toolmanCount.
        (
            $toolmanCount > 0
                ? ' - OK'
                : ' - GAGAL'
        ).
        PHP_EOL;

    if ($toolmanCount === 0) {
        $failed = true;
    }
}

echo "\nROUTING LOAN\n";
echo "------------\n";

$unroutedLoans =
    DB::table('loans')
        ->whereNull(
            'workshop_id'
        )
        ->orWhereNull(
            'assigned_toolman_id'
        )
        ->count();

echo str_pad(
    'Loan belum mempunyai routing',
    38
).
    ': '.
    $unroutedLoans.
    (
        $unroutedLoans === 0
            ? ' - OK'
            : ' - PERLU DICEK'
    ).
    PHP_EOL;

echo "\n".
    (
        $failed
            ? 'PEMERIKSAAN GAGAL.'
            : 'SEMUA KONFIGURASI JURUSAN VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
