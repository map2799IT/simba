@extends('layouts.app')

@section('title', 'Dashboard Waka Sarpras')
@section('page-title', 'Dashboard Waka Sarpras')

@section('content')
    <x-page-header title="Dashboard Wakil Sarana dan Prasarana" description="Ringkasan inventaris seluruh jurusan. Role ini bersifat lihat, print, dan export tanpa hak perubahan data." :breadcrumb="['Dashboard']">
        @if (\Illuminate\Support\Facades\Route::has('locations.inventory.menu'))
            <x-button href="{{ route('locations.inventory.menu') }}" variant="secondary"><i class="bi bi-geo-alt"></i> Lokasi & Print</x-button>
        @endif
    </x-page-header>

    @php
        $cards = [
            ['label' => 'Jurusan Aktif', 'value' => $stats['workshops'], 'icon' => 'bi-building', 'color' => 'blue'],
            ['label' => 'Lokasi Aktif', 'value' => $stats['locations'], 'icon' => 'bi-geo-alt', 'color' => 'blue'],
            ['label' => 'Master Barang', 'value' => $stats['master_items'], 'icon' => 'bi-boxes', 'color' => 'blue'],
            ['label' => 'Unit Alat', 'value' => $stats['tool_units'], 'icon' => 'bi-tools', 'color' => 'blue'],
            ['label' => 'Alat Tersedia', 'value' => $stats['available_tools'], 'icon' => 'bi-check-circle', 'color' => 'emerald'],
            ['label' => 'Alat Dipinjam', 'value' => $stats['borrowed_tools'], 'icon' => 'bi-arrow-left-right', 'color' => 'amber'],
            ['label' => 'Peminjaman Aktif', 'value' => $stats['active_loans'], 'icon' => 'bi-journal-text', 'color' => 'amber'],
            ['label' => 'Terlambat', 'value' => $stats['overdue_loans'], 'icon' => 'bi-clock-history', 'color' => 'red'],
            ['label' => 'Kerusakan Terbuka', 'value' => $stats['open_damages'], 'icon' => 'bi-exclamation-triangle', 'color' => 'red'],
        ];
        $colorMap = [
            'blue' => 'bg-blue-50 text-blue-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'red' => 'bg-red-50 text-red-600',
        ];
        $valueColorMap = [
            'blue' => 'text-slate-900',
            'emerald' => 'text-emerald-600',
            'amber' => 'text-amber-600',
            'red' => 'text-red-600',
        ];
    @endphp

    {{-- KPI Cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 xl:grid-cols-5">
        @foreach ($cards as $card)
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-1.5 text-xl font-bold {{ $valueColorMap[$card['color']] }} sm:text-2xl">{{ number_format((int) $card['value'], 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $colorMap[$card['color']] }}"><i class="bi {{ $card['icon'] }} text-lg"></i></div>
            </div>
        @endforeach
    </div>

    {{-- Report Links --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Menu Laporan</h2>
        </div>
        <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 xl:grid-cols-5">
            @php
                $reportLinks = [
                    ['route' => 'reports.inventory', 'label' => 'Inventaris', 'icon' => 'bi-bar-chart-line'],
                    ['route' => 'reports.stock', 'label' => 'Stok', 'icon' => 'bi-boxes'],
                    ['route' => 'reports.loans', 'label' => 'Peminjaman', 'icon' => 'bi-clipboard-data'],
                    ['route' => 'reports.damages', 'label' => 'Kerusakan', 'icon' => 'bi-graph-down-arrow'],
                    ['route' => 'reports.stock-movements', 'label' => 'Pergerakan', 'icon' => 'bi-activity'],
                ];
            @endphp
            @foreach ($reportLinks as $link)
                @if (\Illuminate\Support\Facades\Route::has($link['route']))
                    <a href="{{ route($link['route']) }}" class="flex min-h-11 flex-col items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-4 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                        <i class="bi {{ $link['icon'] }} text-xl"></i>
                        {{ $link['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Workshop Summary --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Ringkasan Per Jurusan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Alat Tersedia</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Peminjaman Aktif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($workshopSummaries as $workshop)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            <td class="px-5 py-3.5"><div class="font-bold text-slate-900">{{ $workshop->code }}</div><div class="text-xs text-slate-500">{{ $workshop->name }}</div></td>
                            <td class="px-5 py-3.5 text-right text-sm font-semibold text-slate-700">{{ $workshop->locations }}</td>
                            <td class="px-5 py-3.5 text-right text-sm font-semibold text-emerald-600">{{ $workshop->available_assets }}</td>
                            <td class="px-5 py-3.5 text-right text-sm font-semibold text-amber-600">{{ $workshop->active_loans }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8"><x-empty-state icon="bi-building" title="Belum ada data jurusan" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Loans + Movements --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-base font-semibold text-slate-900">Peminjaman Terbaru</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Peminjam</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentLoans as $loan)
                            <tr class="transition-colors hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-sm font-semibold text-slate-700">{{ $loan->code }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $loan->borrower_name ?? '-' }}</td>
                                <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ $loan->workshop_code ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', (string) $loan->status)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-base font-semibold text-slate-900">Pergerakan Stok Terbaru</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis/Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentMovements as $movement)
                            <tr class="transition-colors hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $movement->transaction_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $movement->item_name ?? '-' }}</td>
                                <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ $movement->workshop_code ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $movement->type }} / {{ $movement->quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
