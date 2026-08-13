<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->group(function (): void {
        if (! Route::has('profile.edit')) {
            Route::get(
                '/profile',
                [
                    ProfileController::class,
                    'edit',
                ]
            )->name('profile.edit');
        }

        if (! Route::has('profile.update')) {
            Route::patch(
                '/profile',
                [
                    ProfileController::class,
                    'update',
                ]
            )
                ->middleware(
                    'throttle:30,1'
                )
                ->name('profile.update');
        }

        if (
            ! Route::has(
                'profile.password.update'
            )
        ) {
            Route::put(
                '/profile/password',
                [
                    ProfileController::class,
                    'updatePassword',
                ]
            )
                ->middleware(
                    'throttle:10,1'
                )
                ->name(
                    'profile.password.update'
                );
        }
    });
