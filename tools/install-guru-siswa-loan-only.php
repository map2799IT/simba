<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$webFile = $root.'/routes/web.php';
$routeFile = $root.'/routes/guru-siswa-loan-only.php';

if (! is_file($webFile)) {
    fwrite(STDERR, "GAGAL: routes/web.php tidak ditemukan.\n");
    exit(1);
}

if (! is_file($routeFile)) {
    fwrite(STDERR, "GAGAL: file route fix tidak ditemukan.\n");
    exit(1);
}

$contents = file_get_contents($webFile);

if (! is_string($contents)) {
    fwrite(STDERR, "GAGAL membaca routes/web.php.\n");
    exit(1);
}

$backup = $webFile.'.before-guru-siswa-loan-only.'.date('YmdHis').'.bak';

if (! copy($webFile, $backup)) {
    fwrite(STDERR, "GAGAL membuat backup routes/web.php.\n");
    exit(1);
}

$requireLine = "require __DIR__.'/guru-siswa-loan-only.php';";

$contents = preg_replace(
    "/^[ \\t]*require[ \\t]+__DIR__[ \\t]*\\.[ \\t]*[\"']\\/guru-siswa-loan-only\\.php[\"'][ \\t]*;[ \\t]*$/m",
    '',
    $contents
);

if (! is_string($contents)) {
    copy($backup, $webFile);
    fwrite(STDERR, "GAGAL membersihkan require lama.\n");
    exit(1);
}

$contents = rtrim($contents);

if (str_ends_with($contents, '?>')) {
    $contents = rtrim(substr($contents, 0, -2))
        ."\n\n// Final access Guru/Siswa hanya Peminjaman.\n"
        .$requireLine
        ."\n?>\n";
} else {
    $contents .= "\n\n// Final access Guru/Siswa hanya Peminjaman.\n"
        .$requireLine
        ."\n";
}

if (file_put_contents($webFile, $contents) === false) {
    copy($backup, $webFile);
    fwrite(STDERR, "GAGAL memperbarui routes/web.php.\n");
    exit(1);
}

foreach ([$webFile, $routeFile] as $file) {
    exec(
        escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',
        $output,
        $status
    );

    if ($status !== 0) {
        copy($backup, $webFile);
        fwrite(
            STDERR,
            "GAGAL: syntax route tidak valid.\n"
            .implode("\n", $output)
            ."\nroutes/web.php sudah dikembalikan.\n"
        );
        exit(1);
    }

    $output = [];
}

echo "FIX GURU/SISWA HANYA PEMINJAMAN BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
echo "Route override ditempatkan paling akhir.\n";
