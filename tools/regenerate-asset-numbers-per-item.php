<?php

declare(strict_types=1);

use App\Models\Item;
use App\Services\AssetPrefixService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$options =
    getopt(
        '',
        [
            'apply',
            'confirm:',
            'workshop::',
            'item::',
        ]
    );

$apply =
    array_key_exists(
        'apply',
        $options
    );

$confirmed =
    ($options['confirm'] ?? null)
        === 'REGENERATE';

$workshopFilter =
    isset(
        $options['workshop']
    )
        ? strtoupper(
            trim(
                (string)
                $options['workshop']
            )
        )
        : null;

$itemFilter =
    isset(
        $options['item']
    )
        ? strtoupper(
            trim(
                (string)
                $options['item']
            )
        )
        : null;

echo "SIMBA REGENERATE ASSET NUMBERS PER ITEM\n";
echo "=======================================\n";
echo 'Mode       : '.
    (
        $apply
            ? 'APPLY'
            : 'DRY RUN'
    ).
    PHP_EOL;
echo 'Workshop   : '.
    (
        $workshopFilter
        ?: 'SEMUA'
    ).
    PHP_EOL;
echo 'Item       : '.
    (
        $itemFilter
        ?: 'SEMUA'
    ).
    PHP_EOL;
echo PHP_EOL;

if (
    ! Schema::hasTable(
        'items'
    )
    || ! Schema::hasTable(
        'item_assets'
    )
) {
    fwrite(
        STDERR,
        "GAGAL: tabel items/item_assets tidak tersedia.\n"
    );

    exit(1);
}

if (
    ! Schema::hasColumn(
        'items',
        'asset_prefix'
    )
) {
    fwrite(
        STDERR,
        "GAGAL: items.asset_prefix belum tersedia. Jalankan artisan migrate terlebih dahulu.\n"
    );

    exit(1);
}

if (
    $apply
    && ! $confirmed
) {
    fwrite(
        STDERR,
        "GAGAL: mode APPLY memerlukan --confirm=REGENERATE.\n"
    );

    exit(1);
}

$prefixService =
    app(
        AssetPrefixService::class
    );

$query =
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
        ->when(
            $workshopFilter,
            fn ($builder) =>
                $builder->where(
                    'w.code',
                    $workshopFilter
                )
        )
        ->when(
            $itemFilter,
            function (
                $builder
            ) use (
                $itemFilter
            ): void {
                $builder->where(
                    function (
                        $scope
                    ) use (
                        $itemFilter
                    ): void {
                        $scope
                            ->where(
                                'i.code',
                                $itemFilter
                            )
                            ->orWhere(
                                'i.name',
                                $itemFilter
                            )
                            ->orWhere(
                                'i.asset_prefix',
                                $itemFilter
                            );
                    }
                );
            }
        )
        ->select([
            'a.id',
            'a.item_id',
            'a.workshop_id',
            'a.asset_number',
            'a.barcode_value',
            'a.serial_number',
            'a.received_date',
            'a.created_at',
            'a.receipt_code',
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
        ->orderBy('a.id');

$rows =
    $query->get();

if ($rows->isEmpty()) {
    echo "Tidak ada unit alat yang cocok dengan filter.\n";
    exit(0);
}

/**
 * Siapkan prefix di memori. Mode DRY RUN tidak mengubah database.
 */
$items =
    Item::query()
        ->withoutGlobalScopes()
        ->whereIn(
            'id',
            $rows
                ->pluck('item_id')
                ->unique()
                ->values()
        )
        ->orderBy('id')
        ->get()
        ->keyBy('id');

$usedPrefixes =
    DB::table('items')
        ->where(
            'type',
            'tool'
        )
        ->whereNotNull(
            'asset_prefix'
        )
        ->pluck(
            'asset_prefix',
            'id'
        )
        ->map(
            fn (
                mixed $prefix
            ): string =>
                $prefixService
                    ->sanitize(
                        (string)
                        $prefix
                    )
        )
        ->filter()
        ->all();

$prefixAssignments = [];

foreach ($items as $item) {
    $stored =
        $prefixService
            ->sanitize(
                (string)
                $item->getAttribute(
                    'asset_prefix'
                )
            );

    if ($stored !== '') {
        $prefix =
            $stored;
    } else {
        $basePrefix =
            $prefixService
                ->baseFromItem(
                    $item
                );

        $prefix =
            $basePrefix;

        $suffix = 1;

        while (
            in_array(
                $prefix,
                array_values(
                    array_filter(
                        $usedPrefixes,
                        fn (
                            mixed $value,
                            mixed $id
                        ): bool =>
                            (int) $id
                            !== (int) $item->id,
                        ARRAY_FILTER_USE_BOTH
                    )
                ),
                true
            )
            || in_array(
                $prefix,
                array_values(
                    $prefixAssignments
                ),
                true
            )
        ) {
            $suffixText =
                (string)
                $suffix;

            $prefix =
                substr(
                    $basePrefix,
                    0,
                    18
                    - strlen(
                        $suffixText
                    )
                ).
                $suffixText;

            $suffix++;
        }
    }

    $prefixAssignments[
        (int) $item->id
    ] =
        $prefix;

    $item->setAttribute(
        'asset_prefix',
        $prefix
    );
}

$groups = [];
$mapping = [];
$newNumbers = [];

foreach ($rows as $row) {
    $item =
        $items->get(
            $row->item_id
        );

    if ($item === null) {
        fwrite(
            STDERR,
            "GAGAL: master item {$row->item_id} tidak ditemukan.\n"
        );

        exit(1);
    }

    $year = null;

    if (
        ! empty(
            $row->received_date
        )
    ) {
        $year =
            (int)
            Carbon::parse(
                $row->received_date
            )->format('Y');
    }

    if (
        $year === null
        || $year < 1900
    ) {
        if (
            preg_match(
                '/-(\d{4})-\d{6}$/',
                (string)
                $row->asset_number,
                $matches
            )
        ) {
            $year =
                (int)
                $matches[1];
        }
    }

    if (
        $year === null
        || $year < 1900
    ) {
        $year =
            ! empty(
                $row->created_at
            )
                ? (int)
                    Carbon::parse(
                        $row->created_at
                    )->format('Y')
                : (int)
                    now()->format('Y');
    }

    $workshopCode =
        strtoupper(
            preg_replace(
                '/[^A-Z0-9]/i',
                '',
                (string)
                $row->workshop_code
            )
            ?: 'GEN'
        );

    $itemPrefix =
        (string)
        $item->getAttribute(
            'asset_prefix'
        );

    $groupKey =
        implode(
            '|',
            [
                $row->workshop_id,
                $row->item_id,
                $year,
            ]
        );

    if (
        ! isset(
            $groups[$groupKey]
        )
    ) {
        $groups[$groupKey] = [
            'workshop' =>
                $workshopCode,

            'item_code' =>
                $row->item_code,

            'item_name' =>
                $row->item_name,

            'prefix' =>
                $itemPrefix,

            'year' =>
                $year,

            'sequence' =>
                0,

            'total' =>
                0,
        ];
    }

    $groups[$groupKey]['sequence']++;
    $groups[$groupKey]['total']++;

    $sequence =
        $groups[$groupKey][
            'sequence'
        ];

    $newNumber =
        sprintf(
            'ALT-%s-%s-%d-%06d',
            $workshopCode,
            $itemPrefix,
            $year,
            $sequence
        );

    if (
        isset(
            $newNumbers[$newNumber]
        )
    ) {
        fwrite(
            STDERR,
            "GAGAL: nomor baru ganda {$newNumber} untuk asset {$row->id} dan {$newNumbers[$newNumber]}.\n"
        );

        exit(1);
    }

    $newNumbers[$newNumber] =
        (int) $row->id;

    $oldNumber =
        (string)
        $row->asset_number;

    $oldBarcode =
        $row->barcode_value === null
            ? null
            : (string)
                $row->barcode_value;

    if (
        $oldBarcode === null
        || $oldBarcode === ''
        || $oldBarcode === $oldNumber
    ) {
        $newBarcode =
            $newNumber;
    } elseif (
        str_contains(
            $oldBarcode,
            $oldNumber
        )
    ) {
        $newBarcode =
            str_replace(
                $oldNumber,
                $newNumber,
                $oldBarcode
            );
    } else {
        /*
         * Barcode custom atau URL berbasis ID dipertahankan.
         */
        $newBarcode =
            $oldBarcode;
    }

    $oldSerial =
        $row->serial_number === null
            ? null
            : trim(
                (string)
                $row->serial_number
            );

    $expectedOldSerial =
        str_starts_with(
            $oldNumber,
            'ALT-'
        )
            ? 'SN-'.
                substr(
                    $oldNumber,
                    4
                )
            : null;

    $serialIsAutomatic =
        $oldSerial !== null
        && $oldSerial !== ''
        && $expectedOldSerial !== null
        && $oldSerial
            === $expectedOldSerial;

    $newSerial =
        $serialIsAutomatic
            ? 'SN-'.
                substr(
                    $newNumber,
                    4
                )
            : $oldSerial;

    $mapping[] = [
        'id' =>
            (int) $row->id,

        'workshop' =>
            $workshopCode,

        'item_code' =>
            (string)
            $row->item_code,

        'item_name' =>
            (string)
            $row->item_name,

        'asset_prefix' =>
            $itemPrefix,

        'year' =>
            $year,

        'receipt_code' =>
            (string)
            (
                $row->receipt_code
                ?? ''
            ),

        'old_asset_number' =>
            $oldNumber,

        'new_asset_number' =>
            $newNumber,

        'old_barcode_value' =>
            $oldBarcode,

        'new_barcode_value' =>
            $newBarcode,

        'old_serial_number' =>
            $oldSerial,

        'new_serial_number' =>
            $newSerial,
    ];
}

echo "PREFIX BARANG\n";
echo "--------------\n";

foreach ($items as $item) {
    echo str_pad(
        (string)
        $item->code,
        15
    ).
        ' | '.
        str_pad(
            (string)
            $item->name,
            30
        ).
        ' | '.
        $item->getAttribute(
            'asset_prefix'
        ).
        PHP_EOL;
}

echo "\nKELOMPOK NOMOR\n";
echo "--------------\n";

foreach ($groups as $group) {
    echo sprintf(
        "%s | %-12s | %-28s | %d | %d unit | ALT-%s-%s-%d-000001 ... %06d\n",
        $group['workshop'],
        $group['item_code'],
        $group['item_name'],
        $group['year'],
        $group['total'],
        $group['workshop'],
        $group['prefix'],
        $group['year'],
        $group['total']
    );
}

echo "\nCONTOH PERUBAHAN\n";
echo "----------------\n";

foreach (
    array_slice(
        $mapping,
        0,
        20
    )
    as $entry
) {
    echo $entry[
        'old_asset_number'
    ].
        ' -> '.
        $entry[
            'new_asset_number'
        ].
        PHP_EOL;
}

if (
    count($mapping) > 20
) {
    echo '... dan '.
        (
            count($mapping)
            - 20
        ).
        " perubahan lainnya.\n";
}

$timestamp =
    now()->format(
        'Ymd-His'
    );

$reportDirectory =
    storage_path(
        'app/asset-number-regeneration'
    );

if (
    ! is_dir(
        $reportDirectory
    )
) {
    mkdir(
        $reportDirectory,
        0775,
        true
    );
}

$reportPath =
    $reportDirectory.
    '/asset-number-map-'.
    $timestamp.
    '.csv';

$handle =
    fopen(
        $reportPath,
        'wb'
    );

if ($handle === false) {
    fwrite(
        STDERR,
        "GAGAL membuat laporan CSV.\n"
    );

    exit(1);
}

fputcsv(
    $handle,
    array_keys(
        $mapping[0]
    )
);

foreach ($mapping as $entry) {
    fputcsv(
        $handle,
        $entry
    );
}

fclose($handle);

echo "\nLaporan mapping:\n{$reportPath}\n";

if (! $apply) {
    echo "\nDRY RUN SELESAI. Database belum diubah.\n";
    echo "Gunakan --apply --confirm=REGENERATE setelah backup database.\n";

    exit(0);
}

$token =
    'TMP'.
    now()->format(
        'YmdHis'
    );

DB::transaction(
    function () use (
        $mapping,
        $token,
        $prefixAssignments
    ): void {
        foreach (
            $prefixAssignments
            as $itemId => $prefix
        ) {
            DB::table('items')
                ->where(
                    'id',
                    $itemId
                )
                ->update([
                    'asset_prefix' =>
                        $prefix,

                    'updated_at' =>
                        now(),
                ]);
        }

        /*
         * Tahap sementara mencegah benturan unique index ketika
         * nomor lama dan nomor baru saling berpotongan.
         */
        foreach ($mapping as $entry) {
            $temporary = [
                'asset_number' =>
                    $token.
                    '-A-'.
                    $entry['id'],

                'updated_at' =>
                    now(),
            ];

            if (
                $entry[
                    'new_barcode_value'
                ] !==
                $entry[
                    'old_barcode_value'
                ]
            ) {
                $temporary[
                    'barcode_value'
                ] =
                    $token.
                    '-B-'.
                    $entry['id'];
            }

            if (
                $entry[
                    'new_serial_number'
                ] !==
                $entry[
                    'old_serial_number'
                ]
                && $entry[
                    'new_serial_number'
                ] !== null
            ) {
                $temporary[
                    'serial_number'
                ] =
                    $token.
                    '-S-'.
                    $entry['id'];
            }

            DB::table(
                'item_assets'
            )
                ->where(
                    'id',
                    $entry['id']
                )
                ->update(
                    $temporary
                );
        }

        foreach ($mapping as $entry) {
            DB::table(
                'item_assets'
            )
                ->where(
                    'id',
                    $entry['id']
                )
                ->update([
                    'asset_number' =>
                        $entry[
                            'new_asset_number'
                        ],

                    'barcode_value' =>
                        $entry[
                            'new_barcode_value'
                        ],

                    'serial_number' =>
                        $entry[
                            'new_serial_number'
                        ],

                    'updated_at' =>
                        now(),
                ]);
        }
    },
    attempts: 3
);

echo "\nAPPLY SELESAI.\n";
echo count($mapping).
    " unit telah dinomori ulang.\n";
echo "Cetak ulang seluruh label QR karena nomor inventaris berubah.\n";
