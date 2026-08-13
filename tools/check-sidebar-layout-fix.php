<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$sidebarFile = $root.
    DIRECTORY_SEPARATOR.
    'resources'.
    DIRECTORY_SEPARATOR.
    'views'.
    DIRECTORY_SEPARATOR.
    'layouts'.
    DIRECTORY_SEPARATOR.
    'sidebar.blade.php';

$contents = is_file($sidebarFile)
    ? file_get_contents($sidebarFile)
    : '';

$checks = [
    'View layouts.sidebar' =>
        View::exists('layouts.sidebar'),

    'Sidebar position fixed' =>
        is_string($contents)
        && str_contains(
            $contents,
            'position: fixed;'
        ),

    'Konten digeser ke kanan' =>
        is_string($contents)
        && str_contains(
            $contents,
            'simba-sidebar-main-sibling'
        ),

    'Mobile full width' =>
        is_string($contents)
        && str_contains(
            $contents,
            'margin-left: 0 !important;'
        ),

    'Backdrop mobile' =>
        is_string($contents)
        && str_contains(
            $contents,
            'data-simba-sidebar-backdrop'
        ),
];

$failed = false;

echo "SIMBA SIDEBAR LAYOUT CHECK\n";
echo "==========================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 34).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'PERBAIKAN LAYOUT GAGAL.'
            : 'LAYOUT SIDEBAR SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
