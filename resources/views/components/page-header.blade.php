@props([
    'title' => null,
    'description' => null,
])

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if (isset($breadcrumb) && ! empty($breadcrumb))
            <nav class="mb-2 flex items-center gap-1.5 text-xs text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="transition hover:text-slate-700">Dashboard</a>
                @foreach ($breadcrumb as $crumb)
                    <svg class="h-3 w-3 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    @if (is_array($crumb) && isset($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="truncate transition hover:text-slate-700">{{ $crumb['label'] }}</a>
                    @else
                        <span class="truncate font-medium text-slate-700">{{ $crumb }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @if ($title)
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $title }}</h1>
        @endif

        @if ($description)
            <p class="mt-1.5 text-sm text-slate-500 md:text-base">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions) && trim($actions))
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
            {{ $actions }}
        </div>
    @endif

    @isset($slot)
        @if (trim($slot))
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                {{ $slot }}
            </div>
        @endif
    @endisset
</div>
