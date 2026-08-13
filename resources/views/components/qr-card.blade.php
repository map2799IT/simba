@props([
    'asset',
    'itemName' => null,
    'showActions' => true,
])

@php
    $qrService = app(\App\Services\ItemAssetQrCodeService::class);
    $qrSvg = $qrService->svg($asset, 150);
    $txLabel = collect([$asset->brand, $asset->model])->filter()->implode(' / ');
    $name = $itemName ?? ($txLabel !== '' ? $txLabel : ($asset->item?->name ?? '-'));
@endphp

<div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    {{-- QR Code — fixed 150×150, tepat di tengah --}}
    <div class="flex items-center justify-center p-4">
        <div style="width:150px;height:150px;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;">
            {!! $qrSvg !!}
        </div>
    </div>

    {{-- Info --}}
    <div class="flex flex-1 flex-col border-t border-slate-100 px-3 py-3 text-center">
        <p class="break-words font-mono text-xs font-bold leading-tight text-slate-900">
            {{ $asset->asset_number }}
        </p>
        <p class="mt-1.5 text-[11px] leading-tight text-slate-600 line-clamp-2">
            {{ $name }}
        </p>
        @if ($asset->storageLocation)
            <p class="mt-1 text-[10px] leading-tight text-slate-400 truncate">
                {{ $asset->storageLocation->name }}
            </p>
        @endif
    </div>

    {{-- Actions --}}
    @if ($showActions)
        <div class="flex border-t border-slate-100 no-print">
            <a href="{{ route('item-assets.show', $asset) }}"
                class="flex min-h-10 flex-1 items-center justify-center gap-1.5 border-r border-slate-100 px-2 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                title="Detail Unit">
                <i class="bi bi-eye text-[13px]"></i>
                <span>Detail</span>
            </a>
            <a href="{{ route('item-assets.label', $asset) }}" target="_blank"
                class="flex min-h-10 flex-1 items-center justify-center gap-1.5 px-2 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                title="Cetak QR">
                <i class="bi bi-printer text-[13px]"></i>
                <span>Print</span>
            </a>
        </div>
    @endif
</div>
