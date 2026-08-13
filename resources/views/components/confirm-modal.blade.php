@props([
    'title' => 'Konfirmasi',
    'description' => null,
    'confirmLabel' => 'Ya, Lanjutkan',
    'cancelLabel' => 'Batal',
    'variant' => 'danger',
    'formAction' => null,
    'formMethod' => 'POST',
])

@php
    $variantColors = [
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-400',
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    ];

    $iconColors = [
        'danger' => 'bg-red-100 text-red-600',
        'warning' => 'bg-amber-100 text-amber-600',
        'primary' => 'bg-blue-100 text-blue-600',
    ];

    $icons = [
        'danger' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'primary' => 'bi-question-circle-fill',
    ];

    $btnClass = $variantColors[$variant] ?? $variantColors['danger'];
    $iconBg = $iconColors[$variant] ?? $iconColors['danger'];
    $iconClass = $icons[$variant] ?? $icons['danger'];

    $id = 'modal-' . uniqid();
@endphp

<div
    x-data="{ open: false }"
    x-cloak
    @keydown.escape.window="open = false"
>
    {{-- Trigger --}}
    <button
        type="button"
        @click="open = true"
        class="{{ $attributes->get('class', '') }}"
        {!! $attributes->except(['class']) !!}
    >
        {{ $slot }}
    </button>

    {{-- Modal --}}
    <div
        x-show="open"
        @click="open = false"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 backdrop-blur-sm p-4 sm:items-center"
        style="display: none;"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            @click.stop
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconBg }}">
                    <i class="bi {{ $iconClass }} text-lg"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                    @if ($description)
                        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    @click="open = false"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    {{ $cancelLabel }}
                </button>
                @if ($formAction)
                <form method="POST" action="{{ $formAction }}" class="m-0">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto {{ $btnClass }}">
                        {{ $confirmLabel }}
                    </button>
                </form>
                @else
                <button
                    type="button"
                    @click="$dispatch('confirmed'); open = false"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $btnClass }}"
                >
                    {{ $confirmLabel }}
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
