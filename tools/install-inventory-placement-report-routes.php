<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$routeFiles = glob(
    $root.'/routes/*.php'
) ?: [];

$target = null;
$contents = null;

foreach ($routeFiles as $file) {
    $candidate =
        file_get_contents($file);

    if (
        is_string($candidate)
        && str_contains(
            $candidate,
            "'reports.index'"
        )
        && str_contains(
            $candidate,
            '$safeRoute'
        )
    ) {
        $target = $file;
        $contents = $candidate;
        break;
    }
}

if (
    $target === null
    || ! is_string($contents)
) {
    fwrite(
        STDERR,
        "GAGAL: file route laporan tidak ditemukan.\n"
    );

    exit(1);
}

$backup =
    $target.
    '.before-placement-report.'.
    date('YmdHis').
    '.bak';

if (! copy($target, $backup)) {
    fwrite(
        STDERR,
        "GAGAL membuat backup route.\n"
    );

    exit(1);
}

$reportController =
    '\\App\\Http\\Controllers\\WorkshopAwareInventoryReportController::class';

$exportController =
    '\\App\\Http\\Controllers\\WorkshopAwareInventoryReportExportController::class';

$patterns = [
    [
        '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports[\'"]\s*,\s*[\'"]reports\.index[\'"]\s*,\s*)(\$controllers\[[\'"]reports[\'"]\])/',
        $reportController,
    ],

    [
        '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/inventory[\'"]\s*,\s*[\'"]reports\.inventory[\'"]\s*,\s*)(\$controllers\[[\'"]reports[\'"]\])/',
        $reportController,
    ],

    [
        '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/export\/pdf[\'"]\s*,\s*[\'"]reports\.export\.pdf[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])/',
        $exportController,
    ],

    [
        '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/export\/excel[\'"]\s*,\s*[\'"]reports\.export\.excel[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])/',
        $exportController,
    ],

    [
        '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/inventory\/pdf[\'"]\s*,\s*[\'"]reports\.inventory\.pdf[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])/',
        $exportController,
    ],

    [
        '/(\$safeRoute\(\s*[\'"]get[\'"]\s*,\s*[\'"]\/reports\/inventory\/excel[\'"]\s*,\s*[\'"]reports\.inventory\.excel[\'"]\s*,\s*)(\$controllers\[[\'"]inventoryExports[\'"]\])/',
        $exportController,
    ],
];

$changed = 0;

foreach ($patterns as [$pattern, $controllerClass]) {
    $contents = preg_replace_callback(
        $pattern,
        static fn (array $matches): string =>
            $matches[1].$controllerClass,
        $contents,
        -1,
        $count
    );

    $changed += $count;
}

if ($changed < 2) {
    copy($backup, $target);

    fwrite(
        STDERR,
        "GAGAL: blok route inventaris tidak cocok. File lama dikembalikan.\n"
    );

    exit(1);
}

if (
    file_put_contents(
        $target,
        $contents
    ) === false
) {
    copy($backup, $target);

    fwrite(
        STDERR,
        "GAGAL menulis file route.\n"
    );

    exit(1);
}

exec(
    escapeshellarg(PHP_BINARY).
    ' -l '.
    escapeshellarg($target).
    ' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy($backup, $target);

    fwrite(
        STDERR,
        "GAGAL: syntax route tidak valid.\n".
        implode("\n", $output).
        "\n"
    );

    exit(1);
}

echo "ROUTE LAPORAN INVENTARIS BERHASIL DIPERBARUI.\n";
echo "File   : {$target}\n";
echo "Backup : {$backup}\n";
echo "Blok   : {$changed}\n";
