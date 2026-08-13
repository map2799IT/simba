<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'web.php';

$modernRouteFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'auth-modern.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($modernRouteFile)) {
    fwrite(
        STDERR,
        "routes/auth-modern.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents =
    file_get_contents(
        $webFile
    );

if ($contents === false) {
    fwrite(
        STDERR,
        "Gagal membaca routes/web.php.\n"
    );

    exit(1);
}

$requireLine =
    "require __DIR__.'/auth-modern.php';";

if (
    str_contains(
        $contents,
        $requireLine
    )
) {
    echo "Route auth modern sudah terpasang.\n";
    exit(0);
}

$backup =
    $webFile.
    '.before-modern-auth.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        "Gagal membuat backup routes/web.php.\n"
    );

    exit(1);
}

/*
 * Route harus dimuat setelah routes/auth.php dan route student lama
 * agar endpoint login, register, dan password memakai controller baru.
 */
$updated =
    rtrim($contents).
    PHP_EOL.
    PHP_EOL.
    $requireLine.
    PHP_EOL;

if (
    file_put_contents(
        $webFile,
        $updated
    ) === false
) {
    copy(
        $backup,
        $webFile
    );

    fwrite(
        STDERR,
        "Gagal memperbarui routes/web.php. Backup dipulihkan.\n"
    );

    exit(1);
}

echo "Route auth modern berhasil dipasang.\n";
echo "Backup: {$backup}\n";
