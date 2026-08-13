<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$layouts = [
    $root.
        DIRECTORY_SEPARATOR.
        'resources'.
        DIRECTORY_SEPARATOR.
        'views'.
        DIRECTORY_SEPARATOR.
        'layouts'.
        DIRECTORY_SEPARATOR.
        'app.blade.php',

    $root.
        DIRECTORY_SEPARATOR.
        'resources'.
        DIRECTORY_SEPARATOR.
        'views'.
        DIRECTORY_SEPARATOR.
        'layouts'.
        DIRECTORY_SEPARATOR.
        'guest.blade.php',

    $root.
        DIRECTORY_SEPARATOR.
        'resources'.
        DIRECTORY_SEPARATOR.
        'views'.
        DIRECTORY_SEPARATOR.
        'layouts'.
        DIRECTORY_SEPARATOR.
        'auth.blade.php',
];

$faviconPartial =
    "@include('partials.simba-favicon')";

$cssLink = <<<'BLADE'
<link
    rel="stylesheet"
    href="{{ asset(
        'css/simba-brand.css'
    ) }}"
>
BLADE;

$updatedCount = 0;

foreach ($layouts as $file) {
    if (! is_file($file)) {
        continue;
    }

    $contents =
        file_get_contents($file);

    if ($contents === false) {
        fwrite(
            STDERR,
            "Gagal membaca {$file}.\n"
        );

        continue;
    }

    $changed = false;

    if (
        ! str_contains(
            $contents,
            $faviconPartial
        )
        && str_contains(
            $contents,
            '<head>'
        )
    ) {
        $contents = str_replace(
            '<head>',
            "<head>\n    {$faviconPartial}",
            $contents,
            $count
        );

        $changed =
            $changed
            || $count > 0;
    }

    if (
        ! str_contains(
            $contents,
            'css/simba-brand.css'
        )
        && str_contains(
            $contents,
            '</head>'
        )
    ) {
        $contents = str_replace(
            '</head>',
            "    {$cssLink}\n</head>",
            $contents,
            $count
        );

        $changed =
            $changed
            || $count > 0;
    }

    if (! $changed) {
        echo "Tidak berubah: {$file}\n";
        continue;
    }

    $backup =
        $file.
        '.before-simba-branding.'.
        date('YmdHis').
        '.bak';

    if (! copy($file, $backup)) {
        fwrite(
            STDERR,
            "Gagal membuat backup {$file}.\n"
        );

        continue;
    }

    if (
        file_put_contents(
            $file,
            $contents
        ) === false
    ) {
        copy(
            $backup,
            $file
        );

        fwrite(
            STDERR,
            "Gagal memperbarui {$file}.\n"
        );

        continue;
    }

    $updatedCount++;

    echo "Diperbarui: {$file}\n";
    echo "Backup    : {$backup}\n";
}

echo "\nLayout tambahan yang diperbarui: {$updatedCount}\n";
echo "Sidebar dan auth-modern sudah disediakan sebagai file overwrite.\n";
