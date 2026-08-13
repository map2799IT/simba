@extends('layouts.app')

@section('title', 'Kerusakan Alat')

@section('content')
    @php
        $severityVariant = fn (string $s) => match ($s) {
            'minor' => 'info', 'moderate' => 'warning', 'major' => 'danger', 'critical' => 'danger', default => 'neutral',
        };
        $statusVariant = fn (string $s) => match ($s) {
            'reported' => 'warning', 'verified' => 'info', 'in_repair', 'under_repair' => 'primary',
            'completed', 'closed', 'resolved' => 'success', default => 'neutral',
        };
    @endphp
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Kerusakan dan Perbaikan Alat</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola laporan kerusakan dan proses perbaikan alat bengkel.</p>
        </div>
        <x-button href="{{ route('damage-reports.create') }}" variant="primary">
            <i class="bi bi-exclamation-triangle"></i> Laporkan Kerusakan
        </x-button>
    </div>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('damage-reports.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ request('search') }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Kode laporan, alat, pelapor, diagnosis">
                </div>
                <div class="w-full sm:w-40">
                    <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Bengkel</label>
                    <select id="workshop_id" name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        @foreach ($workshops as $workshop)
                            <option value="{{ $workshop->id }}" @selected(request('workshop_id') == $workshop->id)>{{ $workshop->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-36">
                    <label for="severity" class="mb-1.5 block text-sm font-semibold text-slate-700">Tingkat</label>
                    <select id="severity" name="severity" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        @foreach ($severities as $value => $label)
                            <option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-search"></i></x-button>
                    <x-button href="{{ route('damage-reports.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
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
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Alat</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Waktu Laporan</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pelapor</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tingkat</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Petugas</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-3.5"><x-badge variant="neutral">{{ $report->code }}</x-badge></td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ $report->item->name }}</div>
                                <div class="text-xs text-slate-500">{{ $report->item->code }} · {{ $report->item->workshop?->code }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $report->reported_at->format('d-m-Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-sm text-slate-600">{{ $report->reporter?->name ?? 'Sistem' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5"><x-badge variant="{{ $severityVariant($report->severity) }}">{{ $report->severityLabel() }}</x-badge></td>
                            <td class="whitespace-nowrap px-5 py-3.5"><x-badge variant="{{ $statusVariant($report->status) }}">{{ $report->statusLabel() }}</x-badge></td>
                            <td class="px-5 py-3.5 text-sm text-slate-600">{{ $report->handler?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('damage-reports.show', $report) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Detail"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10">
                            <x-empty-state icon="bi-wrench" title="Belum ada laporan kerusakan" description="Tidak ada laporan kerusakan yang sesuai filter." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }}</p>
                    {{ $reports->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="card-mobile space-y-3">
        @forelse ($reports as $report)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-slate-500">{{ $report->code }}</p>
                        <p class="mt-0.5 font-semibold text-slate-900">{{ $report->item->name }}</p>
                    </div>
                    <x-badge variant="{{ $statusVariant($report->status) }}">{{ $report->statusLabel() }}</x-badge>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Tingkat</dt><dd class="mt-0.5"><x-badge variant="{{ $severityVariant($report->severity) }}">{{ $report->severityLabel() }}</x-badge></dd></div>
                    <div><dt class="text-xs text-slate-500">Waktu</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $report->reported_at->format('d-m-Y H:i') }}</dd></div>
                </dl>
                <div class="mt-4">
                    <a href="{{ route('damage-reports.show', $report) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-eye mr-1.5"></i> Lihat Detail</a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-wrench" title="Belum ada laporan kerusakan" description="Tidak ada laporan kerusakan yang sesuai filter." />
            </div>
        @endforelse
        @if ($reports->hasPages())
            <div class="pt-2">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection