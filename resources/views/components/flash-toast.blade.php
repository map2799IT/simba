@php
    $flashTypes = [
        'success' => [
            'icon' => 'bi-check-circle-fill',
            'iconColor' => 'text-emerald-600',
            'iconBg' => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'progress' => 'bg-emerald-500',
            'title' => 'Berhasil',
            'duration' => 4000,
            'role' => 'status',
            'live' => 'polite',
        ],
        'status' => [
            'icon' => 'bi-check-circle-fill',
            'iconColor' => 'text-emerald-600',
            'iconBg' => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'progress' => 'bg-emerald-500',
            'title' => 'Berhasil',
            'duration' => 4000,
            'role' => 'status',
            'live' => 'polite',
        ],
        'password_success' => [
            'icon' => 'bi-check-circle-fill',
            'iconColor' => 'text-emerald-600',
            'iconBg' => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'progress' => 'bg-emerald-500',
            'title' => 'Berhasil',
            'duration' => 4000,
            'role' => 'status',
            'live' => 'polite',
        ],
        'error' => [
            'icon' => 'bi-x-circle-fill',
            'iconColor' => 'text-red-600',
            'iconBg' => 'bg-red-50',
            'border' => 'border-red-200',
            'progress' => 'bg-red-500',
            'title' => 'Terjadi Kesalahan',
            'duration' => 6000,
            'role' => 'alert',
            'live' => 'assertive',
        ],
        'warning' => [
            'icon' => 'bi-exclamation-triangle-fill',
            'iconColor' => 'text-amber-600',
            'iconBg' => 'bg-amber-50',
            'border' => 'border-amber-200',
            'progress' => 'bg-amber-500',
            'title' => 'Perhatian',
            'duration' => 5500,
            'role' => 'status',
            'live' => 'polite',
        ],
        'info' => [
            'icon' => 'bi-info-circle-fill',
            'iconColor' => 'text-blue-600',
            'iconBg' => 'bg-blue-50',
            'border' => 'border-blue-200',
            'progress' => 'bg-blue-500',
            'title' => 'Informasi',
            'duration' => 4500,
            'role' => 'status',
            'live' => 'polite',
        ],
    ];

    $messages = [];
    foreach (array_keys($flashTypes) as $type) {
        if (session()->has($type) && filled(session($type))) {
            $messages[] = [
                'type' => $type,
                'message' => session($type),
                'config' => $flashTypes[$type],
            ];
        }
    }
@endphp

<div
    x-data="toastHub()"
    x-init="init($el)"
    class="pointer-events-none fixed left-4 right-4 top-4 z-[100] flex flex-col items-center gap-3 sm:left-auto sm:right-5 sm:top-5 sm:items-end"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="(t, i) in toasts" :key="t.id">
        <div
            x-show="t.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-[-8px] scale-[.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-[-4px] opacity-0"
            x-cloak
            class="pointer-events-auto relative w-full max-w-[380px] overflow-hidden rounded-2xl border bg-white shadow-xl"
            :class="t.border"
            :role="t.role"
            :aria-live="t.live"
        >
            <div class="flex items-start gap-3 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="t.iconBg + ' ' + t.iconColor">
                    <i class="bi text-lg" :class="t.icon" aria-hidden="true"></i>
                </span>

                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-sm font-semibold text-slate-900" x-text="t.title"></p>
                    <p class="mt-1 break-words text-sm leading-5 text-slate-600" x-text="t.message"></p>
                </div>

                <button
                    type="button"
                    @click="dismiss(i)"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Tutup notifikasi"
                >
                    <i class="bi bi-x-lg text-sm" aria-hidden="true"></i>
                </button>
            </div>

            <div class="absolute bottom-0 left-0 h-0.5 w-full opacity-80" :class="t.progress" aria-hidden="true"></div>
        </div>
    </template>
</div>

@if ($messages !== [])
<script>
    window.__simbaFlashMessages = {!! json_encode($messages) !!};
</script>
@endif
