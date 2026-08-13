<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'web.php';

$profileRoutesFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'profile.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($profileRoutesFile)) {
    fwrite(
        STDERR,
        "routes/profile.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents(
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
    "require __DIR__.'/profile.php';";

if (
    str_contains(
        $contents,
        $requireLine
    )
) {
    echo "Route profil sudah terpasang.\n";
    exit(0);
}

$backup = $webFile.
    '.before-profile.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        "Gagal membuat backup routes/web.php.\n"
    );

    exit(1);
}

$contents = rtrim($contents).
    PHP_EOL.
    PHP_EOL.
    $requireLine.
    PHP_EOL;

if (
    file_put_contents(
        $webFile,
        $contents
    ) === false
) {
    copy(
        $backup,
        $webFile
    );

    fwrite(
        STDERR,
        "Gagal menulis routes/web.php. Backup dipulihkan.\n"
    );

    exit(1);
}

echo "Modul profil berhasil dipasang.\n";
echo "Backup: {$backup}\n";
