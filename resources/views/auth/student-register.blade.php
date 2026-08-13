@extends('layouts.auth-modern')

@section('title', 'Registrasi NISN - SIMBA')
@section('panel-eyebrow', 'Registrasi Siswa')
@section('panel-title', 'Aktifkan akun dengan NISN')
@section(
    'panel-description',
    'Ketik NISN 10 digit. Data siswa akan muncul otomatis. Setelah data benar, buat password untuk mengaktifkan akun.'
)

@section('hero-eyebrow', 'Registrasi Siswa Terverifikasi')
@section('hero-title', 'Tidak perlu mengisi ulang data sekolah.')
@section(
    'hero-description',
    'SIMBA mengambil nama, kelas, dan jurusan langsung dari Data Siswa yang telah dimasukkan Administrator atau Toolman.'
)

@section('content')
    <div class="alert alert--info">
        Registrasi hanya tersedia untuk NISN yang sudah ada pada
        <strong>Data Siswa</strong> dan belum memiliki akun.
        Login berikutnya menggunakan NISN.
    </div>

    <form
        id="student-registration-form"
        method="POST"
        action="{{ route(
            'student-register.store'
        ) }}"
    >
        @csrf

        <div class="field">
            <label
                for="nisn"
                class="field-label"
            >
                NISN
                <span class="field-hint">
                    10 digit
                </span>
            </label>

            <input
                id="nisn"
                type="text"
                name="nisn"
                value="{{ old('nisn') }}"
                class="control"
                inputmode="numeric"
                autocomplete="off"
                maxlength="10"
                pattern="[0-9]{10}"
                placeholder="Ketik NISN siswa"
                required
                autofocus
            >

            <div
                id="nisn-loading"
                class="loading hidden"
            >
                <span class="spinner"></span>
                Memeriksa data siswa...
            </div>

            <div
                id="nisn-message"
                class="field-help"
            >
                Data akan diperiksa otomatis setelah 10 digit terisi.
            </div>

            @error('nisn')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <section
            id="student-card"
            class="student-card hidden"
            aria-live="polite"
        >
            <div class="student-card__head">
                <span class="student-card__status">
                    ✓
                </span>

                <div>
                    <div class="student-card__title">
                        Data siswa ditemukan
                    </div>

                    <div class="field-help">
                        Pastikan data berikut benar.
                    </div>
                </div>
            </div>

            <div class="student-card__body">
                <div class="student-data">
                    <span>Nama Siswa</span>
                    <strong id="student-name">-</strong>
                </div>

                <div class="student-data">
                    <span>NIS</span>
                    <strong id="student-nis">-</strong>
                </div>

                <div class="student-data">
                    <span>Kelas</span>
                    <strong id="student-class">-</strong>
                </div>

                <div class="student-data">
                    <span>Jurusan</span>
                    <strong id="student-workshop">-</strong>
                </div>

                <div class="student-data">
                    <span>Tahun Ajaran</span>
                    <strong id="student-year">-</strong>
                </div>

                <div class="student-data">
                    <span>Username Login</span>
                    <strong id="student-login">NISN</strong>
                </div>
            </div>
        </section>

        <div
            id="password-section"
            class="hidden"
        >
            <div class="field">
                <label
                    for="password"
                    class="field-label"
                >
                    Password Baru
                    <span class="field-hint">
                        Minimal 8 karakter
                    </span>
                </label>

                <div class="control-wrap">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="control control--with-action"
                        placeholder="Gunakan huruf besar, kecil, dan angka"
                        minlength="8"
                        autocomplete="new-password"
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
                        placeholder="Ulangi password baru"
                        minlength="8"
                        autocomplete="new-password"
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
                id="register-button"
                type="submit"
                class="button button--primary"
            >
                Aktifkan Akun Siswa
            </button>
        </div>
    </form>

    <div class="divider">
        Sudah Terdaftar
    </div>

    <a
        href="{{ route('login') }}"
        class="button button--secondary"
        style="width:100%;"
    >
        Kembali ke Halaman Login
    </a>
@endsection

@section('scripts')
    <script>
        (function () {
            const nisnInput =
                document.getElementById(
                    'nisn'
                );

            const loading =
                document.getElementById(
                    'nisn-loading'
                );

            const message =
                document.getElementById(
                    'nisn-message'
                );

            const studentCard =
                document.getElementById(
                    'student-card'
                );

            const passwordSection =
                document.getElementById(
                    'password-section'
                );

            const password =
                document.getElementById(
                    'password'
                );

            const confirmation =
                document.getElementById(
                    'password_confirmation'
                );

            const csrf =
                document.querySelector(
                    'input[name="_token"]'
                ).value;

            let timer = null;
            let controller = null;
            let verifiedNisn = null;

            const hideStudent =
                function () {
                    verifiedNisn = null;
                    studentCard.classList.add(
                        'hidden'
                    );
                    passwordSection.classList.add(
                        'hidden'
                    );

                    password.required = false;
                    confirmation.required = false;
                };

            const setMessage =
                function (
                    text,
                    type
                ) {
                    message.textContent = text;
                    message.style.color =
                        type === 'error'
                            ? '#dc2626'
                            : type === 'success'
                                ? '#166534'
                                : '#64748b';
                };

            const renderStudent =
                function (student) {
                    document.getElementById(
                        'student-name'
                    ).textContent =
                        student.name;

                    document.getElementById(
                        'student-nis'
                    ).textContent =
                        student.nis;

                    document.getElementById(
                        'student-class'
                    ).textContent =
                        student.class_name;

                    document.getElementById(
                        'student-workshop'
                    ).textContent =
                        student.workshop_code
                        + ' — '
                        + student.workshop_name;

                    document.getElementById(
                        'student-year'
                    ).textContent =
                        student.school_year;

                    document.getElementById(
                        'student-login'
                    ).textContent =
                        student.nisn;

                    verifiedNisn =
                        student.nisn;

                    studentCard.classList.remove(
                        'hidden'
                    );

                    passwordSection.classList.remove(
                        'hidden'
                    );

                    password.required = true;
                    confirmation.required = true;

                    setMessage(
                        'Data sesuai. Silakan buat password akun.',
                        'success'
                    );
                };

            const lookup =
                async function (nisn) {
                    hideStudent();

                    if (! /^\d{10}$/.test(nisn)) {
                        setMessage(
                            'NISN harus terdiri dari tepat 10 digit.',
                            'normal'
                        );
                        return;
                    }

                    controller?.abort();
                    controller =
                        new AbortController();

                    loading.classList.remove(
                        'hidden'
                    );

                    setMessage(
                        'Sedang memeriksa NISN...',
                        'normal'
                    );

                    try {
                        const response =
                            await fetch(
                                @json(
                                    route(
                                        'student-register.lookup'
                                    )
                                ),
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'Content-Type':
                                            'application/json',

                                        'X-CSRF-TOKEN':
                                            csrf,
                                    },
                                    body: JSON.stringify({
                                        nisn: nisn,
                                    }),
                                    signal:
                                        controller.signal,
                                }
                            );

                        const data =
                            await response.json();

                        if (! response.ok) {
                            throw new Error(
                                data.message
                                || 'Data siswa tidak ditemukan.'
                            );
                        }

                        renderStudent(
                            data.student
                        );
                    } catch (error) {
                        if (
                            error.name
                            === 'AbortError'
                        ) {
                            return;
                        }

                        hideStudent();

                        setMessage(
                            error.message
                            || 'Data siswa tidak dapat diperiksa.',
                            'error'
                        );
                    } finally {
                        loading.classList.add(
                            'hidden'
                        );
                    }
                };

            nisnInput.addEventListener(
                'input',
                function () {
                    const cleaned =
                        nisnInput.value
                            .replace(/\D/g, '')
                            .slice(0, 10);

                    nisnInput.value =
                        cleaned;

                    hideStudent();

                    clearTimeout(timer);

                    if (cleaned.length < 10) {
                        setMessage(
                            'Data akan diperiksa otomatis setelah 10 digit terisi.',
                            'normal'
                        );
                        return;
                    }

                    timer = setTimeout(
                        function () {
                            lookup(cleaned);
                        },
                        350
                    );
                }
            );

            document.getElementById(
                'student-registration-form'
            ).addEventListener(
                'submit',
                function (event) {
                    if (
                        verifiedNisn
                        !== nisnInput.value
                    ) {
                        event.preventDefault();

                        setMessage(
                            'Periksa NISN sampai data siswa ditemukan sebelum membuat akun.',
                            'error'
                        );

                        lookup(
                            nisnInput.value
                        );
                    }
                }
            );

            @if (
                old('nisn')
                && strlen(
                    old('nisn')
                ) === 10
            )
                lookup(
                    @json(old('nisn'))
                );
            @endif
        })();
    </script>
@endsection
