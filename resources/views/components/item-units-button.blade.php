@props([
    'item',
    'label' => 'Lihat Unit & QR',
    'class' => 'btn btn-sm btn-outline-primary',
])

@if (
    data_get($item, 'type') === 'tool'
    && \Illuminate\Support\Facades\Route::has(
        'item-assets.index'
    )
)
    <a
        href="{{ route(
            'item-assets.index',
            [
                'item_id' => data_get(
                    $item,
                    'id'
                ),
            ]
        ) }}"
        {{ $attributes->merge([
            'class' => $class,
        ]) }}
    >
        <i class="bi bi-qr-code me-1"></i>
        {{ $label }}
    </a>
@endif
