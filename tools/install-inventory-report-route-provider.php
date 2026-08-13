<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$file = $root.'/bootstrap/providers.php';

if (! is_file($file)) {
    fwrite(
        STDERR,
        "GAGAL: bootstrap/providers.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents($file);

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL membaca bootstrap/providers.php.\n"
    );

    exit(1);
}

$provider =
    'App\\Providers\\InventoryReportRouteOverrideServiceProvider::class';

if (str_contains($contents, $provider)) {
    echo "Provider laporan inventaris sudah terdaftar.\n";
    exit(0);
}

$backup =
    $file.
    '.before-inventory-report-provider.'.
    date('YmdHis').
    '.bak';

if (! copy($file, $backup)) {
    fwrite(
        STDERR,
        "GAGAL membuat backup bootstrap/providers.php.\n"
    );

    exit(1);
}

$position = strrpos($contents, '];');

if ($position === false) {
    fwrite(
        STDERR,
        "GAGAL menemukan penutup array provider.\n"
    );

    exit(1);
}

$addition =
    "    {$provider},\n";

$updated =
    substr($contents, 0, $position).
    $addition.
    substr($contents, $position);

if (file_put_contents($file, $updated) === false) {
    copy($backup, $file);

    fwrite(
        STDERR,
        "GAGAL memperbarui bootstrap/providers.php.\n"
    );

    exit(1);
}

exec(
    escapeshellarg(PHP_BINARY).
    ' -l '.
    escapeshellarg($file).
    ' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy($backup, $file);

    fwrite(
        STDERR,
        "GAGAL: syntax providers.php tidak valid.\n".
        implode("\n", $output).
        "\n"
    );

    exit(1);
}

echo "PROVIDER LAPORAN INVENTARIS BERHASIL DIDAFTARKAN.\n";
echo "Backup: {$backup}\n";
