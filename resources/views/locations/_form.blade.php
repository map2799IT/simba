@php
    $editing = isset($location);

    $currentMode =
        old(
            'location_mode',
            $selectedLocationMode
                ?? (
                    isset($location)
                    && $location->parent_id
                        ? 'child'
                        : 'root'
                )
        );

    $currentParentId =
        old(
            'parent_id',
            $selectedParentId
                ?? $location->parent_id
                ?? ''
        );
@endphp

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="text-sm text-red-800">
            <p class="font-semibold">Data belum dapat disimpan</p>
            <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-700">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif

<div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white"><i class="bi bi-diagram-3"></i></span>
    <div class="text-sm text-blue-800">
        <p class="font-semibold">Struktur lokasi penyimpanan</p>
        <p class="mt-1 text-blue-700">Buat lokasi induk terlebih dahulu, misalnya <strong>Ruang Bengkel TKJ</strong>. Setelah itu tambahkan lemari, rak, laci, atau kotak sebagai lokasi turunannya.</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    {{-- Tingkat Lokasi --}}
    <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">Tingkat Lokasi</label>
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <label class="location-mode-card flex gap-3 rounded-2xl border border-slate-200 p-4" for="location_mode_root">
                <input id="location_mode_root" type="radio" name="location_mode" value="root" class="mt-1 h-4 w-4 accent-blue-600" @checked($currentMode === 'root')>
                <span>
                    <span class="block font-semibold text-slate-900">Lokasi Induk / Utama</span>
                    <span class="mt-1 block text-xs text-slate-500">Tidak berada di dalam lokasi lain. Contoh: Ruang Bengkel, Gudang Utama, atau Laboratorium.</span>
                </span>
            </label>
            <label class="location-mode-card flex gap-3 rounded-2xl border border-slate-200 p-4" for="location_mode_child">
                <input id="location_mode_child" type="radio" name="location_mode" value="child" class="mt-1 h-4 w-4 accent-blue-600" @checked($currentMode === 'child')>
                <span>
                    <span class="block font-semibold text-slate-900">Lokasi Turunan</span>
                    <span class="mt-1 block text-xs text-slate-500">Berada di dalam lokasi induk. Contoh: Lemari A, Rak 01, Laci 02, atau Kotak Perkakas.</span>
                </span>
            </label>
        </div>
        @error('location_mode')<p class="mt-2 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Jurusan --}}
    <div>
        <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Jurusan</label>
        @if ($isAdmin)
            <select id="workshop_id" name="workshop_id" required
                class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('workshop_id') border-red-400 @enderror">
                <option value="">Pilih jurusan</option>
                @foreach ($workshops as $workshop)
                    <option value="{{ $workshop->id }}" @selected((string) old('workshop_id', $location->workshop_id ?? $selectedWorkshopId) === (string) $workshop->id)>{{ $workshop->code }} — {{ $workshop->name }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="workshop_id" value="{{ $selectedWorkshopId }}">
            <input type="text" class="w-full rounded-xl border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-500" value="{{ $workshops->first()?->code .' — '. $workshops->first()?->name }}" disabled>
            @if ($isToolman ?? false)
                <p class="mt-1 text-xs text-slate-500">Toolman hanya dapat membuat lokasi pada jurusannya sendiri.</p>
            @endif
        @endif
        @error('workshop_id')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Parent (hanya untuk child) --}}
    <div id="parent_location_group" class="{{ $currentMode === 'child' ? '' : 'hidden' }}">
        <label for="parent_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Pilih Lokasi Induk</label>
        <select id="parent_id" name="parent_id"
            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('parent_id') border-red-400 @enderror">
            <option value="">Pilih lokasi induk</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" data-workshop="{{ $parent->workshop_id }}" @selected((string) $currentParentId === (string) $parent->id)>
                    {{ $parent->parent_id === null ? 'INDUK' : 'TURUNAN' }} · {{ $parent->code }} — {{ $parent->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Lokasi induk harus berada pada jurusan yang sama.</p>
        @error('parent_id')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Kode --}}
    <div>
        <label for="code" class="mb-1.5 block text-sm font-semibold text-slate-700">Kode Lokasi</label>
        <input id="code" type="text" name="code" value="{{ old('code', $location->code ?? '') }}"
            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('code') border-red-400 @enderror"
            placeholder="Contoh: TKJ-RUANG-01" maxlength="80" required>
        <p class="mt-1 text-xs text-slate-500">Gunakan kode unik dan mudah dibaca.</p>
        @error('code')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Nama --}}
    <div>
        <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Lokasi</label>
        <input id="name" type="text" name="name" value="{{ old('name', $location->name ?? '') }}"
            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-400 @enderror"
            placeholder="Contoh: Ruang Bengkel TKJ" maxlength="150" required>
        @error('name')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Jenis --}}
    <div>
        <label for="type" class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis</label>
        <select id="type" name="type" required
            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('type') border-red-400 @enderror">
            @foreach ($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $location->type ?? ($currentMode === 'root' ? 'room' : 'shelf')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Keterangan --}}
    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Keterangan</label>
        <textarea id="description" name="description" rows="3"
            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-400 @enderror"
            placeholder="Keterangan posisi atau fungsi lokasi">{{ old('description', $location->description ?? '') }}</textarea>
        @error('description')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Aktif --}}
    <div class="md:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-3 cursor-pointer">
            <input id="is_active" type="checkbox" name="is_active" value="1"
                class="h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-blue-500"
                @checked(old('is_active', $location->is_active ?? true))>
            <span class="text-sm font-semibold text-slate-700">Lokasi aktif</span>
        </label>
    </div>
</div>

<style>
    .location-mode-card {
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .location-mode-card:has(input:checked) {
        background: rgba(37, 99, 235, .06);
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
    }
</style>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const workshop = document.getElementById('workshop_id');
            const parent = document.getElementById('parent_id');
            const parentGroup = document.getElementById('parent_location_group');
            const type = document.getElementById('type');
            const modeInputs = document.querySelectorAll('input[name="location_mode"]');

            const currentMode = function () {
                return document.querySelector('input[name="location_mode"]:checked')?.value ?? 'root';
            };

            const filterParents = function () {
                if (! parent) return;
                const workshopId = workshop ? workshop.value : @json((string) $selectedWorkshopId);
                Array.from(parent.options).forEach(function (option) {
                    if (! option.value) return;
                    option.hidden = workshopId !== '' && option.dataset.workshop !== workshopId;
                    if (option.hidden && option.selected) option.selected = false;
                });
            };

            const applyMode = function (changeType) {
                const isChild = currentMode() === 'child';
                if (parentGroup) parentGroup.classList.toggle('hidden', ! isChild);
                if (parent) {
                    parent.disabled = ! isChild;
                    parent.required = isChild;
                    if (! isChild) parent.value = '';
                }
                if (changeType && type && ! @json($editing)) {
                    type.value = isChild ? 'shelf' : 'room';
                }
            };

            workshop?.addEventListener('change', filterParents);
            modeInputs.forEach(function (input) {
                input.addEventListener('change', function () { applyMode(true); });
            });

            filterParents();
            applyMode(false);
        }
    );
</script>