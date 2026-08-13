@php
    $selectedWorkshopId = old(
        'workshop_id',
        $asset->workshop_id
    );

    $selectedLocationId = old(
        'storage_location_id',
        $asset->storage_location_id
    );
@endphp

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <label
            for="asset_number"
            class="form-label"
        >
            Nomor Inventaris
        </label>

        <input
            id="asset_number"
            type="text"
            name="asset_number"
            value="{{ old(
                'asset_number',
                $asset->asset_number
            ) }}"
            class="form-control
                @error('asset_number')
                    is-invalid
                @enderror"
            required
        >

        @error('asset_number')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-12 col-lg-6">
        <label
            for="serial_number"
            class="form-label"
        >
            Nomor Seri
        </label>

        <input
            id="serial_number"
            type="text"
            name="serial_number"
            value="{{ old(
                'serial_number',
                $asset->serial_number
            ) }}"
            class="form-control
                @error('serial_number')
                    is-invalid
                @enderror"
        >

        @error('serial_number')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label
            for="workshop_id"
            class="form-label"
        >
            Bengkel
        </label>

        <select
            id="workshop_id"
            name="workshop_id"
            class="form-select
                @error('workshop_id')
                    is-invalid
                @enderror"
            required
        >
            <option value="">
                Pilih bengkel
            </option>

            @foreach ($workshops as $workshop)
                <option
                    value="{{ $workshop->id }}"
                    @selected(
                        (string) $selectedWorkshopId
                        ===
                        (string) $workshop->id
                    )
                >
                    {{ $workshop->code }}
                    — {{ $workshop->name }}
                </option>
            @endforeach
        </select>

        @error('workshop_id')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label
            for="storage_location_id"
            class="form-label"
        >
            Lokasi Penyimpanan
        </label>

        <select
            id="storage_location_id"
            name="storage_location_id"
            class="form-select
                @error('storage_location_id')
                    is-invalid
                @enderror"
        >
            <option value="">
                Belum ditentukan
            </option>

            @foreach ($locations as $location)
                <option
                    value="{{ $location->id }}"
                    data-workshop-id="{{
                        $location->workshop_id
                    }}"
                    @selected(
                        (string) $selectedLocationId
                        ===
                        (string) $location->id
                    )
                >
                    {{ $location->code }}
                    — {{ $location->name }}
                </option>
            @endforeach
        </select>

        @error('storage_location_id')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label
            for="condition"
            class="form-label"
        >
            Kondisi
        </label>

        <select
            id="condition"
            name="condition"
            class="form-select"
            required
        >
            @foreach (
                $conditionOptions
                as $value => $label
            )
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'condition',
                            $asset->condition
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label
            for="status"
            class="form-label"
        >
            Status
        </label>

        <select
            id="status"
            name="status"
            class="form-select"
            required
        >
            @foreach (
                $statusOptions
                as $value => $label
            )
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'status',
                            $asset->status
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-4">
        <label
            for="received_date"
            class="form-label"
        >
            Tanggal Diterima
        </label>

        <input
            id="received_date"
            type="date"
            name="received_date"
            value="{{ old(
                'received_date',
                $asset->received_date
                    ?->format('Y-m-d')
            ) }}"
            class="form-control"
        >
    </div>

    <div class="col-12 col-md-6">
        <label
            for="unit_price"
            class="form-label"
        >
            Harga per Unit
        </label>

        <input
            id="unit_price"
            type="number"
            name="unit_price"
            value="{{ old(
                'unit_price',
                $asset->unit_price
            ) }}"
            min="0"
            step="1"
            class="form-control"
        >
    </div>

    <div class="col-12 col-md-6">
        <div class="form-check mt-md-4 pt-md-2">
            <input
                id="is_active"
                type="checkbox"
                name="is_active"
                value="1"
                class="form-check-input"
                @checked(
                    old(
                        'is_active',
                        $asset->is_active
                    )
                )
            >

            <label
                for="is_active"
                class="form-check-label"
            >
                Unit aktif
            </label>
        </div>
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
            rows="3"
            class="form-control"
        >{{ old(
            'notes',
            $asset->notes
        ) }}</textarea>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const workshop =
                    document.getElementById(
                        'workshop_id'
                    );

                const location =
                    document.getElementById(
                        'storage_location_id'
                    );

                const filterLocations =
                    function () {
                        if (!workshop || !location) {
                            return;
                        }

                        Array.from(
                            location.options
                        ).forEach(
                            function (option) {
                                const target =
                                    option.dataset
                                        .workshopId;

                                option.hidden =
                                    Boolean(target)
                                    && target
                                    !== workshop.value;
                            }
                        );

                        const selected =
                            location.options[
                                location.selectedIndex
                            ];

                        if (
                            selected
                            && selected.hidden
                        ) {
                            location.value = '';
                        }
                    };

                workshop?.addEventListener(
                    'change',
                    filterLocations
                );

                filterLocations();
            }
        );
    </script>
@endpush
