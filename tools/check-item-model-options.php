<?php

declare(strict_types=1);

use App\Models\Item;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA ITEM MODEL OPTIONS API CHECK\n";
echo "==================================\n\n";

$methodChecks = [
    'typeOptions' =>
        method_exists(
            Item::class,
            'typeOptions'
        ),

    'conditionOptions' =>
        method_exists(
            Item::class,
            'conditionOptions'
        ),

    'statusOptions' =>
        method_exists(
            Item::class,
            'statusOptions'
        ),

    'typeLabel' =>
        method_exists(
            Item::class,
            'typeLabel'
        ),

    'conditionLabel' =>
        method_exists(
            Item::class,
            'conditionLabel'
        ),

    'statusLabel' =>
        method_exists(
            Item::class,
            'statusLabel'
        ),

    'isTool' =>
        method_exists(
            Item::class,
            'isTool'
        ),

    'isMaterial' =>
        method_exists(
            Item::class,
            'isMaterial'
        ),

    'stockMovements' =>
        method_exists(
            Item::class,
            'stockMovements'
        ),
];

foreach (
    $methodChecks
    as $method => $valid
) {
    echo str_pad(
        'Item::'.$method,
        38
    ).
        ': '.
        (
            $valid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

try {
    $types =
        Item::typeOptions();

    $conditions =
        Item::conditionOptions();

    $statuses =
        Item::statusOptions();

    $valuesValid =
        ($types['tool'] ?? null)
            === 'Alat'
        && ($types['material'] ?? null)
            === 'Bahan'
        && isset(
            $conditions['good']
        )
        && isset(
            $statuses[
                'out_of_stock'
            ]
        );

    echo str_pad(
        'Nilai option model',
        38
    ).
        ': '.
        (
            $valuesValid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $valuesValid) {
        $failed = true;
    }
} catch (Throwable $exception) {
    echo str_pad(
        'Pemanggilan Options API',
        38
    ).
        ': GAGAL'.
        PHP_EOL;

    echo $exception->getMessage().
        PHP_EOL;

    $failed = true;
}

foreach (
    [
        'items.create',
        'items.store',
        'stock-receipts.create',
        'stock-receipts.store',
    ]
    as $routeName
) {
    $valid =
        Route::has($routeName);

    echo str_pad(
        'Route '.$routeName,
        38
    ).
        ': '.
        (
            $valid
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'ITEM MODEL OPTIONS API BELUM VALID.'
            : 'ITEM MODEL OPTIONS API SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
