@props([
    'perPage' => 25,
    'label' => 'Baris/halaman',
])

<div class="flex items-center gap-2 text-sm text-slate-600">
    <span class="whitespace-nowrap text-xs text-slate-500">{{ $label }}</span>
    <form method="GET" action="{{ url()->current() }}" class="m-0">
        @foreach (request()->query() as $key => $value)
            @if (is_string($key) && $key !== 'per_page' && is_string($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <select
            name="per_page"
            onchange="this.form.submit()"
            class="rounded-lg border-slate-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
            @foreach ([10, 25, 100] as $n)
                <option value="{{ $n }}" @selected((string) $perPage === (string) $n)>{{ $n }}</option>
            @endforeach
        </select>
    </form>
</div>