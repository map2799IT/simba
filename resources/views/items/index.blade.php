@extends('layouts.app')

@section('title', 'Data Alat & Bahan')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    @php $canManage = auth()->user()->hasRole('admin', 'toolman'); @endphp

    {{-- Page Header --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Data Alat & Bahan</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola master alat dan bahan inventaris sekolah.</p>
        </div>
        @if ($canManage)
            <x-button href="{{ route('items.create') }}" variant="primary" class="w-full sm:w-auto">
                <i class="bi bi-plus-lg"></i>
                Tambah Data
            </x-button>
        @endif
    </div>

    {{-- Search & Filter â€” compact, single row --}}
    <div class="mb-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <form method="GET" action="{{ route('items.index') }}">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                {{-- Search --}}
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Cari nama, kode, atau kategori..."
                    >
                </div>

                {{-- Selects --}}
                <div class="flex gap-2">
                    <select name="type" class="w-full rounded-xl border-slate-300 bg-white py-2.5 px-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-36" aria-label="Jenis">
                        <option value="">Semua Jenis</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="item_category_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 px-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-40" aria-label="Kategori">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('item_category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="bi bi-funnel"></i>
                        <span class="hidden sm:inline">Filter</span>
                    </button>
                    <a href="{{ route('items.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50" aria-label="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if ($canManage ?? false)
        <form id="bulk-items-form" method="POST" action="{{ route('items.bulk.toggle-status') }}" class="mb-4">
            @csrf
            <input type="hidden" name="ids" id="bulk-items-ids">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <span class="text-sm text-slate-600"><span id="bulk-count" class="font-semibold text-blue-600">0</span> dipilih</span>
                <button id="bulk-toggle-btn" type="submit" disabled class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-blue-300 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-40">
                    <i class="bi bi-toggle-off"></i> Ubah Status Terpilih
                </button>
            </div>
        </form>
    @endif

    {{-- Desktop Table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        @if ($canManage ?? false)
                            <th class="w-10 px-4 py-3.5"><input type="checkbox" id="bulk-check-all" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"></th>
                        @endif
                        <x-sortable-header :label="'Nama'" :sort-key="'name'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header :label="'Jenis'" :sort-key="'type'" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</th>
                        <x-sortable-header :label="'Stok'" :sort-key="'stock'" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="transition-colors hover:bg-slate-50">
                            @if ($canManage ?? false)
                                <td class="px-4 py-3.5"><input type="checkbox" class="item-check h-4 w-4 rounded border-slate-300 text-blue-600" value="{{ $item->id }}"></td>
                            @endif
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ $item->name }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $item->code }}</div>
                                <div class="mt-0.5 inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500"><i class="bi bi-clock-history"></i>{{ $item->updated_at?->format('d-m-Y H:i') ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if ($item->isTool())
                                    <x-badge variant="info">Alat</x-badge>
                                @else
                                    <x-badge variant="neutral">Bahan</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-sm text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $item->unit?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @php
                                    $stockVal = (float) $item->stock;
                                    if ($stockVal <= 0) {
                                        $stockClass = 'text-red-600';
                                    } elseif ($item->minimum_stock && $stockVal <= (float) $item->minimum_stock) {
                                        $stockClass = 'text-amber-600';
                                    } else {
                                        $stockClass = 'text-slate-900';
                                    }
                                @endphp
                                <span class="text-sm font-bold {{ $stockClass }}">{{ \App\Support\QuantityFormatter::format($item->stock, $item->unit) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if ($item->is_active)
                                    <x-badge variant="success" dot>Aktif</x-badge>
                                @else
                                    <x-badge variant="neutral" dot>Nonaktif</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('items.show', $item) }}" class="inline-flex min-h-9 items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if ($canManage)
                                        <a href="{{ route('items.edit', $item) }}" class="inline-flex min-h-9 items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @if ($item->isTool() && \Illuminate\Support\Facades\Route::has('item-assets.index'))
                                        <a href="{{ route('item-assets.index', ['item_id' => $item->id]) }}" class="inline-flex min-h-9 items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100" title="Unit Alat & QR">
                                            <i class="bi bi-qr-code-scan"></i>
                                        </a>
                                    @endif
                                    @if ($canManage && \Illuminate\Support\Facades\Route::has('items.toggle-status'))
                                        <a href="{{ route('items.toggle-status', $item) }}" onclick="event.preventDefault(); document.getElementById('toggle-form-{{ $item->id }}').submit();" class="inline-flex min-h-9 items-center rounded-lg px-2.5 py-1.5 text-xs font-medium transition hover:bg-slate-100" title="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="bi {{ $item->is_active ? 'bi-toggle-on text-emerald-500' : 'bi-toggle-off text-slate-400' }}"></i>
                                        </a>
                                        <form id="toggle-form-{{ $item->id }}" method="POST" action="{{ route('items.toggle-status', $item) }}" class="hidden">
                                            @csrf @method('PATCH')
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($canManage ?? false) ? 8 : 7 }}" class="px-5 py-10">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="bi bi-box-seam text-2xl"></i>
                                    </div>
                                    <h3 class="mt-4 text-base font-semibold text-slate-900">Belum Ada Data</h3>
                                    <p class="mt-1 max-w-sm text-sm text-slate-500">Data barang belum tersedia. Tambahkan data pertama untuk memulai.</p>
                                    @if ($canManage)
                                        <div class="mt-5">
                                            <x-button href="{{ route('items.create') }}" variant="primary">
                                                <i class="bi bi-plus-lg"></i> Tambah Data
                                            </x-button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Card List --}}
    <div class="card-mobile space-y-3">
        @forelse ($items as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $item->name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $item->code }}</p>
                        <p class="mt-1 inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500"><i class="bi bi-clock-history"></i>{{ $item->updated_at?->format('d-m-Y H:i') ?? '-' }}</p>
                    </div>
                    @if ($item->isTool())
                        <x-badge variant="info">Alat</x-badge>
                    @else
                        <x-badge variant="neutral">Bahan</x-badge>
                    @endif
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Kategori</dt>
                        <dd class="mt-0.5 font-medium text-slate-700">{{ $item->category?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Satuan</dt>
                        <dd class="mt-0.5 font-medium text-slate-700">{{ $item->unit?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Stok</dt>
                        @php
                            $stockVal = (float) $item->stock;
                            if ($stockVal <= 0) { $stockClass = 'text-red-600'; }
                            elseif ($item->minimum_stock && $stockVal <= (float) $item->minimum_stock) { $stockClass = 'text-amber-600'; }
                            else { $stockClass = 'text-slate-900'; }
                        @endphp
                        <dd class="mt-0.5 font-bold {{ $stockClass }}">{{ \App\Support\QuantityFormatter::format($item->stock, $item->unit) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Status</dt>
                        <dd class="mt-0.5">
                            @if ($item->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="neutral" dot>Nonaktif</x-badge>@endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('items.show', $item) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        <i class="bi bi-eye mr-1.5"></i> Detail
                    </a>
                    @if ($canManage)
                        <a href="{{ route('items.edit', $item) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            <i class="bi bi-pencil mr-1.5"></i> Edit
                        </a>
                    @endif
                    @if ($item->isTool() && \Illuminate\Support\Facades\Route::has('item-assets.index'))
                        <a href="{{ route('item-assets.index', ['item_id' => $item->id]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50" title="Unit & QR">
                            <i class="bi bi-qr-code-scan"></i>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i class="bi bi-box-seam text-2xl"></i>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">Belum Ada Data</h3>
                                    <p class="mt-1 max-w-sm text-sm text-slate-500">Data barang belum tersedia. Tambahkan data pertama untuk memulai.</p>
                                    @if ($canManage)
                                        <div class="mt-5 w-full sm:w-auto">
                                            <x-button href="{{ route('items.create') }}" variant="primary" class="w-full sm:w-auto">
                                                <i class="bi bi-plus-lg"></i> Tambah Data
                                            </x-button>
                                        </div>
                                    @endif
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($items->hasPages())
        <div class="mt-4 flex flex-col items-center justify-between gap-2 sm:flex-row">
            <x-per-page-selector :per-page="$perPage" />
            <p class="text-xs text-slate-500 sm:text-sm">
                {{ $items->firstItem() ?? 0 }}â€“{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }}
            </p>
            {{ $items->links() }}
        </div>
    @endif

    @if ($canManage ?? false)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var checkAll = document.getElementById('bulk-check-all');
                var checks = document.querySelectorAll('.item-check');
                var count = document.getElementById('bulk-count');
                var btn = document.getElementById('bulk-toggle-btn');
                var ids = document.getElementById('bulk-items-ids');
                var form = document.getElementById('bulk-items-form');
                if (!checkAll || !form) return;

                function refresh() {
                    var selected = Array.from(checks).filter(function (c) { return c.checked; });
                    if (count) count.textContent = String(selected.length);
                    if (btn) {
                        btn.disabled = selected.length === 0;
                        btn.classList.toggle('disabled', selected.length === 0);
                    }
                }
                checkAll.addEventListener('change', function () {
                    checks.forEach(function (c) { c.checked = checkAll.checked; });
                    refresh();
                });
                checks.forEach(function (c) { c.addEventListener('change', refresh); });
                form.addEventListener('submit', function (e) {
                    var selected = Array.from(checks).filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
                    if (selected.length === 0) { e.preventDefault(); return; }
                    ids.value = selected.join(',');
                });
            });
        </script>
    @endif
@endsection
