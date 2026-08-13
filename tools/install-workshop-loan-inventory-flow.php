<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$webFile = $root.'/routes/web.php';
$routeFile = $root.'/routes/workshop-loan-inventory-flow.php';

if (! is_file($webFile) || ! is_file($routeFile)) {
    fwrite(STDERR, "GAGAL: routes/web.php atau route workflow tidak ditemukan.\n");
    exit(1);
}

$contents = file_get_contents($webFile);

if (! is_string($contents)) {
    fwrite(STDERR, "GAGAL membaca routes/web.php.\n");
    exit(1);
}

$backup = $webFile.'.before-workshop-loan-flow.'.date('YmdHis').'.bak';

if (! copy($webFile, $backup)) {
    fwrite(STDERR, "GAGAL membuat backup routes/web.php.\n");
    exit(1);
}

$requireLine = "require __DIR__.'/workshop-loan-inventory-flow.php';";

$contents = preg_replace(
    "/^[ \t]*require[ \t]+__DIR__[ \t]*\\.[ \t]*[\"']\\/workshop-loan-inventory-flow\\.php[\"'][ \t]*;[ \t]*$/m",
    '',
    $contents
);

if (! is_string($contents)) {
    copy($backup, $webFile);
    exit("GAGAL membersihkan require lama.\n");
}

$contents = rtrim($contents);

if (str_ends_with($contents, '?>')) {
    $contents = rtrim(substr($contents, 0, -2))
        ."\n\n// Final workflow peminjaman dan stok jurusan.\n"
        .$requireLine."\n?>\n";
} else {
    $contents .= "\n\n// Final workflow peminjaman dan stok jurusan.\n"
        .$requireLine."\n";
}

if (file_put_contents($webFile, $contents) === false) {
    copy($backup, $webFile);
    exit("GAGAL menulis routes/web.php.\n");
}

foreach ([$webFile, $routeFile] as $file) {
    exec(
        escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',
        $output,
        $status
    );

    if ($status !== 0) {
        copy($backup, $webFile);
        exit(
            "GAGAL: syntax route tidak valid.\n".
            implode("\n", $output).
            "\nroutes/web.php sudah dikembalikan.\n"
        );
    }

    $output = [];
}

echo "WORKFLOW PEMINJAMAN DAN STOK JURUSAN BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
