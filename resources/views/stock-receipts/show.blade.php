@extends('layouts.app')

@section('title', 'Detail Barang Masuk')

@section('content')
    @php
        $pending = $stockReceipt
            ->changeRequests
            ->firstWhere(
                'status',
                \App\Models\StockReceiptChangeRequest::STATUS_PENDING
            );
    @endphp

    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Detail Barang Masuk</h1>
            <p class="mt-1 truncate text-sm text-slate-500">
                {{ $stockReceipt->receipt_code }} — {{ $stockReceipt->item?->name }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <x-button href="{{ route('stock-receipts.edit', $stockReceipt) }}" variant="secondary">
                <i class="bi bi-pencil"></i> Edit
            </x-button>
            <x-button href="{{ route('stock-receipts.index') }}" variant="secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </x-button>
        </div>
    </div>

    @if ($pending)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </span>
            <div class="text-sm text-amber-800">
                <p class="font-semibold">Menunggu persetujuan perubahan.</p>
                <p class="mt-1 text-amber-700">
                    Diajukan oleh {{ $pending->requester?->name ?? 'Toolman' }}
                    pada {{ $pending->updated_at?->format('d-m-Y H:i') }}.
                </p>
                @if ($pending->request_note)
                    <p class="mt-1 text-amber-700">Alasan: {{ $pending->request_note }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Foto Barang Masuk --}}
    <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Foto Barang Masuk</h2>
        </div>
        <div class="grid grid-cols-1 gap-5 p-5 {{ $pending ? 'lg:grid-cols-2' : '' }}">
            <div>
                @include('stock-receipts._photo-card', [
                    'stockReceipt' => $stockReceipt,
                    'kind' => 'active',
                    'title' => 'Foto Aktif',
                    'path' => $stockReceipt->photo_path,
                    'emptyText' => 'Belum ada foto pada Barang Masuk ini.',
                ])
            </div>

            @if ($pending)
                <div>
                    @if (! empty($pending->requested_payload['replace_photo']))
                        @include('stock-receipts._photo-card', [
                            'stockReceipt' => $stockReceipt,
                            'kind' => 'proposed',
                            'title' => 'Foto Baru Usulan Toolman',
                            'path' => $pending->requested_payload['photo_path'] ?? null,
                            'emptyText' => 'File foto usulan tidak tersedia.',
                        ])
                    @else
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Foto Usulan</p>
                        <div class="flex min-h-[160px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                            <i class="bi bi-image text-3xl text-slate-300"></i>
                            <p class="mt-2 text-xs text-slate-500">Toolman tidak mengajukan penggantian foto.</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- Data Aktif --}}
    <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Data Aktif</h2>
        </div>
        <div class="grid grid-cols-2 gap-x-5 gap-y-5 p-5 sm:p-6 md:grid-cols-3 xl:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Masuk</p>
                <p class="mt-1 font-mono text-sm font-bold text-slate-900">{{ $stockReceipt->receipt_code }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</p>
                <p class="mt-1 text-sm font-bold text-slate-900">{{ $stockReceipt->transaction_date?->format('d-m-Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan</p>
                <p class="mt-1 text-sm font-bold text-slate-900">{{ $stockReceipt->workshop?->code ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi</p>
                <p class="mt-1 text-sm font-bold text-slate-900">{{ $stockReceipt->storageLocation?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah</p>
                <p class="mt-1 text-sm font-bold text-slate-900">
                    {{ \App\Support\QuantityFormatter::format($stockReceipt->quantity, $stockReceipt->item?->unit) }}
                    {{ $stockReceipt->item?->unit?->name }}
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Barang (Input Barang Masuk)</p>
                <p class="mt-1 text-sm font-bold text-slate-900">{{ $stockReceipt->brand ?: $stockReceipt->item?->name ?: '-' }}
                    @if ($stockReceipt->model) / {{ $stockReceipt->model }}@endif
                </p>
                <p class="mt-0.5 text-xs text-slate-500">Master: {{ $stockReceipt->item?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Harga Unit</p>
                <p class="mt-1 text-sm font-bold text-slate-900">Rp {{ number_format((float) $stockReceipt->unit_price, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diinput Oleh</p>
                <p class="mt-1 text-sm font-bold text-slate-900">{{ $stockReceipt->user?->name ?? '-' }}</p>
            </div>
            <div class="col-span-2 xl:col-span-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Spesifikasi</p>
                <p class="mt-1 text-sm text-slate-700">{{ $stockReceipt->specification ?: '-' }}</p>
            </div>
            <div class="col-span-2 xl:col-span-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan</p>
                <p class="mt-1 text-sm text-slate-700 whitespace-pre-wrap">{{ $stockReceipt->description ?: '-' }}</p>
            </div>
        </div>
    </section>

    {{-- Unit Alat & QR Code --}}
    @if (isset($itemAssets) && $itemAssets->isNotEmpty())
        <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Unit Alat & QR Code</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $itemAssets->count() }} unit dihasilkan dari Barang Masuk ini.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 no-print">
                        @if (\Illuminate\Support\Facades\Route::has('item-assets.qr-bulk.print'))
                            <a href="{{ route('item-assets.qr-bulk.print', $stockReceipt->item_id) }}" target="_blank"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 sm:min-h-9">
                                <i class="bi bi-printer mr-1.5"></i> Cetak Semua QR
                            </a>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('item-assets.qr-bulk.download'))
                            <a href="{{ route('item-assets.qr-bulk.download', $stockReceipt->item_id) }}" target="_blank"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:min-h-9">
                                <i class="bi bi-file-earmark-pdf mr-1.5"></i> Download PDF
                            </a>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('item-assets.qr-bulk.index'))
                            <a href="{{ route('item-assets.qr-bulk.index', ['item_id' => $stockReceipt->item_id]) }}"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:min-h-9">
                                <i class="bi bi-grid mr-1.5"></i> Kelola QR
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($itemAssets as $asset)
                        <x-qr-card :asset="$asset" :item-name="$stockReceipt->item?->name" />
                    @endforeach
                </div>
            </div>
        </section>
    @elseif (isset($itemAssets) && $itemAssets->isEmpty() && $stockReceipt->item?->isTool())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <i class="bi bi-info-circle-fill text-amber-500 mt-0.5"></i>
            <p class="text-sm text-amber-800">Barang Masuk ini belum menghasilkan unit alat. Unit dibuat otomatis saat Barang Masuk disetujui oleh Kepala Bengkel.</p>
        </div>
    @endif

    @if ($pending && $canReview)
        <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Keputusan Kepala Bengkel</h2>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('stock-receipts.approve-edit', $stockReceipt) }}">
                    @csrf
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Catatan Persetujuan</label>
                    <textarea name="review_note" rows="3" class="mb-3 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <i class="bi bi-check-lg mr-1.5"></i> Setujui dan Terapkan
                    </button>
                </form>
                <form method="POST" action="{{ route('stock-receipts.reject-edit', $stockReceipt) }}">
                    @csrf
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Alasan Penolakan</label>
                    <textarea name="review_note" rows="3" required class="mb-3 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                        <i class="bi bi-x-lg mr-1.5"></i> Tolak Permintaan
                    </button>
                </form>
            </div>
        </section>
    @endif

    {{-- Riwayat Persetujuan --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Riwayat Persetujuan</h2>
        </div>

        @php
            $history = $stockReceipt->changeRequests->sortByDesc('id');
            $statusVariant = fn (string $s) => match ($s) {
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'warning',
            };
        @endphp

        @if ($history->isEmpty())
            <div class="p-6">
                <x-empty-state icon="bi-clipboard-check" title="Belum ada riwayat" description="Belum ada permintaan perubahan pada Barang Masuk ini." />
            </div>
        @else
            <div class="table-desktop overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pemohon</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reviewer</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($history as $change)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $change->created_at?->format('d-m-Y H:i') }}</td>
                                <td class="px-5 py-3 text-sm text-slate-800">{{ $change->requester?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-3"><x-badge variant="{{ $statusVariant($change->status) }}">{{ $change->statusLabel() }}</x-badge></td>
                                <td class="px-5 py-3 text-sm text-slate-800">{{ $change->reviewer?->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-sm text-slate-600">{{ $change->review_note ?: $change->request_note ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-mobile divide-y divide-slate-100">
                @foreach ($history as $change)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $change->requester?->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $change->created_at?->format('d-m-Y H:i') }}</p>
                            </div>
                            <x-badge variant="{{ $statusVariant($change->status) }}">{{ $change->statusLabel() }}</x-badge>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $change->review_note ?: $change->request_note ?: '-' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($canDelete)
        <div class="mt-5 flex justify-end">
            <x-confirm-modal
                title="Hapus Barang Masuk?"
                description="Stok dan unit akan dikembalikan. Tindakan ini tidak dapat dibatalkan."
                confirmLabel="Ya, Hapus"
                variant="danger"
                :formAction="route('stock-receipts.destroy', $stockReceipt)"
                :formMethod="('DELETE')"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                <i class="bi bi-trash mr-1.5"></i> Hapus Barang Masuk
            </x-confirm-modal>
        </div>
    @endif
@endsection