@extends('layouts.app')

@section('title', 'Cetak QR Massal')
@section('page-title', 'Cetak QR Massal')

@section('content')
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Cetak QR Massal per Nama Alat
            </h1>

            <p class="page-description mb-0">
                Data dikelompokkan berdasarkan nama alat dan jurusan unit fisik.
                Satu unit menghasilkan satu QR.
            </p>
        </div>

        <a
            href="{{ route('item-assets.index') }}"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-box-seam me-2"></i>
            Lihat Unit Alat
        </a>
    </div>

    <section class="content-card mb-4">
        <div class="content-card-header">
            <form
                method="GET"
                action="{{ route(
                    'item-assets.qr-bulk.index'
                ) }}"
            >
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label
                            for="search"
                            class="form-label"
                        >
                            Pencarian
                        </label>

                        <input
                            id="search"
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Kode master, nama alat, nomor inventaris, seri, merek, atau model"
                        >
                    </div>

                    @if ($isAdmin)
                        <div class="col-12 col-md-4 col-lg-4">
                            <label
                                for="workshop_id"
                                class="form-label"
                            >
                                Jurusan Unit
                            </label>

                            <select
                                id="workshop_id"
                                name="workshop_id"
                                class="form-select"
                            >
                                <option value="">
                                    Semua jurusan
                                </option>

                                @foreach ($workshops as $workshop)
                                    <option
                                        value="{{ $workshop->id }}"
                                        @selected(
                                            (string) $selectedWorkshopId
                                            === (string) $workshop->id
                                        )
                                    >
                                        {{ $workshop->code }}
                                        — {{ $workshop->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div
                        class="col-12 col-md-4 col-lg-2
                            d-flex align-items-end gap-2"
                    >
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Cari
                        </button>

                        <a
                            href="{{ route(
                                'item-assets.qr-bulk.index'
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="content-card">
        <div class="table-responsive">
            <table
                class="table table-hover align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Kode Master</th>
                        <th>Nama Alat</th>
                        <th>Jurusan Unit</th>
                        <th>Lokasi Unit</th>
                        <th class="text-center">Jumlah QR</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($groups as $group)
                        @php
                            $locationLabel = match (true) {
                                (int) $group->location_count === 0 =>
                                    'Lokasi belum ditentukan',

                                (int) $group->location_count === 1
                                && (int) $group->missing_location_count === 0 =>
                                    $group->first_location_name
                                    ?: 'Lokasi belum ditentukan',

                                default =>
                                    'Beberapa lokasi',
                            };

                            $routeParameters = [
                                'item' => $group->item_id,
                                'workshop_id' => $group->workshop_id,
                            ];
                        @endphp

                        <tr>
                            <td class="fw-semibold font-monospace">
                                {{ $group->item_code }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $group->item_name }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $group->asset_units_count }}
                                    unit fisik aktif
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $group->workshop_code }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $group->workshop_name }}
                                </div>
                            </td>

                            <td>
                                {{ $locationLabel }}
                            </td>

                            <td class="text-center">
                                <span class="badge bg-primary fs-6">
                                    {{ $group->asset_units_count }}
                                </span>
                            </td>

                            <td class="text-end">
                                <div
                                    class="d-inline-flex flex-wrap
                                        justify-content-end gap-2"
                                >
                                    <a
                                        href="{{ route(
                                            'item-assets.qr-bulk.print',
                                            $routeParameters
                                        ) }}"
                                        class="btn btn-sm
                                            btn-outline-dark"
                                        target="_blank"
                                    >
                                        <i class="bi bi-printer me-1"></i>
                                        Print
                                    </a>

                                    <a
                                        href="{{ route(
                                            'item-assets.qr-bulk.download',
                                            $routeParameters
                                        ) }}"
                                        class="btn btn-sm
                                            btn-outline-danger"
                                    >
                                        <i class="bi bi-file-earmark-pdf me-1"></i>
                                        Download PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="text-center text-secondary py-5"
                            >
                                Tidak ada unit alat aktif pada jurusan yang dapat dicetak.

                                <div class="small mt-2">
                                    Daftar ini membaca jurusan dari setiap unit alat,
                                    bukan dari master barang.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($groups->hasPages())
            <div class="content-card-body border-top">
                {{ $groups->links() }}
            </div>
        @endif
    </section>
@endsection
