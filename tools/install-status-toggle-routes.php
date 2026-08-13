<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'web.php';

$routeFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'status-toggles.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "File tidak ditemukan: {$webFile}".
        PHP_EOL
    );

    exit(1);
}

if (! is_file($routeFile)) {
    fwrite(
        STDERR,
        "File tidak ditemukan: {$routeFile}".
        PHP_EOL
    );

    exit(1);
}

$contents = file_get_contents($webFile);

if ($contents === false) {
    fwrite(
        STDERR,
        'Gagal membaca routes/web.php'.
        PHP_EOL
    );

    exit(1);
}

$requireLine =
    "require __DIR__.'/status-toggles.php';";

if (
    str_contains(
        $contents,
        $requireLine
    )
) {
    echo 'Route toggle status sudah terpasang.'.
        PHP_EOL;

    exit(0);
}

$backup = $webFile.
    '.before-status-toggle.'.
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
| Status Toggle Routes
|--------------------------------------------------------------------------
*/

if (
    file_exists(
        __DIR__.'/status-toggles.php'
    )
) {
    require __DIR__.'/status-toggles.php';
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

echo 'Route toggle status berhasil dipasang.'.
    PHP_EOL;

echo "Backup: {$backup}".
    PHP_EOL;
