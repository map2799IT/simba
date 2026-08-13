<?php

use App\Http\Controllers\Auth\SimbaAuthenticatedSessionController;
use App\Http\Controllers\Auth\SimbaPasswordResetController;
use App\Http\Controllers\Auth\StudentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')
    ->group(function (): void {
        Route::get(
            '/login',
            [
                SimbaAuthenticatedSessionController::class,
                'create',
            ]
        )->name('login');

        Route::post(
            '/login',
            [
                SimbaAuthenticatedSessionController::class,
                'store',
            ]
        )
            ->middleware('throttle:10,1')
            ->name('login.store');

        /*
         * Registrasi umum ditutup. Seluruh siswa masuk melalui NISN.
         */
        Route::get(
            '/register',
            static fn () =>
                redirect()
                    ->route(
                        'student-register.create'
                    )
        )->name('register');

        Route::post(
            '/register',
            static fn () =>
                redirect()
                    ->route(
                        'student-register.create'
                    )
                    ->withErrors([
                        'nisn' =>
                            'Registrasi SIMBA hanya menggunakan NISN siswa.',
                    ])
        );

        Route::get(
            '/student-register',
            [
                StudentRegistrationController::class,
                'create',
            ]
        )->name(
            'student-register.create'
        );

        Route::post(
            '/student-register/lookup',
            [
                StudentRegistrationController::class,
                'lookup',
            ]
        )
            ->middleware('throttle:20,1')
            ->name(
                'student-register.lookup'
            );

        Route::post(
            '/student-register',
            [
                StudentRegistrationController::class,
                'store',
            ]
        )
            ->middleware('throttle:10,1')
            ->name(
                'student-register.store'
            );

        Route::get(
            '/forgot-password',
            [
                SimbaPasswordResetController::class,
                'requestForm',
            ]
        )->name(
            'password.request'
        );

        Route::post(
            '/forgot-password',
            [
                SimbaPasswordResetController::class,
                'sendResetLink',
            ]
        )
            ->middleware('throttle:5,1')
            ->name(
                'password.email'
            );

        Route::get(
            '/reset-password/{token}',
            [
                SimbaPasswordResetController::class,
                'resetForm',
            ]
        )->name(
            'password.reset'
        );

        Route::post(
            '/reset-password',
            [
                SimbaPasswordResetController::class,
                'reset',
            ]
        )
            ->middleware('throttle:5,1')
            ->name(
                'password.update'
            );
    });

Route::post(
    '/logout',
    [
        SimbaAuthenticatedSessionController::class,
        'destroy',
    ]
)
    ->middleware('auth')
    ->name('logout');
