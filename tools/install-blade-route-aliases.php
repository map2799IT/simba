<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'web.php';

$aliasFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'blade-route-aliases.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "File tidak ditemukan: {$webFile}".
        PHP_EOL
    );

    exit(1);
}

if (! is_file($aliasFile)) {
    fwrite(
        STDERR,
        "File tidak ditemukan: {$aliasFile}".
        PHP_EOL
    );

    exit(1);
}

$contents = file_get_contents(
    $webFile
);

if ($contents === false) {
    fwrite(
        STDERR,
        'Gagal membaca routes/web.php'.
        PHP_EOL
    );

    exit(1);
}

$marker =
    "blade-route-aliases.php";

if (
    str_contains(
        $contents,
        $marker
    )
) {
    echo 'Blade route aliases sudah terpasang.'.
        PHP_EOL;

    exit(0);
}

$backup = $webFile.
    '.before-blade-aliases.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        'Gagal membuat backup routes/web.php'.
        PHP_EOL
    );

    exit(1);
}

$contents = rtrim($contents);

if (
    str_ends_with(
        $contents,
        '?>'
    )
) {
    $contents = rtrim(
        substr(
            $contents,
            0,
            -2
        )
    );
}

$block = <<<'PHP'


/*
|--------------------------------------------------------------------------
| Blade Route Compatibility Aliases
|--------------------------------------------------------------------------
*/

if (
    file_exists(
        __DIR__.'/blade-route-aliases.php'
    )
) {
    require __DIR__.'/blade-route-aliases.php';
}

PHP;

if (
    file_put_contents(
        $webFile,
        $contents.$block
    ) === false
) {
    fwrite(
        STDERR,
        'Gagal memperbarui routes/web.php'.
        PHP_EOL
    );

    exit(1);
}

echo 'Blade route aliases berhasil dipasang.'.
    PHP_EOL;

echo "Backup: {$backup}".
    PHP_EOL;
