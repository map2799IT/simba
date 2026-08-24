@extends('layouts.app')

@section('title', 'Persetujuan Edit Barang Keluar')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Persetujuan Edit Barang Keluar</h1>
            <p class="mt-1 text-sm text-slate-500">Pengajuan perubahan dari Toolman yang menunggu tindakan Anda.</p>
        </div>
        <x-button href="{{ route('stock-issues.index') }}" variant="secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </x-button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-desktop">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Transaksi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Perubahan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pengaju</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $req)
                            @php
                                $oldQty = (float)($req->original_payload['quantity'] ?? 0);
                                $newQty = (float)($req->requested_payload['quantity'] ?? 0);
                                $delta = round($newQty - $oldQty, 3);
                                $unitName = $req->movement?->item?->unit?->name ?? '';
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $req->movement?->item?->name ?? '-' }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $req->movement?->reference_number ?? '#'.$req->movement?->id }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $req->movement?->workshop?->code ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm space-y-1">
                                        @if (abs($delta) > 0.000001)
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-500">Jumlah:</span>
                                                <span class="text-slate-600 line-through">{{ \App\Support\QuantityFormatter::format($oldQty, $req->movement?->item?->unit) }}</span>
                                                <i class="bi bi-arrow-right text-slate-400"></i>
                                                <span class="font-semibold {{ $delta > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                                    {{ \App\Support\QuantityFormatter::format($newQty, $req->movement?->item?->unit) }} {{ $unitName }}
                                                </span>
                                                <x-badge variant="{{ $delta > 0 ? 'danger' : 'success' }}">
                                                    {{ $delta > 0 ? '+' : '' }}{{ \App\Support\QuantityFormatter::format($delta, $req->movement?->item?->unit) }}
                                                </x-badge>
                                            </div>
                                        @endif
                                        @if (($req->requested_payload['destination'] ?? '') !== ($req->original_payload['destination'] ?? ''))
                                            <div class="text-xs text-slate-500">Tujuan: <span class="text-slate-700">{{ $req->requested_payload['destination'] ?? '-' }}</span></div>
                                        @endif
                                        @if (($req->requested_payload['purpose'] ?? '') !== ($req->original_payload['purpose'] ?? ''))
                                            <div class="text-xs text-slate-500">Keperluan: <span class="text-slate-700">{{ $req->requested_payload['purpose'] ?? '-' }}</span></div>
                                        @endif
                                        @if ($req->request_note)
                                            <div class="text-xs text-slate-400 italic">Alasan: {{ $req->request_note }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $req->requester?->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $req->created_at?->format('d-m-Y H:i') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-badge variant="warning" dot>Menunggu</x-badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-confirm-modal
                                            title="Setujui Perubahan?"
                                            description="Data Barang Keluar akan diperbarui sesuai pengajuan. Stok akan disesuaikan otomatis."
                                            confirmLabel="Ya, Setujui"
                                            variant="primary"
                                            :formAction="route('stock-issues.change-request.approve', $req)"
                                            :formMethod="'POST'"
                                            class="inline-flex min-h-9 items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                        >
                                            <i class="bi bi-check-lg mr-1"></i> Setujui
                                        </x-confirm-modal>

                                        <div x-data="{ open: false }">
                                            <button type="button" @click="open = true"
                                                class="inline-flex min-h-9 items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                                                <i class="bi bi-x-lg mr-1"></i> Tolak
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                                                <div @click.stop class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                                                    <h3 class="text-base font-semibold text-slate-900">Tolak Pengajuan?</h3>
                                                    <p class="mt-1.5 text-sm text-slate-500">Data asli Barang Keluar tidak akan berubah. Masukkan alasan penolakan.</p>
                                                    <form method="POST" action="{{ route('stock-issues.change-request.reject', $req) }}" class="mt-4">
                                                        @csrf
                                                        <textarea name="review_note" rows="3" required
                                                            class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm focus:border-red-400 focus:ring-red-400"
                                                            placeholder="Alasan penolakan..."></textarea>
                                                        <div class="mt-4 flex justify-end gap-2">
                                                            <button type="button" @click="open = false"
                                                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                                                            <button type="submit"
                                                                class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Tolak</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8">
                                <x-empty-state icon="bi-clipboard-check" title="Tidak ada pengajuan perubahan" description="Belum ada pengajuan edit Barang Keluar yang menunggu persetujuan." />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile card list --}}
        <div class="card-mobile divide-y divide-slate-100">
            @forelse ($requests as $req)
                @php
                    $oldQty = (float)($req->original_payload['quantity'] ?? 0);
                    $newQty = (float)($req->requested_payload['quantity'] ?? 0);
                    $delta = round($newQty - $oldQty, 3);
                    $unitName = $req->movement?->item?->unit?->name ?? '';
                @endphp
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $req->movement?->item?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $req->movement?->reference_number ?? '-' }} · {{ $req->movement?->workshop?->code }}</p>
                        </div>
                        <x-badge variant="warning" dot>Menunggu</x-badge>
                    </div>
                    @if (abs($delta) > 0.000001)
                        <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 p-3 text-sm">
                            <span class="text-slate-500">Jumlah: </span>
                            <span class="line-through text-slate-400">{{ \App\Support\QuantityFormatter::format($oldQty, $req->movement?->item?->unit) }}</span>
                            <i class="bi bi-arrow-right mx-1 text-slate-400"></i>
                            <span class="font-bold {{ $delta > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ \App\Support\QuantityFormatter::format($newQty, $req->movement?->item?->unit) }} {{ $unitName }}</span>
                        </div>
                    @endif
                    <p class="mt-2 text-xs text-slate-500">Oleh <strong>{{ $req->requester?->name }}</strong> · {{ $req->created_at?->format('d-m-Y H:i') }}</p>
                    <div class="mt-4 flex gap-2">
                        <x-confirm-modal
                            title="Setujui Perubahan?"
                            description="Data Barang Keluar akan diperbarui dan stok disesuaikan."
                            confirmLabel="Ya, Setujui"
                            variant="primary"
                            :formAction="route('stock-issues.change-request.approve', $req)"
                            :formMethod="'POST'"
                            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700"
                        >
                            <i class="bi bi-check-lg mr-1.5"></i> Setujui
                        </x-confirm-modal>
                        <a href="{{ route('stock-issues.change-approvals') }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-red-600 text-sm font-semibold text-white hover:bg-red-700">
                            <i class="bi bi-x-lg mr-1.5"></i> Tolak
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-6">
                    <x-empty-state icon="bi-clipboard-check" title="Tidak ada pengajuan" description="Belum ada pengajuan edit Barang Keluar yang menunggu persetujuan." />
                </div>
            @endforelse
        </div>

        @if ($requests->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
