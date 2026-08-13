@extends('layouts.app')
@section('title', 'Barang Masuk')
@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    <x-page-header title="Barang Masuk" description="Daftar per kode/transaksi. Klik Detail untuk melihat barang." :breadcrumb="['Transaksi Stok', 'Barang Masuk']">
        @if ($canReview ?? false)
            <x-button href="{{ route('stock-receipts.approvals') }}" variant="secondary"><i class="bi bi-clipboard-check"></i> Persetujuan Edit</x-button>
        @endif
        @if ($canCreate ?? false)
            <x-button href="{{ route('stock-receipts.create') }}" variant="primary"><i class="bi bi-plus-circle"></i> Tambah</x-button>
            <x-button href="{{ route('stock-import.index') }}" variant="soft-success"><i class="bi bi-upload"></i> Import</x-button>
        @endif
    </x-page-header>
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white"><i class="bi bi-info-lg"></i></span>
        <div class="text-sm text-blue-800"><p class="font-semibold">Aturan Barang Masuk</p><p class="mt-1 text-blue-700">Satu baris mewakili satu kode/transaksi. Nama masing-masing barang tampil pada halaman Detail.</p></div>
    </div>

    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('stock-receipts.index') }}" class="p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cari kode, tanggal, atau nama barang...">
                    </div>
                    @if ($isAdmin ?? false)
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400"><i class="bi bi-geo-alt"></i></span>
                        <select name="workshop_id" class="rounded-xl border-slate-300 bg-white py-2.5 pl-3 pr-8 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Semua jurusan</option>@foreach ($workshops as $workshop)<option value="{{ $workshop->id }}" @selected((string) request('workshop_id') === (string) $workshop->id)>{{ $workshop->code }}</option>@endforeach</select>
                    </div>
                    @endif
                </div>
                <input type="hidden" name="sort" value="{{ $sort ?? '' }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('stock-receipts.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <x-sortable-header :label="'Kode Barang Masuk'" :sort-key="'receipt_code'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header :label="'Tanggal'" :sort-key="'transaction_date'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header :label="'Jumlah Alat/Bahan'" :sort-key="'quantity'" :sort="$sort" :direction="$direction" :class="'text-right'" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><i class="bi bi-clipboard-check mr-1"></i>Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-4"><div class="font-mono text-sm font-semibold text-slate-900">{{ $movement->receipt_code ?: '-' }}</div><div class="text-xs text-slate-400">{{ $movement->reference_number ?: '-' }}</div></td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $movement->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-sm font-bold text-blue-700"><i class="bi bi-box-seam"></i>{{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }}</span><span class="block text-xs text-slate-400">{{ $movement->item?->unit?->name }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4">@if ($movement->pendingChangeRequest)<x-badge variant="warning" dot>Menunggu</x-badge>@else<x-badge variant="success" dot>Aktif</x-badge>@endif</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><div class="flex items-center justify-end gap-1.5"><a href="{{ route('stock-receipts.print', $movement) }}" target="_blank" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Cetak bukti"><i class="bi bi-printer"></i></a><a href="{{ route('stock-receipts.show', $movement) }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50"><i class="bi bi-eye"></i>Detail</a><a href="{{ route('stock-receipts.edit', $movement) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil"></i></a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10"><x-empty-state icon="bi-box-arrow-in-down" title="Belum ada transaksi Barang Masuk" description="Tambahkan transaksi pertama." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-mobile space-y-3">
        @forelse ($movements as $movement)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-mono text-sm font-semibold text-slate-900">{{ $movement->receipt_code ?: '-' }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $movement->transaction_date?->format('d-m-Y') ?? '-' }}</p></div>@if ($movement->pendingChangeRequest)<x-badge variant="warning" dot>Menunggu</x-badge>@else<x-badge variant="success" dot>Aktif</x-badge>@endif</div>
                <div class="mt-3"><span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-sm font-bold text-blue-700"><i class="bi bi-box-seam"></i>{{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }}</span><span class="ml-1 text-xs text-slate-400">{{ $movement->item?->unit?->name }}</span></div>
                <div class="mt-4 flex items-center gap-2"><a href="{{ route('stock-receipts.print', $movement) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-printer mr-1.5"></i></a><a href="{{ route('stock-receipts.show', $movement) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-700"><i class="bi bi-eye mr-1.5"></i>Detail</a><a href="{{ route('stock-receipts.edit', $movement) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil mr-1.5"></i>Edit</a></div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6"><x-empty-state icon="bi-box-arrow-in-down" title="Belum ada transaksi Barang Masuk" description="Tambahkan transaksi pertama." /></div>
        @endforelse
    </div>

    @if ($movements->hasPages())
        <div class="mt-5 flex flex-col items-center justify-between gap-3 sm:flex-row"><p class="text-sm text-slate-500">Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }}</p>{{ $movements->links() }}<x-per-page-selector :per-page="$perPage" /></div>
    @endif
@endsection