@extends('layouts.app')

@section('title', 'Detail Pengajuan Barang Keluar')
@section('page-title', 'Detail Pengajuan Barang Keluar')

@section('content')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('stock-issues.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <h1 class="page-title">Pengajuan {{ $issueRequest->reference_number }}</h1>
            <p class="page-description mb-0">
                {{ \App\Models\StockIssueRequest::statusLabel($issueRequest->status) }}
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="h5 mb-0">Item yang Diajukan</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th class="text-end">Jumlah</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($issueRequest->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->item?->name ?? '-' }}</div>
                                        <div class="small text-secondary">{{ $item->item?->code ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->item?->isTool() ? 'text-bg-primary' : 'text-bg-success' }}">
                                            {{ $item->item?->typeLabel() ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        @if ($item->item?->isTool())
                                            {{ count($item->asset_ids ?? []) }} unit
                                        @else
                                            {{ number_format((float) $item->quantity, 3, ',', '.') }}
                                            {{ $item->item?->unit?->name ?? '' }}
                                        @endif
                                    </td>
                                    <td>{{ $item->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($issueRequest->description)
                <section class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="h5 mb-0">Keterangan Umum</h2>
                    </div>
                    <div class="content-card-body">
                        <p class="mb-0 text-pre-wrap">{{ $issueRequest->description }}</p>
                    </div>
                </section>
            @endif

            @if ($issueRequest->rejection_reason)
                <section class="content-card mb-4 border-danger">
                    <div class="content-card-header bg-danger-subtle">
                        <h2 class="h5 mb-0 text-danger">Alasan Penolakan</h2>
                    </div>
                    <div class="content-card-body">
                        <p class="mb-0 text-pre-wrap">{{ $issueRequest->rejection_reason }}</p>
                    </div>
                </section>
            @endif
        </div>

        <div class="col-lg-4">
            <section class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="h5 mb-0">Informasi Pengajuan</h2>
                </div>
                <div class="content-card-body">
                    <div class="mb-3">
                        <div class="small text-secondary mb-1">Status</div>
                        @php
                            $statusColors = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'secondary',
                            ];
                            $color = $statusColors[$issueRequest->status] ?? 'secondary';
                        @endphp
                        <span class="badge text-bg-{{ $color }} fs-6">
                            {{ \App\Models\StockIssueRequest::statusLabel($issueRequest->status) }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary mb-1">Tanggal Keluar</div>
                        <div class="fw-semibold">{{ $issueRequest->transaction_date?->format('d-m-Y') ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary mb-1">Jurusan</div>
                        <div class="fw-semibold">{{ $issueRequest->workshop?->code }} — {{ $issueRequest->workshop?->name }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary mb-1">Tujuan</div>
                        <div class="fw-semibold">{{ $issueRequest->destination ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary mb-1">Keperluan</div>
                        <div class="fw-semibold">{{ $issueRequest->purpose ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary mb-1">Diajukan oleh</div>
                        <div class="fw-semibold">{{ $issueRequest->requester?->name ?? '-' }}</div>
                    </div>

                    @if ($issueRequest->reviewer)
                        <div class="mb-3">
                            <div class="small text-secondary mb-1">Diperiksa oleh</div>
                            <div class="fw-semibold">{{ $issueRequest->reviewer?->name ?? '-' }}</div>
                            <div class="small text-secondary">{{ $issueRequest->reviewed_at?->format('d-m-Y H:i') ?? '' }}</div>
                        </div>
                    @endif
                </div>
            </section>

            @if ($canReview && $issueRequest->isPending())
                <section class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="h5 mb-0">Aksi Persetujuan</h2>
                    </div>
                    <div class="content-card-body">
                        <form method="POST" action="{{ route('stock-issues.approve', $issueRequest) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-2"></i> Setujui & Proses
                            </button>
                        </form>

                        <form method="POST" action="{{ route('stock-issues.reject', $issueRequest) }}">
                            @csrf
                            <div class="mb-2">
                                <label for="rejection_reason" class="form-label">Alasan Penolakan</label>
                                <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="3" required
                                    placeholder="Wajib diisi"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-x-circle me-2"></i> Tolak Pengajuan
                            </button>
                        </form>
                    </div>
                </section>
            @endif

            @if ($canCancel)
                <section class="content-card border-warning">
                    <div class="content-card-body">
                        @if ($canEdit)
                            <a href="{{ route('stock-issues.edit', $issueRequest) }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-pencil me-2"></i> Edit Pengajuan
                            </a>
                        @endif
                        <form method="POST" action="{{ route('stock-issues.cancel', $issueRequest) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Batalkan pengajuan ini?')">
                                <i class="bi bi-x-circle me-2"></i> Batalkan Pengajuan
                            </button>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
