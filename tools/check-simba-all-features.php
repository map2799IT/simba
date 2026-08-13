<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Filesystem\Filesystem;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

if (PHP_SAPI !== 'cli') {
    fwrite(
        STDERR,
        "Tool ini hanya boleh dijalankan melalui Terminal.\n"
    );

    exit(1);
}

$options = getopt(
    '',
    [
        'http',
        'all-roles',
        'existing-checkers',
        'json::',
        'max-routes::',
        'help',
    ]
);

if (array_key_exists('help', $options)) {
    echo <<<'HELP'
SIMBA FULL FEATURE & MENU DIAGNOSTIC

Pemakaian:

php tools/check-simba-all-features.php

php tools/check-simba-all-features.php \
    --existing-checkers

php tools/check-simba-all-features.php \
    --http

php tools/check-simba-all-features.php \
    --http \
    --all-roles \
    --existing-checkers

Opsi:

--http
    Menjalankan smoke test GET terhadap menu dan halaman statis.

--all-roles
    Smoke test dijalankan memakai satu user dari setiap role.
    Tanpa opsi ini, smoke test memakai administrator.

--existing-checkers
    Menjalankan semua tools/check-*.php lain yang sudah tersedia.

--json=/path/report.json
    Menentukan lokasi laporan JSON.

--max-routes=150
    Batas jumlah route smoke test per role.

HELP;

    exit(0);
}

$runHttp =
    array_key_exists(
        'http',
        $options
    );

$allRoles =
    array_key_exists(
        'all-roles',
        $options
    );

$runExistingCheckers =
    array_key_exists(
        'existing-checkers',
        $options
    );

$maxRoutes =
    max(
        1,
        min(
            500,
            (int) (
                $options['max-routes']
                ?? 150
            )
        )
    );

$reportPath =
    isset($options['json'])
    && is_string($options['json'])
    && trim($options['json']) !== ''
        ? $options['json']
        : $root.
            '/storage/app/simba-diagnostics/'.
            date('Ymd-His').
            '-full-feature-menu-report.json';

$results = [];

$counts = [
    'PASS' => 0,
    'WARN' => 0,
    'FAIL' => 0,
    'SKIP' => 0,
];

$section = '';

$statusRank = [
    'PASS' => 0,
    'SKIP' => 1,
    'WARN' => 2,
    'FAIL' => 3,
];

$beginSection =
    static function (
        string $title
    ) use (
        &$section
    ): void {
        $section = $title;

        echo "\n".
            strtoupper($title).
            "\n";

        echo str_repeat(
            '=',
            max(
                12,
                strlen($title)
            )
        ).
            "\n";
    };

$record =
    static function (
        string $label,
        string $status,
        mixed $detail = null,
        array $meta = []
    ) use (
        &$results,
        &$counts,
        &$section
    ): void {
        $status =
            strtoupper($status);

        if (
            ! array_key_exists(
                $status,
                $counts
            )
        ) {
            $status = 'WARN';
        }

        $counts[$status]++;

        $detailText =
            match (true) {
                $detail === null =>
                    '',

                is_scalar($detail) =>
                    trim(
                        (string) $detail
                    ),

                default =>
                    json_encode(
                        $detail,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    )
                    ?: '',
            };

        echo str_pad(
            $label,
            54
        ).
            ': '.
            str_pad(
                $status,
                5
            );

        if ($detailText !== '') {
            echo ' '.
                $detailText;
        }

        echo PHP_EOL;

        $results[] = [
            'section' =>
                $section,

            'label' =>
                $label,

            'status' =>
                $status,

            'detail' =>
                $detail,

            'meta' =>
                $meta,
        ];
    };

$runProcess =
    static function (
        array $command,
        ?string $cwd = null,
        int $timeoutSeconds = 120
    ): array {
        if (
            ! function_exists(
                'proc_open'
            )
        ) {
            return [
                'started' => false,
                'timed_out' => false,
                'exit_code' => null,
                'stdout' => '',
                'stderr' =>
                    'proc_open tidak tersedia.',
            ];
        }

        $commandLine =
            implode(
                ' ',
                array_map(
                    static fn (
                        string $argument
                    ): string =>
                        escapeshellarg(
                            $argument
                        ),
                    $command
                )
            );

        $descriptors = [
            0 => [
                'pipe',
                'r',
            ],

            1 => [
                'pipe',
                'w',
            ],

            2 => [
                'pipe',
                'w',
            ],
        ];

        $process =
            @proc_open(
                $commandLine,
                $descriptors,
                $pipes,
                $cwd
            );

        if (! is_resource($process)) {
            return [
                'started' => false,
                'timed_out' => false,
                'exit_code' => null,
                'stdout' => '',
                'stderr' =>
                    'Process tidak dapat dijalankan.',
            ];
        }

        fclose($pipes[0]);

        stream_set_blocking(
            $pipes[1],
            false
        );

        stream_set_blocking(
            $pipes[2],
            false
        );

        $stdout = '';
        $stderr = '';
        $startedAt = microtime(true);
        $timedOut = false;
        $lastStatus = null;

        while (true) {
            $stdout .=
                stream_get_contents(
                    $pipes[1]
                )
                ?: '';

            $stderr .=
                stream_get_contents(
                    $pipes[2]
                )
                ?: '';

            $lastStatus =
                proc_get_status(
                    $process
                );

            if (
                ! ($lastStatus['running']
                    ?? false)
            ) {
                break;
            }

            if (
                microtime(true)
                - $startedAt
                > $timeoutSeconds
            ) {
                $timedOut = true;

                proc_terminate(
                    $process,
                    9
                );

                break;
            }

            usleep(100000);
        }

        $stdout .=
            stream_get_contents(
                $pipes[1]
            )
            ?: '';

        $stderr .=
            stream_get_contents(
                $pipes[2]
            )
            ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode =
            proc_close(
                $process
            );

        if (
            $exitCode === -1
            && is_array($lastStatus)
            && isset(
                $lastStatus['exitcode']
            )
            && $lastStatus['exitcode']
                >= 0
        ) {
            $exitCode =
                $lastStatus[
                    'exitcode'
                ];
        }

        return [
            'started' => true,
            'timed_out' =>
                $timedOut,

            'exit_code' =>
                $exitCode,

            'stdout' =>
                trim($stdout),

            'stderr' =>
                trim($stderr),
        ];
    };

$relativePath =
    static function (
        string $path
    ) use (
        $root
    ): string {
        $normalizedRoot =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $root
                ),
                '/'
            );

        $normalizedPath =
            str_replace(
                '\\',
                '/',
                $path
            );

        if (
            str_starts_with(
                $normalizedPath,
                $normalizedRoot.'/'
            )
        ) {
            return substr(
                $normalizedPath,
                strlen(
                    $normalizedRoot
                )
                + 1
            );
        }

        return $normalizedPath;
    };

$recursiveFiles =
    static function (
        string $directory,
        ?string $suffix = null
    ): array {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    FilesystemIterator::
                        SKIP_DOTS
                )
            );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path =
                $file->getPathname();

            if (
                $suffix !== null
                && ! str_ends_with(
                    $path,
                    $suffix
                )
            ) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    };

echo "SIMBA FULL FEATURE & MENU DIAGNOSTIC\n";
echo "====================================\n";
echo "Waktu     : ".
    date('Y-m-d H:i:s').
    "\n";
echo "Project   : {$root}\n";
echo "PHP       : ".
    PHP_VERSION.
    "\n";
echo "Laravel   : ".
    app()->version().
    "\n";
echo "HTTP test : ".
    (
        $runHttp
            ? 'AKTIF'
            : 'NONAKTIF'
    ).
    "\n";

$beginSection(
    'Environment dan konfigurasi'
);

$record(
    'PHP minimal 8.2',
    version_compare(
        PHP_VERSION,
        '8.2.0',
        '>='
    )
        ? 'PASS'
        : 'FAIL',
    PHP_VERSION
);

$record(
    'Laravel berhasil boot',
    'PASS',
    app()->version()
);

$appKey =
    (string)
    config('app.key');

$record(
    'APP_KEY tersedia',
    $appKey !== ''
        ? 'PASS'
        : 'FAIL',
    $appKey !== ''
        ? 'terpasang'
        : 'kosong'
);

$appEnvironment =
    (string)
    app()->environment();

$record(
    'APP_ENV',
    $appEnvironment === 'production'
        ? 'PASS'
        : 'WARN',
    $appEnvironment
);

$appDebug =
    (bool)
    config('app.debug');

$record(
    'APP_DEBUG',
    ! $appDebug
        ? 'PASS'
        : 'WARN',
    $appDebug
        ? 'true'
        : 'false'
);

$appUrl =
    (string)
    config('app.url');

$record(
    'APP_URL',
    filter_var(
        $appUrl,
        FILTER_VALIDATE_URL
    )
        ? 'PASS'
        : 'WARN',
    $appUrl
);

$timezone =
    (string)
    config('app.timezone');

$record(
    'Timezone aplikasi',
    $timezone !== ''
        ? 'PASS'
        : 'WARN',
    $timezone
);

foreach (
    [
        'openssl',
        'pdo',
        'pdo_mysql',
        'mbstring',
        'tokenizer',
        'xml',
        'ctype',
        'json',
        'fileinfo',
    ]
    as $extension
) {
    $record(
        "Ekstensi PHP {$extension}",
        extension_loaded(
            $extension
        )
            ? 'PASS'
            : 'FAIL'
    );
}

foreach (
    [
        'gd',
        'imagick',
        'zip',
    ]
    as $extension
) {
    $record(
        "Ekstensi opsional {$extension}",
        extension_loaded(
            $extension
        )
            ? 'PASS'
            : 'WARN'
    );
}

$beginSection(
    'Filesystem dan aset publik'
);

$writablePaths = [
    'storage' =>
        $root.
        '/storage',

    'storage/framework' =>
        $root.
        '/storage/framework',

    'storage/framework/cache' =>
        $root.
        '/storage/framework/cache',

    'storage/framework/sessions' =>
        $root.
        '/storage/framework/sessions',

    'storage/framework/views' =>
        $root.
        '/storage/framework/views',

    'storage/logs' =>
        $root.
        '/storage/logs',

    'bootstrap/cache' =>
        $root.
        '/bootstrap/cache',
];

foreach (
    $writablePaths
    as $label => $path
) {
    if (! is_dir($path)) {
        @mkdir(
            $path,
            0775,
            true
        );
    }

    $record(
        "{$label} tersedia dan writable",
        is_dir($path)
        && is_writable($path)
            ? 'PASS'
            : 'FAIL',
        $relativePath($path)
    );
}

$manifestPaths = [
    'Manifest project' =>
        $root.
        '/public/build/manifest.json',
];

$appHost =
    parse_url(
        $appUrl,
        PHP_URL_HOST
    );

$home =
    getenv('HOME')
    ?: dirname($root);

if (
    is_string($appHost)
    && $appHost !== ''
) {
    $manifestPaths[
        'Manifest document root hosting'
    ] =
        rtrim(
            $home,
            '/'
        ).
        '/public_html/'.
        $appHost.
        '/build/manifest.json';
}

foreach (
    $manifestPaths
    as $label => $path
) {
    $record(
        $label,
        is_file($path)
            ? 'PASS'
            : 'WARN',
        $path
    );
}

$publicStorage =
    $root.
    '/public/storage';

$storageTarget =
    $root.
    '/storage/app/public';

$storageLinkValid =
    is_link(
        $publicStorage
    )
        ? realpath(
            $publicStorage
        )
            === realpath(
                $storageTarget
            )
        : is_dir(
            $publicStorage
        );

$record(
    'Public storage link/directory',
    $storageLinkValid
        ? 'PASS'
        : 'WARN',
    $publicStorage
);

$brandingFiles = [
    $root.
        '/public/branding',

    $root.
        '/public/favicon.ico',
];

$brandingFound =
    false;

foreach ($brandingFiles as $path) {
    if (
        is_dir($path)
        || is_file($path)
    ) {
        $brandingFound = true;
        break;
    }
}

$record(
    'Logo atau favicon tersedia',
    $brandingFound
        ? 'PASS'
        : 'WARN'
);

$beginSection(
    'Database, migration, dan struktur'
);

try {
    DB::select(
        'SELECT 1 AS diagnostic_ok'
    );

    $record(
        'Koneksi database',
        'PASS',
        DB::connection()
            ->getDatabaseName()
    );
} catch (Throwable $exception) {
    $record(
        'Koneksi database',
        'FAIL',
        $exception->getMessage()
    );
}

try {
    $driver =
        DB::connection()
            ->getDriverName();

    $record(
        'Driver database',
        $driver === 'mysql'
            ? 'PASS'
            : 'WARN',
        $driver
    );
} catch (Throwable $exception) {
    $record(
        'Driver database',
        'FAIL',
        $exception->getMessage()
    );
}

try {
    $migrator =
        app('migrator');

    if (
        ! $migrator
            ->repositoryExists()
    ) {
        $record(
            'Repository migration',
            'FAIL',
            'tabel migrations tidak tersedia'
        );
    } else {
        $migrationFiles =
            $migrator
                ->getMigrationFiles([
                    $root.
                    '/database/migrations',
                ]);

        $ran =
            $migrator
                ->getRepository()
                ->getRan();

        $pending =
            array_values(
                array_diff(
                    array_keys(
                        $migrationFiles
                    ),
                    $ran
                )
            );

        $record(
            'Repository migration',
            'PASS',
            count($ran).
            ' migration sudah dijalankan'
        );

        $record(
            'Pending migration',
            $pending === []
                ? 'PASS'
                : 'WARN',
            $pending === []
                ? 'tidak ada'
                : $pending
        );
    }
} catch (Throwable $exception) {
    $record(
        'Status migration',
        'WARN',
        $exception->getMessage()
    );
}

$criticalTables = [
    'users',
    'workshops',
    'items',
    'item_categories',
    'units',
    'storage_locations',
    'item_assets',
    'item_stock_movements',
    'loans',
    'loan_items',
    'damage_reports',
    'sessions',
];

foreach (
    $criticalTables
    as $table
) {
    $record(
        "Tabel {$table}",
        Schema::hasTable(
            $table
        )
            ? 'PASS'
            : 'WARN'
    );
}

$criticalColumns = [
    'users' => [
        'id',
        'name',
        'username',
        'email',
        'password',
        'role',
        'workshop_id',
    ],

    'workshops' => [
        'id',
        'code',
        'name',
        'is_active',
    ],

    'items' => [
        'id',
        'type',
        'code',
        'name',
        'item_category_id',
        'unit_id',
        'workshop_id',
        'stock',
        'minimum_stock',
        'is_active',
    ],

    'units' => [
        'id',
        'name',
        'allows_decimal',
        'is_active',
    ],

    'storage_locations' => [
        'id',
        'code',
        'name',
        'workshop_id',
        'parent_id',
        'is_active',
    ],

    'item_assets' => [
        'id',
        'item_id',
        'asset_number',
        'workshop_id',
        'storage_location_id',
        'condition',
        'status',
        'is_active',
    ],

    'item_stock_movements' => [
        'id',
        'item_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'transaction_date',
    ],

    'damage_reports' => [
        'id',
        'item_id',
    ],
];

foreach (
    $criticalColumns
    as $table => $columns
) {
    if (! Schema::hasTable($table)) {
        continue;
    }

    foreach ($columns as $column) {
        $record(
            "{$table}.{$column}",
            Schema::hasColumn(
                $table,
                $column
            )
                ? 'PASS'
                : 'WARN'
        );
    }
}

$beginSection(
    'Akun, role, dan jurusan'
);

if (Schema::hasTable('users')) {
    $userCount =
        DB::table('users')
            ->count();

    $record(
        'Jumlah user',
        $userCount > 0
            ? 'PASS'
            : 'FAIL',
        (string) $userCount
    );

    $expectedRoles = [
        'admin',
        'kepala_bengkel',
        'toolman',
        'guru',
        'siswa',
    ];

    foreach (
        $expectedRoles
        as $role
    ) {
        $count =
            DB::table('users')
                ->where(
                    'role',
                    $role
                )
                ->count();

        $required =
            in_array(
                $role,
                [
                    'admin',
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            );

        $record(
            "Role {$role}",
            $count > 0
                ? 'PASS'
                : (
                    $required
                        ? 'WARN'
                        : 'SKIP'
                ),
            (string) $count
        );
    }

    $duplicateUsernames =
        DB::table('users')
            ->select(
                'username',
                DB::raw(
                    'COUNT(*) AS total'
                )
            )
            ->whereNotNull(
                'username'
            )
            ->groupBy(
                'username'
            )
            ->havingRaw(
                'COUNT(*) > 1'
            )
            ->get();

    $record(
        'Username duplikat',
        $duplicateUsernames
            ->isEmpty()
                ? 'PASS'
                : 'FAIL',
        $duplicateUsernames
            ->pluck(
                'username'
            )
            ->all()
    );

    $invalidPasswordUsers =
        DB::table('users')
            ->get([
                'id',
                'username',
                'password',
            ])
            ->filter(
                static function (
                    object $user
                ): bool {
                    $password =
                        (string)
                        $user->password;

                    return ! str_starts_with(
                        $password,
                        '$2y$'
                    )
                    && ! str_starts_with(
                        $password,
                        '$argon2'
                    );
                }
            )
            ->map(
                static fn (
                    object $user
                ): array => [
                    'id' =>
                        $user->id,

                    'username' =>
                        $user->username,
                ]
            )
            ->values()
            ->all();

    $record(
        'Format hash password user',
        $invalidPasswordUsers === []
            ? 'PASS'
            : 'FAIL',
        $invalidPasswordUsers === []
            ? 'semua berupa hash'
            : $invalidPasswordUsers
    );
}

if (
    Schema::hasTable('workshops')
    && Schema::hasTable('users')
) {
    $workshops =
        DB::table('workshops')
            ->orderBy('code')
            ->get();

    $record(
        'Jumlah jurusan',
        $workshops->isNotEmpty()
            ? 'PASS'
            : 'FAIL',
        (string)
        $workshops->count()
    );

    foreach (
        $workshops
        as $workshop
    ) {
        $headCount =
            DB::table('users')
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->where(
                    'role',
                    'kepala_bengkel'
                )
                ->count();

        $toolmanCount =
            DB::table('users')
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->where(
                    'role',
                    'toolman'
                )
                ->count();

        $record(
            "Jurusan {$workshop->code}: Kepala/Toolman",
            $headCount >= 1
            && $toolmanCount >= 1
                ? 'PASS'
                : 'WARN',
            "kepala={$headCount}, toolman={$toolmanCount}"
        );
    }
}

$beginSection(
    'Model dan relasi penting'
);

$modelMethods = [
    \App\Models\User::class => [
        'hasRole',
        'workshop',
    ],

    \App\Models\Workshop::class => [
        'users',
    ],

    \App\Models\Item::class => [
        'typeOptions',
        'conditionOptions',
        'isTool',
        'isMaterial',
        'category',
        'unit',
        'itemAssets',
        'damageReports',
        'storageLocation',
    ],

    \App\Models\ItemAsset::class => [
        'item',
        'workshop',
        'storageLocation',
        'loanItems',
        'damageReports',
        'statusOptions',
        'conditionOptions',
    ],

    \App\Models\StorageLocation::class => [
        'workshop',
        'parent',
        'children',
    ],

    \App\Models\ItemStockMovement::class => [
        'item',
        'user',
        'workshop',
        'storageLocation',
        'typeOptions',
    ],
];

foreach (
    $modelMethods
    as $modelClass => $methods
) {
    $shortName =
        class_basename(
            $modelClass
        );

    if (! class_exists($modelClass)) {
        $record(
            "Model {$shortName}",
            'FAIL',
            'class tidak ditemukan'
        );

        continue;
    }

    foreach ($methods as $method) {
        $record(
            "{$shortName}::{$method}()",
            method_exists(
                $modelClass,
                $method
            )
                ? 'PASS'
                : 'WARN'
        );
    }
}

$beginSection(
    'Route, controller, dan middleware'
);

$routes =
    Route::getRoutes();

$routeNames = [];
$controllerFailures = [];
$middlewareFailures = [];
$duplicateRouteNames = [];

foreach ($routes as $route) {
    $name =
        $route->getName();

    if (
        is_string($name)
        && $name !== ''
    ) {
        if (
            array_key_exists(
                $name,
                $routeNames
            )
        ) {
            $duplicateRouteNames[] =
                $name;
        }

        $routeNames[$name] =
            true;
    }

    $actionName =
        $route->getActionName();

    if (
        $actionName !== 'Closure'
        && $actionName !== ''
    ) {
        if (
            str_contains(
                $actionName,
                '@'
            )
        ) {
            [
                $controller,
                $method,
            ] =
                explode(
                    '@',
                    $actionName,
                    2
                );

            if (
                ! class_exists(
                    $controller
                )
            ) {
                $controllerFailures[] = [
                    'route' =>
                        $name
                        ?: $route->uri(),

                    'controller' =>
                        $controller,

                    'problem' =>
                        'class tidak ditemukan',
                ];
            } elseif (
                ! method_exists(
                    $controller,
                    $method
                )
            ) {
                $controllerFailures[] = [
                    'route' =>
                        $name
                        ?: $route->uri(),

                    'controller' =>
                        $controller,

                    'method' =>
                        $method,

                    'problem' =>
                        'method tidak ditemukan',
                ];
            }
        } elseif (
            class_exists(
                $actionName
            )
            && ! method_exists(
                $actionName,
                '__invoke'
            )
        ) {
            $controllerFailures[] = [
                'route' =>
                    $name
                    ?: $route->uri(),

                'controller' =>
                    $actionName,

                'problem' =>
                    '__invoke tidak ditemukan',
            ];
        }
    }

    try {
        $route
            ->gatherMiddleware();
    } catch (Throwable $exception) {
        $middlewareFailures[] = [
            'route' =>
                $name
                ?: $route->uri(),

            'message' =>
                $exception->getMessage(),
        ];
    }
}

$record(
    'Jumlah seluruh route',
    count($routes) > 0
        ? 'PASS'
        : 'FAIL',
    (string)
    count($routes)
);

$record(
    'Route bernama unik',
    $duplicateRouteNames === []
        ? 'PASS'
        : 'FAIL',
    array_values(
        array_unique(
            $duplicateRouteNames
        )
    )
);

$record(
    'Controller action seluruh route',
    $controllerFailures === []
        ? 'PASS'
        : 'FAIL',
    $controllerFailures === []
        ? 'semua tersedia'
        : $controllerFailures
);

$record(
    'Resolusi middleware seluruh route',
    $middlewareFailures === []
        ? 'PASS'
        : 'FAIL',
    $middlewareFailures === []
        ? 'semua tersedia'
        : $middlewareFailures
);

$beginSection(
    'Menu dan referensi route pada Blade'
);

$bladeFiles =
    $recursiveFiles(
        $root.
        '/resources/views',
        '.blade.php'
    );

$routeReferences = [];
$menuRouteReferences = [];

foreach ($bladeFiles as $bladeFile) {
    $contents =
        file_get_contents(
            $bladeFile
        );

    if (! is_string($contents)) {
        continue;
    }

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

    foreach ($resolvedMatches as $routeReference) {
        $routeReferences[
            $routeReference
        ][] =
            $relativePath(
                $bladeFile
            );
    }

    $normalizedPath =
        strtolower(
            str_replace(
                '\\',
                '/',
                $bladeFile
            )
        );

    if (
        preg_match(
            '#/(layouts?|components?)/#',
            $normalizedPath
        ) === 1
        || str_contains(
            $normalizedPath,
            'sidebar'
        )
        || str_contains(
            $normalizedPath,
            'navigation'
        )
        || str_contains(
            $normalizedPath,
            '/menu'
        )
    ) {
        foreach ($resolvedMatches as $routeReference) {
            $menuRouteReferences[
                $routeReference
            ][] =
                $relativePath(
                    $bladeFile
                );
        }
    }
}

$missingRouteReferences = [];

foreach (
    $routeReferences
    as $routeName => $files
) {
    if (
        ! Route::has(
            $routeName
        )
    ) {
        $missingRouteReferences[] = [
            'route' =>
                $routeName,

            'files' =>
                array_values(
                    array_unique(
                        $files
                    )
                ),
        ];
    }
}

$record(
    'Jumlah file Blade',
    $bladeFiles !== []
        ? 'PASS'
        : 'FAIL',
    (string)
    count($bladeFiles)
);

$record(
    'Jumlah referensi route Blade',
    'PASS',
    (string)
    count($routeReferences)
);

$record(
    'Route yang dipakai Blade tersedia',
    $missingRouteReferences === []
        ? 'PASS'
        : 'FAIL',
    $missingRouteReferences === []
        ? 'semua tersedia'
        : $missingRouteReferences
);

$record(
    'Jumlah route menu terdeteksi',
    $menuRouteReferences !== []
        ? 'PASS'
        : 'WARN',
    (string)
    count($menuRouteReferences)
);

$beginSection(
    'Syntax seluruh Blade'
);

$bladeCachePath =
    $root.
    '/storage/framework/cache/simba-blade-diagnostic';

if (! is_dir($bladeCachePath)) {
    @mkdir(
        $bladeCachePath,
        0775,
        true
    );
}

$bladeFailures = [];

try {
    $bladeCompiler =
        new BladeCompiler(
            new Filesystem(),
            $bladeCachePath
        );

    foreach (
        $bladeFiles
        as $bladeFile
    ) {
        try {
            $bladeCompiler
                ->compile(
                    $bladeFile
                );

            $compiledPath =
                $bladeCompiler
                    ->getCompiledPath(
                        $bladeFile
                    );

            $process =
                $runProcess(
                    [
                        PHP_BINARY,
                        '-l',
                        $compiledPath,
                    ],
                    $root,
                    20
                );

            if (
                ! $process['started']
                || $process['timed_out']
                || $process['exit_code']
                    !== 0
            ) {
                $bladeFailures[] = [
                    'view' =>
                        $relativePath(
                            $bladeFile
                        ),

                    'stdout' =>
                        $process['stdout'],

                    'stderr' =>
                        $process['stderr'],
                ];
            }
        } catch (Throwable $exception) {
            $bladeFailures[] = [
                'view' =>
                    $relativePath(
                        $bladeFile
                    ),

                'exception' =>
                    get_class(
                        $exception
                    ),

                'message' =>
                    $exception
                        ->getMessage(),
            ];
        }
    }

    $record(
        'Compile dan PHP lint seluruh Blade',
        $bladeFailures === []
            ? 'PASS'
            : 'FAIL',
        $bladeFailures === []
            ? count($bladeFiles).
                ' view valid'
            : $bladeFailures
    );
} catch (Throwable $exception) {
    $record(
        'Compile seluruh Blade',
        'WARN',
        $exception->getMessage()
    );
}

$beginSection(
    'Checker khusus yang sudah terpasang'
);

$checkerFiles =
    glob(
        $root.
        '/tools/check-*.php'
    )
    ?: [];

$selfPath =
    realpath(__FILE__);

$checkerFiles =
    array_values(
        array_filter(
            $checkerFiles,
            static function (
                string $file
            ) use (
                $selfPath
            ): bool {
                $real =
                    realpath($file);

                return $real !== false
                    && $real !== $selfPath
                    && ! str_ends_with(
                        $file,
                        'simba-http-smoke-worker.php'
                    );
            }
        )
    );

sort($checkerFiles);

if (! $runExistingCheckers) {
    $record(
        'Eksekusi checker lama',
        'SKIP',
        count($checkerFiles).
        ' checker ditemukan; gunakan --existing-checkers'
    );
} elseif ($checkerFiles === []) {
    $record(
        'Eksekusi checker lama',
        'SKIP',
        'tidak ada checker tambahan'
    );
} else {
    foreach ($checkerFiles as $checkerFile) {
        $process =
            $runProcess(
                [
                    PHP_BINARY,
                    $checkerFile,
                ],
                $root,
                180
            );

        $output =
            trim(
                implode(
                    "\n",
                    array_filter([
                        $process['stdout'],
                        $process['stderr'],
                    ])
                )
            );

        if (! $process['started']) {
            $status = 'WARN';
        } elseif (
            $process['timed_out']
        ) {
            $status = 'FAIL';
            $output =
                'timeout'.
                (
                    $output !== ''
                        ? "\n{$output}"
                        : ''
                );
        } else {
            $status =
                $process['exit_code']
                    === 0
                        ? 'PASS'
                        : 'FAIL';
        }

        $record(
            $relativePath(
                $checkerFile
            ),
            $status,
            $output !== ''
                ? mb_substr(
                    $output,
                    0,
                    3000
                )
                : 'tanpa output'
        );
    }
}

$beginSection(
    'HTTP smoke test menu'
);

if (! $runHttp) {
    $record(
        'HTTP smoke test',
        'SKIP',
        'gunakan opsi --http'
    );
} else {
    $worker =
        $root.
        '/tools/simba-http-smoke-worker.php';

    if (! is_file($worker)) {
        $record(
            'HTTP smoke worker',
            'FAIL',
            $worker.
            ' tidak ditemukan'
        );
    } else {
        $candidateRoutes = [];

        foreach (
            $menuRouteReferences
            as $routeName => $files
        ) {
            $candidateRoutes[
                $routeName
            ] =
                true;
        }

        foreach ($routes as $route) {
            $routeName =
                $route->getName();

            if (
                ! is_string(
                    $routeName
                )
                || $routeName === ''
            ) {
                continue;
            }

            if (
                str_ends_with(
                    $routeName,
                    '.index'
                )
                || str_ends_with(
                    $routeName,
                    '.create'
                )
                || in_array(
                    $routeName,
                    [
                        'dashboard',
                        'home',
                        'profile.edit',
                        'workshops.index',
                        'items.index',
                        'stock-receipts.index',
                        'damage-reports.index',
                    ],
                    true
                )
            ) {
                $candidateRoutes[
                    $routeName
                ] =
                    true;
            }
        }

        $skipPattern =
            '/(?:logout|download|export|pdf|print|stream|attachment|photo|image|qr-bulk|barcode|destroy|delete|toggle|approve|reject|repair|return|receive)/i';

        $safeRoutes = [];

        foreach (
            array_keys(
                $candidateRoutes
            )
            as $routeName
        ) {
            $route =
                $routes
                    ->getByName(
                        $routeName
                    );

            if ($route === null) {
                continue;
            }

            if (
                ! in_array(
                    'GET',
                    $route->methods(),
                    true
                )
            ) {
                continue;
            }

            if (
                str_contains(
                    $route->uri(),
                    '{'
                )
            ) {
                continue;
            }

            if (
                preg_match(
                    $skipPattern,
                    $routeName.
                    ' '.
                    $route->uri()
                ) === 1
            ) {
                continue;
            }

            $safeRoutes[] =
                $routeName;
        }

        sort($safeRoutes);

        $safeRoutes =
            array_slice(
                array_values(
                    array_unique(
                        $safeRoutes
                    )
                ),
                0,
                $maxRoutes
            );

        $roleUsers = [];

        if (Schema::hasTable('users')) {
            $userQuery =
                DB::table('users')
                    ->orderBy('id');

            if ($allRoles) {
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
                    $user =
                        (clone $userQuery)
                            ->where(
                                'role',
                                $role
                            )
                            ->first();

                    if ($user !== null) {
                        $roleUsers[
                            $role
                        ] =
                            (int) $user->id;
                    }
                }
            } else {
                $admin =
                    (clone $userQuery)
                        ->where(
                            'role',
                            'admin'
                        )
                        ->first();

                $fallback =
                    $admin
                    ?: $userQuery
                        ->first();

                if ($fallback !== null) {
                    $roleUsers[
                        (string)
                        (
                            $fallback->role
                            ?? 'user'
                        )
                    ] =
                        (int)
                        $fallback->id;
                }
            }
        }

        if ($safeRoutes === []) {
            $record(
                'Route aman untuk smoke test',
                'WARN',
                'tidak ditemukan'
            );
        } elseif ($roleUsers === []) {
            $record(
                'User untuk smoke test',
                'FAIL',
                'tabel users kosong'
            );
        } else {
            $record(
                'Route smoke test',
                'PASS',
                count($safeRoutes).
                ' route'
            );

            $record(
                'Role smoke test',
                'PASS',
                array_keys(
                    $roleUsers
                )
            );

            foreach (
                $roleUsers
                as $role => $userId
            ) {
                foreach (
                    $safeRoutes
                    as $routeName
                ) {
                    $process =
                        $runProcess(
                            [
                                PHP_BINARY,
                                $worker,
                                '--route='.
                                    $routeName,

                                '--user-id='.
                                    $userId,
                            ],
                            $root,
                            90
                        );

                    if (
                        ! $process[
                            'started'
                        ]
                    ) {
                        $record(
                            "{$role}: {$routeName}",
                            'WARN',
                            $process[
                                'stderr'
                            ]
                        );

                        continue;
                    }

                    if (
                        $process[
                            'timed_out'
                        ]
                    ) {
                        $record(
                            "{$role}: {$routeName}",
                            'FAIL',
                            'timeout'
                        );

                        continue;
                    }

                    $payload =
                        json_decode(
                            $process[
                                'stdout'
                            ],
                            true
                        );

                    if (! is_array($payload)) {
                        $record(
                            "{$role}: {$routeName}",
                            'FAIL',
                            [
                                'stdout' =>
                                    $process[
                                        'stdout'
                                    ],

                                'stderr' =>
                                    $process[
                                        'stderr'
                                    ],
                            ]
                        );

                        continue;
                    }

                    $httpStatus =
                        (int)
                        (
                            $payload[
                                'status'
                            ]
                            ?? 0
                        );

                    $status =
                        match (true) {
                            $httpStatus >= 200
                            && $httpStatus < 400 =>
                                'PASS',

                            in_array(
                                $httpStatus,
                                [
                                    401,
                                    403,
                                ],
                                true
                            ) =>
                                $role === 'admin'
                                    ? 'WARN'
                                    : 'SKIP',

                            default =>
                                'FAIL',
                        };

                    $detail = [
                        'http' =>
                            $httpStatus,

                        'uri' =>
                            $payload['uri']
                            ?? null,

                        'redirect' =>
                            $payload[
                                'redirect'
                            ]
                            ?? null,
                    ];

                    if (
                        isset(
                            $payload[
                                'exception'
                            ]
                        )
                    ) {
                        $detail[
                            'exception'
                        ] =
                            $payload[
                                'exception'
                            ];
                    }

                    if (
                        isset(
                            $payload[
                                'body_excerpt'
                            ]
                        )
                    ) {
                        $detail[
                            'body_excerpt'
                        ] =
                            $payload[
                                'body_excerpt'
                            ];
                    }

                    $record(
                        "{$role}: {$routeName}",
                        $status,
                        $detail
                    );
                }
            }
        }
    }
}

$beginSection(
    'Ringkasan'
);

echo "PASS : {$counts['PASS']}\n";
echo "WARN : {$counts['WARN']}\n";
echo "FAIL : {$counts['FAIL']}\n";
echo "SKIP : {$counts['SKIP']}\n";

$reportDirectory =
    dirname(
        $reportPath
    );

if (! is_dir($reportDirectory)) {
    @mkdir(
        $reportDirectory,
        0775,
        true
    );
}

$finalStatus =
    $counts['FAIL'] > 0
        ? 'FAIL'
        : (
            $counts['WARN'] > 0
                ? 'WARN'
                : 'PASS'
        );

$report = [
    'generated_at' =>
        date(DATE_ATOM),

    'project_root' =>
        $root,

    'php_version' =>
        PHP_VERSION,

    'laravel_version' =>
        app()->version(),

    'app_url' =>
        $appUrl,

    'options' => [
        'http' =>
            $runHttp,

        'all_roles' =>
            $allRoles,

        'existing_checkers' =>
            $runExistingCheckers,

        'max_routes' =>
            $maxRoutes,
    ],

    'summary' =>
        $counts,

    'final_status' =>
        $finalStatus,

    'results' =>
        $results,
];

$jsonWritten =
    file_put_contents(
        $reportPath,
        json_encode(
            $report,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        )
    );

$record(
    'Laporan JSON',
    $jsonWritten !== false
        ? 'PASS'
        : 'WARN',
    $reportPath
);

echo "\nSTATUS AKHIR: {$finalStatus}\n";
echo "Laporan      : {$reportPath}\n";

if ($counts['FAIL'] > 0) {
    echo "\nFITUR ATAU MENU MASIH MEMILIKI KEGAGALAN.\n";
    exit(1);
}

if ($counts['WARN'] > 0) {
    echo "\nFITUR UTAMA VALID, TETAPI MASIH ADA PERINGATAN.\n";
    exit(0);
}

echo "\nSELURUH PEMERIKSAAN YANG DIJALANKAN SUDAH VALID.\n";

exit(0);
