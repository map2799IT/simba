@props([
    'label' => '',
    'sortKey' => '',
    'sort' => null,
    'direction' => 'asc',
    'width' => null,
    'class' => '',
])

@php
    $query = request()->query();
    $query['sort'] = $sortKey;
    $query['direction'] = ($sort === $sortKey && $direction === 'asc') ? 'desc' : 'asc';
    $queryString = http_build_query($query);
    $active = $sort === $sortKey;
    $arrow = $active
        ? ($direction === 'asc' ? '↑' : '↓')
        : '↕';
    $ariaSort = $active
        ? ($direction === 'asc' ? 'ascending' : 'descending')
        : 'none';
@endphp

<th
    scope="col"
    class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 {{ $class }}"
    {!! $width ? 'style="width:'.$width.'"' : '' !!}
    aria-sort="{{ $ariaSort }}"
>
    @if ($sortKey === '')
        <span>{{ $label }}</span>
    @else
        <a
            href="{{ url()->current().'?'.$queryString }}"
            class="inline-flex items-center gap-1 text-slate-500 hover:text-blue-600 hover:no-underline"
            role="button"
            @if ($active) style="color:#2563eb" @endif
        >
            {{ $label }}
            <span class="{{ $active ? 'text-blue-600' : 'text-slate-300' }}">{{ $arrow }}</span>
        </a>
    @endif
</th>