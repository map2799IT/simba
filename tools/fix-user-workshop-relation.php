<?php

declare(strict_types=1);

$file = dirname(__DIR__).'/app/Models/User.php';

if (! is_file($file)) {
    exit("GAGAL: app/Models/User.php tidak ditemukan.\n");
}

$contents = file_get_contents($file);

if (! is_string($contents)) {
    exit("GAGAL membaca User.php.\n");
}

if (preg_match('/function\s+workshop\s*\(/', $contents) === 1) {
    exit("Relasi User::workshop() sudah tersedia.\n");
}

$backup = $file.'.before-workshop-relation.'.date('YmdHis').'.bak';

if (! copy($file, $backup)) {
    exit("GAGAL membuat backup User.php.\n");
}

$method = <<<'METHOD'

    public function workshop():
        \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Workshop::class,
            'workshop_id',
            'id'
        );
    }

METHOD;

$position = strrpos($contents, '}');

if ($position === false) {
    exit("GAGAL menemukan penutup class User.\n");
}

$updated =
    substr($contents, 0, $position).
    $method.
    substr($contents, $position);

if (file_put_contents($file, $updated) === false) {
    copy($backup, $file);
    exit("GAGAL memperbarui User.php.\n");
}

exec(
    escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy($backup, $file);

    exit(
        "GAGAL: syntax User.php tidak valid.\n".
        implode("\n", $output).
        "\n"
    );
}

echo "RELASI USER WORKSHOP BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
