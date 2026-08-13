<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$controller = new ReflectionClass(
    \App\Http\Controllers\StorageLocationController::class
);

$controllerFile = file_get_contents(
    $controller->getFileName()
);

$accessFile = $root.
    DIRECTORY_SEPARATOR.'app'.
    DIRECTORY_SEPARATOR.'Support'.
    DIRECTORY_SEPARATOR.'SimbaRoleAccess.php';

$accessContents = is_file($accessFile)
    ? file_get_contents($accessFile)
    : '';

$sidebarFile = $root.
    DIRECTORY_SEPARATOR.'resources'.
    DIRECTORY_SEPARATOR.'views'.
    DIRECTORY_SEPARATOR.'layouts'.
    DIRECTORY_SEPARATOR.'sidebar.blade.php';

$sidebarContents = is_file($sidebarFile)
    ? file_get_contents($sidebarFile)
    : '';

$checks = [
    'StorageLocationController' =>
        $controller->isInstantiable(),

    'Toolman masuk canManage' =>
        is_string($controllerFile)
        && preg_match(
            "/private function canManage[\\s\\S]*?'toolman'/",
            $controllerFile
        ) === 1,

    'Workshop Toolman dipaksa' =>
        is_string($controllerFile)
        && preg_match(
            "/forcedWorkshopId[\\s\\S]*?'kepala_bengkel'[\\s\\S]*?'toolman'/",
            $controllerFile
        ) === 1,

    'Route lokasi Toolman penuh' =>
        is_string($accessContents)
        && str_contains(
            $accessContents,
            "self::ROLE_TOOLMAN,"
        )
        && ! str_contains(
            $accessContents,
            "'/locations/create',"
        ),

    'Sidebar lokasi mode Kelola' =>
        is_string($sidebarContents)
        && str_contains(
            $sidebarContents,
            "'badge' => 'Kelola'"
        ),

    'View locations.index' =>
        View::exists('locations.index'),

    'Route locations.create' =>
        Route::has('locations.create'),

    'Route locations.store' =>
        Route::has('locations.store'),

    'Route locations.edit' =>
        Route::has('locations.edit'),

    'Route locations.update' =>
        Route::has('locations.update'),

    'Route locations.destroy' =>
        Route::has('locations.destroy'),

    'Route locations print' =>
        Route::has('locations.inventory.print'),

    'Route locations PDF' =>
        Route::has('locations.inventory.pdf'),
];

$failed = false;

echo "SIMBA TOOLMAN LOCATION ACCESS CHECK\n";
echo "===================================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 38).
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
            ? 'AKSES LOKASI TOOLMAN BELUM VALID.'
            : 'TOOLMAN DAPAT CRUD DAN CETAK LOKASI JURUSANNYA.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
