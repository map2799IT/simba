<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$file = $root.'/app/Services/InventoryPlacementReportService.php';

if (! is_file($file)) {
    echo "InventoryPlacementReportService tidak tersedia. Patch laporan dilewati.\n";
    exit(0);
}

$contents = file_get_contents($file);

if (! is_string($contents)) {
    exit("GAGAL membaca service laporan.\n");
}

if (str_contains($contents, "->where('ia.status', 'available')")) {
    echo "Laporan alat sudah menghitung unit tersedia saja.\n";
    exit(0);
}

$needle = "->where('ia.is_active', true)";
$position = strpos($contents, $needle);

if ($position === false) {
    echo "Pola tool aggregate laporan tidak ditemukan. Patch dilewati agar file aman.\n";
    exit(0);
}

$backup = $file.'.before-available-stock.'.date('YmdHis').'.bak';

if (! copy($file, $backup)) {
    exit("GAGAL membuat backup service laporan.\n");
}

$replacement = $needle."\n            ->where('ia.status', 'available')";
$updated = substr_replace($contents, $replacement, $position, strlen($needle));

if (file_put_contents($file, $updated) === false) {
    copy($backup, $file);
    exit("GAGAL memperbarui service laporan.\n");
}

exec(
    escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy($backup, $file);
    exit("GAGAL syntax service laporan.\n".implode("\n", $output)."\n");
}

echo "LAPORAN INVENTARIS SEKARANG MENGHITUNG UNIT ALAT TERSEDIA.\n";
echo "Backup: {$backup}\n";
