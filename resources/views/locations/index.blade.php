@extends('layouts.app')

@section('title', 'Lokasi Penyimpanan')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    @include('locations._inventory-menu-link')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Lokasi Penyimpanan</h1>
            <p class="mt-1 text-sm text-slate-500">Ruangan, lemari, rak, laci, dan kotak penyimpanan pada setiap jurusan.</p>
        </div>
        @if ($canManage)
            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('locations.create', ['mode' => 'root']) }}" variant="primary"><i class="bi bi-building-add"></i> Tambah Lokasi Induk</x-button>
                <x-button href="{{ route('locations.create', ['mode' => 'child']) }}" variant="secondary"><i class="bi bi-diagram-3"></i> Tambah Lokasi Turunan</x-button>
            </div>
        @endif
    </div>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('locations.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ request('search') }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Kode atau nama lokasi">
                </div>
                @if (auth()->user()->role === 'admin')
                    <div class="w-full sm:w-48">
                        <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Jurusan</label>
                        <select id="workshop_id" name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua jurusan</option>
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}" @selected((int) $selectedWorkshopId === (int) $workshop->id)>{{ $workshop->code }} â€” {{ $workshop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="w-full sm:w-40">
                    <label for="type" class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis</label>
                    <select id="type" name="type" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua jenis</option>
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Cari</x-button>
                    <x-button href="{{ route('locations.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                </div>
            </div>
        </form>
    </div>

    @if ($canManage)
        <form id="bulk-locations-form" method="POST" action="{{ route('locations.bulk.toggle-status') }}" class="mb-4">
            @csrf
            <input type="hidden" name="ids" id="bulk-locations-ids">
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
                        @if ($canManage)
                            <th class="w-10 px-4 py-3.5"><input type="checkbox" id="bulk-check-all" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"></th>
                        @endif
                        <x-sortable-header label="Kode" :sort-key="'code'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header label="Lokasi" :sort-key="'name'" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Induk</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Isi Langsung</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($locations as $location)
                        <tr class="transition-colors hover:bg-slate-50">
                            @if ($canManage)
                                <td class="px-4 py-3.5"><input type="checkbox" class="item-check h-4 w-4 rounded border-slate-300 text-blue-600" value="{{ $location->id }}"></td>
                            @endif
                            <td class="px-5 py-3.5 font-mono text-sm font-semibold text-slate-700">{{ $location->code }}</td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ $location->name }}</div>
                                <div class="text-xs text-slate-500">{{ $location->typeLabel() }} Â· {{ $location->children_count }} turunan</div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-slate-600">{{ $location->workshop?->code ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @if ($location->parent)
                                    <a href="{{ route('locations.show', $location->parent) }}" class="text-sm text-blue-600 hover:underline">{{ $location->parent->code }} â€” {{ $location->parent->name }}</a>
                                @else
                                    <x-badge variant="neutral">Lokasi Induk</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $location->tool_units_count }} unit alat</span>
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $location->material_items_count }} jenis bahan</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if ($location->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="neutral" dot>Nonaktif</x-badge>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('locations.show', $location) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Detail"><i class="bi bi-eye"></i></a>
                                    @if ($canPrint)
                                        @include('locations._inventory-action-buttons', ['location' => $location, 'buttonSize' => 'sm', 'includeChildren' => true])
                                    @endif
                                    @if ($canManage)
                                        <a href="{{ route('locations.create', ['mode' => 'child', 'parent_id' => $location->id]) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Tambah Turunan"><i class="bi bi-diagram-3"></i></a>
                                        <a href="{{ route('locations.edit', $location) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10">
                            <x-empty-state icon="bi-geo-alt" title="Belum ada lokasi penyimpanan" description="Buat lokasi induk pertama untuk mulai mencatat penyimpanan.">
                                @if ($canManage)<x-button href="{{ route('locations.create', ['mode' => 'root']) }}" variant="primary"><i class="bi bi-plus-lg"></i> Buat Lokasi Induk Pertama</x-button>@endif
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($locations->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $locations->firstItem() ?? 0 }}â€“{{ $locations->lastItem() ?? 0 }} dari {{ $locations->total() }}</p>
                    <div class="flex items-center gap-3">
                        <x-per-page-selector :per-page="$perPage" />
                        {{ $locations->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="card-mobile space-y-3">
        @forelse ($locations as $location)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $location->name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $location->code }} Â· {{ $location->workshop?->code ?? '-' }}</p>
                    </div>
                    @if ($location->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="neutral" dot>Nonaktif</x-badge>@endif
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Jenis</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $location->typeLabel() }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Induk</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $location->parent?->code ?? 'Lokasi Induk' }}</dd></div>
                </dl>
                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('locations.show', $location) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-eye mr-1.5"></i> Detail</a>
                    @if ($canManage)
                        <a href="{{ route('locations.edit', $location) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil mr-1.5"></i> Edit</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-geo-alt" title="Belum ada lokasi penyimpanan" description="Buat lokasi induk pertama untuk mulai mencatat penyimpanan." />
            </div>
        @endforelse
        @if ($locations->hasPages())
            <div class="pt-2">{{ $locations->links() }}</div>
        @endif
    </div>

    @if ($canManage)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var checkAll = document.getElementById('bulk-check-all');
                var checks = document.querySelectorAll('.item-check');
                var count = document.getElementById('bulk-count');
                var btn = document.getElementById('bulk-toggle-btn');
                var ids = document.getElementById('bulk-locations-ids');
                var form = document.getElementById('bulk-locations-form');
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