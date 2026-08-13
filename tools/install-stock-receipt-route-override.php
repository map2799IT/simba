<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile = $root.'/routes/web.php';
$overrideFile =
    $root.'/routes/stock-receipt-workflow-override.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "GAGAL: routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($overrideFile)) {
    fwrite(
        STDERR,
        "GAGAL: file override route tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents($webFile);

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL membaca routes/web.php.\n"
    );

    exit(1);
}

$requireLine =
    "require __DIR__.'/stock-receipt-workflow-override.php';";

$backup =
    $webFile.
    '.before-stock-receipt-route-override.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        "GAGAL membuat backup routes/web.php.\n"
    );

    exit(1);
}

/*
 * Hapus require lama agar hanya ada satu require dan dipindahkan
 * ke bagian paling akhir file.
 */
$contents = preg_replace(
    "/^[ \t]*require[ \t]+__DIR__[ \t]*\\.[ \t]*[\"']\\/stock-receipt-workflow-override\\.php[\"'][ \t]*;[ \t]*$/m",
    '',
    $contents
);

if (! is_string($contents)) {
    copy($backup, $webFile);

    fwrite(
        STDERR,
        "GAGAL membersihkan require lama.\n"
    );

    exit(1);
}

$contents = rtrim($contents);

/*
 * Jika routes/web.php memakai penutup PHP, sisipkan require sebelum ?>.
 * Umumnya file Laravel tidak memakai penutup PHP.
 */
if (str_ends_with($contents, '?>')) {
    $contents =
        rtrim(
            substr($contents, 0, -2)
        ).
        "\n\n".
        "// Final override workflow Barang Masuk.\n".
        $requireLine.
        "\n?>\n";
} else {
    $contents .=
        "\n\n".
        "// Final override workflow Barang Masuk.\n".
        $requireLine.
        "\n";
}

if (
    file_put_contents(
        $webFile,
        $contents
    ) === false
) {
    copy($backup, $webFile);

    fwrite(
        STDERR,
        "GAGAL menulis routes/web.php.\n"
    );

    exit(1);
}

$files = [
    $webFile,
    $overrideFile,
];

foreach ($files as $file) {
    exec(
        escapeshellarg(PHP_BINARY).
        ' -l '.
        escapeshellarg($file).
        ' 2>&1',
        $output,
        $status
    );

    if ($status !== 0) {
        copy($backup, $webFile);

        fwrite(
            STDERR,
            "GAGAL: syntax PHP tidak valid.\n".
            implode("\n", $output).
            "\n".
            "routes/web.php sudah dikembalikan.\n"
        );

        exit(1);
    }

    $output = [];
}

echo "FINAL ROUTE OVERRIDE BARANG MASUK BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
echo "Require ditempatkan pada baris terakhir routes/web.php.\n";
