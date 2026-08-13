@extends('layouts.app')

@section('title', 'Menu Inventaris Lokasi')
@section('page-title', 'Menu Inventaris Lokasi')

@section('content')
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Menu Inventaris Lokasi
            </h1>

            <p class="page-description mb-0">
                Lokasi induk dan lokasi turunan dipisahkan
                agar cakupan laporan mudah dipahami.
            </p>
        </div>

        <a
            href="{{ route('locations.index') }}"
            class="btn btn-outline-secondary"
        >
            Kembali ke Lokasi
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="alert alert-primary border-0 h-100 mb-0">
                <div class="fw-bold mb-1">Lokasi Induk</div>
                Ringkasan dan detail mencakup lokasi induk
                beserta seluruh lokasi turunannya.
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="alert alert-success border-0 h-100 mb-0">
                <div class="fw-bold mb-1">Lokasi Turunan</div>
                Ringkasan dan detail hanya mencakup
                lokasi turunan yang dipilih.
            </div>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="content-card-header">
            <div class="d-flex justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Lokasi Induk</h2>
                    <div class="small text-secondary">
                        Mencakup semua ruangan, lemari, rak,
                        laci, atau kotak di bawahnya.
                    </div>
                </div>

                <span class="badge bg-primary align-self-start">
                    {{ $parents->count() }} lokasi
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Lokasi Induk</th>
                        <th>Jurusan</th>
                        <th class="text-center">Turunan</th>
                        <th style="min-width: 460px;">Opsi Cetak</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($parents as $location)
                        <tr>
                            <td class="font-monospace fw-semibold">
                                {{ $location->code }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $location->name }}
                                </div>

                                <span class="badge bg-primary">
                                    Lokasi Induk
                                </span>
                            </td>

                            <td>
                                {{ $location->workshop?->code ?? '-' }}
                            </td>

                            <td class="text-center">
                                {{ $childCounts[$location->id] ?? 0 }}
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a
                                        href="{{ route(
                                            'locations.inventory.summary',
                                            [
                                                'storageLocation' =>
                                                    $location->getRouteKey(),
                                                'include_children' => 1,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Lihat Ringkasan
                                    </a>

                                    <a
                                        href="{{ route(
                                            'locations.inventory.summary.print',
                                            [
                                                'storageLocation' =>
                                                    $location->getRouteKey(),
                                                'include_children' => 1,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-primary"
                                        target="_blank"
                                    >
                                        Print Ringkasan
                                    </a>

                                    <a
                                        href="{{ route(
                                            'locations.inventory.complete',
                                            [
                                                'storageLocation' =>
                                                    $location->getRouteKey(),
                                                'include_children' => 1,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-outline-dark"
                                        target="_blank"
                                    >
                                        Print Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                Belum ada lokasi induk aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div class="d-flex justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Lokasi Turunan</h2>
                    <div class="small text-secondary">
                        Mencetak isi lokasi turunan yang dipilih saja.
                    </div>
                </div>

                <span class="badge bg-success align-self-start">
                    {{ $children->count() }} lokasi
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Lokasi Turunan</th>
                        <th>Induk</th>
                        <th>Jurusan</th>
                        <th style="min-width: 460px;">Opsi Cetak</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($children as $location)
                        <tr>
                            <td class="font-monospace fw-semibold">
                                {{ $location->code }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $location->name }}
                                </div>

                                <span class="badge bg-success">
                                    Lokasi Turunan
                                </span>
                            </td>

                            <td>
                                {{ $location->parent?->code ?? '-' }}
                                — {{ $location->parent?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $location->workshop?->code ?? '-' }}
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a
                                        href="{{ route(
                                            'locations.inventory.summary',
                                            [
                                                'storageLocation' =>
                                                    $location->getRouteKey(),
                                                'include_children' => 0,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-outline-success"
                                    >
                                        Lihat Ringkasan
                                    </a>

                                    <a
                                        href="{{ route(
                                            'locations.inventory.summary.print',
                                            [
                                                'storageLocation' =>
                                                    $location->getRouteKey(),
                                                'include_children' => 0,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-success"
                                        target="_blank"
                                    >
                                        Print Ringkasan
                                    </a>

                                    <a
                                        href="{{ route(
                                            'locations.inventory.complete',
                                            [
                                                'storageLocation' =>
                                                    $location->getRouteKey(),
                                                'include_children' => 0,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-outline-dark"
                                        target="_blank"
                                    >
                                        Print Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                Belum ada lokasi turunan aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
