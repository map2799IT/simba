<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Throwable;

class RouteHealthCheck extends Command
{
    protected $signature =
        'routes:health
        {--only-missing : Hanya tampilkan route bermasalah}';

    protected $description =
        'Memeriksa controller dan method route tanpa memakai route:list.';

    public function handle(): int
    {
        $rows = [];
        $errorCount = 0;

        foreach (
            Route::getRoutes()
            as $route
        ) {
            $action = $route->getActionName();

            if (
                $action === 'Closure'
                || str_starts_with(
                    $action,
                    'Illuminate\\'
                )
            ) {
                continue;
            }

            [$class, $method] =
                $this->parseAction($action);

            if ($class === null) {
                continue;
            }

            $status = 'OK';
            $detail = '';

            if (! class_exists($class)) {
                $status = 'MISSING CLASS';
                $detail = $class;
                $errorCount++;
            } elseif (
                $method !== null
                && ! method_exists(
                    $class,
                    $method
                )
            ) {
                $status = 'MISSING METHOD';
                $detail =
                    $class.'@'.$method;
                $errorCount++;
            } else {
                try {
                    new ReflectionClass($class);
                } catch (Throwable $exception) {
                    $status = 'REFLECTION ERROR';
                    $detail =
                        $exception->getMessage();
                    $errorCount++;
                }
            }

            if (
                $this->option('only-missing')
                && $status === 'OK'
            ) {
                continue;
            }

            $rows[] = [
                implode(
                    '|',
                    $route->methods()
                ),
                $route->uri(),
                $route->getName() ?? '-',
                $status,
                $detail,
            ];
        }

        $this->table(
            [
                'Method',
                'URI',
                'Name',
                'Status',
                'Detail',
            ],
            $rows
        );

        if ($errorCount > 0) {
            $this->error(
                "{$errorCount} route masih bermasalah."
            );

            return self::FAILURE;
        }

        $this->info(
            'Semua controller dan method route terdeteksi.'
        );

        return self::SUCCESS;
    }

    private function parseAction(
        string $action
    ): array {
        if (str_contains($action, '@')) {
            [$class, $method] =
                explode('@', $action, 2);

            return [
                $class,
                $method,
            ];
        }

        /*
         * Invokable controller.
         */
        if (
            class_exists($action)
            || str_contains(
                $action,
                '\\'
            )
        ) {
            return [
                $action,
                '__invoke',
            ];
        }

        return [
            null,
            null,
        ];
    }
}
