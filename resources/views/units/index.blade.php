@extends('layouts.app')

@section('title', 'Satuan')
@section('page-title', 'Satuan')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    @php $canManage = auth()->user()->hasRole('admin', 'toolman'); @endphp

    <x-page-header title="Satuan Barang" description="Kelola satuan alat dan bahan." :breadcrumb="['Master Sistem', 'Satuan']">
        @if ($canManage)
            <x-button href="{{ route('units.create') }}" variant="primary"><i class="bi bi-plus-circle"></i> Tambah Satuan</x-button>
        @endif
    </x-page-header>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('units.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                        <input id="search" type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Kode atau nama satuan">
                    </div>
                </div>
                <div class="w-full sm:w-36">
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Filter</x-button>
                    <x-button href="{{ route('units.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                </div>
            </div>
        </form>
    </div>

    @if ($canManage)
        <form id="bulk-units-form" method="POST" action="{{ route('units.bulk.toggle-status') }}" class="mb-4">
            @csrf
            <input type="hidden" name="ids" id="bulk-units-ids">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <span class="text-sm text-slate-600"><span id="bulk-count" class="font-semibold text-blue-600">0</span> dipilih</span>
                <button id="bulk-toggle-btn" type="submit" disabled class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-blue-300 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-40">
                    <i class="bi bi-toggle-off"></i> Ubah Status Terpilih
                </button>
            </div>
        </form>
    @endif

    {{-- Desktop table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        @if ($canManage)
                            <th class="w-10 px-4 py-3.5"><input type="checkbox" id="bulk-check-all" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"></th>
                        @endif
                        <x-sortable-header :label="'Kode'" :sort-key="'code'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header :label="'Nama Satuan'" :sort-key="'name'" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Desimal</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        @if ($canManage)<th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($units as $unit)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            @if ($canManage)
                                <td class="px-4 py-3.5"><input type="checkbox" class="item-check h-4 w-4 rounded border-slate-300 text-blue-600" value="{{ $unit->id }}"></td>
                            @endif
                            <td class="whitespace-nowrap px-5 py-4"><x-badge variant="neutral">{{ $unit->code }}</x-badge></td>
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $unit->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($unit->allows_decimal)
                                    <x-badge variant="info">Diizinkan</x-badge>
                                @else
                                    <x-badge variant="neutral">Bilangan bulat</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($unit->description ?: '-', 60) }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($unit->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="danger" dot>Nonaktif</x-badge>@endif
                            </td>
                            @if ($canManage)
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('units.edit', $unit) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <x-confirm-modal
                                            title="Ubah Status Satuan?"
                                            description="{{ $unit->is_active ? 'Satuan ini akan dinonaktifkan.' : 'Satuan ini akan diaktifkan kembali.' }}"
                                            :confirmLabel="($unit->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan')"
                                            :variant="($unit->is_active ? 'warning' : 'primary')"
                                            :formAction="route('units.toggle-status', $unit)"
                                            :formMethod="('PATCH')"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium transition hover:bg-slate-50"
                                        >
                                            @if ($unit->is_active)<i class="bi bi-toggle-on text-emerald-500"></i>@else<i class="bi bi-toggle-off text-slate-400"></i>@endif
                                        </x-confirm-modal>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="px-5 py-8">
                            <x-empty-state icon="bi-rulers" title="Belum ada satuan barang" description="Tambahkan satuan pertama untuk alat dan bahan bengkel.">
                                @if ($canManage)<x-button href="{{ route('units.create') }}" variant="primary"><i class="bi bi-plus-circle"></i> Tambah Satuan</x-button>@endif
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile card list --}}
    <div class="card-mobile space-y-3">
        @forelse ($units as $unit)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $unit->name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $unit->code }}</p>
                    </div>
                    @if ($unit->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="danger" dot>Nonaktif</x-badge>@endif
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Jumlah Desimal</dt><dd class="mt-0.5">@if ($unit->allows_decimal)<x-badge variant="info">Diizinkan</x-badge>@else<x-badge variant="neutral">Bilangan bulat</x-badge>@endif</dd></div>
                    <div><dt class="text-xs text-slate-500">Keterangan</dt><dd class="mt-0.5 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($unit->description ?: '-', 40) }}</dd></div>
                </dl>
                @if ($canManage)
                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('units.edit', $unit) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil mr-1.5"></i> Edit</a>
                        <x-confirm-modal
                            title="Ubah Status Satuan?"
                            description="{{ $unit->is_active ? 'Satuan ini akan dinonaktifkan.' : 'Satuan ini akan diaktifkan kembali.' }}"
                            :confirmLabel="($unit->is_active ? 'Nonaktifkan' : 'Aktifkan')"
                            :variant="($unit->is_active ? 'warning' : 'primary')"
                            :formAction="route('units.toggle-status', $unit)"
                            :formMethod="('PATCH')"
                            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium transition hover:bg-slate-50"
                        >
                            @if ($unit->is_active)
                                <i class="bi bi-toggle-on text-emerald-500 mr-1.5"></i> Nonaktifkan
                            @else
                                <i class="bi bi-toggle-off text-slate-400 mr-1.5"></i> Aktifkan
                            @endif
                        </x-confirm-modal>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-rulers" title="Belum ada satuan barang" description="Tambahkan satuan pertama untuk alat dan bahan bengkel." />
            </div>
        @endforelse
    </div>

    @if ($units->hasPages())
        <div class="mt-5 flex flex-col items-center justify-between gap-3 sm:flex-row">
            <x-per-page-selector :per-page="$perPage" />
            <p class="text-sm text-slate-500">Menampilkan {{ $units->firstItem() ?? 0 }}â€“{{ $units->lastItem() ?? 0 }} dari {{ $units->total() }}</p>
            {{ $units->links() }}
        </div>
    @endif

    @if ($canManage)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var checkAll = document.getElementById('bulk-check-all');
                var checks = document.querySelectorAll('.item-check');
                var count = document.getElementById('bulk-count');
                var btn = document.getElementById('bulk-toggle-btn');
                var ids = document.getElementById('bulk-units-ids');
                var form = document.getElementById('bulk-units-form');
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
