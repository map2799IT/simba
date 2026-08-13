<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$layoutFile = $root.
    DIRECTORY_SEPARATOR.
    'resources'.
    DIRECTORY_SEPARATOR.
    'views'.
    DIRECTORY_SEPARATOR.
    'layouts'.
    DIRECTORY_SEPARATOR.
    'app.blade.php';

$sidebarFile = $root.
    DIRECTORY_SEPARATOR.
    'resources'.
    DIRECTORY_SEPARATOR.
    'views'.
    DIRECTORY_SEPARATOR.
    'layouts'.
    DIRECTORY_SEPARATOR.
    'sidebar.blade.php';

if (! is_file($layoutFile)) {
    fwrite(
        STDERR,
        "resources/views/layouts/app.blade.php tidak ditemukan.\n"
    );

    exit(1);
}

if (! is_file($sidebarFile)) {
    fwrite(
        STDERR,
        "resources/views/layouts/sidebar.blade.php tidak ditemukan.\n"
    );

    exit(1);
}

$layout = file_get_contents($layoutFile);

if ($layout === false) {
    fwrite(
        STDERR,
        "Gagal membaca layout app.blade.php.\n"
    );

    exit(1);
}

$include = "@include('layouts.sidebar')";

if (str_contains($layout, $include)) {
    echo "Sidebar partial sudah terpasang.\n";
    echo "File sidebar terbaru langsung digunakan.\n";

    exit(0);
}

$backup = $layoutFile.
    '.before-sidebar.'.
    date('YmdHis').
    '.bak';

if (! copy($layoutFile, $backup)) {
    fwrite(
        STDERR,
        "Gagal membuat backup layout.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Installer hanya mengganti elemen yang jelas merupakan sidebar.
|--------------------------------------------------------------------------
*/

$patterns = [
    '/<aside\b[^>]*(?:id|class)=["\'][^"\']*sidebar[^"\']*["\'][^>]*>.*?<\/aside>/is',

    '/<aside\b[^>]*>.*?(?:route\([\'"]dashboard[\'"]\)|\/dashboard).*?<\/aside>/is',

    '/<nav\b[^>]*(?:id|class)=["\'][^"\']*sidebar[^"\']*["\'][^>]*>.*?<\/nav>/is',

    '/<div\b[^>]*id=["\']sidebar["\'][^>]*>.*?<\/div>\s*<!--\s*\/?sidebar\s*-->/is',
];

$updated = null;

foreach ($patterns as $pattern) {
    $candidate = preg_replace(
        $pattern,
        $include,
        $layout,
        1,
        $count
    );

    if (
        $candidate !== null
        && $count === 1
    ) {
        $updated = $candidate;
        break;
    }
}

if ($updated === null) {
    @unlink($backup);

    fwrite(
        STDERR,
        "Sidebar lama tidak dapat dikenali secara aman.\n".
        "Layout tidak diubah.\n\n".
        "Hapus blok sidebar lama, kemudian pasang:\n".
        $include."\n"
    );

    exit(2);
}

if (
    file_put_contents(
        $layoutFile,
        $updated
    ) === false
) {
    copy($backup, $layoutFile);

    fwrite(
        STDERR,
        "Gagal menulis layout. File lama dipulihkan.\n"
    );

    exit(1);
}

echo "Sidebar berhasil diganti.\n";
echo "Backup: {$backup}\n";
echo "Include: {$include}\n";
