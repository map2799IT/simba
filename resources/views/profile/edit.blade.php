@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
    @php
        $roleLabels = [
            'admin' => 'Administrator',
            'kepala_bengkel' => 'Kepala Bengkel',
            'toolman' => 'Toolman',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
        ];

        $roleLabel =
            $roleLabels[$user->role]
            ?? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $user->role
                )
            );

        $initial =
            strtoupper(
                mb_substr(
                    trim(
                        (string) $user->name
                    ),
                    0,
                    1
                )
            );
    @endphp

    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Profil Saya
            </h1>

            <p class="page-description mb-0">
                Kelola informasi akun dan ubah password dengan aman.
            </p>
        </div>

        <div class="text-secondary small">
            Terakhir diperbarui:
            {{ $user->updated_at
                ?->format('d-m-Y H:i')
                ?? '-' }}
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <section class="content-card h-100">
                <div class="content-card-body text-center py-4">
                    <div
                        class="rounded-circle bg-primary-subtle
                            text-primary d-inline-flex
                            align-items-center justify-content-center
                            fw-bold mb-3"
                        style="
                            width: 84px;
                            height: 84px;
                            font-size: 32px;
                        "
                    >
                        {{ $initial ?: 'U' }}
                    </div>

                    <h2 class="h5 fw-bold mb-1">
                        {{ $user->name }}
                    </h2>

                    <div class="text-secondary mb-3">
                        {{ $user->email }}
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge bg-primary">
                            {{ $roleLabel }}
                        </span>

                        @if ($workshop)
                            <span class="badge bg-success">
                                {{ $workshop->code }}
                            </span>
                        @elseif (
                            in_array(
                                $user->role,
                                [
                                    'admin',
                                    'guru',
                                ],
                                true
                            )
                        )
                            <span class="badge bg-info text-dark">
                                Seluruh Jurusan
                            </span>
                        @endif
                    </div>

                    @if ($student)
                        <hr>

                        <div class="text-start small">
                            <div class="mb-2">
                                <span class="text-secondary">
                                    NISN
                                </span>

                                <div class="fw-semibold font-monospace">
                                    {{ $student->nisn ?? '-' }}
                                </div>
                            </div>

                            <div class="mb-2">
                                <span class="text-secondary">
                                    Kelas
                                </span>

                                <div class="fw-semibold">
                                    {{ $student->class_name
                                        ?? $student->kelas
                                        ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <span class="text-secondary">
                                    Status akun
                                </span>

                                <div class="fw-semibold text-success">
                                    Terdaftar
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-8">
            <section class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-1">
                        Informasi Akun
                    </h2>

                    <div class="small text-secondary">
                        Perubahan email dapat memerlukan verifikasi ulang.
                    </div>
                </div>

                <div class="content-card-body">
                    <form
                        method="POST"
                        action="{{ route(
                            'profile.update'
                        ) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <div class="col-12">
                                <label
                                    for="name"
                                    class="form-label"
                                >
                                    Nama Lengkap
                                </label>

                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old(
                                        'name',
                                        $user->name
                                    ) }}"
                                    class="form-control
                                        @error('name')
                                            is-invalid
                                        @enderror"
                                    maxlength="255"
                                    required
                                    autocomplete="name"
                                >

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            @if ($hasUsername)
                                <div class="col-12 col-md-6">
                                    <label
                                        for="username"
                                        class="form-label"
                                    >
                                        Username
                                    </label>

                                    <input
                                        id="username"
                                        type="text"
                                        name="username"
                                        value="{{ old(
                                            'username',
                                            $user->username
                                        ) }}"
                                        class="form-control
                                            @error('username')
                                                is-invalid
                                            @enderror"
                                        maxlength="100"
                                        autocomplete="username"
                                    >

                                    @error('username')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @endif

                            <div
                                class="col-12
                                    {{ $hasUsername
                                        ? 'col-md-6'
                                        : '' }}"
                            >
                                <label
                                    for="email"
                                    class="form-label"
                                >
                                    Email
                                </label>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old(
                                        'email',
                                        $user->email
                                    ) }}"
                                    class="form-control
                                        @error('email')
                                            is-invalid
                                        @enderror"
                                    maxlength="255"
                                    required
                                    autocomplete="email"
                                >

                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            @if ($hasPhone)
                                <div class="col-12 col-md-6">
                                    <label
                                        for="phone"
                                        class="form-label"
                                    >
                                        Nomor Telepon
                                    </label>

                                    <input
                                        id="phone"
                                        type="text"
                                        name="phone"
                                        value="{{ old(
                                            'phone',
                                            $user->phone
                                        ) }}"
                                        class="form-control
                                            @error('phone')
                                                is-invalid
                                            @enderror"
                                        maxlength="30"
                                        autocomplete="tel"
                                    >

                                    @error('phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @endif

                            <div class="col-12 col-md-6">
                                <label class="form-label">
                                    Role
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $roleLabel }}"
                                    disabled
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">
                                    Jurusan
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{
                                        $workshop
                                            ? $workshop->code.
                                                ' — '.
                                                $workshop->name
                                            : (
                                                in_array(
                                                    $user->role,
                                                    [
                                                        'admin',
                                                        'guru',
                                                    ],
                                                    true
                                                )
                                                    ? 'Seluruh Jurusan'
                                                    : 'Belum ditetapkan'
                                            )
                                    }}"
                                    disabled
                                >
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-save me-2"></i>
                                Simpan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="content-card">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-1">
                        Ganti Password
                    </h2>

                    <div class="small text-secondary">
                        Minimal 8 karakter. Gunakan kombinasi huruf dan angka agar lebih aman.
                    </div>
                </div>

                <div class="content-card-body">
                    @if ($errors->has('current_password') || $errors->has('password'))
                        <div class="alert alert-danger py-2 mb-3">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <strong>Gagal mengubah password.</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @error('current_password')<li>{{ $message }}</li>@enderror
                                @error('password')<li>{{ $message }}</li>@enderror
                            </ul>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('profile.password.update') }}"
                        autocomplete="off"
                    >
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label
                                    for="current_password"
                                    class="form-label"
                                >
                                    Password Saat Ini
                                </label>

                                <div class="input-group">
                                    <input
                                        id="current_password"
                                        type="password"
                                        name="current_password"
                                        class="form-control
                                            @error('current_password')
                                                is-invalid
                                            @enderror"
                                        required
                                        autocomplete="current-password"
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        data-password-toggle="current_password"
                                        aria-label="Tampilkan password"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    @error('current_password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label
                                    for="password"
                                    class="form-label"
                                >
                                    Password Baru
                                </label>

                                <div class="input-group">
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        class="form-control
                                            @error('password')
                                                is-invalid
                                            @enderror"
                                        minlength="8"
                                        required
                                        autocomplete="new-password"
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        data-password-toggle="password"
                                        aria-label="Tampilkan password"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label
                                    for="password_confirmation"
                                    class="form-label"
                                >
                                    Konfirmasi Password Baru
                                </label>

                                <div class="input-group">
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        class="form-control"
                                        minlength="8"
                                        required
                                        autocomplete="new-password"
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        data-password-toggle="password_confirmation"
                                        aria-label="Tampilkan password"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            class="alert alert-light border
                                small mt-3 mb-0"
                        >
                            <i class="bi bi-shield-check me-2"></i>

                            Setelah password berhasil diubah,
                            gunakan password baru pada login berikutnya.
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button
                                type="submit"
                                class="btn btn-warning"
                            >
                                <i class="bi bi-key me-2"></i>
                                Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                document
                    .querySelectorAll(
                        '[data-password-toggle]'
                    )
                    .forEach(
                        function (button) {
                            button.addEventListener(
                                'click',
                                function () {
                                    const input =
                                        document.getElementById(
                                            button.dataset
                                                .passwordToggle
                                        );

                                    if (! input) {
                                        return;
                                    }

                                    const show =
                                        input.type
                                        === 'password';

                                    input.type =
                                        show
                                            ? 'text'
                                            : 'password';

                                    const icon =
                                        button.querySelector(
                                            'i'
                                        );

                                    if (icon) {
                                        icon.className =
                                            show
                                                ? 'bi bi-eye-slash'
                                                : 'bi bi-eye';
                                    }
                                }
                            );
                        }
                    );
            }
        );
    </script>
@endsection
