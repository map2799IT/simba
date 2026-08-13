@extends('layouts.app')

@section('title', 'Unit Alat & QR Code')

@section('content')
    @php
        $conditionVariant = fn (string $c) => match ($c) {
            'good' => 'success', 'minor_damage' => 'warning', 'major_damage' => 'danger', default => 'neutral',
        };
        $statusVariant = fn (string $s) => match ($s) {
            'available' => 'success', 'borrowed', 'reserved' => 'primary', 'damaged', 'maintenance' => 'warning', default => 'neutral',
        };
        $isManager = auth()->user()?->hasRole('admin', 'toolman');
    @endphp
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Unit Alat & QR Code</h1>
            <p class="mt-1 text-sm text-slate-500">Unit dibuat otomatis dari Barang Masuk. Halaman ini untuk monitoring dan pencetakan QR.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (\Illuminate\Support\Facades\Route::has('items.index'))
                <x-button href="{{ route('items.index') }}" variant="secondary"><i class="bi bi-box-seam"></i> Data Alat</x-button>
            @endif
            @if (\Illuminate\Support\Facades\Route::has('stock-receipts.index'))
                <x-button href="{{ route('stock-receipts.index') }}" variant="primary"><i class="bi bi-box-arrow-in-down"></i> Barang Masuk</x-button>
            @endif
        </div>
    </div>

    @if ($selectedItem)
        <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-blue-800">Menampilkan unit untuk: <strong>{{ $selectedItem->code }} — {{ $selectedItem->name }}</strong></p>
            <x-button href="{{ route('item-assets.index') }}" variant="secondary" size="sm">Tampilkan Semua Unit</x-button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('item-assets.index') }}" class="p-4 sm:p-5">
            @if (request()->filled('item_id'))
                <input type="hidden" name="item_id" value="{{ request('item_id') }}">
            @endif
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ request('search') }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Nomor inventaris, seri, atau nama alat">
                </div>
                <div class="w-full sm:w-48">
                    <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Bengkel</label>
                    <select id="workshop_id" name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua bengkel</option>
                        @foreach ($workshops as $workshop)
                            <option value="{{ $workshop->id }}" @selected((string) request('workshop_id') === (string) $workshop->id)>{{ $workshop->code }} — {{ $workshop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <label for="condition" class="mb-1.5 block text-sm font-semibold text-slate-700">Kondisi</label>
                    <select id="condition" name="condition" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        @foreach ($conditionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('condition') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Cari</x-button>
                    <x-button href="{{ route('item-assets.index', request('item_id') ? ['item_id' => request('item_id')] : []) }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Desktop Table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Inventaris</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data Alat</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Seri</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Bengkel / Lokasi</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kondisi</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assets as $asset)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-3.5">
                                <div class="font-mono text-sm font-semibold text-slate-900">{{ $asset->asset_number }}</div>
                                <div class="text-xs text-slate-500">ID unit #{{ $asset->id }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ collect([$asset->brand, $asset->model])->filter()->implode(' / ') ?: ($asset->item?->name ?? '-') }}</div>
                                <div class="text-xs text-slate-500">{{ $asset->item?->name ?? '-' }} · {{ $asset->item?->code ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-slate-600">{{ $asset->serial_number ?: '-' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm text-slate-800">{{ $asset->workshop?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $asset->storageLocation?->name ?? 'Lokasi belum ditentukan' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5"><x-badge variant="{{ $conditionVariant($asset->condition) }}">{{ $asset->conditionLabel() }}</x-badge></td>
                            <td class="whitespace-nowrap px-5 py-3.5"><x-badge variant="{{ $statusVariant($asset->status) }}">{{ $asset->statusLabel() }}</x-badge></td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('item-assets.show', $asset) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Detail"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('item-assets.label', $asset) }}" target="_blank" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Cetak QR"><i class="bi bi-qr-code"></i></a>
                                    @if ($isManager)
                                        <a href="{{ route('item-assets.edit', $asset) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10">
                            <x-empty-state icon="bi-qr-code-scan" title="Belum ada unit alat" description="Tidak ada unit alat yang sesuai filter. Unit dibuat otomatis saat transaksi Barang Masuk diproses." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assets->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }} dari {{ $assets->total() }}</p>
                    {{ $assets->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="card-mobile space-y-3">
        @forelse ($assets as $asset)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $asset->asset_number }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700">{{ collect([$asset->brand, $asset->model])->filter()->implode(' / ') ?: ($asset->item?->name ?? '-') }}</p>
                    </div>
                    <x-badge variant="{{ $statusVariant($asset->status) }}">{{ $asset->statusLabel() }}</x-badge>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Bengkel</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $asset->workshop?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kondisi</dt><dd class="mt-0.5"><x-badge variant="{{ $conditionVariant($asset->condition) }}">{{ $asset->conditionLabel() }}</x-badge></dd></div>
                </dl>
                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('item-assets.show', $asset) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-eye mr-1.5"></i> Detail</a>
                    <a href="{{ route('item-assets.label', $asset) }}" target="_blank" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-qr-code mr-1.5"></i> QR</a>
                    @if ($isManager)
                        <a href="{{ route('item-assets.edit', $asset) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil"></i></a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-qr-code-scan" title="Belum ada unit alat" description="Tidak ada unit alat yang sesuai filter. Unit dibuat otomatis saat transaksi Barang Masuk diproses." />
            </div>
        @endforelse
        @if ($assets->hasPages())
            <div class="pt-2">{{ $assets->links() }}</div>
        @endif
    </div>
@endsection