@extends('layouts.auth-modern')

@section('title', 'Password Baru - SIMBA')
@section('panel-eyebrow', 'Pemulihan Akun')
@section('panel-title', 'Buat password baru')
@section(
    'panel-description',
    'Masukkan email akun dan password baru. Tautan hanya dapat digunakan selama masih berlaku.'
)

@section('hero-eyebrow', 'Keamanan Akun')
@section('hero-title', 'Gunakan password yang kuat dan berbeda.')
@section(
    'hero-description',
    'Password baru minimal delapan karakter, memiliki huruf besar, huruf kecil, dan angka.'
)

@section('content')
    <form
        method="POST"
        action="{{ route(
            'password.update'
        ) }}"
    >
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $token }}"
        >

        <div class="field">
            <label
                for="email"
                class="field-label"
            >
                Email Akun
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old(
                    'email',
                    $email
                ) }}"
                class="control"
                autocomplete="email"
                required
                autofocus
            >

            @error('email')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="field">
            <label
                for="password"
                class="field-label"
            >
                Password Baru
            </label>

            <div class="control-wrap">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="control control--with-action"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="control-action"
                    data-password-toggle="password"
                >
                    Lihat
                </button>
            </div>

            @error('password')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="field">
            <label
                for="password_confirmation"
                class="field-label"
            >
                Konfirmasi Password
            </label>

            <div class="control-wrap">
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="control control--with-action"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="control-action"
                    data-password-toggle="password_confirmation"
                >
                    Lihat
                </button>
            </div>
        </div>

        <button
            type="submit"
            class="button button--primary"
        >
            Simpan Password Baru
        </button>
    </form>
@endsection
