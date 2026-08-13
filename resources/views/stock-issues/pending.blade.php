@extends('layouts.app')

@section('title', 'Pengajuan Menunggu Persetujuan')
@section('page-title', 'Pengajuan Menunggu Persetujuan')

@section('content')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('stock-issues.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <h1 class="page-title">Pengajuan Menunggu Persetujuan</h1>
            <p class="page-description mb-0">Daftar semua pengajuan Barang Keluar yang belum diproses</p>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="content-card-header">
            <form method="GET" action="{{ route('stock-issues.pending') }}">
                <div class="row g-3 align-items-end">
                    @if ($isAdmin)
                    <div class="col-12 col-md-4">
                        <label for="workshop_id" class="form-label">Bengkel</label>
                        <select id="workshop_id" name="workshop_id" class="form-select">
                            <option value="">Semua bengkel</option>
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}"
                                    @selected((string) request('workshop_id') === (string) $workshop->id)>
                                    {{ $workshop->code }} — {{ $workshop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="content-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Referensi</th>
                        <th>Tanggal</th>
                        <th>Jurusan</th>
                        <th>Diajukan oleh</th>
                        <th>Tujuan</th>
                        <th>Keperluan</th>
                        <th class="text-end">Item</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr>
                            <td class="font-monospace fw-semibold">{{ $req->reference_number }}</td>
                            <td>{{ $req->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $req->workshop?->code ?? '-' }}</td>
                            <td>{{ $req->requester?->name ?? '-' }}</td>
                            <td>{{ $req->destination ?? '-' }}</td>
                            <td>{{ $req->purpose ?? '-' }}</td>
                            <td class="text-end">{{ $req->items->count() }}</td>
                            <td class="small text-secondary">{{ $req->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('stock-issues.show', $req) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye me-1"></i> Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-5">
                                <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                                Tidak ada pengajuan menunggu persetujuan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="content-card-body border-top">
                {{ $requests->links() }}
            </div>
        @endif
    </section>
@endsection
