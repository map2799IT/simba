@extends('layouts.app')

@section('title', 'Laporan Inventaris')
@section('page-title', 'Laporan Inventaris')

@section('content')
    @php
        $money = static fn (mixed $value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $quantity = static function (mixed $value, mixed $allowsDecimal): string {
            if (class_exists(\App\Support\QuantityFormatter::class)) {
                return \App\Support\QuantityFormatter::format($value, (bool) $allowsDecimal);
            }
            return (bool) $allowsDecimal
                ? rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',')
                : number_format((float) $value, 0, ',', '.');
        };
        $conditionLabel = static fn (mixed $value): string => match ($value) {
            'good' => 'Baik', 'minor_damage' => 'Rusak Ringan', 'major_damage' => 'Rusak Berat', 'mixed' => 'Beragam',
            default => ucfirst(str_replace('_', ' ', (string) $value)),
        };
        $statusLabel = static fn (mixed $value): string => match ($value) {
            'available' => 'Tersedia', 'out_of_stock' => 'Stok Habis',
            default => ucfirst(str_replace('_', ' ', (string) $value)),
        };
        $conditionVariant = static fn (mixed $value): string => match ($value) {
            'good' => 'success', 'minor_damage' => 'warning', 'major_damage' => 'danger',
            default => 'neutral',
        };
        $statusVariant = static fn (mixed $value): string => match ($value) {
            'available' => 'success', 'out_of_stock' => 'danger',
            default => 'neutral',
        };
        $exportQuery = array_filter([
            'search' => request('search'),
            'workshop_id' => $selectedWorkshopId,
            'item_category_id' => request('item_category_id'),
            'type' => request('type'),
            'condition' => request('condition'),
            'status' => request('status'),
        ], static fn ($value): bool => $value !== null && $value !== '');
    @endphp

    <x-page-header title="Laporan Inventaris" description="Jurusan dan lokasi diambil dari unit alat serta transaksi Barang Masuk." :breadcrumb="['Laporan', 'Inventaris']">
        @if (\Illuminate\Support\Facades\Route::has('reports.export.excel'))
            <x-button href="{{ route('reports.export.excel', $exportQuery) }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Excel</x-button>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('reports.export.pdf'))
            <x-button href="{{ route('reports.export.pdf', $exportQuery) }}" variant="soft-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</x-button>
        @endif
    </x-page-header>

    @if ($accessWarning)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <p class="text-sm text-amber-800">{{ $accessWarning }}</p>
        </div>
    @endif

    @if ($isWorkshopRestricted)
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <i class="bi bi-info-circle-fill text-blue-500"></i>
            <p class="text-sm text-blue-800">Laporan dibatasi otomatis pada jurusan akun: <strong>{{ $workshops->first()?->code ?? '-' }}</strong>.</p>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total Data</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format((int) $summary['total_items'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Data Alat</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ number_format((int) $summary['tools'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Data Bahan</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format((int) $summary['materials'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total Nilai</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ $money($summary['total_value']) }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('reports.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                        <input id="search" type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Kode, nama, merek, atau model">
                    </div>
                </div>
                <div class="w-full lg:w-44">
                    <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Jurusan</label>
                    <select id="workshop_id" name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" @disabled($isWorkshopRestricted)>
                        @unless($isWorkshopRestricted)<option value="">Semua jurusan</option>@endunless
                        @foreach ($workshops as $workshop)
                            <option value="{{ $workshop->id }}" @selected((int) $selectedWorkshopId === (int) $workshop->id)>{{ $workshop->code }} — {{ $workshop->name }}</option>
                        @endforeach
                    </select>
                    @if ($isWorkshopRestricted && $selectedWorkshopId)<input type="hidden" name="workshop_id" value="{{ $selectedWorkshopId }}">@endif
                </div>
                <div class="w-full lg:w-40">
                    <label for="item_category_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori</label>
                    <select id="item_category_id" name="item_category_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('item_category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full lg:w-36">
                    <label for="type" class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis</label>
                    <select id="type" name="type" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        <option value="tool" @selected(request('type') === 'tool')>Alat</option>
                        <option value="material" @selected(request('type') === 'material')>Bahan</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Filter</x-button>
                    <x-button href="{{ route('reports.index') }}" variant="secondary">Reset</x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col items-start justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Daftar Inventaris</h2>
                <p class="mt-0.5 text-sm text-slate-500">Ditemukan {{ number_format((int) $items->total(), 0, ',', '.') }} data barang.</p>
            </div>
            <div class="text-sm font-bold text-slate-900">Total nilai: {{ $money($summary['total_value']) }}</div>
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto sm:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan/Lokasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kondisi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stok</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm font-semibold text-slate-700">{{ $item->code }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $item->name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->report_brand ?: '-' }}@if ($item->report_model && $item->report_model !== '-') / {{ $item->report_model }}@endif</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->category_name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-700">{{ $item->report_workshop_code }}</div>
                                <div class="text-xs text-slate-500">{{ $item->report_location_name }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><x-badge variant="{{ $conditionVariant($item->report_condition) }}" dot>{{ $conditionLabel($item->report_condition) }}</x-badge></td>
                            <td class="whitespace-nowrap px-4 py-3"><x-badge variant="{{ $statusVariant($item->report_status) }}" dot>{{ $statusLabel($item->report_status) }}</x-badge></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-slate-900">{{ $quantity($item->report_stock, $item->allows_decimal) }} <span class="font-normal text-slate-500">{{ $item->unit_symbol ?: $item->unit_name }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-600">{{ $money($item->report_unit_price) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-slate-900">{{ $money($item->report_inventory_value) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-8">
                            <x-empty-state icon="bi-bar-chart-line" title="Tidak ada data inventaris" description="Tidak ditemukan data pada jurusan atau filter yang dipilih." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card list --}}
        <div class="divide-y divide-slate-100 sm:hidden">
            @forelse ($items as $item)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-xs text-slate-500">{{ $item->code }}</p>
                            <p class="truncate font-semibold text-slate-900">{{ $item->name }}</p>
                        </div>
                        <x-badge variant="{{ $statusVariant($item->report_status) }}" dot>{{ $statusLabel($item->report_status) }}</x-badge>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Kategori</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $item->category_name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Jurusan</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $item->report_workshop_code }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Stok</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $quantity($item->report_stock, $item->allows_decimal) }} {{ $item->unit_symbol ?: $item->unit_name }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nilai</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $money($item->report_inventory_value) }}</dd></div>
                    </dl>
                    <div class="mt-2"><x-badge variant="{{ $conditionVariant($item->report_condition) }}" dot>{{ $conditionLabel($item->report_condition) }}</x-badge></div>
                </div>
            @empty
                <x-empty-state icon="bi-bar-chart-line" title="Tidak ada data inventaris" description="Tidak ditemukan data pada jurusan atau filter yang dipilih." />
            @endforelse
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }}</p>
                    {{ $items->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
