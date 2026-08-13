<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | View Login
        |--------------------------------------------------------------------------
        |
        | Fortify tidak menyediakan tampilan login bawaan.
        | Route GET /login akan menampilkan Blade auth.login.
        |
        */

        Fortify::loginView(function () {
            return view('auth.login');
        });

        /*
        |--------------------------------------------------------------------------
        | Autentikasi Username atau Email
        |--------------------------------------------------------------------------
        */

        Fortify::authenticateUsing(
            function (Request $request): ?User {
                $login = trim(
                    (string) $request->input('login')
                );

                $password = (string)
                    $request->input('password');

                $user = User::query()
                    ->where(function ($query) use ($login): void {
                        $query
                            ->where('username', $login)
                            ->orWhere('email', $login);
                    })
                    ->first();

                if ($user === null) {
                    return null;
                }

                if (! $user->is_active) {
                    return null;
                }

                if (! Hash::check(
                    $password,
                    $user->password
                )) {
                    return null;
                }

                return $user;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Pembatasan Percobaan Login
        |--------------------------------------------------------------------------
        */

        RateLimiter::for(
            'login',
            function (Request $request): Limit {
                $login = Str::transliterate(
                    Str::lower(
                        (string) $request->input('login')
                    )
                );

                return Limit::perMinute(5)->by(
                    $login.'|'.$request->ip()
                );
            }
        );
    }
}