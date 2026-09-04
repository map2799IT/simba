<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('partials.simba-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SIMBA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/simba-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/simba-dark.css') }}" id="theme-stylesheet">
    <script>
        (function () {
            try {
                var theme = 'light';
                var saved = localStorage.getItem('simba-theme');
                if (saved === 'dark' || saved === 'light') {
                    theme = saved;
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            }
        })();
    </script>
    @stack('styles')
</head>

<body class="h-full bg-slate-50 text-slate-800 antialiased">
    @php
        $sidebarUser = auth()->user();
        $userName = $sidebarUser?->name ?? $sidebarUser?->username ?? 'Pengguna';
        $userInitial = strtoupper(mb_substr($userName, 0, 1));
        $userRole = ucwords(str_replace('_', ' ', (string) ($sidebarUser?->role ?? 'pengguna')));
    @endphp

    <div
        class="min-h-full"
        x-data="{ sidebarOpen: false }"
        @keydown.escape.window="sidebarOpen = false"
        @close-sidebar.window="sidebarOpen = false"
    >
        {{-- Mobile backdrop --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-in duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-out duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
        ></div>

        @include('layouts.sidebar')

        {{-- Main content area --}}
        <div class="lg:pl-[304px]">
            {{-- Topbar --}}
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur-md lg:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 lg:hidden"
                        aria-label="Buka menu"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="text-base font-bold tracking-tight text-slate-900">SIMBA</span>
                        <span class="hidden text-xs text-slate-400 sm:inline">Sistem Inventaris Bengkel</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Global Search (Ctrl+K) --}}
                    @include('partials.global-search-trigger')

                    {{-- Theme toggle --}}
                    <button
                        type="button"
                        id="theme-toggle"
                        title="Ganti tema terang/gelap"
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                        aria-label="Ganti tema"
                    >
                        <i class="bi bi-moon-stars text-base leading-none dark-icon"></i>
                        <i class="bi bi-sun text-base leading-none light-icon" style="display:none"></i>
                    </button>

                    {{-- User menu --}}
                    <div class="dropdown">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Menu pengguna"
                        >
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white">
                                {{ $userInitial }}
                            </span>
                            <span class="hidden max-w-[120px] truncate sm:inline">{{ $userName }}</span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border border-slate-200 rounded-xl overflow-hidden" style="min-width: 220px;">
                            <li>
                                <div class="dropdown-header py-3">
                                    <div class="fw-bold text-dark text-sm">{{ $userName }}</div>
                                    @if ($sidebarUser?->email)
                                        <div class="small text-slate-500">{{ $sidebarUser->email }}</div>
                                    @endif
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 mt-1">{{ $userRole }}</span>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                            <li>
                                <a href="{{ route('profile.edit') }}" class="dropdown-item py-2 text-sm">
                                    <i class="bi bi-person-circle me-2"></i> Profil
                                </a>
                            </li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-sm text-red-600">
                                        <i class="bi bi-box-arrow-right me-2"></i> Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            {{-- Content --}}
            <main class="p-4 lg:p-7">
                <div class="mx-auto max-w-[1440px]">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    {{-- Global Search (Ctrl+K) --}}
    @include('partials.global-search')

    {{-- Flash toast (fixed positioned) --}}
    <x-flash-toast />

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('theme-toggle');
            if (!toggle) return;

            var root = document.documentElement;
            var darkIcon = toggle.querySelector('.dark-icon');
            var lightIcon = toggle.querySelector('.light-icon');

            function applyIcon(theme) {
                var dark = theme === 'dark';
                if (darkIcon) darkIcon.style.display = dark ? '' : 'none';
                if (lightIcon) lightIcon.style.display = dark ? 'none' : '';
            }

            applyIcon(root.getAttribute('data-theme'));

            toggle.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                localStorage.setItem('simba-theme', next);
                applyIcon(next);
            });
        });
    </script>

    <script>
        window.toastHub = function () {
            return {
                toasts: [],
                init($el) {
                    this.seed();
                    window.addEventListener('simba:toast', (e) => {
                        const detail = e && e.detail ? e.detail : {};
                        this.push(detail.type || 'info', detail.message, detail.title);
                    });
                },
                seed() {
                    const flash = window.__simbaFlashMessages || [];
                    flash.forEach((item) => this.push(item.type || 'info', item.message, null));
                },
                configs: {
                    success: { icon: 'bi-check-circle-fill', iconColor: 'text-emerald-600', iconBg: 'bg-emerald-50', border: 'border-emerald-200', progress: 'bg-emerald-500', title: 'Berhasil', duration: 4000, role: 'status', live: 'polite' },
                    status: { icon: 'bi-check-circle-fill', iconColor: 'text-emerald-600', iconBg: 'bg-emerald-50', border: 'border-emerald-200', progress: 'bg-emerald-500', title: 'Berhasil', duration: 4000, role: 'status', live: 'polite' },
                    password_success: { icon: 'bi-check-circle-fill', iconColor: 'text-emerald-600', iconBg: 'bg-emerald-50', border: 'border-emerald-200', progress: 'bg-emerald-500', title: 'Berhasil', duration: 4000, role: 'status', live: 'polite' },
                    error: { icon: 'bi-x-circle-fill', iconColor: 'text-red-600', iconBg: 'bg-red-50', border: 'border-red-200', progress: 'bg-red-500', title: 'Terjadi Kesalahan', duration: 6000, role: 'alert', live: 'assertive' },
                    warning: { icon: 'bi-exclamation-triangle-fill', iconColor: 'text-amber-600', iconBg: 'bg-amber-50', border: 'border-amber-200', progress: 'bg-amber-500', title: 'Perhatian', duration: 5500, role: 'status', live: 'polite' },
                    info: { icon: 'bi-info-circle-fill', iconColor: 'text-blue-600', iconBg: 'bg-blue-50', border: 'border-blue-200', progress: 'bg-blue-500', title: 'Informasi', duration: 4500, role: 'status', live: 'polite' },
                },
                push(type, message, title) {
                    const cfg = this.configs[type] || this.configs.info;
                    const id = Date.now() + '-' + Math.random().toString(36).slice(2, 7);
                    this.toasts.push(Object.assign({}, cfg, {
                        id,
                        type,
                        message: message || cfg.title,
                        title: title || cfg.title,
                        show: true,
                    }));
                    setTimeout(() => this.dismiss(this.toasts.length - 1), cfg.duration);
                },
                dismiss(index) {
                    const t = this.toasts[index];
                    if (!t) return;
                    t.show = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter((x) => x.id !== t.id);
                    }, 250);
                },
            };
        };
    </script>

    @include('layouts.role-menu-guard')
    @include('layouts.user-jurusan-guard')
    @include('layouts.loan-jurusan-guard')
</body>
</html>
