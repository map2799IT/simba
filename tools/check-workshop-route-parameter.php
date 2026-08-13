<?php

declare(strict_types=1);

use App\Models\Workshop;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA WORKSHOP ROUTE PARAMETER CHECK\n";
echo "====================================\n\n";

$controllerFile =
    $root.
    '/app/Http/Controllers/WorkshopController.php';

$viewFile =
    $root.
    '/resources/views/workshops/index.blade.php';

$checks = [
    'WorkshopController tersedia' =>
        is_file($controllerFile),

    'Workshop index tersedia' =>
        is_file($viewFile),

    'Route workshops.index' =>
        Route::has(
            'workshops.index'
        ),

    'Route workshops.edit' =>
        Route::has(
            'workshops.edit'
        ),

    'Route workshops.destroy' =>
        Route::has(
            'workshops.destroy'
        ),

    'Tabel workshops tersedia' =>
        Schema::hasTable(
            'workshops'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 38).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$controllerContents =
    is_file($controllerFile)
        ? file_get_contents(
            $controllerFile
        )
        : '';

$selectFixPresent =
    is_string($controllerContents)
    && str_contains(
        $controllerContents,
        "\$query->select(\n            'workshops.*'\n        );"
    );

echo str_pad(
    'Select workshops.*',
    38
).
    ': '.
    (
        $selectFixPresent
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $selectFixPresent) {
    $failed = true;
}

if (Schema::hasTable('workshops')) {
    $workshop =
        Workshop::query()
            ->withoutGlobalScopes()
            ->select(
                'workshops.*'
            )
            ->first();

    if ($workshop === null) {
        echo str_pad(
            'Data jurusan',
            38
        ).
            ": KOSONG\n";
    } else {
        $idValid =
            $workshop->getKey() !== null;

        echo str_pad(
            'Workshop primary key',
            38
        ).
            ': '.
            (
                $idValid
                    ? 'OK ('.$workshop->getKey().')'
                    : 'GAGAL'
            ).
            PHP_EOL;

        if (! $idValid) {
            $failed = true;
        }

        try {
            $url =
                route(
                    'workshops.edit',
                    [
                        'workshop' =>
                            $workshop->getRouteKey(),
                    ]
                );

            echo str_pad(
                'Generate URL edit',
                38
            ).
                ': OK'.
                PHP_EOL;

            echo "URL: {$url}\n";
        } catch (Throwable $exception) {
            echo str_pad(
                'Generate URL edit',
                38
            ).
                ': GAGAL'.
                PHP_EOL;

            echo $exception->getMessage().
                PHP_EOL;

            $failed = true;
        }
    }
}

echo "\n".
    (
        $failed
            ? 'WORKSHOP ROUTE PARAMETER BELUM VALID.'
            : 'WORKSHOP EDIT ROUTE PARAMETER SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
