@extends('layouts.app')

@section('title', 'Ringkasan Isi Lokasi')
@section('page-title', 'Ringkasan Isi Lokasi')

@section('content')
    @php
        $formatStock = static function (
            mixed $value
        ): string {
            $formatted =
                number_format(
                    (float) $value,
                    3,
                    ',',
                    '.'
                );

            return rtrim(
                rtrim(
                    $formatted,
                    '0'
                ),
                ','
            );
        };
    @endphp

    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Ringkasan Isi Lokasi
            </h1>

            <p class="page-description mb-2">
                {{ $location->code }}
                — {{ $location->name }}
            </p>

            @if ($location->parent_id === null)
                <span class="badge bg-primary">Lokasi Induk</span>
                <span class="badge bg-light text-dark border">
                    Mencakup seluruh turunan
                </span>
            @else
                <span class="badge bg-success">Lokasi Turunan</span>
                <span class="badge bg-light text-dark border">
                    Induk: {{ $location->parent?->code ?? '-' }}
                </span>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route(
                    'locations.inventory.summary',
                    [
                        'storageLocation' =>
                            $location
                                ->getRouteKey(),

                        'include_children' =>
                            $includeChildren
                                ? 0
                                : 1,
                    ]
                ) }}"
                class="btn btn-outline-secondary"
            >
                @if ($includeChildren)
                    Tampilkan Lokasi Ini Saja
                @else
                    Sertakan Semua Turunan
                @endif
            </a>

            <a
                href="{{ route(
                    'locations.inventory.summary.print',
                    [
                        'storageLocation' =>
                            $location->getRouteKey(),
                        'include_children' =>
                            $includeChildren ? 1 : 0,
                    ]
                ) }}"
                class="btn btn-primary"
                target="_blank"
            >
                <i class="bi bi-printer me-2"></i>
                Print Ringkasan
            </a>

            <a
                href="{{ route(
                    'locations.inventory.summary.pdf',
                    [
                        'storageLocation' =>
                            $location->getRouteKey(),
                        'include_children' =>
                            $includeChildren ? 1 : 0,
                    ]
                ) }}"
                class="btn btn-outline-danger"
            >
                PDF Ringkasan
            </a>

            <a
                href="{{ route(
                    'locations.inventory.complete',
                    [
                        'storageLocation' =>
                            $location->getRouteKey(),
                        'include_children' =>
                            $includeChildren ? 1 : 0,
                    ]
                ) }}"
                class="btn btn-outline-dark"
                target="_blank"
            >
                Print Detail
            </a>

            <a
                href="{{ route('locations.inventory.menu') }}"
                class="btn btn-outline-secondary"
            >
                Menu Cetak
            </a>
        </div>
    </div>

    <div class="alert alert-info border-0">
        <div class="fw-bold mb-1">
            Mode ringkasan
        </div>

        Hanya menampilkan nama master alat/bahan,
        jumlah unit atau stok, dan lokasi.
        Nomor inventaris, nomor seri, QR, dan
        rincian setiap unit tidak ditampilkan.

        @if ($includeChildren)
            Data mencakup lokasi ini dan seluruh
            lokasi turunannya.
        @else
            Data hanya mencakup isi langsung
            lokasi ini.
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Jenis Alat
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ $summary[
                            'tool_types'
                        ] }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-6 col-lg-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Total Unit Alat
                    </div>

                    <div class="fs-3 fw-bold text-primary">
                        {{ $summary[
                            'tool_units'
                        ] }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-6 col-lg-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Jenis Bahan
                    </div>

                    <div class="fs-3 fw-bold text-success">
                        {{ $summary[
                            'material_types'
                        ] }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-6 col-lg-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Lokasi Dicakup
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ $summary[
                            'location_count'
                        ] }}
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-1">
                Cakupan Lokasi
            </h2>

            <div class="small text-secondary">
                Lokasi induk otomatis merangkum
                semua lokasi di bawahnya.
            </div>
        </div>

        <div class="content-card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach (
                    $coveredLocations
                    as $coveredLocation
                )
                    <span
                        class="badge bg-light
                            text-dark border"
                    >
                        {{ $coveredLocation->code }}
                        — {{ $coveredLocation->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content-card mb-4">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-1">
                Ringkasan Alat
            </h2>

            <div class="small text-secondary">
                Satu baris untuk setiap nama/master alat.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Alat</th>
                        <th>Kategori</th>
                        <th class="text-center">
                            Jumlah Unit
                        </th>
                        <th>Lokasi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $toolSummaries
                        as $row
                    )
                        <tr>
                            <td
                                class="font-monospace
                                    fw-semibold"
                            >
                                {{ $row['code'] }}
                            </td>

                            <td class="fw-semibold">
                                {{ $row['name'] }}
                            </td>

                            <td>
                                {{ $row['category'] }}
                            </td>

                            <td
                                class="text-center
                                    fw-bold text-primary"
                            >
                                {{ $row[
                                    'unit_count'
                                ] }}
                                {{ $row[
                                    'unit_name'
                                ] }}
                            </td>

                            <td>
                                {{ implode(
                                    ', ',
                                    $row[
                                        'locations'
                                    ]
                                ) ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="text-center
                                    text-secondary py-4"
                            >
                                Tidak ada alat aktif pada
                                cakupan lokasi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-1">
                Ringkasan Bahan
            </h2>

            <div class="small text-secondary">
                Satu baris untuk setiap nama/master bahan.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Bahan</th>
                        <th>Kategori</th>
                        <th class="text-end">Stok</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $materialSummaries
                        as $row
                    )
                        <tr>
                            <td
                                class="font-monospace
                                    fw-semibold"
                            >
                                {{ $row['code'] }}
                            </td>

                            <td class="fw-semibold">
                                {{ $row['name'] }}
                            </td>

                            <td>
                                {{ $row['category'] }}
                            </td>

                            <td
                                class="text-end
                                    fw-bold text-success"
                            >
                                {{ $formatStock(
                                    $row['stock']
                                ) }}
                                {{ $row[
                                    'unit_name'
                                ] }}
                            </td>

                            <td>
                                {{ $row[
                                    'location'
                                ] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="text-center
                                    text-secondary py-4"
                            >
                                Tidak ada bahan aktif pada
                                cakupan lokasi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
