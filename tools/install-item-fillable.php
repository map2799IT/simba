<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIMBA Item Fillable Installer
|--------------------------------------------------------------------------
|
| Menambahkan seluruh atribut master barang dan Barang Masuk ke
| app/Models/Item.php tanpa mengganti relasi, scope, casts, atau method
| lain yang sudah tersedia.
|
*/

$root = dirname(__DIR__);

$itemFile =
    $root.
    DIRECTORY_SEPARATOR.
    'app'.
    DIRECTORY_SEPARATOR.
    'Models'.
    DIRECTORY_SEPARATOR.
    'Item.php';

if (! is_file($itemFile)) {
    fwrite(
        STDERR,
        "GAGAL: app/Models/Item.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents =
    file_get_contents($itemFile);

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL: Item.php tidak dapat dibaca.\n"
    );

    exit(1);
}

$backupFile =
    $itemFile.
    '.before-fillable-hotfix.bak';

if (! is_file($backupFile)) {
    if (! copy(
        $itemFile,
        $backupFile
    )) {
        fwrite(
            STDERR,
            "GAGAL: backup Item.php tidak dapat dibuat.\n"
        );

        exit(1);
    }
}

$requiredFillable = [
    'type',
    'code',
    'name',
    'item_category_id',
    'unit_id',
    'workshop_id',
    'storage_location_id',
    'brand',
    'model',
    'serial_number',
    'specification',
    'received_date',
    'acquisition_source',
    'fund_source',
    'unit_price',
    'condition',
    'status',
    'stock',
    'minimum_stock',
    'is_borrowable',
    'photo_path',
    'description',
    'is_active',
];

$fillablePattern =
    '/protected\s+\$fillable\s*=\s*\[(.*?)\]\s*;/s';

$currentFillable = [];

if (
    preg_match(
        $fillablePattern,
        $contents,
        $matches
    ) === 1
) {
    preg_match_all(
        '/[\'"]([^\'"]+)[\'"]/',
        $matches[1],
        $fieldMatches
    );

    $currentFillable =
        $fieldMatches[1] ?? [];
}

$mergedFillable =
    array_values(
        array_unique(
            array_merge(
                $currentFillable,
                $requiredFillable
            )
        )
    );

$fillableLines =
    array_map(
        static fn (
            string $field
        ): string =>
            "        '".$field."',",
        $mergedFillable
    );

$newFillable =
    "protected \$fillable = [\n"
    .implode(
        "\n",
        $fillableLines
    )
    ."\n    ];";

if (
    preg_match(
        $fillablePattern,
        $contents
    ) === 1
) {
    $updated =
        preg_replace(
            $fillablePattern,
            $newFillable,
            $contents,
            1
        );
} else {
    $classPattern =
        '/class\s+Item\s+extends\s+Model[^{]*\{/m';

    if (
        preg_match(
            $classPattern,
            $contents,
            $classMatch,
            PREG_OFFSET_CAPTURE
        ) !== 1
    ) {
        fwrite(
            STDERR,
            "GAGAL: deklarasi class Item tidak ditemukan.\n"
        );

        exit(1);
    }

    $classDeclaration =
        $classMatch[0][0];

    $classOffset =
        $classMatch[0][1];

    $openBrace =
        $classOffset
        + strrpos(
            $classDeclaration,
            '{'
        );

    $updated =
        substr(
            $contents,
            0,
            $openBrace + 1
        )
        ."\n\n    "
        .$newFillable
        ."\n"
        .substr(
            $contents,
            $openBrace + 1
        );
}

if (! is_string($updated)) {
    fwrite(
        STDERR,
        "GAGAL: isi Item.php tidak dapat diperbarui.\n"
    );

    exit(1);
}

if (
    file_put_contents(
        $itemFile,
        $updated
    ) === false
) {
    fwrite(
        STDERR,
        "GAGAL: Item.php tidak dapat ditulis.\n"
    );

    exit(1);
}

$command =
    escapeshellarg(
        PHP_BINARY
    )
    .' -l '
    .escapeshellarg(
        $itemFile
    )
    .' 2>&1';

exec(
    $command,
    $lintOutput,
    $lintExitCode
);

if ($lintExitCode !== 0) {
    copy(
        $backupFile,
        $itemFile
    );

    fwrite(
        STDERR,
        "GAGAL: hasil patch tidak valid. File lama dikembalikan.\n"
        .implode(
            "\n",
            $lintOutput
        )
        ."\n"
    );

    exit(1);
}

echo "ITEM FILLABLE BERHASIL DIPERBARUI.\n";
echo "Jumlah atribut fillable: ".
    count($mergedFillable).
    "\n";

echo "Backup: {$backupFile}\n";
