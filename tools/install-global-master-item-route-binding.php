<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$itemFile = $root.'/app/Models/Item.php';

if (! is_file($itemFile)) {
    fwrite(
        STDERR,
        "GAGAL: app/Models/Item.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents($itemFile);

if (! is_string($contents)) {
    fwrite(STDERR, "GAGAL membaca Item.php.\n");
    exit(1);
}

if (
    str_contains(
        $contents,
        'function resolveRouteBindingQuery'
    )
) {
    echo "Item model sudah mempunyai resolveRouteBindingQuery.\n";
    echo "Tidak ada perubahan otomatis.\n";
    exit(0);
}

$backup = $itemFile.
    '.before-global-master-binding.'.
    date('YmdHis').
    '.bak';

if (! copy($itemFile, $backup)) {
    fwrite(STDERR, "GAGAL membuat backup Item.php.\n");
    exit(1);
}

$method = <<<'PHP'

    /**
     * Master katalog workshop_id = null dapat diakses semua jurusan.
     * Master lama yang masih mempunyai workshop hanya dapat diakses
     * admin atau pengguna dari workshop yang sama.
     */
    public function resolveRouteBindingQuery(
        $query,
        $value,
        $field = null
    ) {
        $field ??= $this->getRouteKeyName();

        $query = $query
            ->withoutGlobalScopes()
            ->where($field, $value);

        $user = auth()->user();

        if (
            $user === null
            || (string) $user->role === 'admin'
        ) {
            return $query;
        }

        return $query->where(
            function ($scope) use ($user): void {
                $scope->whereNull('items.workshop_id');

                if ($user->workshop_id !== null) {
                    $scope->orWhere(
                        'items.workshop_id',
                        $user->workshop_id
                    );
                }
            }
        );
    }

PHP;

$lastBrace = strrpos($contents, '}');

if ($lastBrace === false) {
    fwrite(STDERR, "GAGAL: penutup class Item tidak ditemukan.\n");
    exit(1);
}

$updated = substr($contents, 0, $lastBrace).
    $method.
    substr($contents, $lastBrace);

if (file_put_contents($itemFile, $updated) === false) {
    copy($backup, $itemFile);
    fwrite(STDERR, "GAGAL menulis Item.php.\n");
    exit(1);
}

$command = escapeshellarg(PHP_BINARY).
    ' -l '.
    escapeshellarg($itemFile).
    ' 2>&1';

exec($command, $output, $status);

if ($status !== 0) {
    copy($backup, $itemFile);

    fwrite(
        STDERR,
        "GAGAL: hasil patch Item.php tidak valid.\n".
        implode("\n", $output).
        "\n"
    );

    exit(1);
}

echo "GLOBAL MASTER ITEM ROUTE BINDING BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
