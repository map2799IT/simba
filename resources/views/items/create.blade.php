@extends('layouts.app')

@section('title', 'Tambah Master Barang')
@section('page-title', 'Tambah Master Barang')

@section('content')
    <x-page-header
        title="Tambah Master Barang"
        description="Satu master digunakan untuk seluruh merek, model, tahun pembelian, bengkel, dan lokasi."
        :breadcrumb="['Barang', 'Tambah']"
    >
        <x-button href="{{ route('items.index') }}" variant="secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </x-button>
    </x-page-header>

    {{-- Info banner --}}
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white">
            <i class="bi bi-info-lg"></i>
        </span>
        <div class="text-sm text-blue-800">
            <p class="font-semibold">Master hanya berisi nama, kategori, dan satuan.</p>
            <p class="mt-1 text-blue-700">Jenis Alat/Bahan mengikuti kategori. Kode master dibuat tanpa tahun, misalnya ALT-0001 atau BHN-0001. Merek, model, spesifikasi, tahun, bengkel, lokasi, harga, kondisi, sumber dana, dan foto dicatat pada Barang Masuk.</p>
        </div>
    </div>

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </span>
            <div class="text-sm text-red-800">
                <p class="font-semibold">Data belum lengkap</p>
                <p class="mt-1 text-red-700">Periksa kembali field yang ditandai merah.</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('items.store') }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {{-- Section header --}}
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Identitas Katalog</h2>
                <p class="mt-0.5 text-sm text-slate-500">Informasi dasar master barang.</p>
            </div>

            {{-- Form body --}}
            <div class="p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Nama Barang --}}
                    <div class="sm:col-span-full">
                        <label for="name" class="block text-sm font-semibold text-slate-700">
                            Nama Barang <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
                            placeholder="Contoh: Monitor, Tang Crimping, Kabel UTP"
                            maxlength="150"
                            required
                            autofocus
                        >
                        <p class="mt-1 text-xs text-slate-500">Nama barang yang akan tampil di katalog.</p>
                        @error('name')
                            <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label for="item_category_id" class="block text-sm font-semibold text-slate-700">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="item_category_id"
                            name="item_category_id"
                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('item_category_id') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
                            required
                        >
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('item_category_id') === (string) $category->id)>
                                    [{{ $category->applies_to === 'tool' ? 'Alat' : 'Bahan' }}] {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Jenis master ditentukan otomatis dari kategori.</p>
                        @error('item_category_id')
                            <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Satuan --}}
                    <div>
                        <label for="unit_id" class="block text-sm font-semibold text-slate-700">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="unit_id"
                            name="unit_id"
                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('unit_id') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
                            required
                        >
                            <option value="">Pilih satuan</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id')
                            <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Sticky action footer --}}
            <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end sm:px-6">
                <a href="{{ route('items.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" :disabled="submitting" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                    <span x-show="!submitting"><i class="bi bi-check-lg mr-1.5"></i> Simpan Master Barang</span>
                    <span x-show="submitting" x-cloak><svg class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Menyimpan...</span>
                </button>
            </div>
        </div>
    </form>
@endsection
