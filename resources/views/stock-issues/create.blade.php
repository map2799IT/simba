@extends('layouts.app')

@section('title', 'Tambah Barang Keluar')
@section('page-title', 'Tambah Barang Keluar')

@section('content')
    @php
        $oldRows = old(
            'items',
            [
                [
                    'item_id' => '',
                    'quantity' => 1,
                    'asset_ids' => [],
                    'notes' => '',
                ],
            ]
        );
    @endphp

    <div class="page-heading">
        <h1 class="page-title">
            Tambah Barang Keluar
        </h1>

        <p class="page-description mb-0">
            Satu transaksi dapat berisi banyak alat dan bahan.
        </p>
    </div>

    <div class="alert alert-warning">
        <strong>Perhatian:</strong>
        Barang Keluar alat adalah proses permanen. Unit yang dipilih
        akan berstatus dihapuskan dan dinonaktifkan, tetapi riwayatnya
        tetap tersimpan. Untuk penggunaan sementara gunakan Peminjaman.
    </div>

    <form
        method="POST"
        action="{{ route('stock-issues.store') }}"
    >
        @csrf

        <section class="content-card mb-4">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">
                    Informasi Pengeluaran
                </h2>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label
                            for="workshop_id"
                            class="form-label"
                        >
                            Jurusan
                        </label>

                        @if ($isAdmin)
                            <select
                                id="workshop_id"
                                name="workshop_id"
                                class="form-select"
                                required
                            >
                                @foreach ($workshops as $workshop)
                                    <option
                                        value="{{ $workshop->id }}"
                                        @selected(
                                            (int) $selectedWorkshopId
                                            === (int) $workshop->id
                                        )
                                    >
                                        {{ $workshop->code }}
                                        — {{ $workshop->name }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="form-text">
                                Mengganti jurusan akan memuat ulang daftar stok.
                            </div>
                        @else
                            <input
                                type="hidden"
                                id="workshop_id"
                                name="workshop_id"
                                value="{{ $selectedWorkshopId }}"
                            >

                            <input
                                type="text"
                                class="form-control"
                                value="{{ $selectedWorkshop->code }} — {{ $selectedWorkshop->name }}"
                                disabled
                            >
                        @endif
                    </div>

                    <div class="col-12 col-md-3">
                        <label
                            for="transaction_date"
                            class="form-label"
                        >
                            Tanggal Keluar
                        </label>

                        <input
                            id="transaction_date"
                            type="date"
                            name="transaction_date"
                            value="{{ old(
                                'transaction_date',
                                now()->format('Y-m-d')
                            ) }}"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-12 col-md-3">
                        <label
                            for="reference_number"
                            class="form-label"
                        >
                            Nomor Referensi
                        </label>

                        <input
                            id="reference_number"
                            type="text"
                            name="reference_number"
                            value="{{ old('reference_number') }}"
                            class="form-control"
                            placeholder="Kosongkan untuk nomor otomatis"
                        >
                    </div>

                    <div class="col-12 col-md-3">
                        <label
                            for="destination"
                            class="form-label"
                        >
                            Tujuan
                        </label>

                        <input
                            id="destination"
                            type="text"
                            name="destination"
                            value="{{ old('destination') }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-6">
                        <label
                            for="purpose"
                            class="form-label"
                        >
                            Keperluan
                        </label>

                        <input
                            id="purpose"
                            type="text"
                            name="purpose"
                            value="{{ old('purpose') }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-6">
                        <label
                            for="description"
                            class="form-label"
                        >
                            Keterangan Umum
                        </label>

                        <input
                            id="description"
                            type="text"
                            name="description"
                            value="{{ old('description') }}"
                            class="form-control"
                        >
                    </div>
                </div>
            </div>
        </section>

        @if ($items->isEmpty())
            <div class="alert alert-danger">
                Tidak ada stok aktif pada jurusan
                <strong>{{ $selectedWorkshop->code }}</strong>.
                Pastikan Barang Masuk menyimpan workshop_id dan unit alat masih berstatus tersedia.
            </div>
        @endif

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
                        Bahan menggunakan jumlah. Alat menggunakan nomor unit/QR Code.
                    </div>
                </div>

                <button
                    id="add-row"
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    @disabled($items->isEmpty())
                >
                    Tambah Baris
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 360px;">
                                Barang
                            </th>
                            <th style="min-width: 170px;">
                                Jumlah Bahan
                            </th>
                            <th style="min-width: 390px;">
                                Unit Alat/QR
                            </th>
                            <th style="min-width: 240px;">
                                Catatan
                            </th>
                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>

                    <tbody id="issue-rows">
                        @foreach ($oldRows as $index => $row)
                            <tr class="issue-row">
                                <td>
                                    <select
                                        name="items[{{ $index }}][item_id]"
                                        class="form-select item-select"
                                        required
                                    >
                                        <option value="">
                                            Pilih barang
                                        </option>

                                        @foreach ($items as $itemOption)
                                            <option
                                                value="{{ $itemOption->id }}"
                                                data-type="{{ $itemOption->type }}"
                                                data-unit="{{ $itemOption->unit?->name ?? 'unit' }}"
                                                data-stock="{{ (float) $itemOption->stock }}"
                                                data-allows-decimal="{{ $itemOption->unit?->allows_decimal ? '1' : '0' }}"
                                                @selected(
                                                    (string)
                                                    data_get(
                                                        $row,
                                                        'item_id'
                                                    )
                                                    ===
                                                    (string) $itemOption->id
                                                )
                                            >
                                                {{ $itemOption->code }}
                                                — {{ $itemOption->name }}
                                                ({{ $itemOption->typeLabel() }},
                                                stok {{ \App\Support\QuantityFormatter::format(
                                                    $itemOption->stock,
                                                    $itemOption->unit
                                                ) }})
                                            </option>
                                        @endforeach
                                    </select>

                                    <div
                                        class="small text-primary
                                            item-hint mt-1"
                                    ></div>
                                </td>

                                <td>
                                    <div class="input-group">
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
                                            class="form-control quantity-input"
                                        >

                                        <span
                                            class="input-group-text unit-label"
                                        >
                                            unit
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <select
                                        name="items[{{ $index }}][asset_ids][]"
                                        class="form-select asset-select"
                                        multiple
                                        size="5"
                                    >
                                        @foreach ($assets as $asset)
                                            <option
                                                value="{{ $asset->id }}"
                                                data-item-id="{{ $asset->item_id }}"
                                                @selected(
                                                    in_array(
                                                        $asset->id,
                                                        (array) data_get(
                                                            $row,
                                                            'asset_ids',
                                                            []
                                                        ),
                                                        false
                                                    )
                                                )
                                            >
                                                {{ $asset->asset_number }}
                                                @if ($asset->serial_number)
                                                    — SN {{ $asset->serial_number }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="small text-secondary mt-1">
                                        Tekan Ctrl untuk memilih beberapa unit.
                                    </div>
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

            @if ($errors->any())
                <div class="alert alert-danger m-3">
                    <strong>
                        Data belum dapat disimpan:
                    </strong>

                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                class="content-card-body border-top
                    d-flex justify-content-end gap-2"
            >
                <a
                    href="{{ route('stock-issues.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled($items->isEmpty())
                >
                    Simpan Semua Barang Keluar
                </button>
            </div>
        </section>
    </form>

    <template id="issue-row-template">
        <tr class="issue-row">
            <td>
                <select
                    data-name="items[__INDEX__][item_id]"
                    class="form-select item-select"
                    required
                >
                    <option value="">
                        Pilih barang
                    </option>

                    @foreach ($items as $itemOption)
                        <option
                            value="{{ $itemOption->id }}"
                            data-type="{{ $itemOption->type }}"
                            data-unit="{{ $itemOption->unit?->name ?? 'unit' }}"
                            data-stock="{{ (float) $itemOption->stock }}"
                            data-allows-decimal="{{ $itemOption->unit?->allows_decimal ? '1' : '0' }}"
                        >
                            {{ $itemOption->code }}
                            — {{ $itemOption->name }}
                            ({{ $itemOption->typeLabel() }},
                            stok {{ \App\Support\QuantityFormatter::format(
                                $itemOption->stock,
                                $itemOption->unit
                            ) }})
                        </option>
                    @endforeach
                </select>

                <div
                    class="small text-primary
                        item-hint mt-1"
                ></div>
            </td>

            <td>
                <div class="input-group">
                    <input
                        type="number"
                        data-name="items[__INDEX__][quantity]"
                        value="1"
                        min="0.001"
                        step="0.001"
                        class="form-control quantity-input"
                    >

                    <span
                        class="input-group-text unit-label"
                    >
                        unit
                    </span>
                </div>
            </td>

            <td>
                <select
                    data-name="items[__INDEX__][asset_ids][]"
                    class="form-select asset-select"
                    multiple
                    size="5"
                >
                    @foreach ($assets as $asset)
                        <option
                            value="{{ $asset->id }}"
                            data-item-id="{{ $asset->item_id }}"
                        >
                            {{ $asset->asset_number }}
                            @if ($asset->serial_number)
                                — SN {{ $asset->serial_number }}
                            @endif
                        </option>
                    @endforeach
                </select>

                <div class="small text-secondary mt-1">
                    Tekan Ctrl untuk memilih beberapa unit.
                </div>
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
                        'issue-rows'
                    );

                const template =
                    document.getElementById(
                        'issue-row-template'
                    );

                const addButton =
                    document.getElementById(
                        'add-row'
                    );

                let nextIndex =
                    rows.querySelectorAll(
                        '.issue-row'
                    ).length;

                const updateRow =
                    function (row) {
                        const itemSelect =
                            row.querySelector(
                                '.item-select'
                            );

                        const selected =
                            itemSelect.options[
                                itemSelect.selectedIndex
                            ];

                        const quantity =
                            row.querySelector(
                                '.quantity-input'
                            );

                        const assetSelect =
                            row.querySelector(
                                '.asset-select'
                            );

                        const unitLabel =
                            row.querySelector(
                                '.unit-label'
                            );

                        const hint =
                            row.querySelector(
                                '.item-hint'
                            );

                        const itemId =
                            selected?.value || '';

                        const type =
                            selected?.dataset.type;

                        unitLabel.textContent =
                            selected?.dataset.unit
                            || 'unit';

                        Array.from(
                            assetSelect.options
                        ).forEach(
                            function (option) {
                                const visible =
                                    option.dataset
                                        .itemId
                                    === itemId;

                                option.hidden =
                                    ! visible;

                                if (! visible) {
                                    option.selected =
                                        false;
                                }
                            }
                        );

                        if (type === 'tool') {
                            quantity.disabled = true;
                            assetSelect.disabled = false;

                            hint.textContent =
                                'Alat: pilih nomor unit/QR yang keluar permanen.';
                        } else if (
                            type === 'material'
                        ) {
                            quantity.disabled = false;
                            assetSelect.disabled = true;

                            Array.from(
                                assetSelect.options
                            ).forEach(
                                function (option) {
                                    option.selected =
                                        false;
                                }
                            );

                            quantity.step =
                                selected?.dataset
                                    .allowsDecimal
                                === '1'
                                    ? '0.001'
                                    : '1';

                            quantity.min =
                                quantity.step;

                            hint.textContent =
                                'Bahan: isi jumlah keluar. Stok tersedia '
                                + (selected?.dataset.stock || '0')
                                + ' '
                                + (selected?.dataset.unit || '');
                        } else {
                            quantity.disabled = false;
                            assetSelect.disabled = true;
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
                                updateRow(row);
                            }
                        );

                        row.querySelector(
                            '.remove-row'
                        )?.addEventListener(
                            'click',
                            function () {
                                if (
                                    rows.querySelectorAll(
                                        '.issue-row'
                                    ).length <= 1
                                ) {
                                    return;
                                }

                                row.remove();
                            }
                        );

                        updateRow(row);
                    };

                addButton.addEventListener(
                    'click',
                    function () {
                        const fragment =
                            template.content
                                .cloneNode(true);

                        const row =
                            fragment.querySelector(
                                '.issue-row'
                            );

                        row.querySelectorAll(
                            '[data-name]'
                        ).forEach(
                            function (field) {
                                field.name =
                                    field.dataset.name
                                        .replace(
                                            '__INDEX__',
                                            String(nextIndex)
                                        );

                                field.removeAttribute(
                                    'data-name'
                                );
                            }
                        );

                        nextIndex++;
                        rows.appendChild(fragment);
                        bindRow(
                            rows.lastElementChild
                        );
                    }
                );

                rows.querySelectorAll(
                    '.issue-row'
                ).forEach(bindRow);

                const workshopSelect =
                    document.getElementById(
                        'workshop_id'
                    );

                @if ($isAdmin)
                    workshopSelect?.addEventListener(
                        'change',
                        function () {
                            const url =
                                new URL(
                                    window.location.href
                                );

                            url.searchParams.set(
                                'workshop_id',
                                workshopSelect.value
                            );

                            window.location.href =
                                url.toString();
                        }
                    );
                @endif
            }
        );
    </script>
@endpush
