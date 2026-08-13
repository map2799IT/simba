@extends('layouts.app')
@section('title', 'Import Barang Masuk')
@section('content')
    <x-page-header title="Import Barang Masuk" description="Unduh template, isi data (mulai baris 3), lalu unggah. Data TIDAK terlihat selama proses Finance yang sama dengan form manual." :breadcrumb="['Transaksi Stok', 'Barang Masuk', 'Import']">
        <x-button href="{{ route('stock-import.template') }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Unduh Template</x-button>
        <x-button href="{{ route('stock-import.reference') }}" variant="secondary"><i class="bi bi-list-ul"></i> Referensi</x-button>
        <x-button href="{{ route('stock-receipts.index') }}" variant="secondary"><i class="bi bi-arrow-left"></i> Kembali</x-button>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="text-sm text-red-800">
                <p class="font-semibold">Import gagal</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-700">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mb-4 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-sm text-blue-800"><strong>Panduan:</strong> Baris 2 adalah contoh — mulai isi dari baris 3. Nomor Dokumen yang sama menggabungkan beberapa barang menjadi SATU transaksi. Kode/nama barang harus sudah ada di Data Alat &amp; Bahan (master). <strong>Foto tidak perlu dimasukkan ke Excel</strong> — lengkapi foto setelah import melalui Edit Data.</p>
    </div>

    <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Unggah File Barang Masuk</h2>
        </div>
        <div class="p-5 sm:p-6">
            <form method="POST" action="{{ route('stock-import.store') }}" enctype="multipart/form-data"
                x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @if ($isAdmin)
                <div class="mb-4">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Bengkel / Jurusan</label>
                    <select name="workshop_id" required class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih jurusan</option>
                        @foreach ($workshops as $workshop)
                            <option value="{{ $workshop->id }}">{{ $workshop->code }} — {{ $workshop->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <p class="mb-3 text-sm text-slate-600"><i class="bi bi-geo-alt mr-1"></i>Bengkel otomatis dari akun Anda (Toolman).</p>
                @endif
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">File Excel (.xlsx / .csv)</label>
                <input type="file" name="file" accept=".xlsx,.csv" required
                    class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border file:border-slate-300 file:bg-slate-50 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-slate-100">
                <p class="mt-1.5 text-xs text-slate-500">Maksimal 10 MB.</p>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('stock-receipts.index') }}" variant="secondary" class="w-full sm:w-auto">Batal</x-button>
                    <button type="submit" :disabled="submitting"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!submitting"><i class="bi bi-upload mr-1.5"></i> Unggah &amp; Import</span>
                        <span x-show="submitting" x-cloak>Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
