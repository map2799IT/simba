<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Akses SIMBA')</title>
    @include('partials.simba-favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/simba-brand.css') }}">
    @yield('head')
</head>

<body>
    <x-flash-toast />

    <div class="auth-shell">
        <aside class="auth-hero">
            <div class="hero-inner">
                <a href="{{ url('/') }}" class="brand" aria-label="SIMBA">
                    <img src="{{ asset('branding/simba-logo-light.svg') }}" alt="SIMBA" class="brand-logo-image" width="330" height="89">
                </a>

                <div class="hero-copy">
                    <div class="hero-eyebrow">@yield('hero-eyebrow', 'Inventaris Sekolah Terintegrasi')</div>
                    <h1 class="hero-title">@yield('hero-title', 'Kelola bengkel dengan lebih teratur.')</h1>
                    <p class="hero-description">@yield('hero-description', 'Satu sistem untuk inventaris, peminjaman, lokasi penyimpanan, QR unit alat, dan administrasi setiap jurusan.')</p>
                    <div class="hero-points">
                        <div class="hero-point">
                            <strong>Berbasis Jurusan</strong>
                            <span>Akses data mengikuti tanggung jawab setiap pengguna.</span>
                        </div>
                        <div class="hero-point">
                            <strong>Unit Alat Terpantau</strong>
                            <span>Setiap alat fisik mempunyai nomor inventaris dan QR.</span>
                        </div>
                        <div class="hero-point">
                            <strong>Aman dan Tercatat</strong>
                            <span>Aktivitas penting tersimpan dalam sistem.</span>
                        </div>
                    </div>
                </div>

                <div class="hero-footer">&copy; {{ now()->year }} SIMBA &middot; Sistem Informasi Manajemen Bengkel</div>
            </div>
        </aside>

        <main class="auth-main">
            <div class="auth-panel">
                <a href="{{ url('/') }}" class="brand mobile-brand" aria-label="SIMBA">
                    <img src="{{ asset('branding/simba-logo.svg') }}" alt="SIMBA" class="brand-logo-image" width="255" height="69">
                </a>

                <div class="panel-eyebrow">@yield('panel-eyebrow', 'Akses SIMBA')</div>
                <h2 class="panel-title">@yield('panel-title')</h2>
                <p class="panel-description">@yield('panel-description')</p>

                <section class="auth-card">
                    @if ($errors->any())
                        <div class="alert alert--danger">{{ $errors->first() }}</div>
                    @endif
                    @yield('content')
                </section>

                <div class="panel-footer">
                    Gunakan akun sesuai peran dan jurusan. Jangan membagikan password kepada pengguna lain.
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;
        const input = document.getElementById(toggle.dataset.passwordToggle);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Sembunyikan' : 'Lihat';
    });
    </script>

    @yield('scripts')
</body>
</html>
