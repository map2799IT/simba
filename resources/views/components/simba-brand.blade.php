@props([
    'variant' => 'dark',
    'compact' => false,
    'link' => null,
    'alt' => 'SIMBA - Sistem Inventaris dan Peminjaman Bengkel',
])

@php
    $source =
        $compact
            ? asset(
                'branding/simba-mark.svg'
            )
            : asset(
                $variant === 'light'
                    ? 'branding/simba-logo-light.svg'
                    : 'branding/simba-logo.svg'
            );

    $target =
        $link
        ?? url('/');
@endphp

<a
    href="{{ $target }}"
    {{ $attributes->merge([
        'class' =>
            'simba-brand-component'.
            (
                $compact
                    ? ' simba-brand-component--compact'
                    : ''
            ),
    ]) }}
>
    <img
        src="{{ $source }}"
        alt="{{ $alt }}"
        class="simba-brand-component__image"
        width="{{ $compact ? 52 : 310 }}"
        height="{{ $compact ? 52 : 84 }}"
    >
</a>
