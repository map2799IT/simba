<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | Form login SIMBA menggunakan field bernama "login".
    | Field ini dapat berisi username atau email.
    |
    */

    'username' => 'login',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    */

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    */

    'home' => '/dashboard',

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Passkeys
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini boleh tetap tersedia walaupun fitur passkey
    | belum diaktifkan.
    |
    */

    'passkeys' => [
        'relying_party_id' => parse_url(
            config('app.url'),
            PHP_URL_HOST
        ),

        'allowed_origins' => [
            config('app.url'),
        ],

        'user_handle_secret' => config('app.key'),

        'timeout' => 60000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Registrasi publik, reset password, verifikasi email, 2FA,
    | dan passkey dinonaktifkan dahulu.
    |
    */

    'features' => [
        // Features::registration(),
        // Features::resetPasswords(),
        // Features::emailVerification(),

        Features::updateProfileInformation(),
        Features::updatePasswords(),

        // Aktifkan setelah halaman dan model 2FA selesai dibuat.
        // Features::twoFactorAuthentication([
        //     'confirm' => true,
        //     'confirmPassword' => true,
        // ]),

        // Aktifkan setelah dukungan passkey selesai dibuat.
        // Features::passkeys([
        //     'confirmPassword' => true,
        // ]),
    ],

];