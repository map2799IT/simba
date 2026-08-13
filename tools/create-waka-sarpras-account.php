<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$options =
    getopt(
        '',
        [
            'username::',
            'name::',
            'email::',
            'password::',
            'reset-password',
            'force',
        ]
    );

$username =
    trim(
        (string)
        (
            $options['username']
            ?? 'waka_sarpras'
        )
    );

$name =
    trim(
        (string)
        (
            $options['name']
            ?? 'Wakil Sarana dan Prasarana'
        )
    );

$email =
    trim(
        (string)
        (
            $options['email']
            ?? 'waka.sarpras@simba.local'
        )
    );

$passwordProvided =
    isset(
        $options['password']
    )
    && trim(
        (string)
        $options['password']
    ) !== '';

$password =
    $passwordProvided
        ? (string)
            $options['password']
        : Str::password(
            length: 18,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false
        );

$resetPassword =
    array_key_exists(
        'reset-password',
        $options
    );

$force =
    array_key_exists(
        'force',
        $options
    );

if (
    ! Schema::hasTable('users')
    || ! Schema::hasColumn(
        'users',
        'role'
    )
) {
    fwrite(
        STDERR,
        "GAGAL: tabel/kolom users.role tidak tersedia.\n"
    );

    exit(1);
}

if (! $force) {
    echo "BUAT/UPDATE AKUN WAKA SARPRAS\n";
    echo "==============================\n";
    echo "Username : {$username}\n";
    echo "Nama     : {$name}\n";
    echo "Email    : {$email}\n";
    echo "Role     : wakil_sarpras\n";
    echo "Jurusan  : NULL/global\n\n";
    echo "Ketik persis: BUAT WAKA SARPRAS\n";
    echo "> ";

    $confirmation =
        trim(
            (string)
            fgets(STDIN)
        );

    if (
        $confirmation
        !== 'BUAT WAKA SARPRAS'
    ) {
        echo "Dibatalkan. Tidak ada data diubah.\n";
        exit(1);
    }
}

$now =
    now()
        ->toDateTimeString();

$columns =
    array_flip(
        Schema::getColumnListing(
            'users'
        )
    );

$existing =
    DB::table('users')
        ->where(
            'username',
            $username
        )
        ->first();

$values = [
    'name' => $name,
    'username' => $username,
    'email' => $email,
    'role' => 'wakil_sarpras',
    'workshop_id' => null,
    'is_active' => true,
    'email_verified_at' => $now,
    'updated_at' => $now,
];

if (
    $existing === null
    || $resetPassword
) {
    $values['password'] =
        Hash::make(
            $password
        );
}

if ($existing === null) {
    $values['created_at'] =
        $now;
}

$values =
    array_intersect_key(
        $values,
        $columns
    );

/*
|--------------------------------------------------------------------------
| Registrasi pada tabel roles bila struktur mendukung
|--------------------------------------------------------------------------
*/

$roleId = null;

if (Schema::hasTable('roles')) {
    $roleColumns =
        Schema::getColumnListing(
            'roles'
        );

    $keyColumn = null;

    foreach (
        [
            'slug',
            'key',
            'code',
            'name',
        ]
        as $candidate
    ) {
        if (
            in_array(
                $candidate,
                $roleColumns,
                true
            )
        ) {
            $keyColumn =
                $candidate;

            break;
        }
    }

    if ($keyColumn !== null) {
        $keyValue =
            $keyColumn === 'code'
                ? 'WAKIL_SARPRAS'
                : 'wakil_sarpras';

        $roleRow =
            DB::table('roles')
                ->where(
                    $keyColumn,
                    $keyValue
                )
                ->first();

        if ($roleRow === null) {
            $roleValues = [
                'name' =>
                    $keyColumn === 'name'
                        ? 'wakil_sarpras'
                        : 'Wakil Sarana dan Prasarana',

                'slug' => 'wakil_sarpras',
                'key' => 'wakil_sarpras',
                'code' => 'WAKIL_SARPRAS',
                'label' => 'Wakil Sarana dan Prasarana',
                'description' =>
                    'Laporan global dan inventaris lokasi read-only.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $roleValues =
                array_intersect_key(
                    $roleValues,
                    array_flip(
                        $roleColumns
                    )
                );

            try {
                $roleId =
                    DB::table('roles')
                        ->insertGetId(
                            $roleValues
                        );
            } catch (Throwable $exception) {
                echo "WARN: tabel roles tidak dapat diisi otomatis: ".
                    $exception->getMessage().
                    "\n";
            }
        } else {
            $roleId =
                isset($roleRow->id)
                    ? (int)
                        $roleRow->id
                    : null;
        }
    }
}

if (
    $roleId !== null
    && isset($columns['role_id'])
) {
    $values['role_id'] =
        $roleId;
}

DB::transaction(
    function () use (
        $existing,
        $username,
        $values
    ): void {
        if ($existing === null) {
            DB::table('users')
                ->insert(
                    $values
                );

            return;
        }

        DB::table('users')
            ->where(
                'username',
                $username
            )
            ->update(
                $values
            );
    },
    attempts: 3
);

$user =
    DB::table('users')
        ->where(
            'username',
            $username
        )
        ->first();

echo "\nAKUN WAKA SARPRAS SIAP.\n";
echo "ID       : ".
    ($user->id ?? '-').
    "\n";
echo "Username : {$username}\n";
echo "Nama     : {$name}\n";
echo "Email    : {$email}\n";
echo "Role     : wakil_sarpras\n";
echo "Jurusan  : NULL/global\n";

if (
    $existing === null
    || $resetPassword
) {
    echo "Password : {$password}\n";
    echo "Simpan password tersebut sekarang.\n";
} else {
    echo "Password : tetap memakai password lama.\n";
}
