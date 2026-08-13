<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$envFile =
    $root.
    '/.env';

$configFile =
    $root.
    '/config/app.php';

if (! is_file($configFile)) {
    fwrite(
        STDERR,
        "GAGAL: config/app.php tidak ditemukan.\n"
    );

    exit(1);
}

$config =
    file_get_contents(
        $configFile
    );

if (! is_string($config)) {
    fwrite(
        STDERR,
        "GAGAL membaca config/app.php.\n"
    );

    exit(1);
}

$configBackup =
    $configFile.
    '.before-timezone.'.
    date('YmdHis').
    '.bak';

if (! copy($configFile, $configBackup)) {
    fwrite(
        STDERR,
        "GAGAL membuat backup config/app.php.\n"
    );

    exit(1);
}

$updated =
    preg_replace(
        "/'timezone'\\s*=>\\s*[^,\\r\\n]+,/",
        "'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),",
        $config,
        1,
        $count
    );

if (
    ! is_string($updated)
    || $count !== 1
) {
    fwrite(
        STDERR,
        "GAGAL menemukan konfigurasi timezone pada config/app.php.\n"
    );

    exit(1);
}

if (
    file_put_contents(
        $configFile,
        $updated
    ) === false
) {
    copy(
        $configBackup,
        $configFile
    );

    fwrite(
        STDERR,
        "GAGAL memperbarui config/app.php.\n"
    );

    exit(1);
}

if (is_file($envFile)) {
    $env =
        file_get_contents(
            $envFile
        );

    if (! is_string($env)) {
        fwrite(
            STDERR,
            "GAGAL membaca .env.\n"
        );

        exit(1);
    }

    $envBackup =
        $envFile.
        '.before-timezone.'.
        date('YmdHis').
        '.bak';

    copy(
        $envFile,
        $envBackup
    );

    if (
        preg_match(
            '/^APP_TIMEZONE=/m',
            $env
        ) === 1
    ) {
        $env =
            preg_replace(
                '/^APP_TIMEZONE=.*$/m',
                'APP_TIMEZONE=Asia/Jakarta',
                $env
            );
    } else {
        $env =
            rtrim($env).
            "\nAPP_TIMEZONE=Asia/Jakarta\n";
    }

    file_put_contents(
        $envFile,
        $env
    );
}

exec(
    escapeshellarg(
        PHP_BINARY
    ).
    ' -l '.
    escapeshellarg(
        $configFile
    ).
    ' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy(
        $configBackup,
        $configFile
    );

    fwrite(
        STDERR,
        "GAGAL: syntax config/app.php tidak valid.\n".
        implode(
            "\n",
            $output
        ).
        "\n"
    );

    exit(1);
}

echo "TIMEZONE LOCAL BERHASIL DIATUR KE ASIA/JAKARTA.\n";
echo "Backup config: {$configBackup}\n";
echo "Jalankan artisan optimize:clear setelah ini.\n";
