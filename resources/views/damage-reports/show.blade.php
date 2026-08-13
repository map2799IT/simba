@extends('layouts.app')

@section('title', 'Detail Kerusakan')
@section('page-title', 'Detail Kerusakan')

@section('content')
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                {{ $report->code }}
            </h1>

            <p class="page-description">
                {{ $report->item->code }}
                · {{ $report->item->name }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route(
                    'damage-reports.index'
                ) }}"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Kembali
            </a>

            <span
                class="btn disabled {{
                    $report->statusBadgeClass()
                }}"
            >
                {{ $report->statusLabel() }}
            </span>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <section class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Informasi Laporan
                    </h2>
                </div>

                <div class="content-card-body">
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Tingkat Kerusakan
                            </div>

                            <span
                                class="badge {{
                                    $report
                                        ->severityBadgeClass()
                                }}"
                            >
                                {{ $report->severityLabel() }}
                            </span>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Waktu Laporan
                            </div>

                            <div class="fw-semibold">
                                {{ $report->reported_at
                                    ->format('d-m-Y H:i') }}
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Pelapor
                            </div>

                            <div class="fw-semibold">
                                {{ $report->reporter?->name
                                    ?? 'Sistem' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="small text-secondary">
                                Deskripsi Kerusakan
                            </div>

                            <div class="text-pre-wrap">
                                {{ $report->description }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="small text-secondary">
                                Catatan Pelapor
                            </div>

                            <div class="text-pre-wrap">
                                {{ $report->notes ?: '-' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="small text-secondary mb-2">
                                Bukti Gambar
                            </div>

                            @if ($report->evidence_image_url)
                                <a href="{{ $report->evidence_image_url }}" target="_blank" rel="noopener">
                                    <img src="{{ $report->evidence_image_url }}" alt="Bukti kerusakan"
                                        class="img-fluid rounded border"
                                        style="max-height:360px; object-fit:contain; background:#f8fafc;">
                                </a>
                                <div class="small text-secondary mt-1">
                                    Klik gambar untuk melihat ukuran penuh.
                                </div>
                            @else
                                <div class="text-secondary">
                                    Tidak ada bukti gambar.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="content-card">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Informasi Alat
                    </h2>
                </div>

                <div class="content-card-body">
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Kode
                            </div>

                            <div class="fw-bold">
                                {{ $report->item->code }}
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Bengkel
                            </div>

                            <div class="fw-semibold">
                                {{ $report->item
                                    ->workshop?->name
                                    ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Kategori
                            </div>

                            <div class="fw-semibold">
                                {{ $report->item
                                    ->category?->name
                                    ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="small text-secondary">
                                Lokasi
                            </div>

                            <div class="fw-semibold">
                                {{ $report->item
                                    ->location?->full_path
                                    ?? 'Belum ditentukan' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="small text-secondary">
                                Kondisi Sebelum Laporan
                            </div>

                            <div>
                                {{ \App\Models\Item::
                                    conditionOptions()[
                                        $report->condition_before
                                    ]
                                    ?? $report->condition_before }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="small text-secondary">
                                Kondisi Setelah Penanganan
                            </div>

                            <div>
                                @if ($report->condition_after)
                                    {{ \App\Models\Item::
                                        conditionOptions()[
                                            $report->condition_after
                                        ]
                                        ?? $report->condition_after }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        @if ($report->loanItem?->loan)
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Kerusakan ditemukan saat
                                    pengembalian peminjaman:

                                    <a
                                        href="{{ route(
                                            'loans.show',
                                            $report
                                                ->loanItem
                                                ->loan
                                        ) }}"
                                        class="alert-link"
                                    >
                                        {{ $report
                                            ->loanItem
                                            ->loan
                                            ->code }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            @if (
                $canManage
                && $report->canStart()
            )
                <section class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="h6 fw-bold mb-0">
                            Mulai Perbaikan
                        </h2>
                    </div>

                    <div class="content-card-body">
                        <p class="text-secondary">
                            Setelah dimulai, status alat akan
                            berubah menjadi dalam perawatan.
                        </p>

                        <form
                            method="POST"
                            action="{{ route(
                                'damage-reports.start',
                                $report
                            ) }}"
                            onsubmit="
                                return confirm(
                                    'Mulai proses perbaikan alat ini?'
                                );
                            "
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                <i class="bi bi-tools me-2"></i>
                                Mulai Perbaikan
                            </button>
                        </form>
                    </div>
                </section>
            @endif

            @if (
                $canManage
                && $report->canResolve()
            )
                <section
                    class="content-card"
                    x-data="{
                        resolution:
                            @js(old('resolution', 'repaired'))
                    }"
                >
                    <div class="content-card-header">
                        <h2 class="h6 fw-bold mb-1">
                            Selesaikan Penanganan
                        </h2>

                        <p class="small text-secondary mb-0">
                            Catat hasil pemeriksaan dan perbaikan.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'damage-reports.resolve',
                            $report
                        ) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="content-card-body">
                            <div class="mb-3">
                                <label
                                    for="resolution"
                                    class="form-label"
                                >
                                    Hasil Penanganan
                                </label>

                                <select
                                    id="resolution"
                                    name="resolution"
                                    x-model="resolution"
                                    class="form-select"
                                    required
                                >
                                    <option value="repaired">
                                        Selesai Diperbaiki
                                    </option>

                                    <option value="unrepairable">
                                        Tidak Dapat Diperbaiki
                                    </option>
                                </select>
                            </div>

                            <div
                                class="mb-3"
                                x-show="
                                    resolution === 'repaired'
                                "
                            >
                                <label
                                    for="condition_after"
                                    class="form-label"
                                >
                                    Kondisi Setelah Perbaikan
                                </label>

                                <select
                                    id="condition_after"
                                    name="condition_after"
                                    class="form-select"
                                >
                                    <option value="">
                                        Pilih kondisi
                                    </option>

                                    @foreach (
                                        $conditions
                                        as $value => $label
                                    )
                                        <option
                                            value="{{ $value }}"
                                            @selected(
                                                old(
                                                    'condition_after'
                                                ) === $value
                                            )
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="diagnosis"
                                    class="form-label"
                                >
                                    Diagnosis
                                </label>

                                <textarea
                                    id="diagnosis"
                                    name="diagnosis"
                                    rows="4"
                                    class="form-control"
                                    required
                                >{{ old('diagnosis') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="action_taken"
                                    class="form-label"
                                >
                                    Tindakan yang Dilakukan
                                </label>

                                <textarea
                                    id="action_taken"
                                    name="action_taken"
                                    rows="4"
                                    class="form-control"
                                    required
                                >{{ old('action_taken') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="vendor"
                                    class="form-label"
                                >
                                    Vendor atau Teknisi
                                </label>

                                <input
                                    id="vendor"
                                    type="text"
                                    name="vendor"
                                    value="{{ old('vendor') }}"
                                    class="form-control"
                                >
                            </div>

                            <div class="mb-3">
                                <label
                                    for="repair_cost"
                                    class="form-label"
                                >
                                    Biaya Perbaikan
                                </label>

                                <input
                                    id="repair_cost"
                                    type="number"
                                    name="repair_cost"
                                    value="{{ old(
                                        'repair_cost'
                                    ) }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                >
                            </div>

                            <div>
                                <label
                                    for="resolution_notes"
                                    class="form-label"
                                >
                                    Catatan Penyelesaian
                                </label>

                                <textarea
                                    id="resolution_notes"
                                    name="resolution_notes"
                                    rows="3"
                                    class="form-control"
                                >{{ old(
                                    'resolution_notes'
                                ) }}</textarea>
                            </div>
                        </div>

                        <div
                            class="content-card-body
                                border-top"
                        >
                            <button
                                type="submit"
                                class="btn btn-success w-100"
                                onclick="
                                    return confirm(
                                        'Selesaikan laporan kerusakan ini?'
                                    );
                                "
                            >
                                <i
                                    class="bi
                                        bi-check-circle me-2"
                                ></i>
                                Simpan Penyelesaian
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="content-card">
                    <div class="content-card-header">
                        <h2 class="h6 fw-bold mb-0">
                            Hasil Penanganan
                        </h2>
                    </div>

                    <div class="content-card-body">
                        <div class="mb-3">
                            <div class="small text-secondary">
                                Diagnosis
                            </div>

                            <div class="text-pre-wrap">
                                {{ $report->diagnosis ?: '-' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="small text-secondary">
                                Tindakan
                            </div>

                            <div class="text-pre-wrap">
                                {{ $report->action_taken ?: '-' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="small text-secondary">
                                Vendor atau Teknisi
                            </div>

                            <div>
                                {{ $report->vendor ?: '-' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="small text-secondary">
                                Biaya Perbaikan
                            </div>

                            <div>
                                @if (
                                    $report->repair_cost
                                    !== null
                                )
                                    Rp
                                    {{ number_format(
                                        (float)
                                        $report->repair_cost,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="small text-secondary">
                                Diselesaikan Oleh
                            </div>

                            <div>
                                {{ $report->completer?->name
                                    ?? '-' }}
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection