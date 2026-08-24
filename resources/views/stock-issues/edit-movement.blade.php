@extends('layouts.app')

@section('title', 'Edit Barang Keluar')

@section('content')
    @php
        $role = auth()->user()->role;
        $isToolman = $role === 'toolman';
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('stock-issues.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                {{ $isToolman ? 'Ajukan Perubahan Barang Keluar' : 'Edit Barang Keluar' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">{{ $movement->reference_number ?? $movement->receipt_code ?? '#'.$movement->id }}</p>
        </div>
    </div>

    @if ($isToolman)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </span>
            <div class="text-sm text-amber-800">
                <p class="font-semibold">Perubahan memerlukan persetujuan</p>
                <p class="mt-1 text-amber-700">Sebagai Toolman, perubahan yang Anda ajukan akan ditinjau oleh Kepala Bengkel atau Admin sebelum diterapkan. Data asli tidak akan berubah hingga disetujui.</p>
            </div>
        </div>
    @endif

    @if ($movement->pendingIssueChangeRequest)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <i class="bi bi-hourglass-split text-blue-500 mt-0.5 text-lg"></i>
            <div class="text-sm text-blue-800">
                <p class="font-semibold">Ada pengajuan perubahan yang menunggu persetujuan</p>
                <p class="mt-1">Diajukan oleh <strong>{{ $movement->pendingIssueChangeRequest->requester?->name }}</strong> pada {{ $movement->pendingIssueChangeRequest->created_at?->format('d-m-Y H:i') }}. Perubahan baru tidak dapat diajukan sampai pengajuan ini selesai.</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="text-sm text-red-800">
                <p class="font-semibold">Data belum valid</p>
                @foreach ($errors->all() as $error)<p class="mt-1 text-red-700">{{ $error }}</p>@endforeach
            </div>
        </div>
    @endif

    {{-- Informasi Transaksi (Read-only) --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Informasi Transaksi</h2>
            <p class="mt-0.5 text-sm text-slate-500">Data berikut tidak dapat diubah melalui form ini.</p>
        </div>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-4 p-5 sm:grid-cols-3 lg:grid-cols-6">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</dt><dd class="mt-1 text-sm font-bold text-slate-900">{{ $movement->item?->name ?? '-' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</dt><dd class="mt-1 font-mono text-sm font-semibold text-slate-700">{{ $movement->item?->code ?? '-' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Saat Ini</dt><dd class="mt-1 text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }} {{ $movement->item?->unit?->name }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</dt><dd class="mt-1 text-sm font-bold text-slate-900">{{ $movement->transaction_date?->format('d-m-Y') ?? '-' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan</dt><dd class="mt-1 text-sm font-bold text-slate-900">{{ $movement->workshop?->code ?? '-' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Referensi</dt><dd class="mt-1 font-mono text-xs text-slate-700">{{ $movement->reference_number ?? '-' }}</dd></div>
        </dl>
    </div>

    {{-- Form Edit --}}
    <form method="POST" action="{{ route('stock-issues.movement.request-edit', $movement) }}"
          x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Data yang Dapat Diubah</h2>
                @if ($isToolman)
                    <p class="mt-0.5 text-sm text-slate-500">Perubahan jumlah akan menyesuaikan stok barang setelah disetujui.</p>
                @endif
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">
                <div>
                    <label for="quantity" class="block text-sm font-semibold text-slate-700">
                        Jumlah Keluar <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1.5">
                        <input id="quantity" type="number" name="quantity" step="0.001" min="0.001" required
                            value="{{ old('quantity', number_format((float)$movement->quantity, 3, '.', '')) }}"
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 pr-16 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('quantity') border-red-400 @enderror">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-slate-500">{{ $movement->item?->unit?->name }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Jumlah saat ini: {{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }} {{ $movement->item?->unit?->name }}</p>
                    @error('quantity')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="destination" class="block text-sm font-semibold text-slate-700">Tujuan</label>
                    <input id="destination" type="text" name="destination"
                        value="{{ old('destination', $movement->destination) }}"
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        maxlength="150" placeholder="Contoh: Lab TKJ 1">
                </div>
                <div>
                    <label for="purpose" class="block text-sm font-semibold text-slate-700">Keperluan</label>
                    <input id="purpose" type="text" name="purpose"
                        value="{{ old('purpose', $movement->purpose) }}"
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        maxlength="255" placeholder="Contoh: Praktikum jaringan">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-slate-700">Keterangan</label>
                    <textarea id="description" name="description" rows="2"
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $movement->description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="request_note" class="block text-sm font-semibold text-slate-700">
                        Alasan Perubahan <span class="text-red-500">*</span>
                    </label>
                    <textarea id="request_note" name="request_note" rows="2" required
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('request_note') border-red-400 @enderror"
                        placeholder="{{ $isToolman ? 'Jelaskan alasan perubahan untuk ditinjau Kepala Bengkel / Admin...' : 'Catatan perubahan (wajib diisi)...' }}">{{ old('request_note') }}</textarea>
                    @error('request_note')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>
            </div>
            <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end">
                <a href="{{ route('stock-issues.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" :disabled="submitting"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">
                    <span x-show="!submitting">
                        <i class="bi bi-check-lg mr-1.5"></i>
                        {{ $isToolman ? 'Ajukan Perubahan' : 'Simpan Perubahan' }}
                    </span>
                    <span x-show="submitting" x-cloak class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </form>
@endsection
