<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$webFile = $root.'/routes/web.php';
$routeFile = $root.'/routes/location-inventory-two-modes.php';

if (! is_file($webFile) || ! is_file($routeFile)) {
    fwrite(STDERR, "GAGAL: file route tidak lengkap.
");
    exit(1);
}

$webContents = file_get_contents($webFile);
$requireLine = "require __DIR__.'/location-inventory-two-modes.php';";

if (! str_contains((string) $webContents, $requireLine)) {
    $backup = $webFile.'.before-location-print-modes.'.date('YmdHis').'.bak';
    copy($webFile, $backup);
    file_put_contents(
        $webFile,
        rtrim((string) $webContents).PHP_EOL.PHP_EOL.$requireLine.PHP_EOL
    );
    echo "Route inventaris lokasi dipasang.
";
} else {
    echo "Route inventaris lokasi sudah terpasang.
";
}

$indexFile = $root.'/resources/views/locations/index.blade.php';
if (is_file($indexFile)) {
    $contents = file_get_contents($indexFile);

    if (! str_contains((string) $contents, 'locations._inventory-menu-link')) {
        $backup = $indexFile.'.before-inventory-menu-link.'.date('YmdHis').'.bak';
        copy($indexFile, $backup);

        $updated = preg_replace(
            "/@section\('content'\)/",
            "@section('content')
    @include('locations._inventory-menu-link')",
            (string) $contents,
            1,
            $count
        );

        if (! is_string($updated) || $count !== 1) {
            copy($backup, $indexFile);
            fwrite(STDERR, "GAGAL menambahkan tombol Menu Cetak.
");
            exit(1);
        }

        file_put_contents($indexFile, $updated);
        echo "Tombol Menu Cetak ditambahkan.
";
    } else {
        echo "Tombol Menu Cetak sudah tersedia.
";
    }
}

echo "
MENU INDUK/TURUNAN DAN DUA OPSI PRINT BERHASIL DIPASANG.
";
