<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile =
    $root.
    '/routes/web.php';

$bootstrapFile =
    $root.
    '/bootstrap/app.php';

$routeFile =
    $root.
    '/routes/location-inventory-two-modes.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "GAGAL: routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($routeFile)) {
    fwrite(
        STDERR,
        "GAGAL: route inventaris lokasi tidak ditemukan.\n"
    );

    exit(1);
}

$contents =
    file_get_contents(
        $webFile
    );

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL membaca routes/web.php.\n"
    );

    exit(1);
}

$backup =
    $webFile.
    '.before-waka-sarpras.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        "GAGAL membuat backup routes/web.php.\n"
    );

    exit(1);
}

$requireLine =
    "require __DIR__.'/location-inventory-two-modes.php';";

$contents =
    preg_replace(
        "/^[ \\t]*require[ \\t]+__DIR__[ \\t]*\\.[ \\t]*[\"']\\/location-inventory-two-modes\\.php[\"'][ \\t]*;[ \\t]*$/m",
        '',
        $contents
    );

if (! is_string($contents)) {
    copy($backup, $webFile);

    fwrite(
        STDERR,
        "GAGAL membersihkan require route lama.\n"
    );

    exit(1);
}

$contents = rtrim($contents);

if (str_ends_with($contents, '?>')) {
    $contents =
        rtrim(
            substr(
                $contents,
                0,
                -2
            )
        ).
        "\n\n".
        "// Inventaris lokasi read-only dan print Waka Sarpras.\n".
        $requireLine.
        "\n?>\n";
} else {
    $contents .=
        "\n\n".
        "// Inventaris lokasi read-only dan print Waka Sarpras.\n".
        $requireLine.
        "\n";
}

if (
    file_put_contents(
        $webFile,
        $contents
    ) === false
) {
    copy($backup, $webFile);

    fwrite(
        STDERR,
        "GAGAL menulis routes/web.php.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Pastikan middleware role dipasang pada group web
|--------------------------------------------------------------------------
*/

if (! is_file($bootstrapFile)) {
    fwrite(
        STDERR,
        "GAGAL: bootstrap/app.php tidak ditemukan.\n"
    );

    exit(1);
}

$bootstrap =
    file_get_contents(
        $bootstrapFile
    );

if (! is_string($bootstrap)) {
    fwrite(
        STDERR,
        "GAGAL membaca bootstrap/app.php.\n"
    );

    exit(1);
}

if (
    ! str_contains(
        $bootstrap,
        'EnforceSimbaRoleAccess'
    )
) {
    $bootstrapBackup =
        $bootstrapFile.
        '.before-waka-role-middleware.'.
        date('YmdHis').
        '.bak';

    copy(
        $bootstrapFile,
        $bootstrapBackup
    );

    $append =
        "\n        \$middleware->appendToGroup(\n".
        "            'web',\n".
        "            \\App\\Http\\Middleware\\EnforceSimbaRoleAccess::class\n".
        "        );\n";

    $patterns = [
        '/(->withMiddleware\(\s*function\s*\(\s*Middleware\s+\$middleware\s*\)\s*:\s*void\s*\{)/',
        '/(->withMiddleware\(\s*function\s*\(\s*Middleware\s+\$middleware\s*\)\s*\{)/',
        '/(->withMiddleware\(\s*static\s+function\s*\(\s*Middleware\s+\$middleware\s*\)\s*:\s*void\s*\{)/',
    ];

    $patched = false;

    foreach ($patterns as $pattern) {
        $updated =
            preg_replace(
                $pattern,
                '$1'.$append,
                $bootstrap,
                1,
                $count
            );

        if (
            is_string($updated)
            && $count === 1
        ) {
            $bootstrap =
                $updated;

            $patched =
                true;

            break;
        }
    }

    if (! $patched) {
        copy(
            $backup,
            $webFile
        );

        fwrite(
            STDERR,
            "GAGAL: blok withMiddleware tidak ditemukan.\n"
        );

        exit(1);
    }

    file_put_contents(
        $bootstrapFile,
        $bootstrap
    );
}

foreach (
    [
        $webFile,
        $routeFile,
        $bootstrapFile,
    ]
    as $file
) {
    $output = [];

    exec(
        escapeshellarg(PHP_BINARY).
        ' -l '.
        escapeshellarg($file).
        ' 2>&1',
        $output,
        $status
    );

    if ($status !== 0) {
        copy(
            $backup,
            $webFile
        );

        fwrite(
            STDERR,
            "GAGAL syntax: {$file}\n".
            implode(
                "\n",
                $output
            ).
            "\n"
        );

        exit(1);
    }
}

echo "WAKA SARPRAS READ-ONLY BERHASIL DIPASANG.\n";
echo "Backup routes: {$backup}\n";
echo "Akses: Dashboard, Laporan Global, Lokasi, Print, PDF, Excel.\n";
