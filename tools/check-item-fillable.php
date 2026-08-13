<?php

declare(strict_types=1);

use App\Models\Item;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$requiredFillable = [
    'type',
    'code',
    'name',
    'item_category_id',
    'unit_id',
    'workshop_id',
    'storage_location_id',
    'brand',
    'model',
    'serial_number',
    'specification',
    'received_date',
    'acquisition_source',
    'fund_source',
    'unit_price',
    'condition',
    'status',
    'stock',
    'minimum_stock',
    'is_borrowable',
    'photo_path',
    'description',
    'is_active',
];

$failed = false;

echo "SIMBA ITEM FILLABLE CHECK\n";
echo "=========================\n\n";

$item = new Item();

$fillable =
    $item->getFillable();

foreach (
    $requiredFillable
    as $field
) {
    $valid =
        in_array(
            $field,
            $fillable,
            true
        );

    echo str_pad(
        'fillable '.$field,
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

$testData = [
    'type' => 'tool',
    'code' => 'ALT-TEST-0001',
    'name' => 'Tes Fillable',
    'item_category_id' => 1,
    'unit_id' => 1,
    'workshop_id' => 1,
    'storage_location_id' => null,
    'brand' => 'SIMBA',
    'model' => 'TEST',
    'serial_number' => null,
    'specification' => 'Tes',
    'received_date' => null,
    'acquisition_source' => null,
    'fund_source' => null,
    'unit_price' => null,
    'condition' => 'good',
    'status' => 'out_of_stock',
    'stock' => 0,
    'minimum_stock' => 0,
    'is_borrowable' => true,
    'photo_path' => null,
    'description' => 'Tes',
    'is_active' => true,
];

try {
    $item->fill(
        $testData
    );

    $attributes =
        $item->getAttributes();

    foreach (
        [
            'type',
            'item_category_id',
            'unit_id',
            'workshop_id',
            'condition',
            'status',
            'stock',
            'minimum_stock',
            'is_active',
        ]
        as $field
    ) {
        $valid =
            array_key_exists(
                $field,
                $attributes
            );

        echo str_pad(
            'mass assignment '.$field,
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
} catch (Throwable $exception) {
    echo str_pad(
        'Tes mass assignment',
        42
    ).
        ': GAGAL'.
        PHP_EOL;

    echo $exception->getMessage().
        PHP_EOL;

    $failed = true;
}

if (
    Schema::hasTable('items')
) {
    foreach (
        [
            'type',
            'item_category_id',
            'unit_id',
            'workshop_id',
            'condition',
            'status',
            'stock',
            'minimum_stock',
        ]
        as $column
    ) {
        $valid =
            Schema::hasColumn(
                'items',
                $column
            );

        echo str_pad(
            'database items.'.$column,
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
} else {
    echo str_pad(
        'database table items',
        42
    ).
        ": GAGAL\n";

    $failed = true;
}

echo "\n".
    (
        $failed
            ? 'ITEM FILLABLE BELUM VALID.'
            : 'ITEM FILLABLE SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
