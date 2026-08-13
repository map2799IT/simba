<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(ConsoleKernel::class)
    ->bootstrap();

$options = getopt(
    '',
    [
        'route:',
        'user-id:',
    ]
);

$routeName =
    isset($options['route'])
        ? trim(
            (string)
            $options['route']
        )
        : '';

$userId =
    isset($options['user-id'])
        ? (int)
            $options['user-id']
        : 0;

$result = [
    'route' =>
        $routeName,

    'user_id' =>
        $userId,

    'status' =>
        0,
];

try {
    if ($routeName === '') {
        throw new InvalidArgumentException(
            'Nama route kosong.'
        );
    }

    if ($userId <= 0) {
        throw new InvalidArgumentException(
            'User ID tidak valid.'
        );
    }

    $route =
        Route::getRoutes()
            ->getByName(
                $routeName
            );

    if ($route === null) {
        throw new RuntimeException(
            "Route {$routeName} tidak ditemukan."
        );
    }

    if (
        ! in_array(
            'GET',
            $route->methods(),
            true
        )
    ) {
        throw new RuntimeException(
            "Route {$routeName} bukan GET."
        );
    }

    if (
        str_contains(
            $route->uri(),
            '{'
        )
    ) {
        throw new RuntimeException(
            "Route {$routeName} membutuhkan parameter."
        );
    }

    $user =
        User::query()
            ->withoutGlobalScopes()
            ->find(
                $userId
            );

    if ($user === null) {
        throw new RuntimeException(
            "User {$userId} tidak ditemukan."
        );
    }

    /*
     * Smoke test tidak boleh menulis session database.
     */
    config([
        'session.driver' =>
            'array',

        'cache.default' =>
            'array',

        'mail.default' =>
            'log',

        'queue.default' =>
            'sync',

        'app.debug' =>
            true,
    ]);

    Auth::shouldUse(
        config(
            'auth.defaults.guard',
            'web'
        )
    );

    Auth::guard()
        ->setUser(
            $user
        );

    $appUrl =
        (string)
        config(
            'app.url',
            'http://localhost'
        );

    $scheme =
        parse_url(
            $appUrl,
            PHP_URL_SCHEME
        )
        ?: 'http';

    $host =
        parse_url(
            $appUrl,
            PHP_URL_HOST
        )
        ?: 'localhost';

    $uri =
        '/'.ltrim(
            $route->uri(),
            '/'
        );

    $request =
        Request::create(
            $uri,
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST' =>
                    $host,

                'HTTP_ACCEPT' =>
                    'text/html,application/xhtml+xml',

                'HTTPS' =>
                    $scheme === 'https'
                        ? 'on'
                        : 'off',

                'SERVER_PORT' =>
                    $scheme === 'https'
                        ? 443
                        : 80,
            ]
        );

    $request->setUserResolver(
        static fn () =>
            $user
    );

    $httpKernel =
        $app->make(
            HttpKernel::class
        );

    ob_start();

    $response =
        $httpKernel->handle(
            $request
        );

    $unexpectedOutput =
        trim(
            (string)
            ob_get_clean()
        );

    $status =
        $response
            ->getStatusCode();

    $result[
        'status'
    ] =
        $status;

    $result['uri'] =
        $uri;

    $result['role'] =
        $user->role;

    if (
        $response->isRedirection()
    ) {
        $result[
            'redirect'
        ] =
            $response
                ->headers
                ->get(
                    'Location'
                );
    }

    if ($status >= 400) {
        $content =
            method_exists(
                $response,
                'getContent'
            )
                ? (string)
                    $response
                        ->getContent()
                : '';

        $plain =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    strip_tags(
                        $content
                    )
                )
                ?: ''
            );

        if ($plain !== '') {
            $result[
                'body_excerpt'
            ] =
                mb_substr(
                    $plain,
                    0,
                    1200
                );
        }
    }

    if ($unexpectedOutput !== '') {
        $result[
            'unexpected_output'
        ] =
            mb_substr(
                $unexpectedOutput,
                0,
                1000
            );
    }
} catch (Throwable $exception) {
    while (
        ob_get_level() > 0
    ) {
        ob_end_clean();
    }

    $result['status'] =
        500;

    $result['exception'] = [
        'class' =>
            get_class(
                $exception
            ),

        'message' =>
            $exception
                ->getMessage(),

        'file' =>
            $exception
                ->getFile(),

        'line' =>
            $exception
                ->getLine(),
    ];
}

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);
