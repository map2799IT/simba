<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\LoanJurusanRoutingService;
use App\Services\WorkshopLoanInventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA LOAN JURUSAN GLOBAL MASTER CHECK\n";
echo "======================================\n\n";

$serviceFile =
    $root.
    '/app/Services/LoanJurusanRoutingService.php';

$contents =
    is_file($serviceFile)
        ? file_get_contents(
            $serviceFile
        )
        : false;

$checks = [
    'Routing service tersedia' =>
        class_exists(
            LoanJurusanRoutingService::class
        ),

    'Tidak mengimpor Model Item' =>
        is_string($contents)
        && ! str_contains(
            $contents,
            'use App\\Models\\Item;'
        ),

    'Tidak membaca items.workshop_id' =>
        is_string($contents)
        && ! str_contains(
            $contents,
            "Item::query()"
        ),

    'Membaca item_assets.workshop_id' =>
        is_string($contents)
        && str_contains(
            $contents,
            'extractAssetWorkshopIds('
        ),

    'Tidak menetapkan Toolman saat submit' =>
        is_string($contents)
        && ! str_contains(
            $contents,
            "'assigned_toolman_id' =>"
        ),

    'Route loans.store tersedia' =>
        RouteFacade::has(
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

echo "\nSIMULASI SISWA\n";
echo "--------------\n";

$routing =
    app(
        LoanJurusanRoutingService::class
    );

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
        ->whereNotNull(
            'workshop_id'
        )
        ->orderBy('id')
        ->get();

if ($students->isEmpty()) {
    echo "WARN: siswa dengan workshop_id tidak ditemukan.\n";
    $warnings++;
}

foreach ($students as $student) {
    $items =
        $inventory->items(
            (int)
            $student->workshop_id
        );

    $item =
        $items->first();

    if ($item === null) {
        echo $student->username.
            ': WARN tidak ada barang tersedia'.
            PHP_EOL;

        $warnings++;
        continue;
    }

    $request =
        Request::create(
            '/loans',
            'POST',
            [
                'workshop_id' =>
                    $student
                        ->workshop_id,

                'items' => [
                    [
                        'item_id' =>
                            $item->id,

                        'quantity' =>
                            1,
                    ],
                ],
            ]
        );

    $request->setUserResolver(
        static fn () =>
            $student
    );

    $route =
        new Route(
            ['POST'],
            'loans',
            static fn () =>
                null
        );

    $route->name(
        'loans.store'
    );

    $request->setRouteResolver(
        static fn () =>
            $route
    );

    try {
        $routing->prepareRequest(
            $request
        );

        $valid =
            (int)
            $request->input(
                'workshop_id'
            )
            === (int)
            $student
                ->workshop_id;

        echo str_pad(
            $student->username,
            30
        ).
            ': '.
            ($valid ? 'OK' : 'GAGAL').
            ' workshop_id='.
            $request->input(
                'workshop_id'
            ).
            ', item='.
            $item->code.
            PHP_EOL;

        if (! $valid) {
            $failed = true;
        }
    } catch (Throwable $exception) {
        echo str_pad(
            $student->username,
            30
        ).
            ': GAGAL '.
            $exception->getMessage().
            PHP_EOL;

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
            ? 'LOAN JURUSAN GLOBAL MASTER BELUM VALID.'
            : (
                $warnings > 0
                    ? 'KODE VALID DENGAN PERINGATAN DATA.'
                    : 'LOAN JURUSAN GLOBAL MASTER SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
