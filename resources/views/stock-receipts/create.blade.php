@extends('layouts.app')

@section('title', 'Tambah Barang Masuk')
@section('page-title', 'Tambah Barang Masuk')

@section('content')
    @php
        $oldRows = old(
            'items',
            [
                [
                    'item_id' => '',
                    'quantity' => 1,
                    'storage_location_id' => '',
                    'brand' => '',
                    'model' => '',
                    'specification' => '',
                    'unit_price' => '',
                    'minimum_stock' => 0,
                    'condition' => 'good',
                    'notes' => '',
                ],
            ]
        );
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Tambah Barang Masuk</h1>
            <p class="mt-1 text-sm text-slate-500">Setiap baris menghasilkan kode penerimaan bertahun, misalnya ALT-2026-0001 atau BHN-2026-0001.</p>
        </div>
        <x-button href="{{ route('stock-receipts.index') }}" variant="secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </x-button>
    </div>

    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white"><i class="bi bi-info-lg"></i></span>
        <p class="text-sm text-blue-800">Pilih master yang sama beberapa kali apabila merek, model, atau spesifikasinya berbeda. Contoh: Monitor merek A dan Monitor merek B tetap memakai satu master “Monitor”, tetapi memiliki kode Barang Masuk berbeda.</p>
    </div>

    <form
        method="POST"
        action="{{ route('stock-receipts.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        <section class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="h6 fw-bold mb-0">Informasi Perolehan</h2>
            </div>

            <div class="p-5 sm:p-6">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label
                            for="workshop_id"
                            class="form-label"
                        >
                            Bengkel Tujuan
                        </label>

                        @if ($isAdmin)
                            <select
                                id="workshop_id"
                                name="workshop_id"
                                class="form-select
                                    @error('workshop_id')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option value="">Pilih bengkel</option>

                                @foreach ($workshops as $workshop)
                                    <option
                                        value="{{ $workshop->id }}"
                                        @selected(
                                            (string) old(
                                                'workshop_id',
                                                $selectedWorkshopId
                                            )
                                            === (string) $workshop->id
                                        )
                                    >
                                        {{ $workshop->code }}
                                        — {{ $workshop->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input
                                id="workshop_id"
                                type="hidden"
                                name="workshop_id"
                                value="{{ old(
                                    'workshop_id',
                                    $selectedWorkshopId
                                ) }}"
                            >

                            <input
                                type="text"
                                class="form-control"
                                value="{{
                                    $workshops->first()?->code
                                    .' — '.
                                    $workshops->first()?->name
                                }}"
                                disabled
                            >
                        @endif

                        @error('workshop_id')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label
                            for="receipt_date"
                            class="form-label"
                        >
                            Tanggal Perolehan
                        </label>

                        <input
                            id="receipt_date"
                            type="date"
                            name="receipt_date"
                            value="{{ old(
                                'receipt_date',
                                now()->format('Y-m-d')
                            ) }}"
                            class="form-control
                                @error('receipt_date')
                                    is-invalid
                                @enderror"
                            required
                        >

                        @error('receipt_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label
                            for="document_number"
                            class="form-label"
                        >
                            Nomor Dokumen
                        </label>

                        <input
                            id="document_number"
                            type="text"
                            name="document_number"
                            value="{{ old('document_number') }}"
                            class="form-control"
                            placeholder="Faktur/BAST/surat jalan"
                        >
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="source" class="form-label">
                            Sumber Perolehan/Supplier
                        </label>

                        <input
                            id="source"
                            type="text"
                            name="source"
                            value="{{ old('source') }}"
                            class="form-control"
                            placeholder="Pembelian, hibah, supplier, dan lainnya"
                        >
                    </div>

                    <div class="col-12 col-md-6">
                        <label
                            for="fund_source"
                            class="form-label"
                        >
                            Sumber Dana
                        </label>

                        <input
                            id="fund_source"
                            type="text"
                            name="fund_source"
                            value="{{ old('fund_source') }}"
                            class="form-control"
                            placeholder="BOS, APBD, komite, hibah, dan lainnya"
                        >
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">
                            Catatan Penerimaan
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            class="form-control"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1">Detail Barang</h2>
                        <div class="small text-secondary">Merek, model, dan spesifikasi disimpan per baris penerimaan.</div>
                    </div>
                    <button
                        id="add-row"
                        type="button"
                        class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                    </button>
                </div>
            </div>

            <div id="receipt-rows" class="p-5 sm:p-6">
                @foreach ($oldRows as $index => $row)
                    @include(
                        'stock-receipts._receipt-row',
                        [
                            'index' => $index,
                            'row' => $row,
                            'items' => $items,
                            'locations' => $locations,
                            'conditions' => $conditions,
                            'templateMode' => false,
                        ]
                    )
                @endforeach
            </div>

            @if ($errors->any())
                <div class="mx-5 mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
                    <div class="text-sm text-red-800">
                        <p class="font-semibold">Barang Masuk belum dapat disimpan</p>
                        <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-700">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end sm:px-6">
                <x-button href="{{ route('stock-receipts.index') }}" variant="secondary" class="w-full sm:w-auto">Batal</x-button>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <i class="bi bi-check-lg mr-1.5"></i> Simpan Barang Masuk
                </button>
            </div>
        </section>
    </form>

    <template id="receipt-row-template">
        @include(
            'stock-receipts._receipt-row',
            [
                'index' => '__INDEX__',
                'row' => [],
                'items' => $items,
                'locations' => $locations,
                'conditions' => $conditions,
                'templateMode' => true,
            ]
        )
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const body =
                    document.getElementById('receipt-rows');

                const template =
                    document.getElementById('receipt-row-template');

                const workshop =
                    document.getElementById('workshop_id');

                let nextIndex =
                    body.querySelectorAll('.receipt-row').length;

                const selectedWorkshop = function () {
                    return workshop?.value ?? '';
                };

                const renumberRows = function () {
                    body.querySelectorAll('.receipt-row').forEach(
                        function (row, index) {
                            const title =
                                row.querySelector('.row-title');

                            if (title) {
                                title.textContent =
                                    'Barang ' + (index + 1);
                            }
                        }
                    );
                };

                const prepareRow = function (row) {
                    const item =
                        row.querySelector('.item-select');

                    const location =
                        row.querySelector('.location-select');

                    const minimumStock =
                        row.querySelector('.minimum-stock');

                    const quantity =
                        row.querySelector('.quantity-input');

                    const hint =
                        row.querySelector('.item-type-hint');

                    const filterLocations = function () {
                        const workshopId = selectedWorkshop();

                        Array.from(location.options).forEach(
                            function (option) {
                                if (! option.value) {
                                    return;
                                }

                                option.hidden =
                                    workshopId !== ''
                                    && option.dataset.workshop
                                        !== workshopId;

                                if (
                                    option.hidden
                                    && option.selected
                                ) {
                                    option.selected = false;
                                }
                            }
                        );
                    };

                    const applyItemType = function () {
                        const selected =
                            item.options[item.selectedIndex];

                        const type =
                            selected?.dataset.type ?? '';

                        hint.textContent =
                            type === 'tool'
                                ? 'Alat: jumlah bulat; setiap unit mendapat nomor inventaris dan QR.'
                                : (
                                    type === 'material'
                                        ? 'Bahan: jumlah mengikuti aturan satuan.'
                                        : ''
                                );

                        const allowsDecimal =
                            selected?.dataset
                                .allowsDecimal
                            === '1';

                        /*
                         * Alat selalu bulat.
                         *
                         * Bahan hanya boleh desimal jika
                         * satuannya mengaktifkan allows_decimal.
                         */
                        const usesDecimal =
                            type === 'material'
                            && allowsDecimal;

                        minimumStock.disabled =
                            type !== 'material';

                        quantity.min =
                            usesDecimal
                                ? '0.001'
                                : '1';

                        quantity.step =
                            usesDecimal
                                ? '0.001'
                                : '1';

                        minimumStock.min = '0';

                        minimumStock.step =
                            usesDecimal
                                ? '0.001'
                                : '1';

                        if (type !== 'material') {
                            minimumStock.value = '0';
                        }
                    };

                    item.addEventListener(
                        'change',
                        applyItemType
                    );

                    row.querySelector('.remove-row')
                        ?.addEventListener(
                            'click',
                            function () {
                                if (
                                    body.querySelectorAll(
                                        '.receipt-row'
                                    ).length <= 1
                                ) {
                                    return;
                                }

                                row.remove();
                                renumberRows();
                            }
                        );

                    row._filterLocations = filterLocations;

                    filterLocations();
                    applyItemType();
                };

                body.querySelectorAll('.receipt-row')
                    .forEach(prepareRow);

                workshop?.addEventListener(
                    'change',
                    function () {
                        body.querySelectorAll('.receipt-row')
                            .forEach(
                                function (row) {
                                    row._filterLocations?.();
                                }
                            );
                    }
                );

                document.getElementById('add-row')
                    .addEventListener(
                        'click',
                        function () {
                            const fragment =
                                template.content.cloneNode(true);

                            fragment.querySelectorAll('[data-name]')
                                .forEach(
                                    function (element) {
                                        element.name =
                                            element.dataset.name.replace(
                                                '__INDEX__',
                                                nextIndex
                                            );
                                    }
                                );

                            const row =
                                fragment.querySelector('.receipt-row');

                            body.appendChild(fragment);
                            prepareRow(row);
                            renumberRows();
                            nextIndex++;
                        }
                    );

                renumberRows();
            }
        );
    </script>
@endpush
