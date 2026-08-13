<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$routeFile =
    $root.
    '/routes/blade-route-aliases.php';

if (! is_file($routeFile)) {
    fwrite(
        STDERR,
        "GAGAL: routes/blade-route-aliases.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents =
    file_get_contents($routeFile);

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL: route alias tidak dapat dibaca.\n"
    );

    exit(1);
}

$backup =
    $routeFile.
    '.before-category-formrequest.'.
    date('YmdHis').
    '.bak';

if (! copy(
    $routeFile,
    $backup
)) {
    fwrite(
        STDERR,
        "GAGAL: backup route alias tidak dapat dibuat.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Penyebab
|--------------------------------------------------------------------------
|
| categories.store dan categories.update mengirim:
|
| 'request' => request()
|
| kepada app()->call(). Nilai tersebut adalah Illuminate\Http\Request
| biasa dan menimpa resolusi StoreItemCategoryRequest atau
| UpdateItemCategoryRequest dari Laravel Container.
|
| Dengan menghapus parameter request manual, Container akan membangun
| FormRequest yang benar, termasuk authorize() dan rules().
|
*/

$patterns = [
    /*
     * Bentuk satu baris:
     * 'request' => request(),
     */
    '/^[ \t]*[\'"]request[\'"]\s*=>\s*request\(\)\s*,?\s*$/m',

    /*
     * Bentuk multi-baris:
     * 'request' =>
     *     request(),
     */
    '/^[ \t]*[\'"]request[\'"]\s*=>\s*\R[ \t]*request\(\)\s*,?\s*$/m',
];

$updated =
    $contents;

$totalRemoved = 0;

foreach ($patterns as $pattern) {
    $updated =
        preg_replace(
            $pattern,
            '',
            $updated,
            -1,
            $count
        );

    if (! is_string($updated)) {
        copy(
            $backup,
            $routeFile
        );

        fwrite(
            STDERR,
            "GAGAL: proses patch route alias gagal.\n"
        );

        exit(1);
    }

    $totalRemoved += $count;
}

if ($totalRemoved === 0) {
    if (
        ! str_contains(
            $contents,
            "'request' => request()"
        )
        && ! preg_match(
            '/[\'"]request[\'"]\s*=>\s*\R\s*request\(\)/',
            $contents
        )
    ) {
        echo "Route kategori sudah tidak mengirim request manual.\n";
        echo "Tidak ada perubahan yang diperlukan.\n";
        exit(0);
    }

    copy(
        $backup,
        $routeFile
    );

    fwrite(
        STDERR,
        "GAGAL: pola request manual ditemukan tetapi tidak dapat diperbaiki.\n"
    );

    exit(1);
}

/*
 * Rapikan array kosong yang semula berisi request.
 */
$updated =
    preg_replace(
        '/\[\s*\]/',
        '[]',
        $updated
    );

if (! is_string($updated)) {
    copy(
        $backup,
        $routeFile
    );

    fwrite(
        STDERR,
        "GAGAL: hasil route alias tidak valid.\n"
    );

    exit(1);
}

if (
    file_put_contents(
        $routeFile,
        $updated
    ) === false
) {
    copy(
        $backup,
        $routeFile
    );

    fwrite(
        STDERR,
        "GAGAL: route alias tidak dapat ditulis.\n"
    );

    exit(1);
}

$command =
    escapeshellarg(
        PHP_BINARY
    ).
    ' -l '.
    escapeshellarg(
        $routeFile
    ).
    ' 2>&1';

exec(
    $command,
    $lintOutput,
    $lintStatus
);

if ($lintStatus !== 0) {
    copy(
        $backup,
        $routeFile
    );

    fwrite(
        STDERR,
        "GAGAL: syntax route hasil patch tidak valid. Backup dikembalikan.\n".
        implode(
            "\n",
            $lintOutput
        ).
        "\n"
    );

    exit(1);
}

echo "CATEGORY FORM REQUEST ROUTE BERHASIL DIPERBAIKI.\n";
echo "Parameter request manual dihapus: {$totalRemoved}\n";
echo "Backup: {$backup}\n";
