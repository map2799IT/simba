@extends('layouts.app')

@section('title', 'Detail Unit Alat')
@section('page-title', 'Detail Unit Alat')

@section('content')
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title font-monospace">
                {{ $asset->asset_number }}
            </h1>

            <p class="page-description mb-0">
                {{ $asset->item?->name ?? '-' }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route(
                    'item-assets.index',
                    [
                        'item_id' => $asset->item_id,
                    ]
                ) }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>

            <a
                href="{{ route(
                    'item-assets.label',
                    $asset
                ) }}"
                class="btn btn-outline-dark"
                target="_blank"
            >
                <i class="bi bi-qr-code me-2"></i>
                Cetak QR Code
            </a>

            @if (
                auth()->user()?->hasRole(
                    'admin',
                    'toolman'
                )
            )
                <a
                    href="{{ route(
                        'item-assets.edit',
                        $asset
                    ) }}"
                    class="btn btn-primary"
                >
                    Edit Unit
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <section class="content-card h-100">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Informasi Unit
                    </h2>
                </div>

                <div class="content-card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">
                            Nomor inventaris
                        </dt>

                        <dd
                            class="col-sm-8
                                font-monospace fw-bold"
                        >
                            {{ $asset->asset_number }}
                        </dd>

                        <dt class="col-sm-4">
                            Nilai kode QR
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->barcode_value }}
                        </dd>

                        <dt class="col-sm-4">
                            Data alat
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->item?->code ?? '-' }}
                            — {{ $asset->item?->name ?? '-' }}
                        </dd>

                        <dt class="col-sm-4">
                            Merek/model
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->item?->brand ?: '-' }}

                            @if ($asset->item?->model)
                                / {{ $asset->item->model }}
                            @endif
                        </dd>

                        <dt class="col-sm-4">
                            Nomor seri
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->serial_number ?: '-' }}
                        </dd>

                        <dt class="col-sm-4">
                            Bengkel
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->workshop?->code ?? '-' }}
                            — {{ $asset->workshop?->name ?? '-' }}
                        </dd>

                        <dt class="col-sm-4">
                            Lokasi
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset
                                ->storageLocation
                                ?->name
                                ?? '-' }}
                        </dd>

                        <dt class="col-sm-4">
                            Kondisi
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->conditionLabel() }}
                        </dd>

                        <dt class="col-sm-4">
                            Status
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->statusLabel() }}
                        </dd>

                        <dt class="col-sm-4">
                            Tanggal diterima
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->received_date
                                ?->format('d-m-Y')
                                ?? '-' }}
                        </dd>

                        <dt class="col-sm-4">
                            Catatan
                        </dt>

                        <dd class="col-sm-8">
                            {{ $asset->notes ?: '-' }}
                        </dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Peminjaman Terakhir
                    </h2>
                </div>

                <div class="content-card-body">
                    @php
                        $lastLoanItem =
                            $asset->loanItems
                                ->sortByDesc('id')
                                ->first();
                    @endphp

                    @if ($lastLoanItem?->loan)
                        <div class="fw-semibold">
                            {{ $lastLoanItem->loan->code }}
                        </div>

                        <div class="small text-secondary">
                            {{ $lastLoanItem
                                ->loan
                                ->borrower
                                ?->name
                                ?? '-' }}
                        </div>
                    @else
                        <span class="text-secondary">
                            Belum pernah dipinjam.
                        </span>
                    @endif
                </div>
            </section>

            <section class="content-card">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Riwayat Kerusakan
                    </h2>
                </div>

                <div class="content-card-body">
                    @if (
                        $asset->damageReports
                            ->isNotEmpty()
                    )
                        <strong>
                            {{ $asset
                                ->damageReports
                                ->count() }}
                        </strong>
                        laporan kerusakan.
                    @else
                        <span class="text-secondary">
                            Belum ada laporan kerusakan.
                        </span>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
