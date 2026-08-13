@extends('layouts.app')

@section('title', 'Pengembalian Alat')
@section('page-title', 'Pengembalian Alat')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 page-heading">
    <div>
        <h1 class="page-title">Pengembalian Alat</h1>
        <p class="page-description mb-0">
            Hanya alat yang muncul. Bahan habis pakai tidak memiliki pengembalian.
        </p>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<section class="content-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Peminjam</th>
                    <th>Jurusan</th>
                    <th>Jatuh Tempo</th>
                    <th>Unit Belum Kembali</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="font-monospace fw-semibold">{{ $loan->code }}</td>
                        <td>{{ $loan->borrower?->name ?? '-' }}</td>
                        <td>{{ $loan->workshop?->code ?? '-' }}</td>
                        <td>
                            {{ $loan->due_at?->format('d-m-Y H:i') }}
                            @if ($loan->due_at?->isPast())
                                <span class="badge text-bg-danger ms-1">Terlambat</span>
                            @endif
                        </td>
                        <td>{{ $loan->open_tool_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('loans.return-form', $loan) }}"
                                class="btn btn-sm btn-success">Proses Pengembalian</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            Tidak ada alat yang sedang dipinjam.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($loans->hasPages())
        <div class="border-top">{{ $loans->links() }}</div>
    @endif
</section>
@endsection
