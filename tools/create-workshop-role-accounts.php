<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

if (PHP_SAPI !== 'cli') {
    fwrite(
        STDERR,
        "Perintah ini hanya boleh dijalankan dari Terminal.\n"
    );

    exit(1);
}

$options = getopt(
    '',
    [
        'reset-passwords',
        'force',
    ]
);

$resetPasswords =
    array_key_exists(
        'reset-passwords',
        $options
    );

$force =
    array_key_exists(
        'force',
        $options
    );

foreach (
    [
        'users',
        'workshops',
    ]
    as $table
) {
    if (! Schema::hasTable($table)) {
        fwrite(
            STDERR,
            "GAGAL: tabel {$table} tidak ditemukan.\n"
        );

        exit(1);
    }
}

$userColumns =
    Schema::getColumnListing(
        'users'
    );

foreach (
    [
        'name',
        'username',
        'email',
        'password',
        'role',
        'workshop_id',
    ]
    as $requiredColumn
) {
    if (
        ! in_array(
            $requiredColumn,
            $userColumns,
            true
        )
    ) {
        fwrite(
            STDERR,
            "GAGAL: kolom users.{$requiredColumn} tidak tersedia.\n"
        );

        exit(1);
    }
}

$workshops =
    DB::table('workshops')
        ->orderBy('code')
        ->get();

if ($workshops->isEmpty()) {
    fwrite(
        STDERR,
        "GAGAL: belum ada data jurusan pada tabel workshops.\n"
    );

    exit(1);
}

echo "BUAT AKUN KEPALA BENGKEL & TOOLMAN\n";
echo "===================================\n\n";

echo "Jumlah jurusan : ".
    $workshops->count().
    "\n";

echo "Akun yang diproses: ".
    (
        $workshops->count()
        * 2
    ).
    "\n";

echo "Password akun baru dibuat otomatis berdasarkan kode jurusan.\n";

if ($resetPasswords) {
    echo "Password akun lama yang cocok juga akan direset.\n";
} else {
    echo "Password akun lama yang sudah ada tetap dipertahankan.\n";
}

echo "\nFormat akun:\n";
echo "- kabeng_<kode>\n";
echo "- toolman_<kode>\n\n";

if (! $force) {
    echo "Ketik persis: BUAT AKUN JURUSAN\n";
    echo "> ";

    $confirmation =
        trim(
            (string) fgets(STDIN)
        );

    if (
        $confirmation
        !== 'BUAT AKUN JURUSAN'
    ) {
        echo "Dibatalkan. Tidak ada akun yang diubah.\n";
        exit(1);
    }
}

$now =
    now()->toDateTimeString();

$created = 0;
$updated = 0;
$rows = [];

$filterColumns =
    static function (
        string $table,
        array $values
    ): array {
        $columns =
            array_flip(
                Schema::getColumnListing(
                    $table
                )
            );

        return array_intersect_key(
            $values,
            $columns
        );
    };

$roleId =
    static function (
        string $role
    ): ?int {
        if (! Schema::hasTable('roles')) {
            return null;
        }

        $columns =
            Schema::getColumnListing(
                'roles'
            );

        foreach (
            [
                'name',
                'slug',
                'key',
                'code',
            ]
            as $column
        ) {
            if (
                ! in_array(
                    $column,
                    $columns,
                    true
                )
            ) {
                continue;
            }

            $searchValue =
                $column === 'code'
                    ? strtoupper($role)
                    : $role;

            $id =
                DB::table('roles')
                    ->where(
                        $column,
                        $searchValue
                    )
                    ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    };

$assignHead =
    static function (
        int $workshopId,
        int $headUserId,
        string $now
    ) use (
        $filterColumns
    ): void {
        $values =
            $filterColumns(
                'workshops',
                [
                    'manager_id' =>
                        $headUserId,

                    'head_id' =>
                        $headUserId,

                    'head_user_id' =>
                        $headUserId,

                    'responsible_user_id' =>
                        $headUserId,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($values === []) {
            return;
        }

        DB::table('workshops')
            ->where(
                'id',
                $workshopId
            )
            ->update($values);
    };

DB::transaction(
    function () use (
        $workshops,
        $resetPasswords,
        $now,
        $filterColumns,
        $roleId,
        $assignHead,
        &$created,
        &$updated,
        &$rows
    ): void {
        foreach (
            $workshops
            as $workshop
        ) {
            $code =
                strtoupper(
                    trim(
                        (string)
                        $workshop->code
                    )
                );

            $slug =
                strtolower(
                    trim(
                        preg_replace(
                            '/[^a-z0-9]+/i',
                            '_',
                            $code
                        ),
                        '_'
                    )
                );

            if ($slug === '') {
                $slug =
                    'jurusan_'.
                    $workshop->id;
            }

            $safePasswordCode =
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $code
                )
                ?: (string)
                    $workshop->id;

            $accounts = [
                [
                    'role' =>
                        'kepala_bengkel',

                    'name' =>
                        'Kepala Bengkel '.
                        $code,

                    'username' =>
                        'kabeng_'.
                        $slug,

                    'email' =>
                        'kabeng.'.
                        $slug.
                        '@simba.local',

                    'password' =>
                        'Kabeng-'.
                        $safePasswordCode.
                        '-2026!',
                ],

                [
                    'role' =>
                        'toolman',

                    'name' =>
                        'Toolman '.
                        $code,

                    'username' =>
                        'toolman_'.
                        $slug,

                    'email' =>
                        'toolman.'.
                        $slug.
                        '@simba.local',

                    'password' =>
                        'Toolman-'.
                        $safePasswordCode.
                        '-2026!',
                ],
            ];

            $headUserId = null;

            foreach (
                $accounts
                as $account
            ) {
                $existing =
                    DB::table('users')
                        ->where(
                            'username',
                            $account[
                                'username'
                            ]
                        )
                        ->orWhere(
                            'email',
                            $account[
                                'email'
                            ]
                        )
                        ->first();

                $values =
                    [
                        'name' =>
                            $account['name'],

                        'username' =>
                            $account[
                                'username'
                            ],

                        'email' =>
                            $account['email'],

                        'role' =>
                            $account['role'],

                        'role_id' =>
                            $roleId(
                                $account['role']
                            ),

                        'workshop_id' =>
                            (int)
                            $workshop->id,

                        'email_verified_at' =>
                            $now,

                        'is_active' =>
                            (bool)
                            (
                                $workshop
                                    ->is_active
                                ?? true
                            ),

                        'updated_at' =>
                            $now,
                    ];

                if (
                    $existing === null
                    || $resetPasswords
                ) {
                    $values[
                        'password'
                    ] =
                        Hash::make(
                            $account[
                                'password'
                            ]
                        );
                }

                $values =
                    $filterColumns(
                        'users',
                        $values
                    );

                if ($existing === null) {
                    $insertValues =
                        $filterColumns(
                            'users',
                            array_merge(
                                $values,
                                [
                                    'created_at' =>
                                        $now,
                                ]
                            )
                        );

                    $userId =
                        (int)
                        DB::table('users')
                            ->insertGetId(
                                $insertValues
                            );

                    $created++;
                    $status =
                        'DIBUAT';
                } else {
                    $userId =
                        (int)
                        $existing->id;

                    DB::table('users')
                        ->where(
                            'id',
                            $userId
                        )
                        ->update($values);

                    $updated++;
                    $status =
                        $resetPasswords
                            ? 'DIPERBARUI + PASSWORD RESET'
                            : 'DIPERBARUI';
                }

                if (
                    $account['role']
                    === 'kepala_bengkel'
                ) {
                    $headUserId =
                        $userId;
                }

                $rows[] = [
                    'workshop_code' =>
                        $code,

                    'workshop_name' =>
                        (string)
                        $workshop->name,

                    'role' =>
                        $account['role'],

                    'username' =>
                        $account[
                            'username'
                        ],

                    'email' =>
                        $account['email'],

                    'password' =>
                        $existing === null
                        || $resetPasswords
                            ? $account[
                                'password'
                            ]
                            : '(password lama dipertahankan)',

                    'status' =>
                        $status,
                ];
            }

            if ($headUserId !== null) {
                $assignHead(
                    (int)
                    $workshop->id,
                    $headUserId,
                    $now
                );
            }
        }
    },
    attempts: 3
);

$csv =
    "kode_jurusan,nama_jurusan,role,username,email,password,status\n";

foreach ($rows as $row) {
    $csv .= implode(
        ',',
        array_map(
            static fn (
                mixed $value
            ): string =>
                '"'.
                str_replace(
                    '"',
                    '""',
                    (string)
                    $value
                ).
                '"',
            [
                $row[
                    'workshop_code'
                ],

                $row[
                    'workshop_name'
                ],

                $row['role'],
                $row['username'],
                $row['email'],
                $row['password'],
                $row['status'],
            ]
        )
    ).
    "\n";
}

$csvName =
    'workshop-role-accounts-'.
    now()->format(
        'Ymd-His'
    ).
    '.csv';

Storage::disk('local')
    ->put(
        $csvName,
        $csv
    );

echo "\nHASIL PEMBUATAN AKUN\n";
echo "--------------------\n";
echo "Dibuat      : {$created}\n";
echo "Diperbarui  : {$updated}\n\n";

foreach ($rows as $row) {
    echo str_pad(
        $row['workshop_code'],
        10
    ).

    str_pad(
        $row['role'],
        18
    ).

    str_pad(
        $row['username'],
        25
    ).

    $row['password'].
    "\n";
}

echo "\nFile akun:\n";
echo "storage/app/{$csvName}\n";
echo "\nAKUN KEPALA BENGKEL DAN TOOLMAN BERHASIL DIPROSES.\n";
