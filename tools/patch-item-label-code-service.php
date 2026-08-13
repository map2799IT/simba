<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$service = $root.'/app/Services/ItemLabelCodeService.php';

if (! is_file($service)) {
    fwrite(STDERR, "File tidak ditemukan: {$service}\n");
    exit(1);
}

$contents = file_get_contents($service);

if ($contents === false) {
    fwrite(STDERR, "Gagal membaca service.\n");
    exit(1);
}

if (preg_match(
    '/function\s+qrDataUri\s*\(/i',
    $contents
)) {
    echo "qrDataUri() sudah tersedia.\n";
    exit(0);
}

$backup = $service.
    '.before-safe-qrDataUri.'.
    date('YmdHis').
    '.bak';

copy($service, $backup);

$lastBrace = strrpos($contents, '}');

if ($lastBrace === false) {
    fwrite(STDERR, "Penutup class tidak ditemukan.\n");
    exit(1);
}

$method = <<<'PHP'

    /**
     * Membuat QR Code sebagai Data URI.
     */
    public function qrDataUri(
        mixed $value,
        int $size = 300,
        int $margin = 10
    ): ?string {
        return \App\Support\QrCodeDataUriGenerator::dataUri(
            $value,
            $size,
            $margin
        );
    }

PHP;

$patched =
    substr($contents, 0, $lastBrace).
    $method.
    substr($contents, $lastBrace);

file_put_contents($service, $patched);

echo "qrDataUri() berhasil ditambahkan.\n";
echo "Cadangan: {$backup}\n";
