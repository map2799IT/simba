@extends('layouts.app')

@section('title', 'Laporan Inventaris')
@section('page-title', 'Laporan Inventaris')

@section('content')
    @php
        $money = static fn (
            mixed $value
        ): string => 'Rp '.
            number_format(
                (float) $value,
                0,
                ',',
                '.'
            );

        $number = static function (
            mixed $value,
            bool $allowsDecimal = false
        ): string {
            if (! $allowsDecimal) {
                return number_format(
                    (float) $value,
                    0,
                    ',',
                    '.'
                );
            }

            return rtrim(
                rtrim(
                    number_format(
                        (float) $value,
                        3,
                        ',',
                        '.'
                    ),
                    '0'
                ),
                ','
            );
        };

        $exportQuery = array_filter([
            'search' =>
                request('search'),

            'workshop_id' =>
                $selectedWorkshopId,

            'item_category_id' =>
                request(
                    'item_category_id'
                ),

            'type' =>
                request('type'),
        ], static fn ($value): bool =>
            $value !== null
            && $value !== ''
        );
    @endphp

    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Laporan Inventaris
            </h1>

            <p class="page-description mb-0">
                Ringkasan inventaris alat dan bahan sesuai
                kelas/bengkel yang dapat diakses.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route(
                    'reports.export.excel',
                    $exportQuery
                ) }}"
                class="btn btn-outline-success"
            >
                <i class="bi bi-file-earmark-excel me-2"></i>
                Export Excel
            </a>

            <a
                href="{{ route(
                    'reports.export.pdf',
                    $exportQuery
                ) }}"
                class="btn btn-outline-danger"
            >
                <i class="bi bi-file-earmark-pdf me-2"></i>
                Export PDF
            </a>
        </div>
    </div>

    @if ($accessWarning)
        <div class="alert alert-warning">
            {{ $accessWarning }}
        </div>
    @endif

    @if ($isWorkshopRestricted)
        <div class="alert alert-info py-2">
            Data dibatasi otomatis berdasarkan kelas/bengkel
            pada akun Anda. Filter bengkel tidak dapat diganti.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="text-secondary mb-2">
                        Total data barang
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ $totalItems }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="text-secondary mb-2">
                        Data alat aktif
                    </div>

                    <div class="fs-3 fw-bold text-primary">
                        {{ $activeTools }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="text-secondary mb-2">
                        Data bahan aktif
                    </div>

                    <div class="fs-3 fw-bold text-success">
                        {{ $activeMaterials }}
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <section class="content-card h-100">
                <div class="content-card-body">
                    <div class="text-secondary mb-2">
                        Bahan stok minimum
                    </div>

                    <div class="fs-3 fw-bold text-danger">
                        {{ $lowStockMaterials }}
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="content-card-header">
            <form
                method="GET"
                action="{{ route(
                    'reports.inventory'
                ) }}"
            >
                <div class="row g-3">
                    <div class="col-12 col-xl-4">
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
                            placeholder="Kode, nama, merek, atau model"
                        >
                    </div>

                    <div class="col-12 col-md-4 col-xl-3">
                        <label
                            for="workshop_id"
                            class="form-label"
                        >
                            Bengkel/Kelas
                        </label>

                        <select
                            id="workshop_id"
                            name="workshop_id"
                            class="form-select"
                            @disabled(
                                $isWorkshopRestricted
                            )
                        >
                            @unless(
                                $isWorkshopRestricted
                            )
                                <option value="">
                                    Semua bengkel
                                </option>
                            @endunless

                            @foreach (
                                $workshops
                                as $workshop
                            )
                                <option
                                    value="{{ $workshop->id }}"
                                    @selected(
                                        (int)
                                        $selectedWorkshopId
                                        ===
                                        (int)
                                        $workshop->id
                                    )
                                >
                                    {{ $workshop->code }}
                                    — {{ $workshop->name }}
                                </option>
                            @endforeach
                        </select>

                        @if (
                            $isWorkshopRestricted
                            && $selectedWorkshopId
                        )
                            <input
                                type="hidden"
                                name="workshop_id"
                                value="{{ $selectedWorkshopId }}"
                            >
                        @endif
                    </div>

                    <div class="col-12 col-md-4 col-xl-3">
                        <label
                            for="item_category_id"
                            class="form-label"
                        >
                            Kategori
                        </label>

                        <select
                            id="item_category_id"
                            name="item_category_id"
                            class="form-select"
                        >
                            <option value="">
                                Semua kategori
                            </option>

                            @foreach (
                                $categories
                                as $category
                            )
                                <option
                                    value="{{ $category->id }}"
                                    @selected(
                                        (string)
                                        request(
                                            'item_category_id'
                                        )
                                        ===
                                        (string)
                                        $category->id
                                    )
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div
                        class="col-12 col-md-4 col-xl-2
                            d-flex align-items-end gap-2"
                    >
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-search me-1"></i>
                            Cari
                        </button>

                        <a
                            href="{{ route(
                                'reports.inventory'
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
        <div
            class="content-card-header
                d-flex flex-column flex-md-row
                justify-content-between gap-2"
        >
            <div>
                <h2 class="h6 fw-bold mb-1">
                    Daftar Inventaris
                </h2>

                <div class="small text-secondary">
                    Ditemukan {{ $totalItems }} data barang.
                </div>
            </div>

            <div class="fw-bold">
                Total nilai: {{ $money($totalValue) }}
            </div>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                    align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Kategori</th>
                        <th>Bengkel/Lokasi</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                        <th class="text-end">Stok</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Nilai</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($items as $item)
                        @php
                            $allowsDecimal = (bool) ($item->allows_decimal ?? false);
                            $unitLabel = $item->unit_symbol ?: ($item->unit_name ?? '');
                            $conditionLabels = [
                                'good' => 'Baik', 'minor_damage' => 'Rusak Ringan',
                                'major_damage' => 'Rusak Berat', 'mixed' => 'Beragam',
                            ];
                            $statusLabels = ['available' => 'Tersedia', 'out_of_stock' => 'Habis'];
                            $conditionLabel = $conditionLabels[$item->report_condition ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($item->report_condition ?? '-')));
                            $statusLabel = $statusLabels[$item->report_status ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($item->report_status ?? '-')));
                            $typeLabel = $item->type === 'tool' ? 'Alat' : 'Bahan';
                        @endphp
                        <tr>
                            <td class="fw-semibold font-monospace">{{ $item->code }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item->name }}</div>
                                <div class="small text-secondary">
                                    {{ $item->report_brand ?? '-' }}
                                    @if (!empty($item->report_model) && $item->report_model !== '-')
                                        / {{ $item->report_model }}
                                    @endif
                                </div>
                            </td>
                            <td>{{ $item->category_name ?? '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item->report_workshop_code ?? '-' }}</div>
                                <div class="small text-secondary">{{ $item->report_location_name ?? '-' }}</div>
                            </td>
                            <td>{{ $conditionLabel }}</td>
                            <td>{{ $statusLabel }}</td>
                            <td class="text-end">
                                <strong>{{ $number($item->report_stock ?? 0, $allowsDecimal) }}</strong>
                                <span class="small text-secondary">{{ $unitLabel }}</span>
                            </td>
                            <td class="text-end">{{ $money($item->report_unit_price ?? 0) }}</td>
                            <td class="text-end fw-bold">{{ $money($item->report_inventory_value ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-5">
                                Tidak ada data inventaris yang dapat ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="content-card-body border-top">
                {{ $items->links() }}
            </div>
        @endif
    </section>
@endsection
