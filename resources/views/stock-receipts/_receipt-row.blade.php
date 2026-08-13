@php
    $fieldName = static function (
        string $field
    ) use (
        $index,
        $templateMode
    ): array {
        $name = "items[{$index}][{$field}]";

        return $templateMode
            ? [
                'name' => null,
                'dataName' => $name,
            ]
            : [
                'name' => $name,
                'dataName' => null,
            ];
    };

    $attr = static function (
        string $field
    ) use (
        $fieldName
    ): string {
        $data = $fieldName($field);

        return $data['name'] !== null
            ? 'name="'.e($data['name']).'"'
            : 'data-name="'.e($data['dataName']).'"';
    };
@endphp

<div class="receipt-row border rounded-3 p-3 mb-3">
    <div
        class="d-flex justify-content-between
            align-items-center mb-3"
    >
        <div class="fw-bold row-title">
            Barang
            {{ is_numeric($index)
                ? ((int) $index + 1)
                : '' }}
        </div>

        <button
            type="button"
            class="btn btn-sm btn-outline-danger remove-row"
            title="Hapus baris"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <label class="form-label">Master Barang</label>

            <select
                {!! $attr('item_id') !!}
                class="form-select item-select"
                required
            >
                <option value="">Pilih master barang</option>

                @foreach ($items as $itemOption)
                    <option
                        value="{{ $itemOption->id }}"
                        data-type="{{ $itemOption->type }}"
                        data-allows-decimal="{{
                            $itemOption
                                ->unit
                                ?->allows_decimal
                                ? '1'
                                : '0'
                        }}"
                        @selected(
                            ! $templateMode
                            && (string) data_get($row, 'item_id')
                            === (string) $itemOption->id
                        )
                    >
                        {{ $itemOption->code }}
                        — {{ $itemOption->name }}
                        ({{ $itemOption->typeLabel() }})
                    </option>
                @endforeach
            </select>

            <div
                class="small text-primary item-type-hint mt-1"
            ></div>
        </div>

        <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Jumlah</label>

            <input
                type="number"
                {!! $attr('quantity') !!}
                value="{{ $templateMode
                    ? 1
                    : data_get($row, 'quantity', 1) }}"
                min="1"
                step="1"
                class="form-control quantity-input"
                required
            >
        </div>

        <div class="col-12 col-md-8 col-lg-4">
            <label class="form-label">Lokasi Penyimpanan</label>

            <select
                {!! $attr('storage_location_id') !!}
                class="form-select location-select"
                required
            >
                <option value="">Pilih lokasi</option>

                @foreach ($locations as $location)
                    <option
                        value="{{ $location->id }}"
                        data-workshop="{{ $location->workshop_id }}"
                        @selected(
                            ! $templateMode
                            && (string) data_get(
                                $row,
                                'storage_location_id'
                            )
                            === (string) $location->id
                        )
                    >
                        {{ $location->code }}
                        — {{ $location->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Merek</label>

            <input
                type="text"
                {!! $attr('brand') !!}
                value="{{ $templateMode
                    ? ''
                    : data_get($row, 'brand') }}"
                class="form-control"
                maxlength="100"
                placeholder="Contoh: LG, Samsung, Mikrotik"
            >
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Model/Tipe</label>

            <input
                type="text"
                {!! $attr('model') !!}
                value="{{ $templateMode
                    ? ''
                    : data_get($row, 'model') }}"
                class="form-control"
                maxlength="100"
                placeholder="Contoh: 24MK430, RB941"
            >
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Kondisi Awal</label>

            <select
                {!! $attr('condition') !!}
                class="form-select"
                required
            >
                @foreach ($conditions as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(
                            ! $templateMode
                            && data_get(
                                $row,
                                'condition',
                                'good'
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Spesifikasi</label>

            <textarea
                {!! $attr('specification') !!}
                rows="2"
                class="form-control"
                placeholder="Ukuran, kapasitas, warna, resolusi, fitur, atau detail teknis lainnya"
            >{{ $templateMode
                ? ''
                : data_get($row, 'specification') }}</textarea>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Harga per Unit</label>

            <input
                type="number"
                {!! $attr('unit_price') !!}
                value="{{ $templateMode
                    ? ''
                    : data_get($row, 'unit_price') }}"
                min="0"
                step="0.01"
                class="form-control"
            >
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Stok Minimum Bahan</label>

            <input
                type="number"
                {!! $attr('minimum_stock') !!}
                value="{{ $templateMode
                    ? 0
                    : data_get($row, 'minimum_stock', 0) }}"
                min="0"
                step="1"
                class="form-control minimum-stock"
            >
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Foto Barang</label>

            <input
                type="file"
                {!! $attr('photo') !!}
                class="form-control"
                accept=".jpg,.jpeg,.png,.webp"
            >
        </div>

        <div class="col-12">
            <label class="form-label">Catatan Barang</label>

            <input
                type="text"
                {!! $attr('notes') !!}
                value="{{ $templateMode
                    ? ''
                    : data_get($row, 'notes') }}"
                class="form-control"
                maxlength="1000"
            >
        </div>
    </div>
</div>
