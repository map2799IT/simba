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
    'storage-location-qr.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($routeFile)) {
    fwrite(
        STDERR,
        "routes/storage-location-qr.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents(
    $webFile
);

$requireLine =
    "require __DIR__.'/storage-location-qr.php';";

if (
    ! str_contains(
        $contents,
        $requireLine
    )
) {
    $backup = $webFile.
        '.before-location-qr.'.
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
            "Gagal memperbarui routes/web.php.\n"
        );

        exit(1);
    }

    echo "Route tambahan berhasil dipasang.\n";
    echo "Backup: {$backup}\n";
} else {
    echo "Route tambahan sebelumnya sudah terpasang.\n";
}

echo "\nFile controller, model, view, sidebar, dan access sudah siap.\n";
