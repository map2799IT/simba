@extends('layouts.app')

@section('title', 'Proses Pengembalian')
@section('page-title', 'Proses Pengembalian')

@section('content')
<div class="page-heading">
    <h1 class="page-title">Proses Pengembalian Alat</h1>
    <p class="page-description mb-0">
        {{ $loan->code }} — {{ $loan->borrower?->name }} — {{ $loan->workshop?->code }}
    </p>
</div>

<div class="alert alert-success">
    Unit yang dikembalikan berubah menjadi <strong>Tersedia</strong> dan stok bertambah kembali.
</div>

<form method="POST" action="{{ route('loans.return', $loan) }}">
    @csrf

    <section class="content-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">Pilih</th>
                        <th>Barang</th>
                        <th>Nomor Inventaris</th>
                        <th>Lokasi</th>
                        <th>Kondisi Kembali</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($loan->items as $loanItem)
                        <tr>
                            <td>
                                <input type="hidden"
                                    name="returns[{{ $loanItem->id }}][selected]" value="0">
                                <input type="checkbox"
                                    name="returns[{{ $loanItem->id }}][selected]"
                                    value="1" class="form-check-input" checked>
                            </td>
                            <td>{{ $loanItem->item?->name ?? '-' }}</td>
                            <td class="font-monospace fw-semibold">
                                {{ $loanItem->itemAsset?->asset_number ?? 'Data lama tanpa unit QR' }}
                            </td>
                            <td>{{ $loanItem->itemAsset?->storageLocation?->name ?? '-' }}</td>
                            <td>
                                <select name="returns[{{ $loanItem->id }}][condition]"
                                    class="form-select">
                                    @foreach ($conditions as $value => $label)
                                        <option value="{{ $value }}"
                                            @selected($loanItem->itemAsset?->condition === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                    name="returns[{{ $loanItem->id }}][notes]"
                                    class="form-control" placeholder="Opsional">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="content-card-body border-top d-flex justify-content-end gap-2">
            <a href="{{ route('loans.show', $loan) }}"
                class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-success"
                onclick="return confirm('Proses unit yang dipilih sebagai dikembalikan?');">
                Simpan Pengembalian
            </button>
        </div>
    </section>
</form>
@endsection
