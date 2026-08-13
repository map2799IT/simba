<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$service = $root.'/app/Services/ItemLabelCodeService.php';

if (! is_file($service)) {
    fwrite(STDERR, "File tidak ditemukan: {$service}\n");
    exit(1);
}

$backups = glob(
    $service.'.before-qrDataUri*.bak'
) ?: [];

if ($backups !== []) {
    usort(
        $backups,
        static fn (string $a, string $b): int =>
            filemtime($b) <=> filemtime($a)
    );

    $backup = $backups[0];

    if (! copy($backup, $service)) {
        fwrite(STDERR, "Gagal memulihkan {$backup}\n");
        exit(1);
    }

    echo "Service dipulihkan dari:\n{$backup}\n";
} else {
    $current = file_get_contents($service);

    if ($current === false) {
        fwrite(STDERR, "Gagal membaca service.\n");
        exit(1);
    }

    $count = preg_match_all(
        '/function\s+qrSvg\s*\(/i',
        $current
    );

    if ($count > 1) {
        fwrite(
            STDERR,
            "Cadangan sebelum patch tidak ditemukan dan qrSvg() masih duplikat.\n".
            "Pulihkan file ItemLabelCodeService.php dari backup project terlebih dahulu.\n"
        );
        exit(1);
    }

    echo "Cadangan patch lama tidak ditemukan; service saat ini dipertahankan.\n";
}

$contents = file_get_contents($service);

if ($contents === false) {
    fwrite(STDERR, "Gagal membaca hasil pemulihan.\n");
    exit(1);
}

$qrSvgCount = preg_match_all(
    '/function\s+qrSvg\s*\(/i',
    $contents
);

if ($qrSvgCount > 1) {
    fwrite(
        STDERR,
        "Pemulihan masih menghasilkan {$qrSvgCount} qrSvg().\n"
    );
    exit(1);
}

if (! preg_match(
    '/function\s+qrDataUri\s*\(/i',
    $contents
)) {
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

    $contents =
        substr($contents, 0, $lastBrace).
        $method.
        substr($contents, $lastBrace);

    if (file_put_contents($service, $contents) === false) {
        fwrite(STDERR, "Gagal menyimpan qrDataUri().\n");
        exit(1);
    }

    echo "qrDataUri() berhasil ditambahkan.\n";
} else {
    echo "qrDataUri() sudah tersedia.\n";
}

echo "Perbaikan selesai.\n";
