@extends('layouts.auth-modern')

@section('title', 'Masuk - SIMBA')
@section('panel-eyebrow', 'Selamat Datang')
@section('panel-title', 'Masuk ke SIMBA')
@section(
    'panel-description',
    'Gunakan email, username, atau NISN. Siswa yang baru pertama kali menggunakan SIMBA harus melakukan registrasi NISN.'
)

@section('hero-eyebrow', 'Sistem Manajemen Bengkel')
@section('hero-title', 'Inventaris yang rapi dimulai dari satu akses.')
@section(
    'hero-description',
    'Masuk untuk mengelola alat, bahan, lokasi, peminjaman, laporan, dan QR fisik setiap jurusan.'
)

@section('content')
    <form
        method="POST"
        action="{{ route('login.store') }}"
    >
        @csrf

        <div class="field">
            <label
                for="identity"
                class="field-label"
            >
                Email, Username, atau NISN
            </label>

            <input
                id="identity"
                type="text"
                name="identity"
                value="{{ old('identity') }}"
                class="control"
                placeholder="Contoh: admin@simba.local atau 0090000001"
                autocomplete="username"
                required
                autofocus
            >

            @error('identity')
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
                Password
            </label>

            <div class="control-wrap">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="control control--with-action"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
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

        <div class="form-options">
            <label class="check">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                >

                Ingat saya
            </label>

            @if (
                \Illuminate\Support\Facades\Route::has(
                    'password.request'
                )
            )
                <a
                    href="{{ route(
                        'password.request'
                    ) }}"
                    class="text-link"
                >
                    Lupa password?
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="button button--primary"
        >
            Masuk ke Dashboard
        </button>
    </form>

    <div class="divider">
        Registrasi Siswa
    </div>

    <div class="secondary-action">
        <div class="secondary-action__icon">
            N
        </div>

        <div class="secondary-action__copy">
            <strong>
                Belum memiliki akun?
            </strong>

            <span>
                Cari data siswa menggunakan NISN, lalu buat password.
            </span>
        </div>

        <a
            href="{{ route(
                'student-register.create'
            ) }}"
            class="button button--secondary button--small"
        >
            Daftar
        </a>
    </div>
@endsection
