@extends('layouts.app')
@section('title', 'Referensi Import Barang Masuk')
@section('content')
    <x-page-header title="Referensi Import Barang Masuk" description="Data master barang, kategori, satuan, dan lokasi yang dapat dipakai di Excel."
        :breadcrumb="['Transaksi Stok', 'Barang Masuk', 'Import', 'Referensi']">
        <x-button href="{{ route('stock-import.index') }}" variant="secondary"><i class="bi bi-arrow-left"></i> Kembali Ke Import</x-button>
    </x-page-header>

    <div class="mb-4 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-sm text-blue-800"><strong>Catatan:</strong> Kolom <code>kode_barang</code> harus sesuai daftar di bawah. <code>kategori</code> dan <code>satuan</code> mengikuti master (tidak ditulis di Excel). <code>lokasi</code> harus sesuai daftar lokasi jurusan.</p>
    </div>

    @if ($isAdmin)
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" class="p-4">
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Filter Jurusan untuk Lokasi</label>
                    <select name="workshop_id" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua jurusan</option>
                        @foreach ($workshops as $w)
                            <option value="{{ $w->id }}" @selected((string)$selectedWorkshopId === (string)$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- Master Barang --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Master Barang (untuk kolom kode_barang / nama)</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ $items->count() }} barang aktif.</p>
        </div>
        <div class="table-desktop overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nama Barang</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Jenis</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kategori</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-sm text-slate-700">{{ $item->code }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $item->name }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $item->type === 'tool' ? 'Alat' : 'Bahan' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $item->unit?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-mobile divide-y divide-slate-100">
            @foreach ($items as $item)
                <div class="p-4">
                    <div class="flex justify-between items-start gap-2"><p class="font-mono text-sm text-slate-700">{{ $item->code }}</p><span class="text-xs text-slate-400">{{ $item->type === 'tool' ? 'Alat' : 'Bahan' }}</span></div>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">Kategori: {{ $item->category?->name ?? '-' }} · Satuan: {{ $item->unit?->name ?? '-' }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Lokasi --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Lokasi Penyimpanan per Jurusan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Gunakan nama lokasi ini pada kolom <code>lokasi</code>.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($workshops as $w)
                @php
                    $tree = $locationTree[(int) $w->id] ?? null;
                    $map = $tree['locations'] ?? [];
                    $roots = $tree['roots'][0] ?? [];
                    $byParent = $tree['roots'] ?? [];
                @endphp
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-900">{{ $w->code }} — {{ $w->name }}</p>
                    @if ($tree === null)
                        <p class="mt-2 text-xs text-slate-400">Tidak ada lokasi.</p>
                    @else
                        <ul class="mt-2 space-y-1">
                            @forelse ($roots as $locId)
                                <li>
                                    <div class="flex items-center justify-between text-sm font-semibold text-slate-800">
                                        <span><i class="bi bi-diagram-3 mr-1 text-slate-500"></i>{{ $map[$locId]['name'] }}</span>
                                        <span class="font-mono text-xs text-slate-400">{{ $map[$locId]['code'] }}</span>
                                    </div>
                                    @php $kids = $byParent[$locId] ?? []; @endphp
                                    @if (count($kids) > 0)
                                        <ul class="mt-1 ml-4 space-y-1 border-l border-slate-200 pl-3">
                                            @foreach ($kids as $kidId)
                                                <li class="flex items-center justify-between text-sm text-slate-700">
                                                    <span><i class="bi bi-geo-alt mr-1 text-slate-400"></i>{{ $map[$kidId]['name'] }}</span>
                                                    <span class="font-mono text-xs text-slate-400">{{ $map[$kidId]['code'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @empty
                                <li class="text-xs text-slate-400">Hanya ada turunan tanpa induk? Periksa data lokasi.</li>
                            @endforelse
                        </ul>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-sm text-slate-500">Tidak ada jurusan.</div>
            @endforelse
        </div>
    </div>
@endsection
