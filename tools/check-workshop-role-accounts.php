<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA WORKSHOP ROLE ACCOUNT CHECK\n";
echo "=================================\n\n";

$formFile =
    $root.
    '/resources/views/workshops/_form.blade.php';

$formContents =
    is_file($formFile)
        ? file_get_contents(
            $formFile
        )
        : '';

$formValid =
    is_string($formContents)
    && str_contains(
        $formContents,
        'SIMBA WORKSHOP ACCOUNT OPTIONS START'
    )
    && str_contains(
        $formContents,
        '$headUsers'
    )
    && str_contains(
        $formContents,
        '$toolmanUsers'
    );

echo str_pad(
    'Fallback form headUsers/toolmanUsers',
    43
).
    ': '.
    (
        $formValid
            ? 'OK'
            : 'GAGAL'
    ).
    PHP_EOL;

if (! $formValid) {
    $failed = true;
}

foreach (
    [
        'users',
        'workshops',
    ]
    as $table
) {
    $valid =
        Schema::hasTable(
            $table
        );

    echo str_pad(
        "Tabel {$table}",
        43
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
    Schema::hasTable('users')
    && Schema::hasTable(
        'workshops'
    )
) {
    $workshops =
        DB::table('workshops')
            ->orderBy('code')
            ->get();

    echo "\nAKUN PER JURUSAN\n";
    echo "-----------------\n";

    foreach (
        $workshops
        as $workshop
    ) {
        $headCount =
            DB::table('users')
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->where(
                    'role',
                    'kepala_bengkel'
                )
                ->count();

        $toolmanCount =
            DB::table('users')
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->where(
                    'role',
                    'toolman'
                )
                ->count();

        $valid =
            $headCount >= 1
            && $toolmanCount >= 1;

        echo str_pad(
            (string)
            $workshop->code,
            12
        ).
            'Kepala: '.
            str_pad(
                (string)
                $headCount,
                4
            ).
            'Toolman: '.
            str_pad(
                (string)
                $toolmanCount,
                4
            ).
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

    $adminCount =
        DB::table('users')
            ->where(
                'role',
                'admin'
            )
            ->count();

    echo "\n".
        str_pad(
            'Akun administrator tetap tersedia',
            43
        ).
        ': '.
        (
            $adminCount >= 1
                ? 'OK'
                : 'PERINGATAN'
        ).
        PHP_EOL;
}

echo "\n".
    (
        $failed
            ? 'AKUN JURUSAN ATAU FORM MASIH BELUM VALID.'
            : 'AKUN JURUSAN DAN FORM WORKSHOP SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
