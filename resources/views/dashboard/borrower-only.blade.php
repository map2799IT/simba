@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    @php
        $isTeacher = $role === 'guru';
        $statusLabels = [
            'pending' => 'Menunggu Persetujuan', 'requested' => 'Menunggu Persetujuan', 'submitted' => 'Menunggu Persetujuan',
            'approved' => 'Menunggu Serah Terima', 'borrowed' => 'Sedang Dipinjam', 'partially_returned' => 'Dikembalikan Sebagian',
            'returned' => 'Sudah Dikembalikan', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan',
        ];
        $statusVariant = static fn (string $status): string => match ($status) {
            'pending', 'requested', 'submitted' => 'warning',
            'approved' => 'info',
            'borrowed' => 'primary',
            'partially_returned', 'returned', 'completed' => 'success',
            'rejected', 'cancelled' => 'danger',
            default => 'neutral',
        };
    @endphp

    <x-page-header title="Selamat datang, {{ auth()->user()->name }}" description="{{ $isTeacher ? 'Guru' : 'Siswa' }} · {{ $isTeacher ? 'Dapat memilih seluruh jurusan' : ($workshop?->code ?? 'Jurusan belum diatur') }}" :breadcrumb="['Dashboard']">
        @if ($canCreateLoan)
            <x-button href="{{ route('loans.create') }}" variant="primary"><i class="bi bi-journal-plus"></i> Ajukan Peminjaman</x-button>
        @endif
    </x-page-header>

    @if ($isTeacher)
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <i class="bi bi-info-circle-fill text-blue-500"></i>
            <p class="text-sm text-blue-800">Guru dapat membuat pengajuan dari seluruh jurusan. Pilih jurusan pada halaman pengajuan. Dashboard dan daftar hanya menampilkan peminjaman milik Anda sendiri.</p>
        </div>
    @elseif (! $workshop)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <p class="text-sm text-amber-800"><strong>Jurusan belum diatur.</strong> Akun siswa belum dapat membuat pengajuan peminjaman. Hubungi Administrator untuk menetapkan jurusan.</p>
        </div>
    @else
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <i class="bi bi-info-circle-fill text-blue-500"></i>
            <p class="text-sm text-blue-800">Siswa hanya dapat meminjam alat dan bahan dari jurusan <strong>{{ $workshop->code }} — {{ $workshop->name }}</strong>.</p>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 xl:grid-cols-5">
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div><p class="text-xs font-medium text-slate-500">Total Peminjaman</p><p class="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">{{ $totalLoans }}</p><p class="mt-0.5 text-xs text-slate-400">milik Anda</p></div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="bi bi-journal-text text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div><p class="text-xs font-medium text-slate-500">Menunggu Persetujuan</p><p class="mt-1.5 text-xl font-bold text-amber-600 sm:text-2xl">{{ $pendingLoans }}</p></div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i class="bi bi-hourglass-split text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div><p class="text-xs font-medium text-slate-500">Menunggu Serah Terima</p><p class="mt-1.5 text-xl font-bold text-blue-600 sm:text-2xl">{{ $approvedLoans }}</p></div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="bi bi-box-arrow-right text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div><p class="text-xs font-medium text-slate-500">Sedang Dipinjam</p><p class="mt-1.5 text-xl font-bold text-indigo-600 sm:text-2xl">{{ $borrowedLoans }}</p></div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><i class="bi bi-arrow-left-right text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div><p class="text-xs font-medium text-slate-500">Terlambat</p><p class="mt-1.5 text-xl font-bold text-red-600 sm:text-2xl">{{ $overdueLoans }}</p></div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"><i class="bi bi-clock-history text-lg"></i></div>
        </div>
    </div>

    {{-- My Loans Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Peminjaman Saya</h2>
                <p class="mt-0.5 text-sm text-slate-500">Hanya menampilkan pengajuan milik akun Anda.</p>
            </div>
            <x-button href="{{ route('loans.index') }}" variant="ghost" size="sm">Lihat Semua</x-button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Jurusan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Keperluan</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentLoans as $loan)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm font-semibold text-slate-700">{{ $loan->code }}</td>
                            <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ $loan->workshop?->code ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($loan->purpose, 80) }}</td>
                            <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ $loan->due_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><x-badge variant="{{ $statusVariant($loan->status) }}">{{ $statusLabels[$loan->status] ?? ucfirst(str_replace('_', ' ', $loan->status)) }}</x-badge></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route('loans.show', $loan) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8">
                            <x-empty-state icon="bi-journal-text" title="Belum ada pengajuan peminjaman" description="Ajukan peminjaman pertama Anda untuk mulai meminjam alat atau bahan.">
                                @if ($canCreateLoan)<x-button href="{{ route('loans.create') }}" variant="primary"><i class="bi bi-plus-circle"></i> Ajukan Peminjaman</x-button>@endif
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
