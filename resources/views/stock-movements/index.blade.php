@extends('layouts.app')

@section('title', 'Pergerakan Stok')
@section('page-title', 'Pergerakan Stok')

@section('content')
    <x-page-header title="Pergerakan Stok" description="Ringkasan seluruh perubahan stok alat dan bahan." :breadcrumb="['Transaksi Stok', 'Pergerakan']">
        @if ($hasReceiptsRoute ?? false)
            <x-button href="{{ route('stock-receipts.index') }}" variant="primary">Barang Masuk</x-button>
        @endif
        @if ($hasIssuesRoute ?? false)
            <x-button href="{{ route('stock-issues.index') }}" variant="secondary">Barang Keluar</x-button>
        @endif
    </x-page-header>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('stock-movements.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="type" class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis Pergerakan (banyak)</label>
                    <select id="type" name="type[]" multiple size="4" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(in_array($value, (array) request('type'), true))>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="mb-1.5 block text-sm font-semibold text-slate-700">Dari Tanggal</label>
                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="date_to" class="mb-1.5 block text-sm font-semibold text-slate-700">Sampai Tanggal</label>
                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Terapkan</x-button>
                    <x-button href="{{ route('stock-movements.index') }}" variant="secondary">Reset</x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Desktop table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <x-sortable-header :label="'Tanggal'" :sort-key="'transaction_date'" :sort="$sort ?? null" :direction="$direction ?? 'asc'" />
                        <x-sortable-header :label="'Jenis'" :sort-key="'type'" :sort="$sort ?? null" :direction="$direction ?? 'asc'" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                        <x-sortable-header :label="'Jumlah'" :sort-key="'quantity'" :sort="$sort ?? null" :direction="$direction ?? 'asc'" :class="'text-right'" />
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stok</th>
                        <x-sortable-header :label="'Referensi'" :sort-key="'reference_number'" :sort="$sort ?? null" :direction="$direction ?? 'asc'" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $movement->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($movement->type === 'incoming')
                                    <x-badge variant="success" dot><i class="bi bi-arrow-down-left mr-1"></i> {{ $movement->typeLabel() }}</x-badge>
                                @elseif ($movement->type === 'outgoing')
                                    <x-badge variant="danger" dot><i class="bi bi-arrow-up-right mr-1"></i> {{ $movement->typeLabel() }}</x-badge>
                                @elseif ($movement->type === 'loan')
                                    <x-badge variant="info" dot><i class="bi bi-arrow-right mr-1"></i> {{ $movement->typeLabel() }}</x-badge>
                                @elseif ($movement->type === 'return')
                                    <x-badge variant="success" dot><i class="bi bi-arrow-left mr-1"></i> {{ $movement->typeLabel() }}</x-badge>
                                @else
                                    <x-badge variant="neutral" dot>{{ $movement->typeLabel() }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-slate-900">{{ $movement->item?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $movement->item?->code ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-600">{{ \App\Support\QuantityFormatter::format($movement->stock_before) }} → <strong class="text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->stock_after) }}</strong></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $movement->reference_number ?: '-' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $movement->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8">
                            <x-empty-state icon="bi-arrow-left-right" title="Belum ada pergerakan stok" description="Transaksi barang masuk, keluar, dan peminjaman akan muncul di sini." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile card list --}}
    <div class="card-mobile space-y-3">
        @forelse ($movements as $movement)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $movement->item?->name ?? '-' }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $movement->transaction_date?->format('d-m-Y') ?? '-' }}</p>
                    </div>
                    @if ($movement->type === 'incoming')<x-badge variant="success" dot>{{ $movement->typeLabel() }}</x-badge>
                    @elseif ($movement->type === 'outgoing')<x-badge variant="danger" dot>{{ $movement->typeLabel() }}</x-badge>
                    @elseif ($movement->type === 'loan')<x-badge variant="info" dot>{{ $movement->typeLabel() }}</x-badge>
                    @elseif ($movement->type === 'return')<x-badge variant="success" dot>{{ $movement->typeLabel() }}</x-badge>
                    @else<x-badge variant="neutral" dot>{{ $movement->typeLabel() }}</x-badge>@endif
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
    <div><dt class="text-xs text-slate-500">Jumlah</dt><dd class="mt-0.5 font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }}</dd></div>
    <div><dt class="text-xs text-slate-500">Stok</dt><dd class="mt-0.5 font-medium text-slate-700">{{ \App\Support\QuantityFormatter::format($movement->stock_before) }} → {{ \App\Support\QuantityFormatter::format($movement->stock_after) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Referensi</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $movement->reference_number ?: '-' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Petugas</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $movement->user?->name ?? '-' }}</dd></div>
                </dl>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-arrow-left-right" title="Belum ada pergerakan stok" description="Transaksi barang masuk, keluar, dan peminjaman akan muncul di sini." />
            </div>
        @endforelse
    </div>

    @if ($movements->hasPages())
        <div class="mt-5 flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p class="text-sm text-slate-500">Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }}</p>
            {{ $movements->links() }}
            <x-per-page-selector :per-page="$perPage ?? 25" />
        </div>
    @endif
@endsection
