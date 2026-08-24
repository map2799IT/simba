@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    $variants = [
        'primary' => 'bg-blue-600 text-white shadow-sm hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50 focus:ring-slate-400',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus:ring-red-500',
        'soft' => 'bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-400',
        'soft-danger' => 'bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-400',
        'soft-success' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-400',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-400',
    ];

    $sizes = [
        'sm' => 'min-h-9 rounded-lg px-3 py-1.5 text-xs',
        'md' => 'min-h-11 rounded-xl px-4 py-2.5 text-sm',
        'lg' => 'min-h-12 rounded-xl px-5 py-3 text-base',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{!! $href !!}" class="{{ $classes }} {{ $attributes->get('class', '') }}" {!! $attributes->except(['class']) !!}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" class="{{ $classes }} {{ $attributes->get('class', '') }}" {!! $attributes->except(['class', 'type']) !!}>
        {{ $slot }}
    </button>
@endif
