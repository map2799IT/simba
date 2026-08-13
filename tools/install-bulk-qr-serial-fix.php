<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$providersFile = $root.
    DIRECTORY_SEPARATOR.
    'bootstrap'.
    DIRECTORY_SEPARATOR.
    'providers.php';

if (! is_file($providersFile)) {
    fwrite(
        STDERR,
        "bootstrap/providers.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents(
    $providersFile
);

if ($contents === false) {
    fwrite(
        STDERR,
        "Gagal membaca bootstrap/providers.php.\n"
    );

    exit(1);
}

$provider =
    'App\\Providers\\ItemAssetSerialServiceProvider::class';

if (
    str_contains(
        $contents,
        'ItemAssetSerialServiceProvider'
    )
) {
    echo "Provider serial unit sebelumnya sudah terpasang.\n";
    exit(0);
}

$backup = $providersFile.
    '.before-asset-serial.'.
    date('YmdHis').
    '.bak';

if (! copy($providersFile, $backup)) {
    fwrite(
        STDERR,
        "Gagal membuat backup bootstrap/providers.php.\n"
    );

    exit(1);
}

$position = strrpos(
    $contents,
    ']'
);

if ($position === false) {
    fwrite(
        STDERR,
        "Format bootstrap/providers.php tidak dikenali.\n"
    );

    exit(1);
}

$before = rtrim(
    substr(
        $contents,
        0,
        $position
    )
);

if (! str_ends_with($before, ',')) {
    $before .= ',';
}

$contents = $before.
    PHP_EOL.
    '    '.$provider.','.
    PHP_EOL.
    substr(
        $contents,
        $position
    );

if (
    file_put_contents(
        $providersFile,
        $contents
    ) === false
) {
    copy(
        $backup,
        $providersFile
    );

    fwrite(
        STDERR,
        "Gagal memperbarui provider. Backup dipulihkan.\n"
    );

    exit(1);
}

echo "Provider serial unit berhasil dipasang.\n";
echo "Backup: {$backup}\n";
