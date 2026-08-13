@extends('layouts.app')

@section('title', $item->name)
@section('page-title', $item->name)

@section('content')
    <x-page-header
        title="{{ $item->name }}"
        description="{{ $item->code }} — {{ $item->typeLabel() }}"
        :breadcrumb="['Barang', $item->code]"
    >
        @if (\Illuminate\Support\Facades\Route::has('stock-receipts.create'))
            <x-button href="{{ route('stock-receipts.create') }}" variant="primary">
                <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
            </x-button>
        @endif
        <x-button href="{{ route('items.edit', $item) }}" variant="secondary">
            <i class="bi bi-pencil"></i> Edit Master
        </x-button>
        @if (\Illuminate\Support\Facades\Route::has('items.toggle-status'))
            <x-confirm-modal
                title="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Master Barang?"
                description="{{ $item->is_active ? 'Barang ini akan disembunyikan dari transaksi baru.' : 'Barang ini akan tersedia kembali untuk transaksi.' }}"
                :confirmLabel="($item->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan')"
                :variant="($item->is_active ? 'warning' : 'primary')"
                :formAction="route('items.toggle-status', $item)"
                :formMethod="('PATCH')"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i class="bi {{ $item->is_active ? 'bi-toggle-off' : 'bi-toggle-on' }} mr-1.5"></i>
                {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
            </x-confirm-modal>
        @endif
    </x-page-header>

    {{-- Info banner --}}
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white">
            <i class="bi bi-info-lg"></i>
        </span>
        <div class="text-sm text-blue-800">
            <p class="font-semibold">Master ini dapat dipakai berulang kali.</p>
            <p class="mt-1 text-blue-700">Merek, model, tahun, bengkel, dan lokasi yang berbeda tercatat pada Riwayat Barang Masuk di bawah.</p>
        </div>
    </div>

    {{-- Detail card --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="text-base font-semibold text-slate-900">Informasi Barang</h2>
        </div>
        <div class="p-5 sm:p-6">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Master</dt>
                    <dd class="mt-1 font-mono text-sm font-bold text-slate-900">{{ $item->code }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Barang</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ $item->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</dt>
                    <dd class="mt-1">
                        @if ($item->isTool())
                            <x-badge variant="info"><i class="bi bi-tools mr-1"></i> {{ $item->typeLabel() }}</x-badge>
                        @else
                            <x-badge variant="neutral">{{ $item->typeLabel() }}</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-700">{{ $item->category?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-700">{{ $item->unit?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok Total</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($item->stock, $item->unit) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt>
                    <dd class="mt-1">
                        @if ($item->is_active)
                            <x-badge variant="success" dot>Aktif</x-badge>
                        @else
                            <x-badge variant="danger" dot>Nonaktif</x-badge>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Riwayat Barang Masuk --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="text-base font-semibold text-slate-900">Riwayat Barang Masuk</h2>
            <p class="mt-0.5 text-sm text-slate-500">Tahun, merek, model, spesifikasi, bengkel, lokasi, kondisi, dan harga berada di sini.</p>
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto sm:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Masuk</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Merek / Model</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Bengkel / Lokasi</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kondisi</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($receipts as $receipt)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-sm font-semibold text-slate-700">{{ $receipt->receipt_code ?: '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $receipt->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-medium text-slate-800">{{ $receipt->brand ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $receipt->model ?: '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm text-slate-700">{{ $receipt->workshop?->code ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $receipt->storageLocation?->name ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @php $cond = \App\Models\Item::conditionOptions()[$receipt->condition] ?? ucfirst(str_replace('_', ' ', (string) $receipt->condition)); @endphp
                                @if ($receipt->condition === 'good')
                                    <x-badge variant="success" dot>{{ $cond }}</x-badge>
                                @elseif (in_array($receipt->condition, ['minor_damage', 'maintenance']))
                                    <x-badge variant="warning" dot>{{ $cond }}</x-badge>
                                @else
                                    <x-badge variant="danger" dot>{{ $cond }}</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($receipt->quantity, $item->unit) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm text-slate-600">Rp {{ number_format((float) $receipt->unit_price, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-empty-state
                                    icon="bi-box-arrow-in-down"
                                    title="Belum ada Barang Masuk"
                                    description="Master ini belum mempunyai transaksi Barang Masuk."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card list --}}
        <div class="divide-y divide-slate-100 sm:hidden">
            @forelse ($receipts as $receipt)
                @php $cond = \App\Models\Item::conditionOptions()[$receipt->condition] ?? ucfirst(str_replace('_', ' ', (string) $receipt->condition)); @endphp
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-semibold text-slate-800">{{ $receipt->receipt_code ?: '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $receipt->transaction_date?->format('d-m-Y') ?? '-' }}</p>
                        </div>
                        @if ($receipt->condition === 'good')
                            <x-badge variant="success" dot>{{ $cond }}</x-badge>
                        @elseif (in_array($receipt->condition, ['minor_damage', 'maintenance']))
                            <x-badge variant="warning" dot>{{ $cond }}</x-badge>
                        @else
                            <x-badge variant="danger" dot>{{ $cond }}</x-badge>
                        @endif
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500">Merek/Model</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">{{ $receipt->brand ?: '-' }} {{ $receipt->model ?: '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Bengkel</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">{{ $receipt->workshop?->code ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Jumlah</dt>
                            <dd class="mt-0.5 font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format($receipt->quantity, $item->unit) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Harga Unit</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">Rp {{ number_format((float) $receipt->unit_price, 0, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            @empty
                <x-empty-state
                    icon="bi-box-arrow-in-down"
                    title="Belum ada Barang Masuk"
                    description="Master ini belum mempunyai transaksi Barang Masuk."
                />
            @endforelse
        </div>

        @if ($receipts->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $receipts->firstItem() ?? 0 }}–{{ $receipts->lastItem() ?? 0 }} dari {{ $receipts->total() }}</p>
                    {{ $receipts->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
