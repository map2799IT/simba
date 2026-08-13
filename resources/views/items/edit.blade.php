@extends('layouts.app')

@section('title', 'Edit Master Barang')
@section('page-title', 'Edit Master Barang')

@section('content')
    <x-page-header
        title="Edit Master Barang"
        description="{{ $item->code }} — {{ $item->name }}"
        :breadcrumb="['Barang', 'Edit']"
    >
        <x-button href="{{ route('items.index') }}" variant="secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </x-button>
    </x-page-header>

    {{-- Warning banner --}}
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </span>
        <div class="text-sm text-amber-800">
            <p class="font-semibold">Hanya identitas master yang dapat diedit.</p>
            <p class="mt-1 text-amber-700">Merek, model, spesifikasi, tahun, bengkel, lokasi, harga, kondisi, sumber dana, dan foto mengikuti masing-masing transaksi Barang Masuk.</p>
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

    <form method="POST" action="{{ route('items.update', $item) }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {{-- Section: Read-only info --}}
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Informasi Sistem</h2>
                <p class="mt-0.5 text-sm text-slate-500">Data berikut tidak dapat diubah.</p>
            </div>

            <div class="p-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Master</dt>
                        <dd class="mt-1 font-mono text-sm font-bold text-slate-900">{{ $item->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ $item->typeLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok Total</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($item->stock, $item->unit) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Section: Editable fields --}}
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Identitas Katalog</h2>
                <p class="mt-0.5 text-sm text-slate-500">Ubah informasi dasar master barang.</p>
            </div>

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
                            value="{{ old('name', $item->name) }}"
                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror"
                            maxlength="150"
                            required
                        >
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
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('item_category_id', $item->item_category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
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
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $item->unit_id) === (string) $unit->id)>
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
                    <span x-show="!submitting"><i class="bi bi-check-lg mr-1.5"></i> Simpan Perubahan</span>
                    <span x-show="submitting" x-cloak><svg class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Menyimpan...</span>
                </button>
            </div>
        </div>
    </form>
@endsection
