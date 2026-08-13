<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIMBA Item Model Options API Installer
|--------------------------------------------------------------------------
|
| Script ini menambahkan API yang belum tersedia ke app/Models/Item.php
| tanpa mengganti isi model secara keseluruhan.
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
    '.before-options-api-hotfix.bak';

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

$changed = false;

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

$classOpenBracePosition =
    $classOffset
    + strrpos(
        $classDeclaration,
        '{'
    );

$constants = '';

if (
    ! preg_match(
        '/\bTYPE_OPTIONS\b/',
        $contents
    )
) {
    $constants .= <<<'PHP'

    public const TYPE_OPTIONS = [
        'tool' => 'Alat',
        'material' => 'Bahan',
    ];

PHP;
}

if (
    ! preg_match(
        '/\bCONDITION_OPTIONS\b/',
        $contents
    )
) {
    $constants .= <<<'PHP'
    public const CONDITION_OPTIONS = [
        'good' => 'Baik',
        'minor_damage' => 'Rusak Ringan',
        'major_damage' => 'Rusak Berat',
        'maintenance' => 'Dalam Perawatan',
        'unfit' => 'Tidak Layak Pakai',
    ];

PHP;
}

if (
    ! preg_match(
        '/\bSTATUS_OPTIONS\b/',
        $contents
    )
) {
    $constants .= <<<'PHP'
    public const STATUS_OPTIONS = [
        'available' => 'Tersedia',
        'reserved' => 'Dipesan',
        'borrowed' => 'Dipinjam',
        'damaged' => 'Rusak',
        'maintenance' => 'Dalam Perawatan',
        'lost' => 'Hilang',
        'retired' => 'Dihapuskan',
        'out_of_stock' => 'Stok Habis',
    ];

PHP;
}

if ($constants !== '') {
    $contents =
        substr(
            $contents,
            0,
            $classOpenBracePosition + 1
        )
        .$constants
        .substr(
            $contents,
            $classOpenBracePosition + 1
        );

    $changed = true;
}

$methods = '';

if (
    ! preg_match(
        '/function\s+typeOptions\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'

    public static function typeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+conditionOptions\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public static function conditionOptions(): array
    {
        return self::CONDITION_OPTIONS;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+statusOptions\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public static function statusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+typeLabel\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function typeLabel(): string
    {
        return self::TYPE_OPTIONS[
            (string) $this->type
        ] ?? (string) $this->type;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+conditionLabel\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function conditionLabel(): string
    {
        return self::CONDITION_OPTIONS[
            (string) $this->condition
        ] ?? (string) $this->condition;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+statusLabel\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[
            (string) $this->status
        ] ?? (string) $this->status;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+isTool\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function isTool(): bool
    {
        return (string) $this->type
            === 'tool';
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+isMaterial\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function isMaterial(): bool
    {
        return (string) $this->type
            === 'material';
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+isLowStock\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function isLowStock(): bool
    {
        return $this->isMaterial()
            && (float) $this->stock
                <= (float)
                    $this->minimum_stock;
    }

PHP;
}

if (
    ! preg_match(
        '/function\s+stockMovements\s*\(/',
        $contents
    )
) {
    $methods .= <<<'PHP'
    public function stockMovements()
    {
        return $this->hasMany(
            ItemStockMovement::class
        );
    }

PHP;
}

if ($methods !== '') {
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

    $contents =
        substr(
            $contents,
            0,
            $lastBrace
        )
        .$methods
        .substr(
            $contents,
            $lastBrace
        );

    $changed = true;
}

if (! $changed) {
    echo "Item model sudah memiliki seluruh Options API.\n";
    exit(0);
}

if (
    file_put_contents(
        $itemFile,
        $contents
    ) === false
) {
    fwrite(
        STDERR,
        "GAGAL: Item.php tidak dapat diperbarui.\n"
    );

    exit(1);
}

$phpBinary =
    PHP_BINARY;

$command =
    escapeshellarg(
        $phpBinary
    )
    .' -l '
    .escapeshellarg(
        $itemFile
    )
    .' 2>&1';

exec(
    $command,
    $output,
    $exitCode
);

if ($exitCode !== 0) {
    copy(
        $backupFile,
        $itemFile
    );

    fwrite(
        STDERR,
        "GAGAL: hasil patch tidak valid. File lama dikembalikan.\n"
        .implode(
            "\n",
            $output
        )
        ."\n"
    );

    exit(1);
}

echo "ITEM MODEL OPTIONS API BERHASIL DIPASANG.\n";
echo "Backup: {$backupFile}\n";
