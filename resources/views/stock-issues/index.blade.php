@extends('layouts.app')

@section('title', 'Barang Keluar')
@section('page-title', 'Barang Keluar')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp
    <div
        class="d-flex flex-column flex-xl-row
            justify-content-between align-items-xl-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Barang Keluar
            </h1>

            <p class="page-description mb-0">
                Bahan mengurangi stok berdasarkan jumlah. Alat keluar permanen
                dipilih berdasarkan nomor unit/QR dan tidak dihapus dari riwayat.
            </p>
        </div>

        @if (
            auth()->user()?->hasRole(
                'admin',
                'toolman'
            )
        )
            <a
                href="{{ route('stock-issues.create') }}"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-circle me-2"></i>
                Tambah Barang Keluar
            </a>
        @endif

        @if (in_array(auth()->user()?->role, ['admin', 'kepala_bengkel'], true) && \Illuminate\Support\Facades\Route::has('stock-issues.change-approvals'))
            <a href="{{ route('stock-issues.change-approvals') }}" class="btn btn-outline-warning">
                <i class="bi bi-clipboard-check me-1"></i> Persetujuan Edit
            </a>
        @endif
    </div>

    <div class="alert alert-info">
        <strong>Catatan:</strong>
        alat yang hanya digunakan sementara tetap diproses melalui
        modul Peminjaman. Barang Keluar untuk alat berarti unit keluar
        permanen, misalnya penghapusan, hibah, atau mutasi keluar.
        Pengajuan Barang Keluar oleh Toolman memerlukan persetujuan
        Kepala Bengkel atau Wakil Sarpras sebelum stok dikurangi.
    </div>

    @if ($canReview && $pendingRequests->isNotEmpty())
        <section class="content-card mb-4 border-warning">
            <div class="content-card-header bg-warning-subtle">
                <h2 class="h5 mb-0">
                    <i class="bi bi-hourglass-split me-2 text-warning"></i>
                    Pengajuan Menunggu Persetujuan
                    <span class="badge text-bg-warning ms-1">{{ $pendingRequests->count() }}</span>
                    <a href="{{ route('stock-issues.pending') }}" class="btn btn-sm btn-outline-warning float-end">
                        Lihat Semua
                    </a>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Referensi</th>
                            <th>Tanggal</th>
                            <th>Jurusan</th>
                            <th>Diajukan oleh</th>
                            <th>Tujuan</th>
                            <th class="text-end">Item</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingRequests as $req)
                            <tr>
                                <td class="font-monospace fw-semibold">{{ $req->reference_number }}</td>
                                <td>{{ $req->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $req->workshop?->code ?? '-' }}</td>
                                <td>{{ $req->requester?->name ?? '-' }}</td>
                                <td>{{ $req->destination ?? '-' }}</td>
                                <td class="text-end">{{ $req->items->count() }}</td>
                                <td>
                                    <a href="{{ route('stock-issues.show', $req) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye me-1"></i> Tinjau
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="content-card mb-4">
        <div class="content-card-header">
            <form
                method="GET"
                action="{{ route('stock-issues.index') }}"
            >
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
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
                            placeholder="Referensi, tujuan, kode, atau nama"
                        >
                    </div>

                    @if ($isAdmin)
                    <div class="col-12 col-md-4 col-lg-3">
                        <label
                            for="workshop_id"
                            class="form-label"
                        >
                            Bengkel
                        </label>

                        <select
                            id="workshop_id"
                            name="workshop_id"
                            class="form-select"
                        >
                            <option value="">
                                Semua bengkel
                            </option>

                            @foreach ($workshops as $workshop)
                                <option
                                    value="{{ $workshop->id }}"
                                    @selected(
                                        (string)
                                        request('workshop_id')
                                        ===
                                        (string) $workshop->id
                                    )
                                >
                                    {{ $workshop->code }}
                                    — {{ $workshop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-6 col-md-4 col-lg-2">
                        <label
                            for="date_from"
                            class="form-label"
                        >
                            Dari
                        </label>

                        <input
                            id="date_from"
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label
                            for="date_to"
                            class="form-label"
                        >
                            Sampai
                        </label>

                        <input
                            id="date_to"
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="form-control"
                        >
                    </div>

                    <div
                        class="col-12 col-lg-1
                            d-flex align-items-end"
                    >
                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="content-card">
        <div class="table-responsive">
            <table
                class="table table-hover
                    align-middle mb-0"
            >
                <thead>
                    <tr>
                        <x-sortable-header :label="'Tanggal'" :sort-key="'transaction_date'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header :label="'Referensi'" :sort-key="'reference_number'" :sort="$sort" :direction="$direction" />
                        <th>Barang</th>
                        <th>Jenis</th>
                        <th>Jurusan</th>
                        <x-sortable-header :label="'Jumlah'" :sort-key="'quantity'" :sort="$sort" :direction="$direction" :class="'text-right'" />
                        <th class="text-end">Perubahan Stok</th>
                        <th>Tujuan/Keperluan</th>
                        <th>Petugas</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td>
                                {{ $movement->transaction_date
                                    ? $movement->transaction_date->format('d-m-Y')
                                    : '-' }}
                            </td>

                            <td class="font-monospace fw-semibold">
                                {{ $movement->reference_number ?: '-' }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $movement->item?->name ?? '-' }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $movement->item?->code ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <span
                                    class="badge {{ $movement->item?->isTool()
                                        ? 'text-bg-primary'
                                        : 'text-bg-success' }}"
                                >
                                    {{ $movement->item?->typeLabel() ?? '-' }}
                                </span>
                            </td>

                            <td>
                                {{ $movement->workshop?->code ?? '-' }}
                            </td>

                            <td class="text-end fw-semibold">
                                -{{ number_format(
                                    (float) $movement->quantity,
                                    3,
                                    ',',
                                    '.'
                                ) }}

                                <span class="small text-secondary">
                                    {{ $movement->item?->unit?->name ?? '' }}
                                </span>
                            </td>

                            <td class="text-end">
                                {{ number_format(
                                    (float) $movement->stock_before,
                                    3,
                                    ',',
                                    '.'
                                ) }}
                                →
                                <strong>
                                    {{ number_format(
                                        (float) $movement->stock_after,
                                        3,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>
                            </td>

                            <td>
                                <div>
                                    {{ $movement->destination ?: '-' }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $movement->purpose ?: '-' }}
                                </div>
                            </td>

                            <td>
                                {{ $movement->user?->name ?? '-' }}
                            </td>
                            <td class="text-end">
                                @php
                                    $userRole = auth()->user()?->role;
                                    $canEditDirect = in_array($userRole, ['admin', 'kepala_bengkel'], true);
                                    $canAjukan = $userRole === 'toolman';
                                    $hasPending = $movement->pendingIssueChangeRequest !== null;
                                @endphp
                                @if ($canEditDirect)
                                    <a href="{{ route('stock-issues.movement.edit', $movement) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Edit Barang Keluar">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                @elseif ($canAjukan)
                                    @if ($hasPending)
                                        <span class="badge text-bg-warning">Menunggu Persetujuan</span>
                                    @else
                                        <a href="{{ route('stock-issues.movement.edit', $movement) }}"
                                            class="btn btn-sm btn-outline-warning"
                                            title="Ajukan Edit">
                                            <i class="bi bi-pencil-square"></i> Ajukan Edit
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                class="text-center
                                    text-secondary py-5"
                            >
                                Belum ada transaksi Barang Keluar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="content-card-body border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                {{ $movements->links() }}
                <x-per-page-selector :per-page="$perPage" />
            </div>
        @endif
    </section>
@endsection
