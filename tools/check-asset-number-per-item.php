<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA ASSET NUMBER PER ITEM CHECK\n";
echo "=================================\n\n";

$columnValid =
    Schema::hasColumn(
        'items',
        'asset_prefix'
    );

echo str_pad(
    'items.asset_prefix',
    50
).
    ': '.
    (
        $columnValid
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $columnValid) {
    exit(1);
}

$duplicates =
    DB::table(
        'item_assets'
    )
        ->select(
            'asset_number',
            DB::raw(
                'COUNT(*) AS total'
            )
        )
        ->groupBy(
            'asset_number'
        )
        ->havingRaw(
            'COUNT(*) > 1'
        )
        ->get();

echo str_pad(
    'Nomor inventaris ganda',
    50
).
    ': '.
    (
        $duplicates->isEmpty()
            ? 'OK'
            : 'GAGAL '.
                $duplicates->count()
    ).
    PHP_EOL;

if (
    $duplicates->isNotEmpty()
) {
    $failed = true;
}

$rows =
    DB::table(
        'item_assets as a'
    )
        ->join(
            'items as i',
            'i.id',
            '=',
            'a.item_id'
        )
        ->join(
            'workshops as w',
            'w.id',
            '=',
            'a.workshop_id'
        )
        ->where(
            'i.type',
            'tool'
        )
        ->select([
            'a.id',
            'a.asset_number',
            'a.barcode_value',
            'a.serial_number',
            'a.received_date',
            'a.created_at',
            'a.item_id',
            'a.workshop_id',
            'i.code as item_code',
            'i.name as item_name',
            'i.asset_prefix',
            'w.code as workshop_code',
        ])
        ->orderBy(
            'w.code'
        )
        ->orderBy(
            'i.id'
        )
        ->orderByRaw(
            'COALESCE(a.received_date, DATE(a.created_at))'
        )
        ->orderBy('a.id')
        ->get();

$groups = [];

foreach ($rows as $row) {
    $prefix =
        strtoupper(
            preg_replace(
                '/[^A-Z0-9]/i',
                '',
                (string)
                $row->asset_prefix
            )
            ?: ''
        );

    if ($prefix === '') {
        echo "GAGAL: {$row->item_code} {$row->item_name} belum mempunyai asset_prefix.\n";
        $failed = true;
        continue;
    }

    $pattern =
        '/^ALT-'.
        preg_quote(
            strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/i',
                    '',
                    (string)
                    $row->workshop_code
                )
                ?: 'GEN'
            ),
            '/'
        ).
        '-'.
        preg_quote(
            $prefix,
            '/'
        ).
        '-(\d{4})-(\d{6})$/';

    if (
        ! preg_match(
            $pattern,
            (string)
            $row->asset_number,
            $matches
        )
    ) {
        echo "GAGAL FORMAT: {$row->asset_number} | {$row->item_name}\n";
        $failed = true;
        continue;
    }

    $year =
        (int) $matches[1];

    $sequence =
        (int) $matches[2];

    $groupKey =
        $row->workshop_id.
        '|'.
        $row->item_id.
        '|'.
        $year;

    $groups[$groupKey][
        'label'
    ] =
        $row->workshop_code.
        ' | '.
        $row->item_code.
        ' | '.
        $row->item_name.
        ' | '.
        $year;

    $groups[$groupKey][
        'sequences'
    ][] =
        $sequence;

    if (
        is_string(
            $row->barcode_value
        )
        && str_starts_with(
            $row->barcode_value,
            'ALT-'
        )
        && $row->barcode_value
            !== $row->asset_number
    ) {
        echo "WARN BARCODE: {$row->asset_number} memiliki barcode {$row->barcode_value}\n";
        $warnings++;
    }

    $expectedSerial =
        'SN-'.
        substr(
            (string)
            $row->asset_number,
            4
        );

    if (
        is_string(
            $row->serial_number
        )
        && str_starts_with(
            $row->serial_number,
            'SN-'
        )
        && preg_match(
            '/-\d{6}$/',
            $row->serial_number
        )
        && $row->serial_number
            !== $expectedSerial
    ) {
        echo "WARN SERIAL: {$row->asset_number} memiliki serial {$row->serial_number}\n";
        $warnings++;
    }
}

echo "\nURUTAN PER BARANG\n";
echo "-----------------\n";

foreach ($groups as $group) {
    $sequences =
        $group[
            'sequences'
        ];

    sort($sequences);

    $expected =
        range(
            1,
            count(
                $sequences
            )
        );

    $valid =
        $sequences ===
        $expected;

    echo str_pad(
        $group['label'],
        70
    ).
        ': '.
        (
            $valid
                ? 'OK '
                : 'GAGAL '
        ).
        count(
            $sequences
        ).
        ' unit'.
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

echo "\nSTATUS\n";
echo "------\n";
echo 'FAIL: '.
    (
        $failed
            ? 'YA'
            : 'TIDAK'
    ).
    PHP_EOL;
echo "WARN: {$warnings}\n";

echo "\n".
    (
        $failed
            ? 'ASSET NUMBER PER ITEM BELUM VALID.'
            : (
                $warnings > 0
                    ? 'NOMOR UTAMA VALID, TETAPI ADA BARCODE/SERIAL CUSTOM YANG PERLU DITINJAU.'
                    : 'ASSET NUMBER PER ITEM SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit(
    $failed
        ? 1
        : 0
);
