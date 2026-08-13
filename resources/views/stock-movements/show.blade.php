@extends('layouts.app')

@section('title', 'Detail Pergerakan Stok')
@section('page-title', 'Detail Pergerakan Stok')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Detail Pergerakan Stok
        </h1>

        <p class="page-description mb-0">
            Transaksi #{{ $movement->id }}
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">
                    Tanggal
                </dt>

                <dd class="col-sm-8">
                    {{ $movement
                        ->transaction_date
                        ?->format('d-m-Y')
                        ?? '-' }}
                </dd>

                <dt class="col-sm-4">
                    Jenis
                </dt>

                <dd class="col-sm-8">
                    {{ $movement->typeLabel() }}
                </dd>

                <dt class="col-sm-4">
                    Barang
                </dt>

                <dd class="col-sm-8">
                    {{ $movement->item?->code ?? '-' }}
                    — {{ $movement->item?->name ?? '-' }}
                </dd>

                <dt class="col-sm-4">
                    Jumlah
                </dt>

                <dd class="col-sm-8">
                    {{ number_format(
                        (float) $movement->quantity,
                        3,
                        ',',
                        '.'
                    ) }}
                </dd>

                <dt class="col-sm-4">
                    Stok
                </dt>

                <dd class="col-sm-8">
                    {{ number_format(
                        (float)
                        $movement->stock_before,
                        3,
                        ',',
                        '.'
                    ) }}
                    →
                    {{ number_format(
                        (float)
                        $movement->stock_after,
                        3,
                        ',',
                        '.'
                    ) }}
                </dd>

                <dt class="col-sm-4">
                    Referensi
                </dt>

                <dd class="col-sm-8">
                    {{ $movement->reference_number
                        ?: '-' }}
                </dd>

                <dt class="col-sm-4">
                    Sumber
                </dt>

                <dd class="col-sm-8">
                    {{ $movement->source ?: '-' }}
                </dd>

                <dt class="col-sm-4">
                    Keterangan
                </dt>

                <dd class="col-sm-8">
                    {{ $movement->description ?: '-' }}
                </dd>

                <dt class="col-sm-4">
                    Petugas
                </dt>

                <dd class="col-sm-8">
                    {{ $movement->user?->name ?? '-' }}
                </dd>
            </dl>
        </div>
    </section>
@endsection
