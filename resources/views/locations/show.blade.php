@extends('layouts.app')

@section('title', 'Detail Lokasi')
@section('page-title', 'Detail Lokasi')

@section('content')
    @php
        $formatStock = static function (
            mixed $value
        ): string {
            $formatted = number_format(
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
                {{ $location->code }}
                — {{ $location->name }}
            </h1>

            <p class="page-description mb-0">
                {{ $location->workshop?->code }}
                · {{ $location->typeLabel() }}
                ·
                @if ($location->parent)
                    Turunan dari
                    {{ $location->parent->code }}
                    — {{ $location->parent->name }}
                @else
                    Lokasi Induk
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($canPrint)
    @include(
        'locations._inventory-action-buttons',
        [
            'location' => $location,
            'buttonSize' => 'normal',
            'includeChildren' => true,
        ]
    )
@endif

            @if ($canManage)
                <a
                    href="{{ route(
                        'locations.create',
                        [
                            'mode' => 'child',
                            'parent_id' => $location->id,
                        ]
                    ) }}"
                    class="btn btn-outline-success"
                >
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Lokasi Turunan
                </a>

                <a
                    href="{{ route(
                        'locations.edit',
                        $location
                    ) }}"
                    class="btn btn-primary"
                >
                    Edit Lokasi
                </a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Jenis Alat
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ $summary['tool_types'] }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-6 col-md-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Unit Alat
                    </div>

                    <div class="fs-3 fw-bold text-primary">
                        {{ $summary['tool_units'] }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-6 col-md-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Jenis Bahan
                    </div>

                    <div class="fs-3 fw-bold text-success">
                        {{ $summary['material_types'] }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-6 col-md-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-secondary">
                        Lokasi Turunan
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ $location->children->count() }}
                    </div>
                </div>
            </section>
        </div>
    </div>

    @if ($location->children->isNotEmpty())
        <section class="content-card mb-4">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">
                    Lokasi di Dalamnya
                </h2>
            </div>

            <div class="content-card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach (
                        $location->children
                        as $child
                    )
                        <a
                            href="{{ route(
                                'locations.show',
                                $child
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            {{ $child->code }}
                            — {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="content-card mb-4">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-1">
                Alat di Lokasi Ini
            </h2>

            <div class="small text-secondary">
                Dikelompokkan berdasarkan nama alat.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Alat</th>
                        <th class="text-center">Jumlah Unit</th>
                        <th>Nomor Inventaris</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $toolGroups
                        as $group
                    )
                        @php
                            $first = $group->first();
                        @endphp

                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $first->item?->name }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $first->item?->code }}
                                </div>
                            </td>

                            <td class="text-center fw-bold">
                                {{ $group->count() }}
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($group as $asset)
                                        <span class="badge bg-light text-dark border">
                                            {{ $asset->asset_number }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($group as $asset)
                                        <span class="badge bg-secondary">
                                            {{ $asset->statusLabel() }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-secondary py-4"
                            >
                                Tidak ada unit alat pada lokasi ini.
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
                Bahan di Lokasi Ini
            </h2>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Bahan</th>
                        <th class="text-end">Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($materials as $item)
                        <tr>
                            <td class="font-monospace fw-semibold">
                                {{ $item->code }}
                            </td>

                            <td>{{ $item->name }}</td>

                            <td class="text-end fw-bold">
                                {{ $formatStock(
                                    $item->stock
                                ) }}
                                {{ $item->unit?->name }}
                            </td>

                            <td>
                                {{ $item->statusLabel() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-secondary py-4"
                            >
                                Tidak ada bahan pada lokasi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
