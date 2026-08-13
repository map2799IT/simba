<?php

declare(strict_types=1);

$root = dirname(__DIR__);

installProvider($root);
installMiddleware($root);
installLayoutGuards($root);
installReportRoutes($root);

echo "\nINSTALASI JURUSAN DAN ROUTING PEMINJAMAN SELESAI\n";
echo "================================================\n";
echo "Lanjutkan dengan migrate, seeder, dump-autoload, dan optimize:clear.\n";

function installProvider(
    string $root
): void {
    $file = $root.
        DIRECTORY_SEPARATOR.
        'bootstrap'.
        DIRECTORY_SEPARATOR.
        'providers.php';

    if (! is_file($file)) {
        fwrite(
            STDERR,
            "bootstrap/providers.php tidak ditemukan.\n"
        );

        exit(1);
    }

    $contents = file_get_contents(
        $file
    );

    $class =
        'App\\Providers\\JurusanAccessServiceProvider::class';

    if (
        str_contains(
            $contents,
            'JurusanAccessServiceProvider'
        )
    ) {
        echo "[OK] Service provider sudah terpasang.\n";
        return;
    }

    backupFile(
        $file,
        'jurusan-provider'
    );

    $position = strrpos(
        $contents,
        ']'
    );

    if ($position === false) {
        fwrite(
            STDERR,
            "Format bootstrap/providers.php tidak dikenali.\n"
        );

        exit(1);
    }

    $before = rtrim(
        substr(
            $contents,
            0,
            $position
        )
    );

    if (
        ! str_ends_with(
            $before,
            ','
        )
    ) {
        $before .= ',';
    }

    $contents =
        $before.
        "\n    {$class},\n".
        substr(
            $contents,
            $position
        );

    file_put_contents(
        $file,
        $contents
    );

    echo "[PASANG] JurusanAccessServiceProvider\n";
}

function installMiddleware(
    string $root
): void {
    $file = $root.
        DIRECTORY_SEPARATOR.
        'bootstrap'.
        DIRECTORY_SEPARATOR.
        'app.php';

    if (! is_file($file)) {
        fwrite(
            STDERR,
            "bootstrap/app.php tidak ditemukan.\n"
        );

        exit(1);
    }

    $contents = file_get_contents(
        $file
    );

    $classes = [
        '\\App\\Http\\Middleware\\EnforceSimbaRoleAccess::class',
        '\\App\\Http\\Middleware\\EnforceUserJurusanAssignment::class',
        '\\App\\Http\\Middleware\\RouteLoanToJurusanToolman::class',
    ];

    $missing = [];

    foreach ($classes as $class) {
        $short = basename(
            str_replace(
                '\\',
                '/',
                str_replace(
                    '::class',
                    '',
                    $class
                )
            )
        );

        if (
            ! str_contains(
                $contents,
                $short
            )
        ) {
            $missing[] = $class;
        }
    }

    if ($missing === []) {
        echo "[OK] Middleware sudah terpasang.\n";
        return;
    }

    backupFile(
        $file,
        'jurusan-middleware'
    );

    $lines = "\n";

    foreach ($missing as $class) {
        $lines .=
            "        \$middleware->appendToGroup(\n".
            "            'web',\n".
            "            {$class}\n".
            "        );\n";
    }

    $patterns = [
        '/(->withMiddleware\(\s*function\s*\(\s*Middleware\s+\$middleware\s*\)\s*:\s*void\s*\{)/',
        '/(->withMiddleware\(\s*function\s*\(\s*Middleware\s+\$middleware\s*\)\s*\{)/',
        '/(->withMiddleware\(\s*static\s+function\s*\(\s*Middleware\s+\$middleware\s*\)\s*:\s*void\s*\{)/',
    ];

    $patched = false;

    foreach ($patterns as $pattern) {
        $updated = preg_replace(
            $pattern,
            '$1'.$lines,
            $contents,
            1,
            $count
        );

        if (
            $updated !== null
            && $count === 1
        ) {
            $contents = $updated;
            $patched = true;
            break;
        }
    }

    if (! $patched) {
        fwrite(
            STDERR,
            "Blok withMiddleware tidak ditemukan.\n"
        );

        exit(1);
    }

    file_put_contents(
        $file,
        $contents
    );

    echo "[PASANG] Middleware jurusan dan routing loan\n";
}

function installLayoutGuards(
    string $root
): void {
    $file = $root.
        DIRECTORY_SEPARATOR.
        'resources'.
        DIRECTORY_SEPARATOR.
        'views'.
        DIRECTORY_SEPARATOR.
        'layouts'.
        DIRECTORY_SEPARATOR.
        'app.blade.php';

    if (! is_file($file)) {
        fwrite(
            STDERR,
            "Layout app.blade.php tidak ditemukan.\n"
        );

        exit(1);
    }

    $contents = file_get_contents(
        $file
    );

    $includes = [
        "@include('layouts.role-menu-guard')",
        "@include('layouts.user-jurusan-guard')",
        "@include('layouts.loan-jurusan-guard')",
    ];

    $missing = array_values(
        array_filter(
            $includes,
            static fn (
                string $include
            ): bool =>
                ! str_contains(
                    $contents,
                    $include
                )
        )
    );

    if ($missing === []) {
        echo "[OK] Guard layout sudah terpasang.\n";
        return;
    }

    backupFile(
        $file,
        'jurusan-layout'
    );

    $block =
        "    ".
        implode(
            "\n    ",
            $missing
        ).
        "\n";

    if (
        preg_match(
            '/<\/body>/i',
            $contents
        ) === 1
    ) {
        $contents = preg_replace(
            '/<\/body>/i',
            $block.'</body>',
            $contents,
            1
        );
    } else {
        $contents .=
            "\n".$block;
    }

    file_put_contents(
        $file,
        $contents
    );

    echo "[PASANG] Guard menu, user, dan loan\n";
}

function installReportRoutes(
    string $root
): void {
    $installer = $root.
        DIRECTORY_SEPARATOR.
        'tools'.
        DIRECTORY_SEPARATOR.
        'install-inventory-report-scope.php';

    if (! is_file($installer)) {
        return;
    }

    passthru(
        escapeshellarg(PHP_BINARY).
        ' '.
        escapeshellarg(
            $installer
        ),
        $exitCode
    );

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

function backupFile(
    string $file,
    string $label
): void {
    copy(
        $file,
        $file.
        '.before-'.
        $label.
        '.'.
        date('YmdHis').
        '.bak'
    );
}
