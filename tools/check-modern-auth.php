<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$checks = [
    'Login controller modern' =>
        class_exists(
            \App\Http\Controllers\Auth\SimbaAuthenticatedSessionController::class
        ),

    'Password reset controller' =>
        class_exists(
            \App\Http\Controllers\Auth\SimbaPasswordResetController::class
        ),

    'Student registration controller' =>
        class_exists(
            \App\Http\Controllers\Auth\StudentRegistrationController::class
        ),

    'Layout auth modern' =>
        View::exists(
            'layouts.auth-modern'
        ),

    'View login' =>
        View::exists(
            'auth.login'
        ),

    'View lupa password' =>
        View::exists(
            'auth.forgot-password'
        ),

    'View reset password' =>
        View::exists(
            'auth.reset-password'
        ),

    'View registrasi NISN' =>
        View::exists(
            'auth.student-register'
        ),

    'Tabel students' =>
        Schema::hasTable(
            'students'
        ),

    'Kolom students.nisn' =>
        Schema::hasColumn(
            'students',
            'nisn'
        ),

    'Route login' =>
        Route::has('login'),

    'Route login.store' =>
        Route::has(
            'login.store'
        ),

    'Route password.request' =>
        Route::has(
            'password.request'
        ),

    'Route password.email' =>
        Route::has(
            'password.email'
        ),

    'Route password.reset' =>
        Route::has(
            'password.reset'
        ),

    'Route registrasi NISN' =>
        Route::has(
            'student-register.create'
        ),

    'Route lookup NISN' =>
        Route::has(
            'student-register.lookup'
        ),

    'Route simpan registrasi' =>
        Route::has(
            'student-register.store'
        ),
];

$failed = false;

echo "SIMBA MODERN AUTH CHECK\n";
echo "=======================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 40).
        ': '.
        ($passed ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $passed) {
        $failed = true;
    }
}

echo "\nROUTE ACTIONS\n";
echo "-------------\n";

foreach (
    [
        'login',
        'login.store',
        'password.request',
        'password.email',
        'password.reset',
        'student-register.create',
        'student-register.lookup',
        'student-register.store',
    ]
    as $name
) {
    $route =
        Route::getRoutes()
            ->getByName($name);

    echo str_pad($name, 40).
        ': '.
        (
            $route?->getActionName()
            ?? 'TIDAK ADA'
        ).
        PHP_EOL;
}

echo "\n".
    (
        $failed
            ? 'MODUL AUTH MODERN BELUM VALID.'
            : 'LOGIN, LUPA PASSWORD, DAN REGISTRASI NISN SIAP.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
