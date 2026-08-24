@extends('layouts.app')
@section('title', $reportTitle)
@section('content')

<x-page-header
    title="{{ $reportTitle }}{{ request('year') ? ' ' . request('year') : '' }}"
    description="Laporan penerimaan barang masuk dikelompokkan per nama barang."
    :breadcrumb="['Laporan', $reportTitle]"
>
    <div class="flex flex-wrap gap-2">
        <x-button href="{{ route('reports.stock-receipts.excel', request()->query()) }}" variant="soft-success">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </x-button>
        <x-button href="{{ route('reports.stock-receipts.pdf', request()->query()) }}" variant="soft-danger">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </x-button>
    </div>
</x-page-header>

{{-- Summary cards --}}
<div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Transaksi</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total_transactions']) }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis Barang</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['unique_items']) }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Kuantitas</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total_quantity'], 2, ',', '.') }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Nilai</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</p>
    </div>
</div>

{{-- Filter --}}
<section class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ route('reports.stock-receipts') }}">
        <div class="flex flex-wrap items-end gap-3 p-4">
            {{-- Search --}}
            <div class="relative min-w-[200px] flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="bi bi-search"></i>
                </span>
                <input
                    name="search"
                    value="{{ request('search') }}"
                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="Nama barang, kode, referensi, merek..."
                >
            </div>

            {{-- Date from --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500">Dari Tanggal</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
            </div>

            {{-- Date to --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500">Sampai Tanggal</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
            </div>

            {{-- Workshop --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500">Bengkel</label>
                <select
                    name="workshop_id"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Semua Bengkel</option>
                    @foreach ($workshops as $ws)
                        <option value="{{ $ws->id }}" @selected(request('workshop_id') == $ws->id)>
                            {{ $ws->code }} — {{ $ws->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Category --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500">Kategori</label>
                <select
                    name="item_category_id"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('item_category_id') == $cat->id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Sort --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500">Urutkan</label>
                <select
                    name="sort"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="item_name" @selected(request('sort', 'item_name') === 'item_name')>Nama Barang</option>
                    <option value="transaction_date" @selected(request('sort') === 'transaction_date')>Tanggal</option>
                    <option value="quantity" @selected(request('sort') === 'quantity')>Kuantitas</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500">Arah</label>
                <select
                    name="direction"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="asc" @selected(request('direction', 'asc') === 'asc')>A → Z / Terlama</option>
                    <option value="desc" @selected(request('direction') === 'desc')>Z → A / Terbaru</option>
                </select>
            </div>

            <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Filter</x-button>
            <x-button href="{{ route('reports.stock-receipts') }}" variant="secondary">Reset</x-button>
        </div>
    </form>
</section>

{{-- Table --}}
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-base font-semibold text-slate-900">{{ $reportTitle }}{{ request('year') ? ' ' . request('year') : '' }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $rows->total() }} entri ditemukan
            @if(request('year'))
                — tahun {{ request('year') }}
            @endif
            @if(request('date_from') || request('date_to'))
                — periode
                @if(request('date_from')) {{ \Carbon\Carbon::parse(request('date_from'))->isoFormat('D MMM YYYY') }} @endif
                @if(request('date_to')) s/d {{ \Carbon\Carbon::parse(request('date_to'))->isoFormat('D MMM YYYY') }} @endif
            @endif
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Masuk</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Penerimaan</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Barang</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Barang</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Bengkel</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Merek / Model</th>
                    <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jml Masuk</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kondisi</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sumber Dana</th>
                    <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga Satuan</th>
                    <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total Nilai</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Referensi</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sumber</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi Simpan</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Petugas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $lastItemName = null; @endphp
                @forelse ($rows as $row)
                    @php
                        $itemName  = $row->item?->name ?? '-';
                        $newGroup  = $itemName !== $lastItemName;
                        $lastItemName = $itemName;
                        $qty       = (float) $row->quantity;
                        $unitPrice = (float) ($row->unit_price ?? 0);
                        $total     = $unitPrice ? $qty * $unitPrice : null;
                        $brand     = $row->brand ?? $row->item?->brand;
                        $model     = $row->model ?? $row->item?->model;
                        $condMap   = ['good' => 'Baik', 'damaged' => 'Rusak', 'needs_repair' => 'Perlu Perbaikan'];
                        $condLabel = $condMap[$row->condition ?? ''] ?? $row->condition ?? '-';
                    @endphp
                    @if ($newGroup)
                        <tr class="bg-blue-50/60">
                            <td colspan="17" class="px-4 py-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-blue-700">
                                    <i class="bi bi-box-seam me-1"></i>{{ $itemName }}
                                </span>
                                <span class="ml-2 text-xs text-slate-500">
                                    {{ $row->item?->code ?? '' }}
                                    @if ($row->item?->category) · {{ $row->item->category->name }} @endif
                                    @if ($row->item?->workshop) · {{ $row->item->workshop->code }} @endif
                                </span>
                            </td>
                        </tr>
                    @endif
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                            {{ $row->transaction_date?->isoFormat('D MMM YYYY') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $row->receipt_code ?? '-' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $row->item?->code ?? '-' }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $itemName }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->item?->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->item?->workshop?->code ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($brand || $model)
                                {{ implode(' / ', array_filter([$brand, $model])) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-700">
                            +{{ number_format($qty, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->item?->unit?->code ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $condLabel }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->fund_source ?? '-' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">
                            @if ($unitPrice)
                                Rp {{ number_format($unitPrice, 0, ',', '.') }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">
                            @if ($total)
                                Rp {{ number_format($total, 0, ',', '.') }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->reference_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->source ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->storageLocation?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->user?->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="17" class="px-5 py-12">
                            <x-empty-state
                                icon="bi-box-arrow-in-down"
                                title="Belum ada data barang masuk"
                                description="Tidak ada penerimaan barang yang sesuai dengan filter yang dipilih."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $rows->links() }}
        </div>
    @endif
</section>

@endsection
