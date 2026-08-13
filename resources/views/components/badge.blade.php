@props([
    'variant' => 'neutral',
    'dot' => false,
])

@php
    $variants = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'danger' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
        'info' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
        'neutral' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
        'primary' => 'bg-blue-600 text-white',
    ];

    $dotColors = [
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-500',
        'info' => 'bg-blue-500',
        'neutral' => 'bg-slate-400',
        'primary' => 'bg-white',
    ];

    $badgeClass = $variants[$variant] ?? $variants['neutral'];
    $dotClass = $dotColors[$variant] ?? $dotColors['neutral'];
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }} {{ $attributes->get('class', '') }}">
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
    @endif
    {{ $slot }}
</span>
