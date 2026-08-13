@props([
    'icon' => 'bi-inbox',
    'title' => 'Tidak ada data',
    'description' => null,
    'action' => null,
])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <i class="bi {{ $icon }} text-3xl"></i>
    </div>
    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($action)
        <div class="mt-5">{{ $action }}</div>
    @endif
    @isset($slot)
        @if (trim($slot))
            <div class="mt-5">{{ $slot }}</div>
        @endif
    @endisset
</div>
