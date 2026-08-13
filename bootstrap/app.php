<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup(
            'web',
            \App\Http\Middleware\EnforceUserJurusanAssignment::class
        );
        $middleware->appendToGroup(
            'web',
            \App\Http\Middleware\RouteLoanToJurusanToolman::class
        );

        $middleware->appendToGroup(
            'web',
            \App\Http\Middleware\EnforceSimbaRoleAccess::class
        );

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            try {
                if (app()->runningInConsole()) {
                    return;
                }
                if (! \Illuminate\Support\Facades\Schema::hasTable('user_error_logs')) {
                    return;
                }
                $request = request();
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                // Catat 403, 404, 419, 422 (penting), 500+
                $shouldRecord = $status >= 500
                    || in_array($status, [403, 404, 419, 422], true);

                if (! $shouldRecord) {
                    return;
                }

                \App\Models\UserErrorLog::record($e, $request, $status);
            } catch (\Throwable) {
                //
            }
        });
    })
    ->create();