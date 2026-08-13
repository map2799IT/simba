<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);

require $root.
    DIRECTORY_SEPARATOR.
    'vendor'.
    DIRECTORY_SEPARATOR.
    'autoload.php';

$app = require $root.
    DIRECTORY_SEPARATOR.
    'bootstrap'.
    DIRECTORY_SEPARATOR.
    'app.php';

$app->make(Kernel::class)->bootstrap();

$expected = [
    'audit-logs.index',
    'audit-logs.show',

    'categories.index',
    'categories.create',
    'categories.store',
    'categories.edit',
    'categories.update',
    'categories.toggle-status',

    'damage-reports.start',
    'damage-reports.resolve',

    'item',
    'register',
];

$failed = false;

echo "SIMBA 12 ROUTE CHECK\n";
echo "====================\n\n";

foreach ($expected as $name) {
    $available = Route::has($name);

    echo str_pad($name, 36).
        ': '.
        (
            $available
                ? 'OK'
                : 'GAGAL'
        ).
        PHP_EOL;

    if (! $available) {
        $failed = true;
    }
}

exit($failed ? 1 : 0);
