@extends('layouts.app')

@section('title', 'Edit Pengajuan Barang Keluar')
@section('page-title', 'Edit Pengajuan Barang Keluar')

@section('content')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('stock-issues.show', $issueRequest) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <h1 class="page-title">Edit Pengajuan {{ $issueRequest->reference_number }}</h1>
            <p class="page-description mb-0">Status: Menunggu Persetujuan</p>
        </div>
    </div>

    <div class="alert alert-warning">
        <strong>Perhatian:</strong> Edit hanya dapat dilakukan saat pengajuan masih menunggu persetujuan.
        Setelah disetujui, perubahan tidak dapat dilakukan.
    </div>

    <form method="POST" action="{{ route('stock-issues.update', $issueRequest) }}">
        @csrf
        @method('PUT')

        <section class="content-card mb-4">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">Informasi Pengeluaran</h2>
            </div>
            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Jurusan</label>
                        <input type="text" class="form-control" value="{{ $selectedWorkshop->code }} — {{ $selectedWorkshop->name }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label for="transaction_date" class="form-label">Tanggal Keluar *</label>
                        <input type="date" id="transaction_date" name="transaction_date" class="form-control"
                            value="{{ old('transaction_date', $issueRequest->transaction_date?->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="reference_number" class="form-label">Nomor Referensi</label>
                        <input type="text" id="reference_number" name="reference_number" class="form-control"
                            value="{{ old('reference_number', $issueRequest->reference_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="destination" class="form-label">Tujuan</label>
                        <input type="text" id="destination" name="destination" class="form-control"
                            value="{{ old('destination', $issueRequest->destination) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="purpose" class="form-label">Keperluan</label>
                        <input type="text" id="purpose" name="purpose" class="form-control"
                            value="{{ old('purpose', $issueRequest->purpose) }}">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Keterangan Umum</label>
                        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $issueRequest->description) }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="content-card mb-4">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">Item Pengeluaran</h2>
            </div>
            <div class="content-card-body">
                @php
                    $oldRows = old('items', $issueRequest->items->map(fn ($i) => [
                        'item_id' => $i->item_id,
                        'quantity' => $i->quantity,
                        'asset_ids' => $i->asset_ids ?? [],
                        'notes' => $i->notes,
                    ])->all());
                @endphp

                <div class="table-responsive">
                    <table class="table align-middle" id="issue-rows">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th style="width: 140px;">Jumlah (Bahan)</th>
                                <th>Catatan</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($oldRows as $index => $row)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-select form-select-sm" required>
                                            <option value="">— Pilih barang —</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}"
                                                    @selected((string) ($row['item_id'] ?? '') === (string) $item->id)>
                                                    {{ $item->code }} — {{ $item->name }}
                                                    ({{ $item->isTool() ? 'Alat' : 'Bahan' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm"
                                            value="{{ $row['quantity'] ?? '' }}" step="0.001" min="0">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][notes]" class="form-control form-control-sm"
                                            value="{{ $row['notes'] ?? '' }}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-row">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                </button>
            </div>
        </section>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('stock-issues.show', $issueRequest) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>

    <script>
    document.getElementById('add-row').addEventListener('click', function () {
        const tbody = document.querySelector('#issue-rows tbody');
        const index = tbody.children.length;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><select name="items[${index}][item_id]" class="form-select form-select-sm" required>
                <option value="">— Pilih barang —</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">{{ $item->code }} — {{ $item->name }}</option>
                @endforeach
            </select></td>
            <td><input type="number" name="items[${index}][quantity]" class="form-control form-control-sm" step="0.001" min="0"></td>
            <td><input type="text" name="items[${index}][notes]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    });
    </script>
@endsection
