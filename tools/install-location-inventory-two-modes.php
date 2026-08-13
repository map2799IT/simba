<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile =
    $root.
    '/routes/web.php';

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
        "GAGAL: route module tidak ditemukan.\n"
    );

    exit(1);
}

$webContents =
    file_get_contents(
        $webFile
    );

$requireLine =
    "require __DIR__.'/location-inventory-two-modes.php';";

if (
    ! str_contains(
        (string) $webContents,
        $requireLine
    )
) {
    $backup =
        $webFile.
        '.before-location-inventory-two-modes.'.
        date('YmdHis').
        '.bak';

    if (! copy(
        $webFile,
        $backup
    )) {
        fwrite(
            STDERR,
            "GAGAL membuat backup routes/web.php.\n"
        );

        exit(1);
    }

    $webContents =
        rtrim(
            (string) $webContents
        ).
        PHP_EOL.
        PHP_EOL.
        $requireLine.
        PHP_EOL;

    if (
        file_put_contents(
            $webFile,
            $webContents
        ) === false
    ) {
        copy(
            $backup,
            $webFile
        );

        fwrite(
            STDERR,
            "GAGAL memperbarui routes/web.php.\n"
        );

        exit(1);
    }

    echo "Route dua mode inventaris dipasang.\n";
} else {
    echo "Route dua mode inventaris sudah terpasang.\n";
}

$views = [
    $root.
        '/resources/views/locations/index.blade.php'
        => 'index',

    $root.
        '/resources/views/locations/show.blade.php'
        => 'show',
];

foreach (
    $views
    as $viewFile => $mode
) {
    if (! is_file($viewFile)) {
        echo "DILEWATI: {$viewFile} tidak ditemukan.\n";
        continue;
    }

    $contents =
        file_get_contents(
            $viewFile
        );

    if (
        str_contains(
            (string) $contents,
            "locations._inventory-action-buttons"
        )
    ) {
        echo "View {$mode} sudah memiliki dua tombol inventaris.\n";
        continue;
    }

    $backup =
        $viewFile.
        '.before-two-inventory-modes.'.
        date('YmdHis').
        '.bak';

    if (! copy(
        $viewFile,
        $backup
    )) {
        fwrite(
            STDERR,
            "GAGAL membuat backup view {$mode}.\n"
        );

        exit(1);
    }

    if ($mode === 'index') {
        $pattern =
            '~@if\s*\(\s*\$canPrint\s*\)\s*'.
            '<a\b.*?'.
            "locations\\.inventory\\.print".
            '.*?</a>\s*@endif~s';

        $replacement = <<<'BLADE'
@if ($canPrint)
    @include(
        'locations._inventory-action-buttons',
        [
            'location' => $location,
            'buttonSize' => 'sm',
            'includeChildren' => true,
        ]
    )
@endif
BLADE;
    } else {
        $pattern =
            '~@if\s*\(\s*\$canPrint\s*\).*?'.
            "locations\\.inventory\\.print".
            '.*?'.
            "locations\\.inventory\\.pdf".
            '.*?@endif~s';

        $replacement = <<<'BLADE'
@if ($canPrint)
    @include(
        'locations._inventory-action-buttons',
        [
            'location' => $location,
            'buttonSize' => 'normal',
            'includeChildren' => true,
        ]
    )
@endif
BLADE;
    }

    $updated =
        preg_replace(
            $pattern,
            $replacement,
            (string) $contents,
            1,
            $count
        );

    if (
        ! is_string($updated)
        || $count !== 1
    ) {
        copy(
            $backup,
            $viewFile
        );

        fwrite(
            STDERR,
            "GAGAL: blok tombol lama pada view {$mode} tidak ditemukan.\n"
        );

        exit(1);
    }

    if (
        file_put_contents(
            $viewFile,
            $updated
        ) === false
    ) {
        copy(
            $backup,
            $viewFile
        );

        fwrite(
            STDERR,
            "GAGAL menulis view {$mode}.\n"
        );

        exit(1);
    }

    echo "View {$mode} berhasil diperbarui.\n";
}

echo "\nDUA MODE INVENTARIS LOKASI BERHASIL DIPASANG.\n";
