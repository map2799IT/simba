<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$file =
    $root.
    '/tools/check-simba-all-features.php';

if (! is_file($file)) {
    echo "Tool diagnostik belum tersedia. Patch dilewati.\n";
    exit(0);
}

$contents = file_get_contents($file);

if (! is_string($contents)) {
    fwrite(STDERR, "GAGAL membaca tool diagnostik.\n");
    exit(1);
}

if (
    str_contains(
        $contents,
        'SIMBA ROUTE REFERENCE PARSER V2'
    )
) {
    echo "Parser route diagnostik V2 sudah terpasang.\n";
    exit(0);
}

$backup =
    $file.
    '.before-route-parser-v2.'.
    date('YmdHis').
    '.bak';

if (! copy($file, $backup)) {
    fwrite(STDERR, "GAGAL membuat backup diagnostik.\n");
    exit(1);
}

/*
 * Parser lama mencampur:
 *
 * - route('nama.route')              -> nama route;
 * - request()->route('user')         -> parameter route;
 * - request()->routeIs('items.*')    -> pola menu aktif.
 *
 * Hanya dua bentuk pertama yang benar-benar perlu dicek:
 *
 * - helper route();
 * - Route::has().
 *
 * request()->route() dan routeIs() tidak boleh dianggap nama route.
 */
$oldPattern = <<<'OLD'
    preg_match_all(
        '/\b(?:route|Route::has|routeIs)\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        $contents,
        $matches
    );
OLD;

$newPattern = <<<'NEW'
    /* SIMBA ROUTE REFERENCE PARSER V2 */
    preg_match_all(
        '/(?<!->)(?<!::)\broute\s*\(\s*[\'"]([^\'"]+)[\'"]|Route::has\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        $contents,
        $matches
    );

    $resolvedMatches = array_values(
        array_filter(
            array_merge(
                $matches[1] ?? [],
                $matches[2] ?? []
            ),
            static fn (mixed $value): bool =>
                is_string($value)
                && $value !== ''
                && ! str_contains($value, '*')
        )
    );
NEW;

if (! str_contains($contents, $oldPattern)) {
    fwrite(
        STDERR,
        "GAGAL: blok parser lama tidak ditemukan. File tidak diubah.\n"
    );
    exit(1);
}

$contents = str_replace(
    $oldPattern,
    $newPattern,
    $contents,
    $replaced
);

$contents = preg_replace(
    '/foreach\s*\(\s*\$matches\[1\]\s*\?\?\s*\[\]\s*as\s*\$routeReference\s*\)/',
    'foreach ($resolvedMatches as $routeReference)',
    $contents,
    -1,
    $foreachReplaced
);

if (
    $replaced !== 1
    || $foreachReplaced < 1
) {
    copy($backup, $file);

    fwrite(
        STDERR,
        "GAGAL memasang parser V2. File lama dikembalikan.\n"
    );
    exit(1);
}

if (file_put_contents($file, $contents) === false) {
    copy($backup, $file);
    fwrite(STDERR, "GAGAL menulis tool diagnostik.\n");
    exit(1);
}

exec(
    escapeshellarg(PHP_BINARY).
    ' -l '.
    escapeshellarg($file).
    ' 2>&1',
    $output,
    $status
);

if ($status !== 0) {
    copy($backup, $file);

    fwrite(
        STDERR,
        "GAGAL: syntax tool diagnostik tidak valid.\n".
        implode("\n", $output).
        "\n"
    );

    exit(1);
}

echo "PARSER ROUTE DIAGNOSTIK V2 BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
