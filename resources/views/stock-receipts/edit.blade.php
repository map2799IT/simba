@extends('layouts.app')

@section('title', 'Edit Barang Masuk')
@section('page-title', 'Edit Barang Masuk')

@section('content')
    @php
        $currentWorkshopId = old(
            'workshop_id',
            $stockReceipt->workshop_id
        );

        $allowsDecimal = (bool) $stockReceipt
            ->item
            ?->unit
            ?->allows_decimal;

        $quantityStep = $allowsDecimal
            && ! $stockReceipt->item?->isTool()
                ? '0.001'
                : '1';
    @endphp

    <div class="d-flex justify-content-between align-items-center gap-3 page-heading">
        <div>
            <h1 class="page-title">Edit Barang Masuk</h1>
            <p class="page-description mb-0">
                {{ $stockReceipt->receipt_code }} — {{ $stockReceipt->item?->name }}
            </p>
        </div>

        <a
            href="{{ route('stock-receipts.show', $stockReceipt) }}"
            class="btn btn-outline-secondary"
        >
            Kembali
        </a>
    </div>

    @if ($requiresApproval)
        <div class="alert alert-warning border-0">
            Perubahan Toolman tidak langsung diterapkan. Permintaan akan menunggu persetujuan Kepala Bengkel atau Administrator.
        </div>
    @else
        <div class="alert alert-info border-0">
            Perubahan oleh Kepala Bengkel/Administrator diterapkan langsung pada stok dan unit alat.
        </div>
    @endif

    @if ($stockReceipt->pendingChangeRequest)
        <div class="alert alert-secondary">
            Sudah ada permintaan perubahan yang menunggu persetujuan. Menyimpan kembali akan memperbarui permintaan tersebut.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('stock-receipts.update', $stockReceipt) }}"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        <section class="content-card">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">Data Barang Masuk</h2>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Master Barang</label>
                        <input
                            type="text"
                            class="form-control"
                            value="{{ $stockReceipt->item?->code }} — {{ $stockReceipt->item?->name }}"
                            disabled
                        >
                        <div class="form-text">
                            Master barang tidak dapat diganti agar nomor inventaris, QR, dan riwayat tetap konsisten.
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="quantity" class="form-label">Jumlah</label>
                        <input
                            id="quantity"
                            type="number"
                            name="quantity"
                            value="{{ old(
                                'quantity',
                                \App\Support\QuantityFormatter::inputValue(
                                    $stockReceipt->quantity,
                                    $allowsDecimal
                                )
                            ) }}"
                            min="{{ $quantityStep }}"
                            step="{{ $quantityStep }}"
                            class="form-control @error('quantity') is-invalid @enderror"
                            required
                        >
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="receipt_date" class="form-label">Tanggal Perolehan</label>
                        <input
                            id="receipt_date"
                            type="date"
                            name="receipt_date"
                            value="{{ old(
                                'receipt_date',
                                $stockReceipt->transaction_date?->format('Y-m-d')
                            ) }}"
                            class="form-control @error('receipt_date') is-invalid @enderror"
                            required
                        >
                        @error('receipt_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="workshop_id" class="form-label">Jurusan</label>

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
                                        @selected((string) $currentWorkshopId === (string) $workshop->id)
                                    >
                                        {{ $workshop->code }} — {{ $workshop->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="hidden"
                                id="workshop_id"
                                name="workshop_id"
                                value="{{ $stockReceipt->workshop_id }}"
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $stockReceipt->workshop?->code }} — {{ $stockReceipt->workshop?->name }}"
                                disabled
                            >
                        @endif
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="storage_location_id" class="form-label">
                            Lokasi Penyimpanan
                        </label>
                        <select
                            id="storage_location_id"
                            name="storage_location_id"
                            class="form-select @error('storage_location_id') is-invalid @enderror"
                            required
                        >
                            @foreach ($locations as $location)
                                <option
                                    value="{{ $location->id }}"
                                    data-workshop="{{ $location->workshop_id }}"
                                    @selected(
                                        (string) old(
                                            'storage_location_id',
                                            $stockReceipt->storage_location_id
                                        ) === (string) $location->id
                                    )
                                >
                                    {{ $location->code }} — {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('storage_location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="document_number" class="form-label">Nomor Dokumen</label>
                        <input
                            id="document_number"
                            type="text"
                            name="document_number"
                            value="{{ old('document_number', $stockReceipt->reference_number) }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="source" class="form-label">Sumber Perolehan</label>
                        <input
                            id="source"
                            type="text"
                            name="source"
                            value="{{ old('source', $stockReceipt->source) }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="fund_source" class="form-label">Sumber Dana</label>
                        <input
                            id="fund_source"
                            type="text"
                            name="fund_source"
                            value="{{ old('fund_source', $stockReceipt->fund_source) }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="brand" class="form-label">Merek</label>
                        <input
                            id="brand"
                            type="text"
                            name="brand"
                            value="{{ old('brand', $stockReceipt->brand) }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="model" class="form-label">Model/Tipe</label>
                        <input
                            id="model"
                            type="text"
                            name="model"
                            value="{{ old('model', $stockReceipt->model) }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="condition" class="form-label">Kondisi</label>
                        <select
                            id="condition"
                            name="condition"
                            class="form-select"
                            required
                        >
                            @foreach ($conditions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('condition', $stockReceipt->condition) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="specification" class="form-label">Spesifikasi</label>
                        <textarea
                            id="specification"
                            name="specification"
                            rows="3"
                            class="form-control"
                        >{{ old('specification', $stockReceipt->specification) }}</textarea>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="unit_price" class="form-label">Harga per Unit</label>
                        <input
                            id="unit_price"
                            type="number"
                            name="unit_price"
                            value="{{ old('unit_price', $stockReceipt->unit_price) }}"
                            min="0"
                            step="0.01"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-12 col-lg-5">
                                @include(
                                    'stock-receipts._photo-card',
                                    [
                                        'stockReceipt' => $stockReceipt,
                                        'kind' => 'active',
                                        'title' => 'Foto Aktif Saat Ini',
                                        'path' => $stockReceipt->photo_path,
                                        'height' => 230,
                                        'emptyText' => 'Belum ada foto aktif.',
                                    ]
                                )
                            </div>

                            <div class="col-12 col-lg-7">
                                <label
                                    for="photo"
                                    class="form-label"
                                >
                                    {{
                                        $stockReceipt->photo_path
                                            ? 'Ganti Foto'
                                            : 'Tambahkan Foto'
                                    }}
                                </label>

                                <input
                                    id="photo"
                                    type="file"
                                    name="photo"
                                    class="form-control
                                        @error('photo')
                                            is-invalid
                                        @enderror"
                                    accept="image/jpeg,image/png,image/webp"
                                >

                                @error('photo')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="form-text">
                                    JPG, PNG, atau WEBP. Maksimal 3 MB.
                                    Kosongkan untuk mempertahankan foto aktif.
                                </div>

                                @if ($requiresApproval)
                                    <div class="alert alert-warning py-2 mt-3 mb-0">
                                        Foto baru menjadi foto usulan dan belum mengganti
                                        foto aktif sampai disetujui Kepala Bengkel
                                        atau Administrator.
                                    </div>
                                @endif

                                <div
                                    id="photo-preview-wrapper"
                                    class="mt-3 d-none"
                                >
                                    <div class="small text-secondary mb-2">
                                        Pratinjau Foto Baru
                                    </div>

                                    <img
                                        id="photo-preview"
                                        alt="Pratinjau foto baru"
                                        class="img-fluid rounded border bg-light w-100"
                                        style="height: 230px; object-fit: contain;"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-control"
                        >{{ old('notes', $stockReceipt->description) }}</textarea>
                    </div>

                    @if ($requiresApproval)
                        <div class="col-12">
                            <label for="change_reason" class="form-label">Alasan Perubahan</label>
                            <textarea
                                id="change_reason"
                                name="change_reason"
                                rows="3"
                                class="form-control @error('change_reason') is-invalid @enderror"
                                required
                                placeholder="Jelaskan data yang salah dan alasan perbaikannya."
                            >{{ old('change_reason') }}</textarea>
                            @error('change_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>

            <div class="content-card-body border-top d-flex justify-content-end gap-2">
                <a
                    href="{{ route('stock-receipts.show', $stockReceipt) }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn {{ $requiresApproval ? 'btn-warning' : 'btn-primary' }}"
                >
                    {{ $requiresApproval ? 'Ajukan Persetujuan' : 'Simpan Perubahan' }}
                </button>
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const workshop = document.getElementById('workshop_id');
            const location = document.getElementById('storage_location_id');

            const filterLocations = function () {
                const workshopId = workshop?.value ?? '';

                Array.from(location.options).forEach(function (option) {
                    option.hidden = option.dataset.workshop !== workshopId;

                    if (option.hidden && option.selected) {
                        option.selected = false;
                    }
                });

                if (! location.value) {
                    const first = Array.from(location.options)
                        .find(function (option) {
                            return ! option.hidden;
                        });

                    if (first) {
                        first.selected = true;
                    }
                }
            };

            workshop?.addEventListener('change', filterLocations);
            filterLocations();

            const photoInput =
                document.getElementById('photo');

            const photoPreview =
                document.getElementById('photo-preview');

            const photoPreviewWrapper =
                document.getElementById(
                    'photo-preview-wrapper'
                );

            photoInput?.addEventListener(
                'change',
                function () {
                    const file =
                        photoInput.files?.[0];

                    if (! file) {
                        photoPreview?.removeAttribute('src');
                        photoPreviewWrapper?.classList.add('d-none');
                        return;
                    }

                    if (! file.type.startsWith('image/')) {
                        photoInput.value = '';
                        photoPreview?.removeAttribute('src');
                        photoPreviewWrapper?.classList.add('d-none');
                        window.alert('File harus berupa gambar.');
                        return;
                    }

                    const reader =
                        new FileReader();

                    reader.addEventListener(
                        'load',
                        function (event) {
                            if (
                                photoPreview
                                && event.target?.result
                            ) {
                                photoPreview.src =
                                    event.target.result;

                                photoPreviewWrapper
                                    ?.classList
                                    .remove('d-none');
                            }
                        }
                    );

                    reader.readAsDataURL(file);
                }
            );
        });
    </script>
@endpush
