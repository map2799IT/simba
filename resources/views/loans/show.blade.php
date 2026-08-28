@extends('layouts.app')

@section('title', 'Detail Peminjaman')
@section('page-title', 'Detail Peminjaman')

@section('content')
    @php
        $toolItems =
            $loan->items
                ->where(
                    'is_consumable',
                    false
                );

        $materialItems =
            $loan->items
                ->where(
                    'is_consumable',
                    true
                );

        $scheduleReached =
            ! $loan->scheduled_at
            || $loan->scheduled_at->isPast()
            || $loan->scheduled_at->isCurrentMinute();
    @endphp

    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Detail Peminjaman
            </h1>

            <p class="page-description mb-0">
                {{ $loan->code }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if (
                $canManage
                && in_array(
                    $loan->status,
                    [
                        'borrowed',
                        'partially_returned',
                    ],
                    true
                )
            )
                <a
                    href="{{ route(
                        'loans.return-form',
                        $loan
                    ) }}"
                    class="btn btn-success"
                >
                    Proses Pengembalian
                </a>
            @endif

            <a
                href="{{ route('loans.index') }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>
        </div>
    </div>

    @if ($loan->status === 'pending')
        <div class="alert alert-warning">
            <div class="fw-bold">
                Pengajuan sudah tersimpan dan sedang menunggu persetujuan.
            </div>

            <div class="mt-1">
                Tujuan:
                Toolman
                <strong>{{ $loan->workshop?->code }}</strong>.
                Stok belum berkurang dan unit belum berstatus Dipinjam.
            </div>
        </div>
    @elseif ($loan->status === 'approved')
        <div class="alert alert-info">
            Pengajuan sudah disetujui oleh
            <strong>
                {{ $loan->assignedToolman?->name ?? $loan->approver?->name ?? 'petugas' }}
            </strong>.
            Serah terima dilakukan saat jadwal peminjaman tiba.
        </div>
    @endif

    <section class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="small text-secondary">
                        Peminjam
                    </div>

                    <div class="fw-bold">
                        {{ $loan->borrower?->name ?? '-' }}
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="small text-secondary">
                        Jurusan Tujuan
                    </div>

                    <div class="fw-bold">
                        {{ $loan->workshop?->code ?? '-' }}
                        — {{ $loan->workshop?->name }}
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="small text-secondary">
                        Jadwal Peminjaman
                    </div>

                    <div class="fw-bold">
                        {{
                            $loan->scheduled_at
                                ?->format('d-m-Y H:i')
                            ?? '-'
                        }}
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="small text-secondary">
                        Jatuh Tempo
                    </div>

                    <div class="fw-bold">
                        @if ($loan->isExtended())
                            <span class="text-success">
                                <i class="bi bi-clock-history me-1"></i>
                                {{ $loan->extended_due_at?->format('d-m-Y H:i') }}
                            </span>
                            <span class="badge bg-success-subtle text-success ms-1">
                                Diperpanjang
                            </span>
                        @else
                            {{ $loan->due_at?->format('d-m-Y H:i') }}
                        @endif
                    </div>

                    @if ($loan->isExtended())
                        <div class="small text-secondary mt-1">
                            Batas awal:
                            {{ $loan->due_at?->format('d-m-Y H:i') }}
                        </div>
                        @if ($loan->extender)
                            <div class="small text-secondary">
                                Oleh:
                                {{ $loan->extender?->name }}
                            </div>
                        @endif
                        @if ($loan->extension_reason)
                            <div class="small text-secondary text-wrap">
                                Alasan:
                                {{ $loan->extension_reason }}
                            </div>
                        @endif
                    @endif
                </div>

                <div class="col-6 col-lg-3">
                    <div class="small text-secondary">
                        Status
                    </div>

                    <div class="fw-bold">
                        {{ $loan->statusLabel() }}
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="small text-secondary">
                        Toolman Penyetuju
                    </div>

                    <div class="fw-bold">
                        {{
                            $loan
                                ->assignedToolman
                                ?->name
                            ?? (
                                $loan->status
                                    === 'pending'
                                    ? 'Menunggu Toolman '.
                                        (
                                            $loan
                                                ->workshop
                                                ?->code
                                            ?? ''
                                        )
                                    : '-'
                            )
                        }}
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="small text-secondary">
                        Keperluan
                    </div>

                    <div class="fw-bold">
                        {{ $loan->purpose }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if (
        $loan->status === 'approved'
        && ! $scheduleReached
    )
        <div class="alert alert-info">
            Pengajuan sudah disetujui. Serah terima baru dapat dilakukan pada
            <strong>
                {{ $loan->scheduled_at?->format('d-m-Y H:i') }}
            </strong>.
        </div>
    @endif

    <section class="content-card mb-4">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-0">
                Barang yang Diajukan
            </h2>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Jenis</th>
                        <th>Unit/Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($loan->items as $loanItem)
                        <tr>
                            <td>
                                {{ $loanItem->item?->name ?? '-' }}
                            </td>

                            <td>
                                {{
                                    $loanItem->is_consumable
                                        ? 'Bahan Habis Pakai'
                                        : 'Alat'
                                }}
                            </td>

                            <td>
                                @if ($loanItem->is_consumable)
                                    {{ $loanItem->quantity }}
                                    {{ $loanItem->item?->unit?->name }}
                                @else
                                    <span class="font-monospace">
                                        {{
                                            $loanItem
                                                ->itemAsset
                                                ?->asset_number
                                            ?? 'Unit belum terhubung'
                                        }}
                                    </span>
                                    @if ($loanItem->itemAsset && $loanItem->itemAsset->condition !== 'good')
                                        <span class="badge text-bg-danger ms-1">
                                            {{ $loanItem->itemAsset->conditionLabel() }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($loanItem->returned_at)
                                    <span class="badge text-bg-success">
                                        Selesai
                                    </span>
                                @elseif ($loanItem->issued_at)
                                    <span class="badge text-bg-primary">
                                        Diserahkan
                                    </span>
                                @else
                                    <span class="badge text-bg-warning">
                                        Menunggu
                                    </span>
                                @endif

                                @if ($canManage && ! $loanItem->is_consumable && ! $loanItem->returned_at && $loanItem->issued_at)
                                    <button type="button" class="btn btn-sm btn-outline-warning ms-2"
                                        onclick="document.getElementById('replace-{{ $loanItem->id }}').style.display = document.getElementById('replace-{{ $loanItem->id }}').style.display === 'none' ? 'table-row' : 'none'">
                                        <i class="bi bi-arrow-repeat"></i> Ganti Unit
                                    </button>
                                @endif

                                @if (($canRequestReplacement ?? false) && ! $loanItem->is_consumable && ! $loanItem->returned_at && $loanItem->issued_at)
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2"
                                        onclick="document.getElementById('report-damage-{{ $loanItem->id }}').style.display = document.getElementById('report-damage-{{ $loanItem->id }}').style.display === 'none' ? 'table-row' : 'none'">
                                        <i class="bi bi-exclamation-triangle"></i> Laporkan Rusak
                                    </button>
                                @endif
                            </td>
                        </tr>

                        @if (($canRequestReplacement ?? false) && ! $loanItem->is_consumable && ! $loanItem->returned_at && $loanItem->issued_at)
                            <tr id="report-damage-{{ $loanItem->id }}" style="display:none;">
                                <td colspan="4" class="bg-light">
                                    <form method="POST" action="{{ route('loans.items.request-replacement', [$loan, $loanItem]) }}">
                                        @csrf
                                        <div class="d-flex flex-column gap-2">
                                            <label class="fw-semibold small">Keterangan Kerusakan</label>
                                            <textarea name="damage_description" rows="2" required
                                                class="form-control form-control-sm"
                                                placeholder="Jelaskan kerusakan unit alat..."></textarea>
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="document.getElementById('report-damage-{{ $loanItem->id }}').style.display='none'">
                                                    Batal
                                                </button>
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-send me-1"></i> Ajukan Penggantian
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif

                        @if ($canManage && ! $loanItem->is_consumable && ! $loanItem->returned_at && $loanItem->issued_at)
                            <tr id="replace-{{ $loanItem->id }}" style="display:none;">
                                <td colspan="4" class="bg-light">
                                    @php
                                        $replacementAssets = \App\Models\ItemAsset::query()
                                            ->withoutGlobalScopes()
                                            ->where('item_id', $loanItem->item_id)
                                            ->where('workshop_id', $loan->workshop_id)
                                            ->where('is_active', true)
                                            ->where('status', \App\Models\ItemAsset::STATUS_AVAILABLE)
                                            ->where('condition', \App\Models\ItemAsset::CONDITION_GOOD)
                                            ->whereKeyNot($loanItem->item_asset_id ?? 0)
                                            ->orderBy('received_date')
                                            ->orderBy('asset_number')
                                            ->get();
                                    @endphp

                                    @if ($replacementAssets->isNotEmpty())
                                        <form method="POST" action="{{ route('loans.items.replace', [$loan, $loanItem]) }}">
                                            @csrf
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="fw-semibold">Unit pengganti (kondisi baik, tahun terbaru):</span>
                                                <select name="new_asset_id" class="form-select form-select-sm" style="max-width: 300px;" required>
                                                    <option value="">— Pilih unit —</option>
                                                    @foreach ($replacementAssets as $asset)
                                                        <option value="{{ $asset->id }}">
                                                            {{ $asset->asset_number }}
                                                            @if ($asset->received_date)
                                                                ({{ $asset->received_date->format('Y') }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-warning"
                                                    onclick="return confirm('Ganti unit alat ini? Unit lama akan ditandai sebagai rusak.')">
                                                    <i class="bi bi-check-lg"></i> Konfirmasi Ganti
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="document.getElementById('replace-{{ $loanItem->id }}').style.display='none'">
                                                    Batal
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-danger small">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                Tidak ada unit pengganti yang tersedia untuk barang ini pada jurusan.
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="document.getElementById('replace-{{ $loanItem->id }}').style.display='none'">
                                                Tutup
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($canManage)
        <section class="content-card">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-0">
                    Proses Toolman
                    {{ $loan->workshop?->code }}
                </h2>
            </div>

            <div class="content-card-body">
                <div class="d-flex flex-wrap gap-2">
                    @if (($canExtend ?? false) && in_array($loan->status, ['approved', 'borrowed', 'partially_returned'], true))
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            data-bs-toggle="collapse"
                            data-bs-target="#extension-form"
                        >
                            <i class="bi bi-clock-history me-1"></i>
                            {{ $loan->isExtended() ? 'Ubah Perpanjangan' : 'Berikan Perpanjangan' }}
                        </button>
                    @endif

                    @if ($loan->status === 'pending')
                        <form
                            method="POST"
                            action="{{ route(
                                'loans.approve',
                                $loan
                            ) }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Setujui Pengajuan
                            </button>
                        </form>

                        <button
                            type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="collapse"
                            data-bs-target="#reject-form"
                        >
                            Tolak
                        </button>
                    @endif

                    @if ($loan->status === 'approved')
                        <form
                            method="POST"
                            action="{{ route(
                                'loans.checkout',
                                $loan
                            ) }}"
                            onsubmit="return confirm('Lakukan serah terima? Stok akan langsung berkurang.');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-primary"
                                @disabled(! $scheduleReached)
                            >
                                {{
                                    $scheduleReached
                                        ? 'Serah Terima / Kurangi Stok'
                                        : 'Menunggu Jadwal'
                                }}
                            </button>
                        </form>
                    @endif

                    @if (
                        $loan->status === 'borrowed'
                        && auth()->check()
                        && auth()->user()->hasRole(
                            'admin',
                            'kepala_bengkel',
                            'toolman'
                        )
                        && \Illuminate\Support\Facades\Route::has('loans.permit')
                    )
                        <a
                            href="{{ route('loans.permit', $loan) }}"
                            target="_blank"
                            class="btn btn-outline-primary"
                        >
                            <i class="bi bi-printer me-1"></i>
                            Cetak Surat Serah Terima
                        </a>
                    @endif

                    @if ($canCancel)
                        <form
                            method="POST"
                            action="{{ route(
                                'loans.cancel',
                                $loan
                            ) }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-outline-secondary"
                            >
                                Batalkan
                            </button>
                        </form>
                    @endif
                </div>

                @if ($loan->status === 'pending')
                    <div
                        class="collapse mt-3"
                        id="reject-form"
                    >
                        <form
                            method="POST"
                            action="{{ route(
                                'loans.reject',
                                $loan
                            ) }}"
                        >
                            @csrf

                            <label class="form-label">
                                Alasan Penolakan
                            </label>

                            <textarea
                                name="rejection_reason"
                                rows="3"
                                class="form-control mb-3"
                                required
                            ></textarea>

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                Tolak Pengajuan
                            </button>
                        </form>
                    </div>
                @endif

                @if (($canExtend ?? false) && in_array($loan->status, ['approved', 'borrowed', 'partially_returned'], true))
                    <div
                        class="collapse mt-3"
                        id="extension-form"
                    >
                        <div class="border rounded p-3 bg-light-subtle">
                            <form
                                method="POST"
                                action="{{ route(
                                    'loans.extend',
                                    $loan
                                ) }}"
                            >
                                @csrf

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">
                                            Deadline Default
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            value="{{ $loan->due_at?->format('d-m-Y H:i') }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label">
                                            Tanggal & Waktu Perpanjangan
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input
                                            id="extended_due_at"
                                            type="datetime-local"
                                            name="extended_due_at"
                                            value="{{
                                                old(
                                                    'extended_due_at',
                                                    $loan->extended_due_at
                                                        ? $loan->extended_due_at->format('Y-m-d\TH:i')
                                                        : $loan->due_at?->format('Y-m-d\TH:i')
                                                )
                                            }}"
                                            class="form-control
                                                @error('extended_due_at')
                                                    is-invalid
                                                @enderror"
                                            required
                                        >

                                        @error('extended_due_at')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Alasan Perpanjangan
                                            <span class="text-danger">*</span>
                                        </label>

                                        <textarea
                                            name="extension_reason"
                                            rows="2"
                                            class="form-control
                                                @error('extension_reason')
                                                    is-invalid
                                                @enderror"
                                            required
                                            placeholder="Contoh: Digunakan guru untuk kegiatan praktik lanjutan."
                                        >{{ old('extension_reason', $loan->extension_reason) }}</textarea>

                                        @error('extension_reason')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#extension-form"
                                    >
                                        Batal
                                    </button>

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="bi bi-clock-history me-1"></i>
                                        Simpan Perpanjangan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @elseif ($canCancel)
        <form
            method="POST"
            action="{{ route(
                'loans.cancel',
                $loan
            ) }}"
        >
            @csrf

            <button
                type="submit"
                class="btn btn-outline-danger"
            >
                Batalkan Pengajuan
            </button>
        </form>
    @endif
@endsection
