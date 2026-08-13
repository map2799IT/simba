@extends('layouts.app')

@section('title', 'Persetujuan Edit Barang Masuk')
@section('page-title', 'Persetujuan Edit Barang Masuk')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 page-heading">
        <div>
            <h1 class="page-title">Persetujuan Edit Barang Masuk</h1>
            <p class="page-description mb-0">
                Permintaan Toolman yang belum diterapkan pada stok dan unit.
            </p>
        </div>

        <a
            href="{{ route('stock-receipts.index') }}"
            class="btn btn-outline-secondary"
        >
            Kembali
        </a>
    </div>

    <section class="content-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Foto Aktif</th>
                        <th>Foto Usulan</th>
                        <th>Jurusan</th>
                        <th>Pemohon</th>
                        <th>Alasan</th>
                        <th>Tanggal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($requests as $change)
                        <tr>
                            <td class="font-monospace fw-semibold">
                                {{ $change->movement?->receipt_code ?? '-' }}
                            </td>
                            <td>{{ $change->movement?->item?->name ?? '-' }}</td>

                            <td>
                                @if (
                                    $change->movement?->photo_path
                                    && \Illuminate\Support\Facades\Route::has(
                                        'stock-receipts.photo.active'
                                    )
                                )
                                    <a
                                        href="{{ route(
                                            'stock-receipts.photo.active',
                                            $change->movement
                                        ) }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <img
                                            src="{{ route(
                                                'stock-receipts.photo.active',
                                                $change->movement
                                            ) }}"
                                            alt="Foto aktif"
                                            class="rounded border bg-light"
                                            style="width: 64px; height: 52px; object-fit: cover;"
                                            loading="lazy"
                                        >
                                    </a>
                                @else
                                    <span class="text-secondary small">
                                        Tidak ada
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if (
                                    ! empty(
                                        $change->requested_payload[
                                            'replace_photo'
                                        ]
                                    )
                                    && ! empty(
                                        $change->requested_payload[
                                            'photo_path'
                                        ]
                                    )
                                    && \Illuminate\Support\Facades\Route::has(
                                        'stock-receipts.photo.proposed'
                                    )
                                )
                                    <a
                                        href="{{ route(
                                            'stock-receipts.photo.proposed',
                                            $change->movement
                                        ) }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <img
                                            src="{{ route(
                                                'stock-receipts.photo.proposed',
                                                $change->movement
                                            ) }}"
                                            alt="Foto usulan"
                                            class="rounded border border-warning bg-light"
                                            style="width: 64px; height: 52px; object-fit: cover;"
                                            loading="lazy"
                                        >
                                    </a>
                                @else
                                    <span class="text-secondary small">
                                        Tidak diubah
                                    </span>
                                @endif
                            </td>

                            <td>{{ $change->movement?->workshop?->code ?? '-' }}</td>
                            <td>{{ $change->requester?->name ?? '-' }}</td>
                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $change->request_note ?: '-',
                                    100
                                ) }}
                            </td>
                            <td>{{ $change->updated_at?->format('d-m-Y H:i') }}</td>
                            <td class="text-end">
                                <a
                                    href="{{ route(
                                        'stock-receipts.show',
                                        $change->movement
                                    ) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-5">
                                Tidak ada permintaan perubahan yang menunggu persetujuan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="border-top">
                {{ $requests->links() }}
            </div>
        @endif
    </section>
@endsection
