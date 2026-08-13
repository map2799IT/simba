<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="text-base font-semibold text-slate-900">Informasi Satuan</h2>
        <p class="mt-0.5 text-sm text-slate-500">Masukkan detail satuan barang.</p>
    </div>
    <div class="p-5 sm:p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
            <div class="sm:col-span-1">
                <label for="code" class="block text-sm font-semibold text-slate-700">Kode Satuan <span class="text-red-500">*</span></label>
                <input id="code" type="text" name="code" value="{{ old('code', $unit->code ?? '') }}" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('code') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" placeholder="Contoh: UNIT" maxlength="20" required>
                @error('code')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-3">
                <label for="name" class="block text-sm font-semibold text-slate-700">Nama Satuan <span class="text-red-500">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name', $unit->name ?? '') }}" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" placeholder="Contoh: Unit" maxlength="50" required>
                @error('name')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-full">
                <input type="hidden" name="allows_decimal" value="0">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input id="allows_decimal" type="checkbox" name="allows_decimal" value="1" class="mt-0.5 h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('allows_decimal', $unit->allows_decimal ?? false))>
                    <span>
                        <span class="block text-sm font-semibold text-slate-700">Mengizinkan jumlah pecahan/desimal</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Aktifkan untuk satuan seperti liter, kilogram, meter, atau kilogram.</span>
                    </span>
                </label>
            </div>
            <div class="sm:col-span-full">
                <label for="description" class="block text-sm font-semibold text-slate-700">Keterangan</label>
                <textarea id="description" name="description" rows="4" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror">{{ old('description', $unit->description ?? '') }}</textarea>
                @error('description')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-full">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input id="is_active" type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('is_active', $unit->is_active ?? true))>
                    <span class="text-sm font-semibold text-slate-700">Satuan aktif</span>
                </label>
            </div>
        </div>
    </div>
    <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end sm:px-6">
        <a href="{{ route('units.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"><i class="bi bi-save mr-1.5"></i> {{ isset($unit) && $unit->exists ? 'Simpan Perubahan' : 'Simpan Satuan' }}</button>
    </div>
</div>
