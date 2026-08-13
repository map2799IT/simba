<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile =
    $root.
    '/routes/web.php';

$routeFile =
    $root.
    '/routes/stock-receipt-photo.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "GAGAL: routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($routeFile)) {
    fwrite(
        STDERR,
        "GAGAL: routes/stock-receipt-photo.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents =
    file_get_contents($webFile);

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL membaca routes/web.php.\n"
    );

    exit(1);
}

$backup =
    $webFile.
    '.before-stock-receipt-photo.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        "GAGAL membuat backup routes/web.php.\n"
    );

    exit(1);
}

$requireLine =
    "require __DIR__.'/stock-receipt-photo.php';";

/*
 * Hapus require lama agar tidak terjadi route duplikat.
 */
$contents =
    preg_replace(
        "/^[ \t]*require[ \t]+__DIR__[ \t]*\\.[ \t]*[\"']\\/stock-receipt-photo\\.php[\"'][ \t]*;[ \t]*$/m",
        '',
        $contents
    );

if (! is_string($contents)) {
    copy($backup, $webFile);

    fwrite(
        STDERR,
        "GAGAL membersihkan require route foto lama.\n"
    );

    exit(1);
}

$contents =
    rtrim($contents);

if (
    str_ends_with(
        $contents,
        '?>'
    )
) {
    $contents =
        rtrim(
            substr(
                $contents,
                0,
                -2
            )
        ).
        "\n\n".
        "// Route foto Barang Masuk terlindungi.\n".
        $requireLine.
        "\n?>\n";
} else {
    $contents .=
        "\n\n".
        "// Route foto Barang Masuk terlindungi.\n".
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
        "GAGAL memperbarui routes/web.php.\n"
    );

    exit(1);
}

foreach (
    [
        $webFile,
        $routeFile,
    ]
    as $file
) {
    exec(
        escapeshellarg(
            PHP_BINARY
        ).
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
            "GAGAL: syntax route tidak valid.\n".
            implode(
                "\n",
                $output
            ).
            "\n".
            "routes/web.php sudah dikembalikan.\n"
        );

        exit(1);
    }

    $output = [];
}

echo "ROUTE FOTO BARANG MASUK BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
echo "Route aktif dan usulan ditempatkan pada bagian akhir routes/web.php.\n";
