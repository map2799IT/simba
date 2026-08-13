<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$file = $root.'/app/Models/ItemStockMovement.php';

if (! is_file($file)) {
    fwrite(STDERR, "GAGAL: app/Models/ItemStockMovement.php tidak ditemukan.\n");
    exit(1);
}

$contents = file_get_contents($file);

if (! is_string($contents)) {
    fwrite(STDERR, "GAGAL membaca ItemStockMovement.php.\n");
    exit(1);
}

$methods = '';

if (preg_match('/function\s+changeRequests\s*\(/', $contents) !== 1) {
    $methods .= <<<'PHP'

    public function changeRequests():
        \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\StockReceiptChangeRequest::class,
            'item_stock_movement_id',
            'id'
        );
    }

PHP;
}

if (preg_match('/function\s+pendingChangeRequest\s*\(/', $contents) !== 1) {
    $methods .= <<<'PHP'

    public function pendingChangeRequest():
        \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(
            \App\Models\StockReceiptChangeRequest::class,
            'item_stock_movement_id',
            'id'
        )
            ->where(
                'status',
                \App\Models\StockReceiptChangeRequest::STATUS_PENDING
            )
            ->latestOfMany();
    }

PHP;
}

if ($methods === '') {
    echo "Relasi approval ItemStockMovement sudah tersedia.\n";
    exit(0);
}

$backup = $file.'.before-approval-relations.'.date('YmdHis').'.bak';

if (! copy($file, $backup)) {
    fwrite(STDERR, "GAGAL membuat backup ItemStockMovement.php.\n");
    exit(1);
}

$position = strrpos($contents, '}');

if ($position === false) {
    fwrite(STDERR, "GAGAL menemukan penutup class ItemStockMovement.\n");
    exit(1);
}

$updated =
    substr($contents, 0, $position).
    rtrim($methods).
    "\n".
    substr($contents, $position);

if (file_put_contents($file, $updated) === false) {
    copy($backup, $file);
    fwrite(STDERR, "GAGAL menulis ItemStockMovement.php.\n");
    exit(1);
}

exec(
    escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy($backup, $file);

    fwrite(
        STDERR,
        "GAGAL: syntax model tidak valid. File lama dikembalikan.\n".
        implode("\n", $output).
        "\n"
    );

    exit(1);
}

echo "RELASI APPROVAL ITEM STOCK MOVEMENT BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
