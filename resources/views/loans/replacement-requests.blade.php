@extends('layouts.app')

@section('title', 'Penggantian Alat Rusak')
@section('page-title', 'Penggantian Alat Rusak')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Penggantian Alat Rusak</h1>
            <p class="mt-1 text-sm text-slate-500">Pengajuan penggantian unit alat yang dilaporkan rusak oleh siswa/guru.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('loans.replacement-requests.index', ['status' => 'pending']) }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-semibold transition {{ $statusFilter === 'pending' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">
                Menunggu ({{ $pendingCount }})
            </a>
            <a href="{{ route('loans.replacement-requests.index', ['status' => 'all']) }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-semibold transition {{ $statusFilter === 'all' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">
                Semua
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-desktop">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Rusak</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pelapor</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan Rusak</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $req)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $req->item?->name ?? '-' }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $req->item?->code ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-mono text-sm text-slate-700">{{ $req->oldAsset?->asset_number ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $req->loan->borrower?->name ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-medium text-slate-700">{{ $req->requester?->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $req->created_at?->format('d-m-Y H:i') }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600 max-w-[220px]">{{ \Illuminate\Support\Str::limit($req->damage_description, 60) }}</td>
                                <td class="px-5 py-4">
                                    @if ($req->status === 'pending')
                                        <x-badge variant="warning" dot>Menunggu</x-badge>
                                    @elseif ($req->status === 'fulfilled')
                                        <x-badge variant="success" dot>Sudah Diganti</x-badge>
                                    @else
                                        <x-badge variant="neutral" dot>Dibatalkan</x-badge>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($req->isPending())
                                        <div x-data="{ open: false }">
                                            <button type="button" @click="open = true"
                                                class="inline-flex min-h-9 items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                                <i class="bi bi-arrow-repeat mr-1"></i> Proses Ganti
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                                                <div @click.stop class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                                                    <h3 class="text-base font-semibold text-slate-900">Ganti Unit Alat</h3>
                                                    <p class="mt-1.5 text-sm text-slate-500">
                                                        Unit <strong class="font-mono">{{ $req->oldAsset?->asset_number }}</strong>
                                                        ({{ $req->item?->name }}) akan ditandai rusak. Masukkan kode unit pengganti.
                                                    </p>
                                                    <form method="POST" action="{{ route('loans.replacement-requests.fulfill', $req) }}" class="mt-4">
                                                        @csrf
                                                        <label class="block text-sm font-semibold text-slate-700">Kode Unit Pengganti</label>
                                                        <input type="text" name="replacement_asset_code" required
                                                            class="mt-1.5 w-full rounded-xl border-slate-300 px-3.5 py-2.5 font-mono text-sm uppercase focus:border-blue-500 focus:ring-blue-500 @error('replacement_asset_code') border-red-400 @enderror"
                                                            placeholder="Contoh: ALT-0001-001">
                                                        @error('replacement_asset_code')
                                                            <p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                                                        @enderror
                                                        <label class="mt-3 block text-sm font-semibold text-slate-700">Catatan</label>
                                                        <textarea name="notes" rows="2" class="mt-1.5 w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                                        <div class="mt-4 flex justify-end gap-2">
                                                            <button type="button" @click="open = false"
                                                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                                                            <button type="submit"
                                                                class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Konfirmasi Ganti</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($req->newAsset)
                                        <span class="font-mono text-xs text-slate-500">→ {{ $req->newAsset->asset_number }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8">
                                <x-empty-state icon="bi-shield-check" title="Tidak ada pengajuan penggantian"
                                    description="Belum ada siswa/guru yang melaporkan alat rusak untuk diganti." />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile card --}}
        <div class="card-mobile divide-y divide-slate-100">
            @forelse ($requests as $req)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $req->item?->name ?? '-' }}</p>
                            <p class="font-mono text-xs text-slate-500">{{ $req->oldAsset?->asset_number }}</p>
                        </div>
                        @if ($req->status === 'pending')<x-badge variant="warning" dot>Menunggu</x-badge>
                        @elseif ($req->status === 'fulfilled')<x-badge variant="success" dot>Diganti</x-badge>
                        @else<x-badge variant="neutral" dot>Batal</x-badge>@endif
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Oleh {{ $req->requester?->name }} · {{ $req->created_at?->format('d-m-Y H:i') }}</p>
                    @if ($req->isPending())
                        <a href="{{ route('loans.replacement-requests.index', ['status' => 'pending']) }}"
                            class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">
                            <i class="bi bi-arrow-repeat mr-1.5"></i> Proses Ganti
                        </a>
                    @endif
                </div>
            @empty
                <div class="p-6">
                    <x-empty-state icon="bi-shield-check" title="Tidak ada pengajuan penggantian"
                        description="Belum ada siswa/guru yang melaporkan alat rusak untuk diganti." />
                </div>
            @endforelse
        </div>

        @if ($requests->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection