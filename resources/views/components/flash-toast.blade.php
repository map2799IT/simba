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
                'message' => session($type),
                'config' => $flashTypes[$type],
            ];
        }
    }
@endphp

@if ($messages !== [])
    <div
        class="fixed left-4 right-4 top-4 z-[100] flex flex-col items-center gap-3 sm:left-auto sm:right-5 sm:top-5 sm:items-end"
        aria-live="polite"
        aria-atomic="true"
    >
        @foreach ($messages as $message)
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, {{ $message['config']['duration'] }})"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-[-8px] scale-[.98] opacity-0"
                x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-[-4px] opacity-0"
                x-cloak
                class="pointer-events-auto relative w-full max-w-[380px] overflow-hidden rounded-2xl border {{ $message['config']['border'] }} bg-white p-4 shadow-xl"
                role="{{ $message['config']['role'] }}"
                aria-live="{{ $message['config']['live'] }}"
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $message['config']['iconBg'] }} {{ $message['config']['iconColor'] }}">
                        <i class="bi {{ $message['config']['icon'] }} text-lg" aria-hidden="true"></i>
                    </span>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm font-semibold text-slate-900">{{ $message['config']['title'] }}</p>
                        <p class="mt-1 break-words text-sm leading-5 text-slate-600">{{ $message['message'] }}</p>
                    </div>

                    <button
                        type="button"
                        @click="show = false"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup notifikasi"
                    >
                        <i class="bi bi-x-lg text-sm" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="absolute bottom-0 left-0 h-0.5 w-full {{ $message['config']['progress'] }} opacity-80" aria-hidden="true"></div>
            </div>
        @endforeach
    </div>
@endif
