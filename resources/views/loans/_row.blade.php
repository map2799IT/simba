@php
    $rowItemId =
        (string)
        (
            $oldRow['item_id']
            ?? ''
        );
@endphp

<div
    class="loan-row border rounded p-3 mb-3"
    data-index="{{ $index }}"
>
    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-5">
            <label class="form-label">
                Barang
            </label>

            <select
                name="items[{{ $index }}][item_id]"
                class="form-select item-select
                    @error("items.{$index}.item_id")
                        is-invalid
                    @enderror"
                required
            >
                <option value="">
                    Pilih barang
                </option>

                @foreach ($items as $item)
                    <option
                        value="{{ $item->id }}"
                        data-type="{{ $item->type }}"
                        data-stock="{{ $item->workshop_available_stock }}"
                        data-decimal="{{ $item->unit?->allows_decimal ? 1 : 0 }}"
                        @selected(
                            $rowItemId
                            === (string)
                                $item->id
                        )
                    >
                        {{ $item->code }}
                        — {{ $item->name }}
                        — {{
                            $item->isTool()
                                ? 'Alat'
                                : 'Bahan'
                        }}
                        (stok
                        {{
                            class_exists(
                                \App\Support\QuantityFormatter::class
                            )
                                ? \App\Support\QuantityFormatter::format(
                                    $item->workshop_available_stock,
                                    $item->unit
                                )
                                : $item->workshop_available_stock
                        }}
                        {{ $item->unit?->name }})
                    </option>
                @endforeach
            </select>

            @error("items.{$index}.item_id")
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="col-12 col-lg-2">
            <label class="form-label quantity-label">
                Jumlah
            </label>

            <input
                type="number"
                name="items[{{ $index }}][quantity]"
                value="{{ $oldRow['quantity'] ?? 1 }}"
                min="1"
                step="1"
                class="form-control quantity-input
                    @error("items.{$index}.quantity")
                        is-invalid
                    @enderror"
                required
            >

            <div class="form-text quantity-help">
                Pilih barang terlebih dahulu.
            </div>

            @error("items.{$index}.quantity")
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="col-12 col-lg-4">
            <label class="form-label">
                Unit yang Dipilih Otomatis
            </label>

            <div
                class="auto-unit-preview border rounded bg-light p-2"
                style="min-height: 76px; max-height: 190px; overflow-y: auto;"
            >
                <span class="text-secondary">
                    Pilih barang dan masukkan jumlah.
                </span>
            </div>
        </div>

        <div class="col-12 col-lg-1 text-end">
            <label class="form-label d-block">
                &nbsp;
            </label>

            <button
                type="button"
                class="btn btn-outline-danger remove-row"
                title="Hapus baris"
            >
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
