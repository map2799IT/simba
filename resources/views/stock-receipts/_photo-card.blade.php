@php
    $photoKind  = $kind ?? 'active';
    $photoTitle = $title ?? ($photoKind === 'proposed' ? 'Foto Usulan' : 'Foto Aktif');
    $photoExists = ! empty($path);
    $photoRoute  = $photoKind === 'proposed'
        ? 'stock-receipts.photo.proposed'
        : 'stock-receipts.photo.active';
    $photoUrl = $photoExists && \Illuminate\Support\Facades\Route::has($photoRoute)
        ? route($photoRoute, $stockReceipt)
        : null;
@endphp

<div>
    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $photoTitle }}</p>

    @if ($photoUrl)
        <a href="{{ $photoUrl }}" target="_blank" rel="noopener"
            title="Buka {{ strtolower($photoTitle) }} ukuran penuh"
            class="block overflow-hidden rounded-xl border border-slate-200">
            <img
                src="{{ $photoUrl }}"
                alt="{{ $photoTitle }} {{ $stockReceipt->item?->name }}"
                class="w-full object-contain"
                style="max-height: 260px; background: #f8fafc;"
                loading="{{ $loading ?? 'lazy' }}"
            >
        </a>
        <p class="mt-1.5 text-xs text-slate-400">Klik gambar untuk melihat ukuran penuh.</p>
    @else
        <div class="flex min-h-[160px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
            <i class="bi bi-image text-3xl text-slate-300"></i>
            <p class="mt-2 text-xs text-slate-500">{{ $emptyText ?? 'Tidak ada foto.' }}</p>
        </div>
    @endif
</div>
