<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$changedFiles = [];
$warnings = [];
$timestamp = date('YmdHis');

echo "SIMBA WAKA SARPRAS USER FORM HOTFIX\n";
echo "===================================\n\n";

/*
|--------------------------------------------------------------------------
| 1. Tambahkan option wakil_sarpras ke select role
|--------------------------------------------------------------------------
*/

$viewDirectory =
    $root.
    '/resources/views';

$viewFiles = [];

if (is_dir($viewDirectory)) {
    $iterator =
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $viewDirectory,
                FilesystemIterator::SKIP_DOTS
            )
        );

    foreach ($iterator as $file) {
        if (
            ! $file->isFile()
            || ! str_ends_with(
                $file->getFilename(),
                '.blade.php'
            )
        ) {
            continue;
        }

        $path =
            $file->getPathname();

        $contents =
            file_get_contents($path);

        if (
            ! is_string($contents)
            || (
                ! str_contains(
                    $contents,
                    'name="role"'
                )
                && ! str_contains(
                    $contents,
                    "name='role'"
                )
            )
        ) {
            continue;
        }

        $viewFiles[] =
            $path;
    }
}

foreach ($viewFiles as $viewFile) {
    $contents =
        file_get_contents(
            $viewFile
        );

    if (! is_string($contents)) {
        continue;
    }

    $searchPositions = [];

    foreach (
        [
            'name="role"',
            "name='role'",
        ]
        as $needle
    ) {
        $offset = 0;

        while (
            (
                $position =
                    strpos(
                        $contents,
                        $needle,
                        $offset
                    )
            ) !== false
        ) {
            $searchPositions[] =
                $position;

            $offset =
                $position
                + strlen($needle);
        }
    }

    rsort(
        $searchPositions
    );

    $updated =
        $contents;

    foreach ($searchPositions as $position) {
        $selectStart =
            strripos(
                substr(
                    $updated,
                    0,
                    $position
                ),
                '<select'
            );

        $selectEnd =
            stripos(
                $updated,
                '</select>',
                $position
            );

        if (
            $selectStart === false
            || $selectEnd === false
        ) {
            continue;
        }

        $segment =
            substr(
                $updated,
                $selectStart,
                $selectEnd
                + strlen('</select>')
                - $selectStart
            );

        if (
            str_contains(
                $segment,
                'value="wakil_sarpras"'
            )
            || str_contains(
                $segment,
                "value='wakil_sarpras'"
            )
        ) {
            continue;
        }

        $option = <<<'BLADE'

                    {{-- SIMBA WAKA SARPRAS ROLE OPTION --}}
                    <option
                        value="wakil_sarpras"
                        @selected(
                            old(
                                'role',
                                isset($user)
                                    ? $user->role
                                    : ''
                            ) === 'wakil_sarpras'
                        )
                    >
                        Wakil Sarana dan Prasarana
                    </option>
BLADE;

        $updated =
            substr(
                $updated,
                0,
                $selectEnd
            ).
            $option.
            "\n".
            substr(
                $updated,
                $selectEnd
            );
    }

    if ($updated === $contents) {
        continue;
    }

    $backup =
        $viewFile.
        '.before-waka-role-option.'.
        $timestamp.
        '.bak';

    if (! copy($viewFile, $backup)) {
        $warnings[] =
            "Gagal backup view {$viewFile}.";

        continue;
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

        $warnings[] =
            "Gagal menulis view {$viewFile}.";

        continue;
    }

    $changedFiles[] =
        $viewFile;
}

/*
|--------------------------------------------------------------------------
| 2. Tambahkan wakil_sarpras ke validasi/daftar role pengguna
|--------------------------------------------------------------------------
*/

$sourceDirectories = [
    $root.
        '/app/Http/Controllers',

    $root.
        '/app/Http/Requests',
];

$phpFiles = [];

foreach ($sourceDirectories as $directory) {
    if (! is_dir($directory)) {
        continue;
    }

    $iterator =
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            )
        );

    foreach ($iterator as $file) {
        if (
            ! $file->isFile()
            || $file->getExtension()
                !== 'php'
        ) {
            continue;
        }

        $filename =
            $file->getFilename();

        if (
            ! str_contains(
                $filename,
                'User'
            )
        ) {
            continue;
        }

        $phpFiles[] =
            $file->getPathname();
    }
}

foreach ($phpFiles as $phpFile) {
    $contents =
        file_get_contents(
            $phpFile
        );

    if (
        ! is_string($contents)
        || ! str_contains(
            strtolower($contents),
            'role'
        )
    ) {
        continue;
    }

    $updated =
        $contents;

    /*
     * Validator format:
     * in:admin,kepala_bengkel,toolman,guru,siswa
     */
    $updated =
        preg_replace_callback(
            '/in:([a-z0-9_,\-]+)/i',
            static function (
                array $matches
            ): string {
                $roles =
                    array_values(
                        array_filter(
                            array_map(
                                'trim',
                                explode(
                                    ',',
                                    $matches[1]
                                )
                            )
                        )
                    );

                $known =
                    array_intersect(
                        $roles,
                        [
                            'admin',
                            'kepala_bengkel',
                            'toolman',
                            'guru',
                            'siswa',
                        ]
                    );

                if (
                    count($known) < 2
                    || in_array(
                        'wakil_sarpras',
                        $roles,
                        true
                    )
                ) {
                    return $matches[0];
                }

                $roles[] =
                    'wakil_sarpras';

                return 'in:'.
                    implode(
                        ',',
                        array_values(
                            array_unique(
                                $roles
                            )
                        )
                    );
            },
            $updated
        ) ?? $updated;

    /*
     * Validator format:
     * Rule::in(['admin', ...])
     */
    $updated =
        preg_replace_callback(
            '/Rule::in\(\s*\[(.*?)\]\s*\)/s',
            static function (
                array $matches
            ): string {
                $body =
                    $matches[1];

                if (
                    str_contains(
                        $body,
                        'wakil_sarpras'
                    )
                ) {
                    return $matches[0];
                }

                $knownCount = 0;

                foreach (
                    [
                        'admin',
                        'kepala_bengkel',
                        'toolman',
                        'guru',
                        'siswa',
                    ]
                    as $role
                ) {
                    if (
                        str_contains(
                            $body,
                            "'{$role}'"
                        )
                        || str_contains(
                            $body,
                            "\"{$role}\""
                        )
                    ) {
                        $knownCount++;
                    }
                }

                if ($knownCount < 2) {
                    return $matches[0];
                }

                $trimmed =
                    rtrim(
                        $body
                    );

                $separator =
                    str_ends_with(
                        $trimmed,
                        ','
                    )
                        ? "\n"
                        : ",\n";

                return "Rule::in([\n".
                    $trimmed.
                    $separator.
                    "                'wakil_sarpras',\n".
                    "            ])";
            },
            $updated
        ) ?? $updated;

    /*
     * Daftar role hardcoded biasa.
     * Hanya diproses pada file Controller/Request pengguna.
     */
    if (
        ! str_contains(
            $updated,
            'wakil_sarpras'
        )
    ) {
        $knownCount = 0;

        foreach (
            [
                'admin',
                'kepala_bengkel',
                'toolman',
                'guru',
                'siswa',
            ]
            as $role
        ) {
            if (
                str_contains(
                    $updated,
                    "'{$role}'"
                )
                || str_contains(
                    $updated,
                    "\"{$role}\""
                )
            ) {
                $knownCount++;
            }
        }

        if ($knownCount >= 4) {
            $patterns = [
                "/('siswa'\s*,)/",
                '/("siswa"\s*,)/',
                "/('siswa'\s*\])/",
                '/("siswa"\s*\])/',
            ];

            $replacements = [
                "$1\n                'wakil_sarpras',",
                "$1\n                \"wakil_sarpras\",",
                "'siswa',\n                'wakil_sarpras'\n            ]",
                "\"siswa\",\n                \"wakil_sarpras\"\n            ]",
            ];

            foreach (
                $patterns
                as $index => $pattern
            ) {
                $candidate =
                    preg_replace(
                        $pattern,
                        $replacements[$index],
                        $updated,
                        1,
                        $count
                    );

                if (
                    is_string($candidate)
                    && $count === 1
                ) {
                    $updated =
                        $candidate;

                    break;
                }
            }
        }
    }

    if ($updated === $contents) {
        continue;
    }

    $backup =
        $phpFile.
        '.before-waka-role-validation.'.
        $timestamp.
        '.bak';

    if (! copy($phpFile, $backup)) {
        $warnings[] =
            "Gagal backup {$phpFile}.";

        continue;
    }

    if (
        file_put_contents(
            $phpFile,
            $updated
        ) === false
    ) {
        copy(
            $backup,
            $phpFile
        );

        $warnings[] =
            "Gagal menulis {$phpFile}.";

        continue;
    }

    $changedFiles[] =
        $phpFile;
}

/*
|--------------------------------------------------------------------------
| 3. Sinkronkan tabel roles bila dipakai aplikasi
|--------------------------------------------------------------------------
*/

$roleId = null;

if (Schema::hasTable('roles')) {
    $columns =
        Schema::getColumnListing(
            'roles'
        );

    $existingQuery =
        DB::table('roles');

    $searchColumns =
        array_values(
            array_intersect(
                [
                    'slug',
                    'key',
                    'code',
                    'name',
                    'label',
                    'display_name',
                ],
                $columns
            )
        );

    if ($searchColumns !== []) {
        $existingQuery->where(
            function (
                $query
            ) use (
                $searchColumns
            ): void {
                foreach (
                    $searchColumns
                    as $index => $column
                ) {
                    $values =
                        $column === 'code'
                            ? [
                                'WAKIL_SARPRAS',
                                'wakil_sarpras',
                            ]
                            : [
                                'wakil_sarpras',
                                'Wakil Sarana dan Prasarana',
                            ];

                    foreach ($values as $value) {
                        if (
                            $index === 0
                            && $value === $values[0]
                        ) {
                            $query->where(
                                $column,
                                $value
                            );
                        } else {
                            $query->orWhere(
                                $column,
                                $value
                            );
                        }
                    }
                }
            }
        );

        $existing =
            $existingQuery->first();

        if ($existing !== null) {
            $roleId =
                isset($existing->id)
                    ? (int)
                        $existing->id
                    : null;
        }
    }

    if ($roleId === null) {
        $data = [
            'name' =>
                in_array(
                    'slug',
                    $columns,
                    true
                )
                    ? 'Wakil Sarana dan Prasarana'
                    : 'wakil_sarpras',

            'slug' =>
                'wakil_sarpras',

            'key' =>
                'wakil_sarpras',

            'code' =>
                'WAKIL_SARPRAS',

            'label' =>
                'Wakil Sarana dan Prasarana',

            'display_name' =>
                'Wakil Sarana dan Prasarana',

            'description' =>
                'Pengawasan global read-only untuk laporan dan lokasi inventaris.',

            'is_active' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];

        $data =
            array_intersect_key(
                $data,
                array_flip(
                    $columns
                )
            );

        try {
            $roleId =
                (int)
                DB::table('roles')
                    ->insertGetId(
                        $data
                    );
        } catch (Throwable $exception) {
            $warnings[] =
                'Tabel roles tidak dapat disinkronkan otomatis: '.
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4. Sinkronkan akun Waka Sarpras
|--------------------------------------------------------------------------
*/

if (Schema::hasTable('users')) {
    $userColumns =
        array_flip(
            Schema::getColumnListing(
                'users'
            )
        );

    $updates = [
        'role' =>
            'wakil_sarpras',

        'workshop_id' =>
            null,

        'updated_at' =>
            now(),
    ];

    if (
        $roleId !== null
        && isset(
            $userColumns[
                'role_id'
            ]
        )
    ) {
        $updates['role_id'] =
            $roleId;
    }

    $updates =
        array_intersect_key(
            $updates,
            $userColumns
        );

    DB::table('users')
        ->where(
            'role',
            'wakil_sarpras'
        )
        ->orWhere(
            'username',
            'waka_sarpras'
        )
        ->update(
            $updates
        );
}

/*
|--------------------------------------------------------------------------
| 5. Syntax check file PHP yang diubah
|--------------------------------------------------------------------------
*/

$syntaxFailed = false;

foreach (
    array_values(
        array_unique(
            $changedFiles
        )
    )
    as $changedFile
) {
    if (
        ! str_ends_with(
            $changedFile,
            '.php'
        )
    ) {
        continue;
    }

    $output = [];

    exec(
        escapeshellarg(
            PHP_BINARY
        ).
        ' -l '.
        escapeshellarg(
            $changedFile
        ).
        ' 2>&1',
        $output,
        $status
    );

    if ($status !== 0) {
        echo "GAGAL SYNTAX: {$changedFile}\n";
        echo implode(
            "\n",
            $output
        ).
            "\n";

        $syntaxFailed =
            true;
    }
}

echo "\nFILE YANG DIPERBARUI\n";
echo "--------------------\n";

if ($changedFiles === []) {
    echo "Tidak ada file yang perlu diubah atau patch sudah terpasang.\n";
} else {
    foreach (
        array_values(
            array_unique(
                $changedFiles
            )
        )
        as $changedFile
    ) {
        echo $changedFile.
            PHP_EOL;
    }
}

if ($warnings !== []) {
    echo "\nPERINGATAN\n";
    echo "----------\n";

    foreach ($warnings as $warning) {
        echo "WARN: {$warning}\n";
    }
}

if ($syntaxFailed) {
    echo "\nHOTFIX GAGAL KARENA SYNTAX ERROR.\n";
    exit(1);
}

echo "\nWAKA SARPRAS USER FORM HOTFIX BERHASIL DIPASANG.\n";
echo "Jalankan artisan optimize:clear lalu checker.\n";
