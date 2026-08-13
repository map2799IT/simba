<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;
$warnings = 0;

echo "SIMBA WAKA SARPRAS USER ROLE FORM CHECK\n";
echo "=======================================\n\n";

$result =
    static function (
        string $label,
        bool $valid,
        string $detail = ''
    ) use (
        &$failed
    ): void {
        echo str_pad(
            $label,
            54
        ).
            ': '.
            ($valid ? 'OK' : 'GAGAL').
            (
                $detail !== ''
                    ? ' '.$detail
                    : ''
            ).
            PHP_EOL;

        if (! $valid) {
            $failed = true;
        }
    };

echo "ROUTE\n";
echo "-----\n";

foreach (
    [
        'admin.users.index',
        'admin.users.edit',
        'admin.users.update',
    ]
    as $routeName
) {
    $route =
        Route::getRoutes()
            ->getByName(
                $routeName
            );

    $result(
        $routeName,
        $route !== null,
        $route?->getActionName()
        ?? ''
    );
}

echo "\nFORM ROLE\n";
echo "---------\n";

$viewDirectory =
    $root.
    '/resources/views';

$roleViews = [];

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

        $contents =
            file_get_contents(
                $file->getPathname()
            );

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

        $roleViews[] = [
            'path' =>
                $file->getPathname(),

            'has_waka' =>
                str_contains(
                    $contents,
                    'value="wakil_sarpras"'
                )
                || str_contains(
                    $contents,
                    "value='wakil_sarpras'"
                ),
        ];
    }
}

$result(
    'View dengan select role ditemukan',
    $roleViews !== [],
    'jumlah='.
        count($roleViews)
);

foreach ($roleViews as $view) {
    $relative =
        str_replace(
            $root.
            DIRECTORY_SEPARATOR,
            '',
            $view['path']
        );

    $result(
        $relative,
        $view['has_waka']
    );
}

echo "\nVALIDASI CONTROLLER/REQUEST\n";
echo "---------------------------\n";

$sourceDirectories = [
    $root.
        '/app/Http/Controllers',

    $root.
        '/app/Http/Requests',
];

$userFiles = [];

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
            || ! str_contains(
                $file->getFilename(),
                'User'
            )
        ) {
            continue;
        }

        $contents =
            file_get_contents(
                $file->getPathname()
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

        $knownCount = 0;

        foreach (
            [
                'admin',
                'kepala_bengkel',
                'toolman',
                'guru',
                'siswa',
            ]
            as $knownRole
        ) {
            if (
                str_contains(
                    $contents,
                    $knownRole
                )
            ) {
                $knownCount++;
            }
        }

        if ($knownCount < 2) {
            continue;
        }

        $userFiles[] = [
            'path' =>
                $file->getPathname(),

            'has_waka' =>
                str_contains(
                    $contents,
                    'wakil_sarpras'
                ),
        ];
    }
}

if ($userFiles === []) {
    echo "WARN: daftar role kemungkinan berasal dari database, bukan hardcoded.\n";
    $warnings++;
} else {
    foreach ($userFiles as $file) {
        $relative =
            str_replace(
                $root.
                DIRECTORY_SEPARATOR,
                '',
                $file['path']
            );

        $result(
            $relative,
            $file['has_waka']
        );
    }
}

echo "\nDATABASE ROLE\n";
echo "-------------\n";

$result(
    'users.role tersedia',
    Schema::hasTable('users')
    && Schema::hasColumn(
        'users',
        'role'
    )
);

if (Schema::hasTable('users')) {
    $wakaUsers =
        DB::table('users')
            ->where(
                'role',
                'wakil_sarpras'
            )
            ->orWhere(
                'username',
                'waka_sarpras'
            )
            ->get();

    $result(
        'Akun Waka ditemukan',
        $wakaUsers->isNotEmpty(),
        'jumlah='.
            $wakaUsers->count()
    );

    foreach ($wakaUsers as $user) {
        $result(
            'Role akun '.
                (
                    $user->username
                    ?? $user->id
                ),
            ($user->role ?? null)
                === 'wakil_sarpras'
        );

        if (
            Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            $result(
                'Workshop akun harus NULL',
                ($user->workshop_id ?? null)
                    === null
            );
        }
    }
}

if (Schema::hasTable('roles')) {
    $columns =
        Schema::getColumnListing(
            'roles'
        );

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

    $roleFound = false;
    $roleId = null;

    if ($searchColumns !== []) {
        $query =
            DB::table('roles');

        $query->where(
            function (
                $scope
            ) use (
                $searchColumns
            ): void {
                foreach (
                    $searchColumns
                    as $column
                ) {
                    foreach (
                        [
                            'wakil_sarpras',
                            'WAKIL_SARPRAS',
                            'Wakil Sarana dan Prasarana',
                        ]
                        as $value
                    ) {
                        $scope->orWhere(
                            $column,
                            $value
                        );
                    }
                }
            }
        );

        $role =
            $query->first();

        $roleFound =
            $role !== null;

        $roleId =
            isset($role->id)
                ? (int)
                    $role->id
                : null;
    }

    $result(
        'Record roles Waka Sarpras',
        $roleFound
    );

    if (
        $roleId !== null
        && Schema::hasColumn(
            'users',
            'role_id'
        )
    ) {
        $invalidRoleId =
            DB::table('users')
                ->where(
                    'role',
                    'wakil_sarpras'
                )
                ->where(
                    'role_id',
                    '!=',
                    $roleId
                )
                ->count();

        $result(
            'users.role_id sinkron',
            $invalidRoleId === 0,
            'invalid='.
                $invalidRoleId
        );
    }
} else {
    echo "INFO: aplikasi tidak menggunakan tabel roles.\n";
}

echo "\nSTATUS\n";
echo "------\n";
echo 'FAIL: '.
    ($failed ? 'YA' : 'TIDAK').
    PHP_EOL;
echo "WARN: {$warnings}\n";

echo "\n".
    (
        $failed
            ? 'WAKA SARPRAS USER ROLE FORM BELUM VALID.'
            : (
                $warnings > 0
                    ? 'FORM DAN AKUN VALID DENGAN PERINGATAN STRUKTUR DINAMIS.'
                    : 'WAKA SARPRAS USER ROLE FORM SUDAH VALID.'
            )
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
