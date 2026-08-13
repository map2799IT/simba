@extends('layouts.app')

@section('title', 'Tambah Barang Masuk')
@section('page-title', 'Tambah Barang Masuk')

@section('content')
    @php
        $itemOptions = $items ?? collect();

        $oldRows = old(
            'items',
            [
                [
                    'item_id' => '',
                    'quantity' => 1,
                    'unit_price' => '',
                    'notes' => '',
                ],
            ]
        );
    @endphp

    <div class="page-heading">
        <h1 class="page-title">
            Tambah Barang Masuk
        </h1>

        <p class="page-description mb-0">
            Satu transaksi dapat berisi banyak alat dan bahan.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route(
            'stock-receipts.store'
        ) }}"
    >
        @csrf

        <section class="content-card mb-4">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">
                    Informasi Penerimaan
                </h2>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label
                            for="receipt_date"
                            class="form-label"
                        >
                            Tanggal Penerimaan
                        </label>

                        <input
                            id="receipt_date"
                            type="date"
                            name="receipt_date"
                            value="{{ old(
                                'receipt_date',
                                now()->format('Y-m-d')
                            ) }}"
                            class="form-control"
                            required
                        >
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
                            value="{{ old(
                                'document_number'
                            ) }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label
                            for="source"
                            class="form-label"
                        >
                            Supplier/Sumber
                        </label>

                        <input
                            id="source"
                            type="text"
                            name="source"
                            value="{{ old('source') }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12">
                        <label
                            for="notes"
                            class="form-label"
                        >
                            Catatan
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

        <section class="content-card">
            <div
                class="content-card-header
                    d-flex justify-content-between
                    align-items-center"
            >
                <div>
                    <h2 class="h6 fw-bold mb-1">
                        Detail Barang
                    </h2>

                    <div class="small text-secondary">
                        Alat akan menghasilkan QR Code
                        sebanyak jumlah yang diterima.
                    </div>
                </div>

                <button
                    id="add-row"
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                >
                    Tambah Baris
                </button>
            </div>

            <div class="table-responsive">
                <table
                    class="table align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th style="min-width: 320px;">
                                Barang
                            </th>

                            <th style="width: 150px;">
                                Jumlah
                            </th>

                            <th style="width: 190px;">
                                Harga per Unit
                            </th>

                            <th style="min-width: 220px;">
                                Catatan
                            </th>

                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>

                    <tbody id="receipt-rows">
                        @foreach ($oldRows as $index => $row)
                            <tr
                                class="receipt-row"
                                data-index="{{ $index }}"
                            >
                                <td>
                                    <select
                                        name="items[{{ $index }}][item_id]"
                                        class="form-select item-select"
                                        required
                                    >
                                        <option value="">
                                            Pilih barang
                                        </option>

                                        @foreach (
                                            $itemOptions
                                            as $itemOption
                                        )
                                            <option
                                                value="{{ $itemOption->id }}"
                                                data-type="{{
                                                    $itemOption->type
                                                }}"
                                                @selected(
                                                    (string)
                                                    data_get(
                                                        $row,
                                                        'item_id'
                                                    )
                                                    ===
                                                    (string)
                                                    $itemOption->id
                                                )
                                            >
                                                {{ $itemOption->code }}
                                                — {{ $itemOption->name }}
                                                ({{
                                                    $itemOption->type === 'tool'
                                                        ? 'Alat'
                                                        : 'Bahan'
                                                }})
                                            </option>
                                        @endforeach
                                    </select>

                                    <div
                                        class="small text-primary
                                            item-type-hint mt-1"
                                    ></div>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        value="{{ data_get(
                                            $row,
                                            'quantity',
                                            1
                                        ) }}"
                                        min="0.001"
                                        step="0.001"
                                        class="form-control
                                            quantity-input"
                                        required
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][unit_price]"
                                        value="{{ data_get(
                                            $row,
                                            'unit_price'
                                        ) }}"
                                        min="0"
                                        step="1"
                                        class="form-control"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="items[{{ $index }}][notes]"
                                        value="{{ data_get(
                                            $row,
                                            'notes'
                                        ) }}"
                                        class="form-control"
                                    >
                                </td>

                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm
                                            btn-outline-danger
                                            remove-row"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div
                class="content-card-body border-top
                    d-flex justify-content-end gap-2"
            >
                <a
                    href="{{ route(
                        'stock-receipts.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Barang Masuk
                </button>
            </div>
        </section>
    </form>

    <template id="receipt-row-template">
        <tr class="receipt-row">
            <td>
                <select
                    data-name="items[__INDEX__][item_id]"
                    class="form-select item-select"
                    required
                >
                    <option value="">
                        Pilih barang
                    </option>

                    @foreach (
                        $itemOptions
                        as $itemOption
                    )
                        <option
                            value="{{ $itemOption->id }}"
                            data-type="{{
                                $itemOption->type
                            }}"
                        >
                            {{ $itemOption->code }}
                            — {{ $itemOption->name }}
                            ({{
                                $itemOption->type === 'tool'
                                    ? 'Alat'
                                    : 'Bahan'
                            }})
                        </option>
                    @endforeach
                </select>

                <div
                    class="small text-primary
                        item-type-hint mt-1"
                ></div>
            </td>

            <td>
                <input
                    type="number"
                    data-name="items[__INDEX__][quantity]"
                    value="1"
                    min="0.001"
                    step="0.001"
                    class="form-control quantity-input"
                    required
                >
            </td>

            <td>
                <input
                    type="number"
                    data-name="items[__INDEX__][unit_price]"
                    min="0"
                    step="1"
                    class="form-control"
                >
            </td>

            <td>
                <input
                    type="text"
                    data-name="items[__INDEX__][notes]"
                    class="form-control"
                >
            </td>

            <td class="text-end">
                <button
                    type="button"
                    class="btn btn-sm
                        btn-outline-danger remove-row"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const rows =
                    document.getElementById(
                        'receipt-rows'
                    );

                const template =
                    document.getElementById(
                        'receipt-row-template'
                    );

                const addButton =
                    document.getElementById(
                        'add-row'
                    );

                let nextIndex =
                    rows.querySelectorAll(
                        '.receipt-row'
                    ).length;

                const applyNames =
                    function (row, index) {
                        row.dataset.index = index;

                        row.querySelectorAll(
                            '[data-name]'
                        ).forEach(
                            function (field) {
                                field.name =
                                    field.dataset.name
                                        .replace(
                                            '__INDEX__',
                                            String(index)
                                        );

                                field.removeAttribute(
                                    'data-name'
                                );
                            }
                        );
                    };

                const updateTypeHint =
                    function (row) {
                        const select =
                            row.querySelector(
                                '.item-select'
                            );

                        const quantity =
                            row.querySelector(
                                '.quantity-input'
                            );

                        const hint =
                            row.querySelector(
                                '.item-type-hint'
                            );

                        const option =
                            select.options[
                                select.selectedIndex
                            ];

                        const type =
                            option?.dataset.type;

                        if (type === 'tool') {
                            quantity.step = '1';
                            quantity.min = '1';

                            hint.textContent =
                                'Alat: jumlah harus bulat dan sistem membuat QR per unit.';
                        } else if (
                            type === 'material'
                        ) {
                            quantity.step = '0.001';
                            quantity.min = '0.001';

                            hint.textContent =
                                'Bahan: jumlah boleh desimal dan tidak membuat unit QR.';
                        } else {
                            hint.textContent = '';
                        }
                    };

                const bindRow =
                    function (row) {
                        row.querySelector(
                            '.item-select'
                        )?.addEventListener(
                            'change',
                            function () {
                                updateTypeHint(row);
                            }
                        );

                        row.querySelector(
                            '.remove-row'
                        )?.addEventListener(
                            'click',
                            function () {
                                if (
                                    rows.querySelectorAll(
                                        '.receipt-row'
                                    ).length <= 1
                                ) {
                                    return;
                                }

                                row.remove();
                            }
                        );

                        updateTypeHint(row);
                    };

                addButton.addEventListener(
                    'click',
                    function () {
                        const fragment =
                            template.content
                                .cloneNode(true);

                        const row =
                            fragment.querySelector(
                                '.receipt-row'
                            );

                        applyNames(
                            row,
                            nextIndex
                        );

                        nextIndex++;

                        rows.appendChild(fragment);

                        bindRow(
                            rows.lastElementChild
                        );
                    }
                );

                rows.querySelectorAll(
                    '.receipt-row'
                ).forEach(bindRow);
            }
        );
    </script>
@endpush
