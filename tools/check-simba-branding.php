<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$files = [
    'Logo SVG' =>
        $root.
        '/public/branding/simba-logo.svg',

    'Logo light SVG' =>
        $root.
        '/public/branding/simba-logo-light.svg',

    'Mark SVG' =>
        $root.
        '/public/branding/simba-mark.svg',

    'Logo PNG' =>
        $root.
        '/public/branding/simba-logo.png',

    'Favicon ICO' =>
        $root.
        '/public/branding/favicon.ico',

    'Favicon root' =>
        $root.
        '/public/favicon.ico',

    'Apple touch icon' =>
        $root.
        '/public/branding/apple-touch-icon.png',

    'Manifest' =>
        $root.
        '/public/branding/site.webmanifest',

    'Brand CSS' =>
        $root.
        '/public/css/simba-brand.css',
];

$failed = false;

echo "SIMBA BRANDING CHECK\n";
echo "====================\n\n";

foreach ($files as $label => $file) {
    $exists =
        is_file($file)
        && filesize($file) > 0;

    echo str_pad($label, 36).
        ': '.
        ($exists ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $exists) {
        $failed = true;
    }
}

$viewChecks = [
    'Favicon partial' =>
        View::exists(
            'partials.simba-favicon'
        ),

    'Brand component' =>
        View::exists(
            'components.simba-brand'
        ),

    'Sidebar logo' =>
        View::exists(
            'layouts.sidebar'
        ),

    'Auth modern logo' =>
        View::exists(
            'layouts.auth-modern'
        ),
];

foreach (
    $viewChecks
    as $label => $exists
) {
    echo str_pad($label, 36).
        ': '.
        ($exists ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $exists) {
        $failed = true;
    }
}

echo "\n".
    (
        $failed
            ? 'BRANDING SIMBA BELUM LENGKAP.'
            : 'LOGO DAN FAVICON SIMBA SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
