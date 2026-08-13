<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\WorkshopLoanInventoryService;
use App\Support\SimbaRoleAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$failed = false;

echo "SIMBA GURU/SISWA LOAN ONLY CHECK\n";
echo "================================\n\n";

$checks = [
    'Role access service' =>
        class_exists(SimbaRoleAccess::class),
    'Role middleware' =>
        class_exists(\App\Http\Middleware\EnforceSimbaRoleAccess::class),
    'Borrower dashboard controller' =>
        class_exists(\App\Http\Controllers\BorrowerAwareDashboardController::class),
    'Workshop loan controller' =>
        class_exists(\App\Http\Controllers\WorkshopLoanController::class),
    'Loan inventory service' =>
        class_exists(WorkshopLoanInventoryService::class),
    'Sidebar view' =>
        View::exists('layouts.sidebar'),
    'Borrower dashboard view' =>
        View::exists('dashboard.borrower-only'),
    'Loan create view' =>
        View::exists('loans.create'),
    'Loan row partial' =>
        View::exists('loans._row'),
    'Loan index view' =>
        View::exists('loans.index'),
    'Missing workshop view' =>
        View::exists('loans.workshop-required'),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 44).': '.($valid ? 'OK' : 'GAGAL').PHP_EOL;
    if (! $valid) {
        $failed = true;
    }
}

echo "\nROUTE ACTION\n";
echo "------------\n";

$routeActions = [
    'dashboard' =>
        'BorrowerAwareDashboardController@index',
    'loans.index' =>
        'WorkshopLoanController@index',
    'loans.create' =>
        'WorkshopLoanController@create',
    'loans.store' =>
        'WorkshopLoanController@store',
    'loans.show' =>
        'WorkshopLoanController@show',
    'loans.cancel' =>
        'WorkshopLoanController@cancel',
];

foreach ($routeActions as $name => $expected) {
    $route = Route::getRoutes()->getByName($name);
    $action = $route?->getActionName();
    $valid = is_string($action) && str_contains($action, $expected);

    echo str_pad($name, 44)
        .': '.($valid ? 'OK ' : 'GAGAL ')
        .($action ?? '-').PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nMATRIX AKSES BACKEND\n";
echo "--------------------\n";

$access = app(SimbaRoleAccess::class);

$allowed = [
    ['dashboard', 'GET'],
    ['loans.index', 'GET'],
    ['loans.create', 'GET'],
    ['loans.store', 'POST'],
    ['loans.show', 'GET'],
    ['loans.cancel', 'POST'],
    ['profile.edit', 'GET'],
];

$denied = [
    ['items.index', 'GET'],
    ['item-assets.index', 'GET'],
    ['stock-receipts.index', 'GET'],
    ['stock-issues.index', 'GET'],
    ['stock-movements.index', 'GET'],
    ['damage-reports.index', 'GET'],
    ['reports.inventory', 'GET'],
    ['loans.approve', 'POST'],
    ['loans.checkout', 'POST'],
    ['loans.return', 'POST'],
    ['loans.returns.index', 'GET'],
];

foreach (['guru', 'siswa'] as $role) {
    $user = new User();
    $user->role = $role;

    foreach ($allowed as [$routeName, $method]) {
        $valid = $access->canRoute($user, $routeName, $method, '');
        echo str_pad("{$role} allow {$routeName}", 44)
            .': '.($valid ? 'OK' : 'GAGAL').PHP_EOL;
        if (! $valid) {
            $failed = true;
        }
    }

    foreach ($denied as [$routeName, $method]) {
        $valid = ! $access->canRoute($user, $routeName, $method, '');
        echo str_pad("{$role} deny {$routeName}", 44)
            .': '.($valid ? 'OK' : 'GAGAL').PHP_EOL;
        if (! $valid) {
            $failed = true;
        }
    }
}

echo "\nCAKUPAN JURUSAN\n";
echo "----------------\n";

$service = app(WorkshopLoanInventoryService::class);

foreach (['guru', 'siswa'] as $role) {
    $account = User::query()
        ->withoutGlobalScopes()
        ->where('role', $role)
        ->orderBy('id')
        ->first();

    if ($account === null) {
        echo str_pad("Akun {$role}", 44).": WARN tidak ditemukan\n";
        continue;
    }

    $request = Request::create('/loans/create', 'GET');
    $request->setUserResolver(static fn () => $account);

    $visible = $service->visibleWorkshops($request);

    if ($role === 'guru') {
        $valid = $visible->count() > 0;
        echo str_pad('Guru melihat jurusan aktif', 44)
            .': '.($valid ? 'OK ' : 'GAGAL ')
            .$visible->count().PHP_EOL;
    } else {
        $expected = $account->workshop_id === null ? 0 : 1;
        $valid = $visible->count() === $expected;
        echo str_pad('Siswa melihat jurusannya saja', 44)
            .': '.($valid ? 'OK ' : 'GAGAL ')
            .$visible->count().PHP_EOL;
    }

    if (! $valid) {
        $failed = true;
    }
}

echo "\nSIDEBAR\n";
echo "-------\n";

$sidebar = file_get_contents($root.'/resources/views/layouts/sidebar.blade.php');
$sidebarValid = is_string($sidebar)
    && str_contains($sidebar, '$isBorrowerOnly')
    && str_contains(
        $sidebar,
        '\'show\' => ! $isBorrowerOnly'
    );

echo str_pad('Guard menu Guru/Siswa', 44)
    .': '.($sidebarValid ? 'OK' : 'GAGAL').PHP_EOL;

if (! $sidebarValid) {
    $failed = true;
}

echo "\n".($failed
    ? 'GURU/SISWA LOAN ONLY BELUM VALID.'
    : 'GURU/SISWA LOAN ONLY SUDAH VALID.'
).PHP_EOL;

exit($failed ? 1 : 0);
