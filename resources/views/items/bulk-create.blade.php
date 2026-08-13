@extends('layouts.app')

@section('title', 'Tambah Banyak Master Barang')
@section('page-title', 'Tambah Banyak Master Barang')

@section('content')
    @php
        $oldRows = old(
            'items',
            [
                [
                    'type' => 'tool',
                    'name' => '',
                    'item_category_id' => '',
                    'unit_id' => '',
                    'workshop_id' => '',
                    'storage_location_id' => '',
                    'brand' => '',
                    'model' => '',
                    'unit_price' => '',
                    'minimum_stock' => 0,
                    'condition' => 'good',
                    'is_borrowable' => 1,
                    'description' => '',
                ],
            ]
        );
    @endphp

    <div class="page-heading">
        <h1 class="page-title">
            Tambah Banyak Master Barang
        </h1>

        <p class="page-description mb-0">
            Satu baris membuat satu master barang. Stok awal,
            unit fisik alat, nomor inventaris, dan QR Code dibuat
            melalui menu Barang Masuk.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('items.bulk.store') }}"
    >
        @csrf

        <section class="content-card">
            <div
                class="content-card-header
                    d-flex justify-content-between
                    align-items-center"
            >
                <div>
                    <h2 class="h6 fw-bold mb-1">
                        Daftar Master Barang
                    </h2>

                    <div class="small text-secondary">
                        Maksimal 100 baris dalam satu proses.
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
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 135px;">
                                Jenis
                            </th>
                            <th style="min-width: 240px;">
                                Nama
                            </th>
                            <th style="min-width: 200px;">
                                Kategori
                            </th>
                            <th style="min-width: 150px;">
                                Satuan
                            </th>
                            <th style="min-width: 220px;">
                                Bengkel
                            </th>
                            <th style="min-width: 220px;">
                                Lokasi
                            </th>
                            <th style="min-width: 150px;">
                                Merek
                            </th>
                            <th style="min-width: 150px;">
                                Model
                            </th>
                            <th style="min-width: 160px;">
                                Harga/Unit
                            </th>
                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>

                    <tbody id="bulk-item-rows">
                        @foreach ($oldRows as $index => $row)
                            <tr class="bulk-item-row">
                                <td>
                                    <select
                                        name="items[{{ $index }}][type]"
                                        class="form-select type-select"
                                        required
                                    >
                                        @foreach ($types as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected(
                                                    data_get(
                                                        $row,
                                                        'type',
                                                        'tool'
                                                    ) === $value
                                                )
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="items[{{ $index }}][name]"
                                        value="{{ data_get($row, 'name') }}"
                                        class="form-control"
                                        maxlength="150"
                                        required
                                    >
                                </td>

                                <td>
                                    <select
                                        name="items[{{ $index }}][item_category_id]"
                                        class="form-select category-select"
                                        required
                                    >
                                        <option value="">
                                            Pilih kategori
                                        </option>

                                        @foreach ($categories as $category)
                                            <option
                                                value="{{ $category->id }}"
                                                data-applies-to="{{ $category->applies_to }}"
                                                @selected(
                                                    (string)
                                                    data_get(
                                                        $row,
                                                        'item_category_id'
                                                    )
                                                    ===
                                                    (string) $category->id
                                                )
                                            >
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select
                                        name="items[{{ $index }}][unit_id]"
                                        class="form-select"
                                        required
                                    >
                                        <option value="">
                                            Pilih satuan
                                        </option>

                                        @foreach ($units as $unit)
                                            <option
                                                value="{{ $unit->id }}"
                                                @selected(
                                                    (string)
                                                    data_get(
                                                        $row,
                                                        'unit_id'
                                                    )
                                                    ===
                                                    (string) $unit->id
                                                )
                                            >
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select
                                        name="items[{{ $index }}][workshop_id]"
                                        class="form-select workshop-select"
                                        required
                                    >
                                        <option value="">
                                            Pilih bengkel
                                        </option>

                                        @foreach ($workshops as $workshop)
                                            <option
                                                value="{{ $workshop->id }}"
                                                @selected(
                                                    (string)
                                                    data_get(
                                                        $row,
                                                        'workshop_id'
                                                    )
                                                    ===
                                                    (string) $workshop->id
                                                )
                                            >
                                                {{ $workshop->code }}
                                                — {{ $workshop->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select
                                        name="items[{{ $index }}][storage_location_id]"
                                        class="form-select location-select"
                                    >
                                        <option value="">
                                            Belum ditentukan
                                        </option>

                                        @foreach ($locations as $location)
                                            <option
                                                value="{{ $location->id }}"
                                                data-workshop-id="{{ $location->workshop_id }}"
                                                @selected(
                                                    (string)
                                                    data_get(
                                                        $row,
                                                        'storage_location_id'
                                                    )
                                                    ===
                                                    (string) $location->id
                                                )
                                            >
                                                {{ $location->code }}
                                                — {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="items[{{ $index }}][brand]"
                                        value="{{ data_get($row, 'brand') }}"
                                        class="form-control"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="items[{{ $index }}][model]"
                                        value="{{ data_get($row, 'model') }}"
                                        class="form-control"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][unit_price]"
                                        value="{{ data_get($row, 'unit_price') }}"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                    >

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][minimum_stock]"
                                        value="{{ data_get($row, 'minimum_stock', 0) }}"
                                    >

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][condition]"
                                        value="{{ data_get($row, 'condition', 'good') }}"
                                    >

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][is_borrowable]"
                                        value="{{ data_get($row, 'is_borrowable', 1) }}"
                                    >

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][description]"
                                        value="{{ data_get($row, 'description') }}"
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
                    href="{{ route('items.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Semua Master
                </button>
            </div>
        </section>
    </form>

    <template id="bulk-item-row-template">
        <tr class="bulk-item-row">
            <td>
                <select
                    data-name="items[__INDEX__][type]"
                    class="form-select type-select"
                    required
                >
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <input
                    type="text"
                    data-name="items[__INDEX__][name]"
                    class="form-control"
                    maxlength="150"
                    required
                >
            </td>

            <td>
                <select
                    data-name="items[__INDEX__][item_category_id]"
                    class="form-select category-select"
                    required
                >
                    <option value="">
                        Pilih kategori
                    </option>

                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            data-applies-to="{{ $category->applies_to }}"
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <select
                    data-name="items[__INDEX__][unit_id]"
                    class="form-select"
                    required
                >
                    <option value="">
                        Pilih satuan
                    </option>

                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <select
                    data-name="items[__INDEX__][workshop_id]"
                    class="form-select workshop-select"
                    required
                >
                    <option value="">
                        Pilih bengkel
                    </option>

                    @foreach ($workshops as $workshop)
                        <option value="{{ $workshop->id }}">
                            {{ $workshop->code }}
                            — {{ $workshop->name }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <select
                    data-name="items[__INDEX__][storage_location_id]"
                    class="form-select location-select"
                >
                    <option value="">
                        Belum ditentukan
                    </option>

                    @foreach ($locations as $location)
                        <option
                            value="{{ $location->id }}"
                            data-workshop-id="{{ $location->workshop_id }}"
                        >
                            {{ $location->code }}
                            — {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <input
                    type="text"
                    data-name="items[__INDEX__][brand]"
                    class="form-control"
                >
            </td>

            <td>
                <input
                    type="text"
                    data-name="items[__INDEX__][model]"
                    class="form-control"
                >
            </td>

            <td>
                <input
                    type="number"
                    data-name="items[__INDEX__][unit_price]"
                    min="0"
                    step="0.01"
                    class="form-control"
                >

                <input
                    type="hidden"
                    data-name="items[__INDEX__][minimum_stock]"
                    value="0"
                >

                <input
                    type="hidden"
                    data-name="items[__INDEX__][condition]"
                    value="good"
                >

                <input
                    type="hidden"
                    data-name="items[__INDEX__][is_borrowable]"
                    value="1"
                >

                <input
                    type="hidden"
                    data-name="items[__INDEX__][description]"
                    value=""
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
                        'bulk-item-rows'
                    );

                const template =
                    document.getElementById(
                        'bulk-item-row-template'
                    );

                const addButton =
                    document.getElementById(
                        'add-row'
                    );

                let nextIndex =
                    rows.querySelectorAll(
                        '.bulk-item-row'
                    ).length;

                const updateRow =
                    function (row) {
                        const type =
                            row.querySelector(
                                '.type-select'
                            );

                        const category =
                            row.querySelector(
                                '.category-select'
                            );

                        const workshop =
                            row.querySelector(
                                '.workshop-select'
                            );

                        const location =
                            row.querySelector(
                                '.location-select'
                            );

                        Array.from(
                            category.options
                        ).forEach(
                            function (option) {
                                const appliesTo =
                                    option.dataset
                                        .appliesTo;

                                option.hidden =
                                    Boolean(appliesTo)
                                    && appliesTo
                                        !== 'both'
                                    && appliesTo
                                        !== type.value;
                            }
                        );

                        if (
                            category
                                .selectedOptions[0]
                                ?.hidden
                        ) {
                            category.value = '';
                        }

                        Array.from(
                            location.options
                        ).forEach(
                            function (option) {
                                const workshopId =
                                    option.dataset
                                        .workshopId;

                                option.hidden =
                                    Boolean(workshopId)
                                    && workshopId
                                        !== workshop.value;
                            }
                        );

                        if (
                            location
                                .selectedOptions[0]
                                ?.hidden
                        ) {
                            location.value = '';
                        }
                    };

                const bindRow =
                    function (row) {
                        row.querySelector(
                            '.type-select'
                        )?.addEventListener(
                            'change',
                            function () {
                                updateRow(row);
                            }
                        );

                        row.querySelector(
                            '.workshop-select'
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
                                        '.bulk-item-row'
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
                                '.bulk-item-row'
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
                    '.bulk-item-row'
                ).forEach(bindRow);
            }
        );
    </script>
@endpush
