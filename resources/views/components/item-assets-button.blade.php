@props([
    'label' => 'Unit Alat & QR Code',
    'class' => 'btn btn-outline-primary',
])

@if (
    \Illuminate\Support\Facades\Route::has(
        'item-assets.index'
    )
)
    <a
        href="{{ route('item-assets.index') }}"
        {{ $attributes->merge([
            'class' => $class,
        ]) }}
    >
        <i class="bi bi-qr-code-scan me-2"></i>
        {{ $label }}
    </a>
@endif
