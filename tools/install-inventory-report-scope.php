<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$webFile = $root.
    DIRECTORY_SEPARATOR.
    'routes'.
    DIRECTORY_SEPARATOR.
    'web.php';

if (! is_file($webFile)) {
    fwrite(
        STDERR,
        "routes/web.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents = file_get_contents(
    $webFile
);

if ($contents === false) {
    fwrite(
        STDERR,
        "Gagal membaca routes/web.php.\n"
    );

    exit(1);
}

$backup = $webFile.
    '.before-inventory-scope.'.
    date('YmdHis').
    '.bak';

if (! copy($webFile, $backup)) {
    fwrite(
        STDERR,
        "Gagal membuat backup web.php.\n"
    );

    exit(1);
}

$controller =
    '\\App\\Http\\Controllers\\ScopedInventoryReportController::class';

$replacements = [
    [
        'name' =>
            'reports.index',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports[\'"]\s*,\s*[\'"]reports\.index[\'"]\s*,\s*)(\$controllers\[[\'"]reports[\'"]\])(\s*,\s*[\'"]index[\'"])/s',

        'replacement' =>
            '$1'.$controller.'$3',
    ],
    [
        'name' =>
            'reports.inventory',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/inventory[\'"]\s*,\s*[\'"]reports\.inventory[\'"]\s*,\s*)(\$controllers\[[\'"]reports[\'"]\])(\s*,\s*\$inventoryReportMethod)/s',

        'replacement' =>
            '$1'.$controller.', \'inventory\'',
    ],
    [
        'name' =>
            'reports.export.pdf',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/export\/pdf[\'"]\s*,\s*[\'"]reports\.export\.pdf[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])(\s*,\s*[\'"]pdf[\'"])/s',

        'replacement' =>
            '$1'.$controller.'$3',
    ],
    [
        'name' =>
            'reports.export.excel',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/export\/excel[\'"]\s*,\s*[\'"]reports\.export\.excel[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])(\s*,\s*[\'"]excel[\'"])/s',

        'replacement' =>
            '$1'.$controller.'$3',
    ],
    [
        'name' =>
            'reports.inventory.pdf',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/inventory\/pdf[\'"]\s*,\s*[\'"]reports\.inventory\.pdf[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])(\s*,\s*[\'"]pdf[\'"])/s',

        'replacement' =>
            '$1'.$controller.'$3',
    ],
    [
        'name' =>
            'reports.inventory.excel',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/inventory\/excel[\'"]\s*,\s*[\'"]reports\.inventory\.excel[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])(\s*,\s*[\'"]excel[\'"])/s',

        'replacement' =>
            '$1'.$controller.'$3',
    ],
    [
        'name' =>
            'locations.inventory.pdf',

        'pattern' =>
            '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/locations\/\{location\}\/inventory\/pdf[\'"]\s*,\s*[\'"]locations\.inventory\.pdf[\'"]\s*,\s*)(\$controllers\[[\'"]locations[\'"]\])(\s*,\s*[\'"]inventoryPdf[\'"])/s',

        'replacement' =>
            '$1'.$controller.', \'locationPdf\'',
    ],
];

$changed = [];

foreach ($replacements as $definition) {
    $updated = preg_replace(
        $definition['pattern'],
        $definition['replacement'],
        $contents,
        1,
        $count
    );

    if (
        $updated === null
        || $count === 0
    ) {
        echo '[LEWATI] '.
            $definition['name'].
            " - pola tidak ditemukan atau sudah diperbaiki.\n";

        continue;
    }

    $contents = $updated;
    $changed[] =
        $definition['name'];
}

if ($changed === []) {
    echo "Tidak ada route yang diubah.\n";
    echo "Backup tetap tersedia: {$backup}\n";

    exit(0);
}

if (
    file_put_contents(
        $webFile,
        $contents
    ) === false
) {
    fwrite(
        STDERR,
        "Gagal menulis routes/web.php.\n"
    );

    exit(1);
}

echo "Route laporan inventaris berhasil diperbarui:\n";

foreach ($changed as $name) {
    echo "- {$name}\n";
}

echo "Backup: {$backup}\n";
