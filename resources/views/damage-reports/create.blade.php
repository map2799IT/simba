@extends('layouts.app')

@section('title', 'Laporkan Kerusakan')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Laporkan Kerusakan Alat</h1>
            <p class="mt-1 text-sm text-slate-500">Catat alat yang mengalami kerusakan atau memerlukan perawatan.</p>
        </div>
        <x-button href="{{ route('damage-reports.index') }}" variant="secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </x-button>
    </div>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="text-sm text-red-800">
                <p class="font-semibold">Laporan belum dapat disimpan</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-700">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('damage-reports.store') }}" enctype="multipart/form-data" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Informasi Kerusakan</h2>
                <p class="mt-0.5 text-sm text-slate-500">Alat akan otomatis diberi status rusak atau perawatan setelah laporan disimpan.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="item_id" class="block text-sm font-semibold text-slate-700">Alat <span class="text-red-500">*</span></label>
                    <select id="item_id" name="item_id" required
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('item_id') border-red-400 @enderror">
                        <option value="">Pilih alat</option>
                        @foreach ($tools as $tool)
                            <option value="{{ $tool->id }}" @selected($selectedItemId == $tool->id)>
                                {{ $tool->code }} — {{ $tool->name }} — {{ $tool->workshop?->code }} — {{ $tool->location?->full_path ?? 'Belum ada lokasi' }}
                            </option>
                        @endforeach
                    </select>
                    @error('item_id')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="severity" class="block text-sm font-semibold text-slate-700">Tingkat Kerusakan <span class="text-red-500">*</span></label>
                    <select id="severity" name="severity" required
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('severity') border-red-400 @enderror">
                        <option value="">Pilih tingkat kerusakan</option>
                        @foreach ($severities as $value => $label)
                            <option value="{{ $value }}" @selected(old('severity') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('severity')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="reported_at" class="block text-sm font-semibold text-slate-700">Waktu Laporan <span class="text-red-500">*</span></label>
                    <input id="reported_at" type="datetime-local" name="reported_at" required
                        value="{{ old('reported_at', now()->format('Y-m-d\TH:i')) }}"
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('reported_at') border-red-400 @enderror">
                    @error('reported_at')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-slate-700">Deskripsi Kerusakan <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="5" required
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-400 @enderror"
                        placeholder="Jelaskan gejala, bagian yang rusak, dan kejadian saat kerusakan ditemukan">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-semibold text-slate-700">Catatan Tambahan</label>
                    <textarea id="notes" name="notes" rows="3"
                        class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label for="evidence_image" class="block text-sm font-semibold text-slate-700">Bukti Gambar <span class="text-xs font-normal text-slate-400">(opsional)</span></label>
                    <input id="evidence_image" type="file" name="evidence_image" accept="image/jpeg,image/png,image/webp"
                        class="mt-1.5 block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border file:border-slate-300 file:bg-slate-50 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-slate-100 @error('evidence_image') border-red-400 @enderror">
                    <p class="mt-1.5 text-xs text-slate-500">Format: JPG, PNG, atau WEBP. Maksimal 5 MB.</p>
                    @error('evidence_image')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>
            </div>

            <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end sm:px-6">
                <x-button href="{{ route('damage-reports.index') }}" variant="secondary" class="w-full sm:w-auto">Batal</x-button>
                <button type="submit" :disabled="submitting"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">
                    <span x-show="!submitting"><i class="bi bi-exclamation-triangle mr-1.5"></i> Simpan Laporan</span>
                    <span x-show="submitting" x-cloak>
                        <svg class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </form>
@endsection