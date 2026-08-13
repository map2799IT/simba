<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIMBA Full Hosting Package Builder
|--------------------------------------------------------------------------
|
| Letakkan file ini di:
| C:\xampp\htdocs\simba\tools\build-full-hosting-package.php
|
| Jalankan:
| php tools\build-full-hosting-package.php
|
| Tambahkan --include-vendor bila hosting tidak memiliki Composer:
| php tools\build-full-hosting-package.php --include-vendor
|
*/

$root = dirname(__DIR__);
$includeVendor = in_array(
    '--include-vendor',
    $argv,
    true
);

$manifest =
    $root.
    DIRECTORY_SEPARATOR.
    'public'.
    DIRECTORY_SEPARATOR.
    'build'.
    DIRECTORY_SEPARATOR.
    'manifest.json';

if (! is_file($manifest)) {
    fwrite(
        STDERR,
        "GAGAL: public/build/manifest.json belum ada.\n".
        "Jalankan npm run build terlebih dahulu.\n"
    );

    exit(1);
}

if (! class_exists(ZipArchive::class)) {
    fwrite(
        STDERR,
        "GAGAL: ekstensi PHP zip belum aktif.\n"
    );

    exit(1);
}

$timestamp = date('Ymd-His');

$output =
    $root.
    DIRECTORY_SEPARATOR.
    "simba-full-hosting-{$timestamp}.zip";

$excludedTopLevel = [
    '.git',
    '.github',
    '.idea',
    '.vscode',
    'node_modules',
];

if (! $includeVendor) {
    $excludedTopLevel[] = 'vendor';
}

$excludedExact = [
    '.env',
    'public/hot',
    'public/storage',
    'storage/app/public',
];

$excludedPrefixes = [
    'storage/framework/',
    'storage/logs/',
    'bootstrap/cache/',
];

$shouldExclude =
    static function (
        string $relative
    ) use (
        $excludedTopLevel,
        $excludedExact,
        $excludedPrefixes,
        $output
    ): bool {
        $relative = str_replace(
            '\\',
            '/',
            $relative
        );

        $top =
            explode(
                '/',
                $relative,
                2
            )[0];

        if (
            in_array(
                $top,
                $excludedTopLevel,
                true
            )
        ) {
            return true;
        }

        if (
            in_array(
                $relative,
                $excludedExact,
                true
            )
        ) {
            return true;
        }

        foreach (
            $excludedPrefixes
            as $prefix
        ) {
            if (
                str_starts_with(
                    $relative,
                    $prefix
                )
            ) {
                return true;
            }
        }

        if (
            preg_match(
                '/^simba-full-hosting-\d{8}-\d{6}\.zip$/',
                basename($relative)
            ) === 1
        ) {
            return true;
        }

        return realpath(
            dirname($output)
        ).
            DIRECTORY_SEPARATOR.
            basename($output)
            === realpath(
                dirname($output)
            ).
            DIRECTORY_SEPARATOR.
            $relative;
    };

$zip = new ZipArchive();

$result =
    $zip->open(
        $output,
        ZipArchive::CREATE
        | ZipArchive::OVERWRITE
    );

if ($result !== true) {
    fwrite(
        STDERR,
        "GAGAL: ZIP tidak dapat dibuat. Kode {$result}\n"
    );

    exit(1);
}

$iterator =
    new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

$fileCount = 0;

foreach ($iterator as $file) {
    if (
        ! $file->isFile()
        || $file->isLink()
    ) {
        continue;
    }

    $absolute =
        $file->getPathname();

    $relative =
        substr(
            $absolute,
            strlen($root) + 1
        );

    $relative =
        str_replace(
            '\\',
            '/',
            $relative
        );

    if ($shouldExclude($relative)) {
        continue;
    }

    if (
        ! $zip->addFile(
            $absolute,
            $relative
        )
    ) {
        $zip->close();

        @unlink($output);

        fwrite(
            STDERR,
            "GAGAL menambahkan: {$relative}\n"
        );

        exit(1);
    }

    $fileCount++;
}

$deploymentInfo =
    "SIMBA FULL HOSTING PACKAGE\n".
    "Created: ".date(DATE_ATOM)."\n".
    "Vendor included: ".
    ($includeVendor ? 'yes' : 'no').
    "\n".
    "Files: {$fileCount}\n".
    "The hosting .env and uploaded files are intentionally excluded.\n";

$zip->addFromString(
    'DEPLOYMENT-PACKAGE.txt',
    $deploymentInfo
);

$zip->close();

echo "ZIP DEPLOYMENT BERHASIL DIBUAT.\n";
echo "File : {$output}\n";
echo "Jumlah file : {$fileCount}\n";
echo "Vendor : ".
    ($includeVendor ? 'disertakan' : 'tidak disertakan').
    "\n";
