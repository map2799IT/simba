<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SimbaRoleAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [
    'SimbaRoleAccess' =>
        class_exists(
            SimbaRoleAccess::class
        ),

    'Role middleware' =>
        class_exists(
            \App\Http\Middleware\EnforceSimbaRoleAccess::class
        ),

    'Menu guard view' =>
        View::exists(
            'layouts.role-menu-guard'
        ),

    'users.workshop_id' =>
        Schema::hasColumn(
            'users',
            'workshop_id'
        ),

    'Scoped report controller' =>
        class_exists(
            \App\Http\Controllers\ScopedInventoryReportController::class
        ),

    'reports.export.pdf' =>
        Route::has(
            'reports.export.pdf'
        ),

    'locations.inventory.pdf' =>
        Route::has(
            'locations.inventory.pdf'
        ),
];

$failed = false;

echo "SIMBA FULL ROLE ACCESS CHECK\n";
echo "============================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 36).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\nMATRIX TEST\n";
echo "-----------\n";

$access = app(
    SimbaRoleAccess::class
);

$tests = [
    [
        'role' =>
            'kepala_bengkel',

        'route' =>
            'workshops.index',

        'expected' =>
            false,
    ],
    [
        'role' =>
            'toolman',

        'route' =>
            'units.index',

        'expected' =>
            false,
    ],
    [
        'role' =>
            'toolman',

        'route' =>
            'items.create',

        'expected' =>
            true,
    ],
    [
        'role' =>
            'kepala_bengkel',

        'route' =>
            'stock-receipts.index',

        'expected' =>
            true,
    ],
    [
        'role' =>
            'kepala_bengkel',

        'route' =>
            'stock-receipts.create',

        'expected' =>
            false,
    ],
    [
        'role' =>
            'siswa',

        'route' =>
            'stock-receipts.index',

        'expected' =>
            false,
    ],
    [
        'role' =>
            'siswa',

        'route' =>
            'reports.inventory',

        'expected' =>
            true,
    ],
];

foreach ($tests as $test) {
    $user = new User([
        'role' => $test['role'],
    ]);

    $actual = $access->canRoute(
        $user,
        $test['route']
    );

    $passed =
        $actual ===
        $test['expected'];

    echo str_pad(
        $test['role'].
        ' -> '.
        $test['route'],
        56
    ).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'PEMERIKSAAN GAGAL.'
            : 'SEMUA HAK AKSES UTAMA VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
