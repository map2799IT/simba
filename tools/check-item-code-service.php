<?php

declare(strict_types=1);

use App\Models\ItemCategory;
use App\Models\Workshop;
use App\Services\ItemCodeService;
use App\Services\ItemLabelCodeService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA ITEM CODE SERVICE CHECK\n";
echo "=============================\n\n";

$checks = [
    'ItemCodeService class' =>
        class_exists(
            ItemCodeService::class
        ),

    'ItemLabelCodeService class' =>
        class_exists(
            ItemLabelCodeService::class
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

if (
    class_exists(
        ItemCodeService::class
    )
) {
    $reflection =
        new ReflectionClass(
            ItemCodeService::class
        );

    $file =
        $reflection->getFileName();

    $valid =
        $reflection->getShortName()
            === 'ItemCodeService'
        && $file !== false
        && str_ends_with(
            str_replace(
                '\\',
                '/',
                $file
            ),
            '/app/Services/ItemCodeService.php'
        );

    echo str_pad(
        'Deklarasi ItemCodeService',
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

if (
    class_exists(
        ItemCodeService::class
    )
    && class_exists(
        ItemLabelCodeService::class
    )
) {
    $codeReflection =
        new ReflectionClass(
            ItemCodeService::class
        );

    $labelReflection =
        new ReflectionClass(
            ItemLabelCodeService::class
        );

    $valid =
        $codeReflection->getName()
            !== $labelReflection->getName()
        && $codeReflection->getFileName()
            !== $labelReflection->getFileName();

    echo str_pad(
        'Class service terpisah',
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

$workshop =
    Workshop::query()
        ->where(
            'is_active',
            true
        )
        ->first();

$toolCategory =
    ItemCategory::query()
        ->whereIn(
            'applies_to',
            [
                'tool',
                'both',
            ]
        )
        ->first();

$materialCategory =
    ItemCategory::query()
        ->whereIn(
            'applies_to',
            [
                'material',
                'both',
            ]
        )
        ->first();

if (
    $workshop === null
    || $toolCategory === null
    || $materialCategory === null
) {
    echo str_pad(
        'Tes generator kode',
        38
    ).
        ": DILEWATI - master belum lengkap\n";
} else {
    try {
        $service =
            app(
                ItemCodeService::class
            );

        $codes =
            DB::transaction(
                function () use (
                    $service,
                    $workshop,
                    $toolCategory,
                    $materialCategory
                ): array {
                    return [
                        'tool' =>
                            $service->generate(
                                'tool',
                                $workshop,
                                $toolCategory
                            ),

                        'material' =>
                            $service->generate(
                                'material',
                                $workshop,
                                $materialCategory
                            ),
                    ];
                }
            );

        $year =
            now()->format('Y');

        $toolValid =
            preg_match(
                '/^ALT-'.
                    preg_quote(
                        $year,
                        '/'
                    ).
                    '-\d{4,}$/',
                $codes['tool']
            ) === 1;

        $materialValid =
            preg_match(
                '/^BHN-'.
                    preg_quote(
                        $year,
                        '/'
                    ).
                    '-\d{4,}$/',
                $codes['material']
            ) === 1;

        echo str_pad(
            'Kode alat',
            38
        ).
            ': '.
            $codes['tool'].
            (
                $toolValid
                    ? ' - OK'
                    : ' - GAGAL'
            ).
            PHP_EOL;

        echo str_pad(
            'Kode bahan',
            38
        ).
            ': '.
            $codes['material'].
            (
                $materialValid
                    ? ' - OK'
                    : ' - GAGAL'
            ).
            PHP_EOL;

        if (
            ! $toolValid
            || ! $materialValid
        ) {
            $failed = true;
        }
    } catch (Throwable $exception) {
        echo str_pad(
            'Tes generator kode',
            38
        ).
            ': GAGAL'.
            PHP_EOL;

        echo $exception->getMessage().
            PHP_EOL;

        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'ITEM CODE SERVICE BELUM VALID.'
            : 'ITEM CODE SERVICE DAN LABEL SERVICE SUDAH TERPISAH.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
