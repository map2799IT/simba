<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$itemFile =
    $root.
    '/app/Models/Item.php';

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

$backup =
    $itemFile.
    '.before-damage-relations.'.
    date('YmdHis').
    '.bak';

if (! copy($itemFile, $backup)) {
    fwrite(
        STDERR,
        "GAGAL: backup Item.php tidak dapat dibuat.\n"
    );

    exit(1);
}

$methods = '';

if (
    preg_match(
        '/function\s+damageReports\s*\(/',
        $contents
    ) !== 1
) {
    $methods .= <<<'PHP_METHOD'

    /**
     * Semua laporan kerusakan yang terkait langsung
     * dengan master barang ini.
     */
    public function damageReports():
        \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\DamageReport::class,
            'item_id',
            'id'
        );
    }

PHP_METHOD;
}

if (
    preg_match(
        '/function\s+itemAssets\s*\(/',
        $contents
    ) !== 1
) {
    $methods .= <<<'PHP_METHOD'

    /**
     * Seluruh unit fisik yang berasal dari master barang.
     */
    public function itemAssets():
        \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\ItemAsset::class,
            'item_id',
            'id'
        );
    }

PHP_METHOD;
}

if (
    preg_match(
        '/function\s+storageLocation\s*\(/',
        $contents
    ) !== 1
) {
    $methods .= <<<'PHP_METHOD'

    /**
     * Kompatibilitas data master lama yang masih
     * memiliki storage_location_id.
     */
    public function storageLocation():
        \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            \App\Models\StorageLocation::class,
            'storage_location_id',
            'id'
        );
    }

PHP_METHOD;
}

if ($methods === '') {
    echo "Semua relasi Item sudah tersedia.\n";
    echo "Tidak ada file yang diubah.\n";
    exit(0);
}

$lastBrace =
    strrpos(
        $contents,
        '}'
    );

if ($lastBrace === false) {
    fwrite(
        STDERR,
        "GAGAL: penutup class Item tidak ditemukan.\n"
    );

    exit(1);
}

$updated =
    substr(
        $contents,
        0,
        $lastBrace
    ).
    $methods.
    substr(
        $contents,
        $lastBrace
    );

if (
    file_put_contents(
        $itemFile,
        $updated
    ) === false
) {
    copy(
        $backup,
        $itemFile
    );

    fwrite(
        STDERR,
        "GAGAL: Item.php tidak dapat ditulis.\n"
    );

    exit(1);
}

$command =
    escapeshellarg(PHP_BINARY).
    ' -l '.
    escapeshellarg($itemFile).
    ' 2>&1';

exec(
    $command,
    $lintOutput,
    $lintStatus
);

if ($lintStatus !== 0) {
    copy(
        $backup,
        $itemFile
    );

    fwrite(
        STDERR,
        "GAGAL: syntax Item.php tidak valid.\n".
        "File lama sudah dikembalikan.\n\n".
        implode(
            "\n",
            $lintOutput
        ).
        "\n"
    );

    exit(1);
}

echo "RELASI ITEM BERHASIL DIPERBAIKI.\n";
echo "Backup: {$backup}\n";
