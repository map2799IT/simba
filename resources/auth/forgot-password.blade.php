@extends('layouts.auth-modern')

@section('title', 'Lupa Password - SIMBA')
@section('panel-eyebrow', 'Pemulihan Akun')
@section('panel-title', 'Lupa password?')
@section(
    'panel-description',
    'Masukkan email, username, atau NISN yang terhubung dengan akun. Tautan pemulihan dikirim ke email akun yang aktif.'
)

@section('hero-eyebrow', 'Akses Akun')
@section('hero-title', 'Pulihkan akses dengan aman.')
@section(
    'hero-description',
    'SIMBA tidak menampilkan keberadaan akun secara terbuka. Siswa tanpa email aktif dapat meminta reset password kepada Toolman jurusan.'
)

@section('content')
    <div class="alert alert--info">
        <strong>Khusus siswa:</strong>
        apabila akun menggunakan email internal SIMBA,
        hubungi Toolman jurusan untuk melakukan reset password.
    </div>

    <form
        method="POST"
        action="{{ route(
            'password.email'
        ) }}"
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
                placeholder="Masukkan identitas akun"
                autocomplete="username"
                required
                autofocus
            >

            @error('identity')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror

            <div class="field-help">
                Demi keamanan, hasil pemeriksaan akun tidak ditampilkan secara rinci.
            </div>
        </div>

        <button
            type="submit"
            class="button button--primary"
        >
            Kirim Tautan Pemulihan
        </button>
    </form>

    <div class="divider">
        Kembali
    </div>

    <a
        href="{{ route('login') }}"
        class="button button--secondary"
        style="width:100%;"
    >
        Kembali ke Halaman Login
    </a>
@endsection
