@extends('layouts.app')

@section('title', 'Peminjaman')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Peminjaman</h1>
            <p class="mt-1 text-sm text-slate-500">Pengajuan diproses oleh Toolman berdasarkan jurusan yang dipilih.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($canManage)
                <x-button href="{{ route('loans.returns.index') }}" variant="secondary">
                    <i class="bi bi-arrow-return-left"></i> Pengembalian
                </x-button>
            @endif
            @if ($canCreateLoan)
                <x-button href="{{ route('loans.create') }}" variant="primary">
                    <i class="bi bi-plus-lg"></i> Buat Pengajuan
                </x-button>
            @else
                <button type="button" disabled title="Akun siswa belum mempunyai jurusan"
                    class="inline-flex min-h-11 cursor-not-allowed items-center gap-2 rounded-xl border border-slate-300 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400">
                    <i class="bi bi-lock"></i> Jurusan Belum Diatur
                </button>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('loans.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input id="search" type="search" name="search" value="{{ request('search') }}"
                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Kode, peminjam, atau keperluan">
                    </div>
                </div>
                @if ($canFilterWorkshop)
                    <div class="w-full sm:w-48">
                        <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Jurusan</label>
                        <select id="workshop_id" name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua jurusan</option>
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}" @selected((string) request('workshop_id') === (string) $workshop->id)>
                                    {{ $workshop->code }} â€” {{ $workshop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="w-full sm:w-40">
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Cari</x-button>
                    <x-button href="{{ route('loans.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Desktop Table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <x-sortable-header label="Kode / Peminjam" sort-key="code" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan</th>
                        <x-sortable-header label="Jatuh Tempo" sort-key="due_at" :sort="$sort" :direction="$direction" />
                        <x-sortable-header label="Keperluan" sort-key="purpose" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($loans as $loan)
                        @php
                            $isOverdue = in_array($loan->status, ['borrowed', 'partially_returned'], true)
                                && $loan->effectiveDueAt()?->isPast();
                            $statusVariant = match ($loan->status) {
                                'pending' => 'warning',
                                'approved' => 'info',
                                'borrowed' => 'primary',
                                'partially_returned' => 'neutral',
                                'returned', 'completed' => 'success',
                                'rejected', 'cancelled' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-3.5">
                                <div class="font-mono text-sm font-semibold text-slate-900">{{ $loan->code }}</div>
                                <div class="text-xs text-slate-500">{{ $loan->borrower?->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-800">{{ $loan->workshop?->code ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $loan->workshop?->name }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="text-sm {{ $isOverdue ? 'font-bold text-red-600' : 'text-slate-700' }}">
                                    {{ $loan->effectiveDueAt()?->format('d-m-Y H:i') ?? '-' }}
                                </div>
                                @if ($isOverdue)
                                    <x-badge variant="danger" dot>Terlambat</x-badge>
                                @elseif ($loan->isExtended())
                                    <x-badge variant="info">Diperpanjang</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-sm text-slate-600 max-w-[180px]">{{ \Illuminate\Support\Str::limit($loan->purpose, 50) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <x-badge variant="{{ $statusVariant }}">{{ $loan->statusLabel() }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('loans.show', $loan) }}"
                                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state icon="bi-journal-text" title="Belum ada transaksi peminjaman"
                                    description="Ajukan peminjaman pertama untuk mulai mencatat." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($loans->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $loans->firstItem() ?? 0 }}â€“{{ $loans->lastItem() ?? 0 }} dari {{ $loans->total() }}</p>
                    <x-per-page-selector :per-page="$perPage" />
                    {{ $loans->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="card-mobile space-y-3">
        @forelse ($loans as $loan)
            @php
                $isOverdue = in_array($loan->status, ['borrowed', 'partially_returned'], true) && $loan->effectiveDueAt()?->isPast();
                $statusVariant = match ($loan->status) {
                    'pending' => 'warning', 'approved' => 'info', 'borrowed' => 'primary',
                    'partially_returned' => 'neutral', 'returned', 'completed' => 'success',
                    'rejected', 'cancelled' => 'danger', default => 'neutral',
                };
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $loan->code }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $loan->borrower?->name ?? '-' }} Â· {{ $loan->workshop?->code ?? '-' }}</p>
                    </div>
                    <x-badge variant="{{ $statusVariant }}">{{ $loan->statusLabel() }}</x-badge>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Jatuh Tempo</dt>
                        <dd class="mt-0.5 font-medium {{ $isOverdue ? 'text-red-600' : 'text-slate-700' }}">
                            {{ $loan->effectiveDueAt()?->format('d-m-Y H:i') ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Keperluan</dt>
                        <dd class="mt-0.5 text-slate-700">{{ \Illuminate\Support\Str::limit($loan->purpose, 40) }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <a href="{{ route('loans.show', $loan) }}"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        <i class="bi bi-eye mr-1.5"></i> Lihat Detail
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-journal-text" title="Belum ada transaksi peminjaman" description="Ajukan peminjaman pertama untuk mulai mencatat." />
            </div>
        @endforelse
        @if ($loans->hasPages())
            <div class="pt-2">{{ $loans->links() }}</div>
        @endif
    </div>
@endsection
