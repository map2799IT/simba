<?php

declare(strict_types=1);

use App\Support\QuantityFormatter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA QUANTITY FORMAT & INPUT CHECK\n";
echo "===================================\n\n";

$checks = [
    'QuantityFormatter tersedia' =>
        class_exists(
            QuantityFormatter::class
        ),

    'View items.index' =>
        View::exists(
            'items.index'
        ),

    'View items.show' =>
        View::exists(
            'items.show'
        ),

    'View items.edit' =>
        View::exists(
            'items.edit'
        ),

    'View stock-receipts.create' =>
        View::exists(
            'stock-receipts.create'
        ),

    'View stock-receipts.index' =>
        View::exists(
            'stock-receipts.index'
        ),

    'View receipt row' =>
        View::exists(
            'stock-receipts._receipt-row'
        ),

    'Kolom units.allows_decimal' =>
        Schema::hasColumn(
            'units',
            'allows_decimal'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad(
        $label,
        42
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

$formatTests = [
    [
        'Bulat 0.000',
        QuantityFormatter::format(
            0,
            false
        ),
        '0',
    ],

    [
        'Bulat 20.000',
        QuantityFormatter::format(
            20,
            false
        ),
        '20',
    ],

    [
        'Desimal 20.000',
        QuantityFormatter::format(
            20,
            true
        ),
        '20',
    ],

    [
        'Desimal 20.500',
        QuantityFormatter::format(
            20.5,
            true
        ),
        '20,5',
    ],

    [
        'Desimal 20.125',
        QuantityFormatter::format(
            20.125,
            true
        ),
        '20,125',
    ],
];

echo "\nTES FORMAT\n";
echo "----------\n";

foreach (
    $formatTests
    as [
        $label,
        $actual,
        $expected,
    ]
) {
    $valid =
        $actual === $expected;

    echo str_pad(
        $label,
        42
    ).
        ': '.
        $actual.
        (
            $valid
                ? ' - OK'
                : " - GAGAL, target {$expected}"
        ).
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$files = [
    'items.index' =>
        $root.
        '/resources/views/items/index.blade.php',

    'items.show' =>
        $root.
        '/resources/views/items/show.blade.php',

    'items.edit' =>
        $root.
        '/resources/views/items/edit.blade.php',

    'receipt.index' =>
        $root.
        '/resources/views/stock-receipts/index.blade.php',

    'receipt.create' =>
        $root.
        '/resources/views/stock-receipts/create.blade.php',

    'receipt.row' =>
        $root.
        '/resources/views/stock-receipts/_receipt-row.blade.php',
];

echo "\nTES SOURCE\n";
echo "----------\n";

foreach ($files as $label => $file) {
    $contents =
        is_file($file)
            ? file_get_contents($file)
            : '';

    $valid =
        is_string($contents)
        && $contents !== '';

    if (
        in_array(
            $label,
            [
                'items.index',
                'items.show',
                'items.edit',
                'receipt.index',
            ],
            true
        )
    ) {
        $valid =
            $valid
            && str_contains(
                $contents,
                'QuantityFormatter::format'
            );
    }

    if ($label === 'receipt.create') {
        $valid =
            $valid
            && str_contains(
                $contents,
                'quantity.min'
            )
            && str_contains(
                $contents,
                'usesDecimal'
            )
            && str_contains(
                $contents,
                'minimumStock.step'
            );
    }

    if ($label === 'receipt.row') {
        $valid =
            $valid
            && str_contains(
                $contents,
                'data-allows-decimal'
            )
            && str_contains(
                $contents,
                'min="1"'
            )
            && str_contains(
                $contents,
                'step="1"'
            );
    }

    echo str_pad(
        $label,
        42
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
            ? 'FORMAT JUMLAH DAN INPUT BELUM VALID.'
            : 'FORMAT JUMLAH DAN INPUT SUDAH VALID.'
    ).
    PHP_EOL;

exit(
    $failed
        ? 1
        : 0
);
