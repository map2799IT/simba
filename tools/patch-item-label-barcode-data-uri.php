<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$service = $root.
    DIRECTORY_SEPARATOR.
    'app'.
    DIRECTORY_SEPARATOR.
    'Services'.
    DIRECTORY_SEPARATOR.
    'ItemLabelCodeService.php';

if (! is_file($service)) {
    fwrite(
        STDERR,
        "File tidak ditemukan: {$service}".
        PHP_EOL
    );

    exit(1);
}

$contents = file_get_contents($service);

if ($contents === false) {
    fwrite(
        STDERR,
        'Gagal membaca ItemLabelCodeService.php'.
        PHP_EOL
    );

    exit(1);
}

/*
 * Jangan menambahkan method kedua bila sudah tersedia.
 */
if (
    preg_match(
        '/function\s+barcodeDataUri\s*\(/i',
        $contents
    )
) {
    echo 'barcodeDataUri() sudah tersedia.'.
        PHP_EOL;

    exit(0);
}

$backup = $service.
    '.before-barcodeDataUri.'.
    date('YmdHis').
    '.bak';

if (! copy($service, $backup)) {
    fwrite(
        STDERR,
        'Gagal membuat file cadangan.'.
        PHP_EOL
    );

    exit(1);
}

$lastBrace = strrpos(
    $contents,
    '}'
);

if ($lastBrace === false) {
    fwrite(
        STDERR,
        'Penutup class tidak ditemukan.'.
        PHP_EOL
    );

    exit(1);
}

$method = <<<'PHP'

    /**
     * Kompatibilitas untuk controller/view lama yang masih
     * meminta barcode dalam bentuk Data URI.
     *
     * Label unit alat tetap dapat menggunakan QR Code.
     */
    public function barcodeDataUri(
        mixed $value,
        int $widthFactor = 2,
        int $height = 70
    ): ?string {
        return \App\Support\BarcodeDataUriGenerator::
            dataUri(
                $value,
                $widthFactor,
                $height
            );
    }

PHP;

$patched =
    substr(
        $contents,
        0,
        $lastBrace
    ).
    $method.
    substr(
        $contents,
        $lastBrace
    );

if (
    file_put_contents(
        $service,
        $patched
    ) === false
) {
    fwrite(
        STDERR,
        'Gagal menyimpan hasil patch.'.
        PHP_EOL
    );

    exit(1);
}

echo 'barcodeDataUri() berhasil ditambahkan.'.
    PHP_EOL;

echo "Cadangan: {$backup}".
    PHP_EOL;
