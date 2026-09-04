@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    @php
        $money = static fn (mixed $value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $number = static function (mixed $value, int $decimals = 0): string {
            $formatted = number_format((float) $value, $decimals, ',', '.');
            return $decimals > 0 ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
        };
        $loanStatusLabels = [
            'pending' => 'Menunggu', 'requested' => 'Menunggu', 'submitted' => 'Menunggu',
            'approved' => 'Disetujui', 'borrowed' => 'Dipinjam', 'active' => 'Aktif',
            'checked_out' => 'Dipinjam', 'returned' => 'Dikembalikan', 'completed' => 'Selesai',
            'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan',
        ];
        $movementLabels = [
            'initial' => 'Saldo Awal', 'incoming' => 'Barang Masuk', 'outgoing' => 'Barang Keluar',
            'adjustment_in' => 'Penyesuaian Masuk', 'adjustment_out' => 'Penyesuaian Keluar',
            'loan' => 'Peminjaman', 'return' => 'Pengembalian',
        ];
        $damageStatusLabels = [
            'reported' => 'Dilaporkan', 'verified' => 'Diverifikasi', 'in_repair' => 'Dalam Perbaikan',
            'under_repair' => 'Dalam Perbaikan', 'completed' => 'Selesai', 'closed' => 'Ditutup', 'resolved' => 'Selesai',
        ];
        $loanStatusVariant = static fn (string $status): string => match ($status) {
            'pending', 'requested', 'submitted' => 'warning',
            'approved' => 'info',
            'borrowed', 'active', 'checked_out' => 'primary',
            'returned', 'completed' => 'success',
            'rejected', 'cancelled' => 'danger',
            default => 'neutral',
        };
        $dashboardQuery = array_filter(['workshop_id' => $effectiveWorkshopId], static fn ($value): bool => $value !== null && $value !== '');
    @endphp

    <x-page-header title="Selamat datang, {{ auth()->user()->name }}" description="{{ $roleLabel }} · {{ $scopeLabel }}" :breadcrumb="['Dashboard']">
        @if ($hasGlobalScope)
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-2 sm:flex-row">
                <select name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-64" onchange="this.form.submit()">
                    <option value="">Seluruh jurusan</option>
                    @foreach ($workshops as $workshop)
                        <option value="{{ $workshop->id }}" @selected((int) $effectiveWorkshopId === (int) $workshop->id)>{{ $workshop->code }} — {{ $workshop->name }}</option>
                    @endforeach
                </select>
                @if ($effectiveWorkshopId)
                    <x-button href="{{ route('dashboard') }}" variant="secondary">Reset</x-button>
                @endif
            </form>
        @endif
    </x-page-header>

    @if ($missingAssignment)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <p class="text-sm text-amber-800">Akun {{ $roleLabel }} belum mempunyai jurusan. Administrator perlu menetapkan <code class="rounded bg-amber-100 px-1">users.workshop_id</code> sebelum dashboard dapat menampilkan data.</p>
        </div>
    @elseif (in_array($role, ['kepala_bengkel', 'toolman', 'siswa'], true))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <i class="bi bi-info-circle-fill text-blue-500"></i>
            <p class="text-sm text-blue-800">Dashboard dibatasi otomatis ke <strong>{{ $scopeLabel }}</strong>.</p>
        </div>
    @elseif ($role === 'guru')
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <i class="bi bi-info-circle-fill text-blue-500"></i>
            <p class="text-sm text-blue-800">Guru dapat melihat inventaris seluruh jurusan. Statistik peminjaman hanya menampilkan pengajuan Anda.</p>
        </div>
    @endif

    {{-- KPI Cards Row 1 --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 xl:grid-cols-6">
        @php
            $kpiCards = [
                ['label' => 'Master Barang', 'value' => $number($stats['total_items']), 'sub' => $stats['tool_masters'] . ' alat · ' . $stats['material_masters'] . ' bahan', 'icon' => 'bi-box-seam', 'gradient' => 'from-blue-500 to-indigo-600', 'text' => 'text-blue-600', 'shadow' => 'shadow-blue-500/20'],
                ['label' => 'Unit Alat', 'value' => $number($stats['tool_units']), 'sub' => $stats['available_units'] . ' tersedia', 'icon' => 'bi-qr-code-scan', 'gradient' => 'from-cyan-500 to-sky-600', 'text' => 'text-cyan-600', 'shadow' => 'shadow-cyan-500/20'],
                ['label' => 'Dipinjam/Dipesan', 'value' => $number($stats['borrowed_units']), 'sub' => 'unit alat', 'icon' => 'bi-arrow-left-right', 'gradient' => 'from-amber-500 to-orange-600', 'text' => 'text-amber-600', 'shadow' => 'shadow-amber-500/20'],
                ['label' => 'Unit Bermasalah', 'value' => $number($stats['problem_units']), 'sub' => 'rusak/perbaikan/hilang', 'icon' => 'bi-exclamation-octagon', 'gradient' => 'from-rose-500 to-red-600', 'text' => 'text-rose-600', 'shadow' => 'shadow-rose-500/20'],
                ['label' => 'Peminjaman Menunggu', 'value' => $number($stats['pending_loans']), 'sub' => 'sesuai hak akses', 'icon' => 'bi-hourglass-split', 'gradient' => 'from-violet-500 to-purple-600', 'text' => 'text-violet-600', 'shadow' => 'shadow-violet-500/20'],
                ['label' => 'Kerusakan Aktif', 'value' => $number($stats['open_damages']), 'sub' => 'belum selesai', 'icon' => 'bi-wrench-adjustable', 'gradient' => 'from-orange-500 to-amber-600', 'text' => 'text-orange-600', 'shadow' => 'shadow-orange-500/20'],
            ];
        @endphp
        @foreach ($kpiCards as $card)
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">{{ $card['value'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $card['sub'] }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $card['gradient'] }} text-white shadow-lg {{ $card['shadow'] }} transition-transform duration-200 group-hover:scale-110">
                        <i class="bi {{ $card['icon'] }} text-lg"></i>
                    </span>
                </div>
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $card['gradient'] }} opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>
            </div>
        @endforeach
    </div>

    {{-- KPI Cards Row 2 --}}
    <div class="mb-5 grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
            <div class="absolute -right-3 -top-3 h-20 w-20 rounded-full bg-blue-50/70 blur-xl" aria-hidden="true"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600"><i class="bi bi-cash-stack text-base"></i></span>
                <div>
                    <p class="text-xs font-medium text-slate-500">Nilai Inventaris</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-900">{{ $money($stats['inventory_value']) }}</p>
                </div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
            <div class="absolute -right-3 -top-3 h-20 w-20 rounded-full bg-red-50/70 blur-xl" aria-hidden="true"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600"><i class="bi bi-exclamation-diamond text-base"></i></span>
                <div>
                    <p class="text-xs font-medium text-slate-500">Stok Minimum</p>
                    <p class="mt-0.5 text-lg font-bold text-red-600">{{ $number($stats['low_stock_materials']) }} bahan</p>
                </div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
            <div class="absolute -right-3 -top-3 h-20 w-20 rounded-full bg-emerald-50/70 blur-xl" aria-hidden="true"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"><i class="bi bi-box-arrow-in-down text-base"></i></span>
                <div>
                    <p class="text-xs font-medium text-slate-500">Barang Masuk Bulan Ini</p>
                    <p class="mt-0.5 text-lg font-bold text-emerald-600">+{{ $number($stats['incoming_this_month'], 3) }}</p>
                </div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
            <div class="absolute -right-3 -top-3 h-20 w-20 rounded-full bg-rose-50/70 blur-xl" aria-hidden="true"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600"><i class="bi bi-box-arrow-up text-base"></i></span>
                <div>
                    <p class="text-xs font-medium text-slate-500">Barang Keluar Bulan Ini</p>
                    <p class="mt-0.5 text-lg font-bold text-red-600">-{{ $number($stats['outgoing_this_month'], 3) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Visual Widget: Pergerakan Bulan Ini --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Pergerakan Stok Bulan Ini</h2>
                <p class="mt-0.5 text-sm text-slate-500">Visual cepat untuk barang masuk vs keluar.</p>
            </div>
            <span class="hidden h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 sm:flex"><i class="bi bi-graph-up-arrow text-base"></i></span>
        </div>
        @php
            $incoming = (float) $stats['incoming_this_month'];
            $outgoing = (float) $stats['outgoing_this_month'];
            $maxMovement = max($incoming, $outgoing, 1);
            $incomingPct = min(100, ($incoming / $maxMovement) * 100);
            $outgoingPct = min(100, ($outgoing / $maxMovement) * 100);
        @endphp
        <div class="grid gap-6 p-5 md:grid-cols-2">
            <div>
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700"><i class="bi bi-arrow-down-circle-fill text-xs"></i> Barang Masuk</span>
                    <span class="font-semibold text-slate-800">{{ $number($incoming, 3) }}</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500" style="width: {{ $incomingPct }}%"></div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="inline-flex items-center gap-1.5 font-medium text-red-700"><i class="bi bi-arrow-up-circle-fill text-xs"></i> Barang Keluar</span>
                    <span class="font-semibold text-slate-800">{{ $number($outgoing, 3) }}</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-gradient-to-r from-rose-400 to-red-600 transition-all duration-500" style="width: {{ $outgoingPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Access --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Akses Cepat</h2>
                <p class="mt-0.5 text-sm text-slate-500">Menu yang paling sering digunakan.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 lg:grid-cols-5">
            @php
                $quickLinks = [
                    ['route' => 'items.index', 'cond' => \Illuminate\Support\Facades\Route::has('items.index'), 'label' => 'Data Inventaris', 'icon' => 'bi-tools', 'gradient' => 'from-blue-500 to-indigo-600'],
                    ['route' => 'item-assets.index', 'cond' => \Illuminate\Support\Facades\Route::has('item-assets.index'), 'label' => 'Unit Alat & QR', 'icon' => 'bi-qr-code-scan', 'gradient' => 'from-cyan-500 to-sky-600'],
                    ['route' => 'stock-receipts.create', 'cond' => in_array($role, ['admin', 'toolman'], true) && \Illuminate\Support\Facades\Route::has('stock-receipts.create'), 'label' => 'Barang Masuk', 'icon' => 'bi-box-arrow-in-down', 'gradient' => 'from-emerald-500 to-teal-600'],
                    ['route' => 'stock-issues.create', 'cond' => in_array($role, ['admin', 'toolman'], true) && \Illuminate\Support\Facades\Route::has('stock-issues.create'), 'label' => 'Barang Keluar', 'icon' => 'bi-box-arrow-up', 'gradient' => 'from-rose-500 to-red-600'],
                    ['route' => 'loans.create', 'cond' => \Illuminate\Support\Facades\Route::has('loans.create'), 'label' => 'Ajukan Peminjaman', 'icon' => 'bi-journal-plus', 'gradient' => 'from-violet-500 to-purple-600'],
                    ['route' => 'loans.index', 'cond' => \Illuminate\Support\Facades\Route::has('loans.index'), 'label' => 'Daftar Peminjaman', 'icon' => 'bi-journal-text', 'gradient' => 'from-indigo-500 to-blue-600'],
                    ['route' => 'damage-reports.create', 'cond' => \Illuminate\Support\Facades\Route::has('damage-reports.create'), 'label' => 'Laporkan Kerusakan', 'icon' => 'bi-exclamation-triangle', 'gradient' => 'from-amber-500 to-orange-600'],
                    ['route' => 'reports.inventory', 'cond' => \Illuminate\Support\Facades\Route::has('reports.inventory'), 'label' => 'Laporan Inventaris', 'icon' => 'bi-bar-chart-line', 'gradient' => 'from-teal-500 to-emerald-600'],
                    ['route' => 'admin.users.index', 'cond' => $role === 'admin' && \Illuminate\Support\Facades\Route::has('admin.users.index'), 'label' => 'Pengguna', 'icon' => 'bi-people', 'gradient' => 'from-slate-600 to-slate-700'],
                ];
            @endphp
            @foreach ($quickLinks as $link)
                @if ($link['cond'])
                    <a href="{{ $link['route'] === 'reports.inventory' ? route('reports.inventory', $dashboardQuery) : route($link['route']) }}" class="group flex flex-col items-center gap-2.5 rounded-2xl border border-slate-200 bg-white p-4 text-center no-underline transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md" style="text-decoration:none!important">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $link['gradient'] }} text-white shadow-lg transition-transform duration-200 group-hover:scale-110">
                            <i class="bi {{ $link['icon'] }} text-lg"></i>
                        </span>
                        <span class="text-[12px] font-semibold leading-tight text-slate-700 group-hover:text-slate-900">{{ $link['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Recent Loans + Low Stock --}}
    <div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-7">
        {{-- Recent Loans --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-4">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Peminjaman Terbaru</h2>
                    <p class="mt-0.5 text-sm text-slate-500">@if (in_array($role, ['guru', 'siswa'], true)) Pengajuan milik Anda @else Pengajuan sesuai ruang lingkup @endif</p>
                </div>
                @if (\Illuminate\Support\Facades\Route::has('loans.index'))<x-button href="{{ route('loans.index') }}" variant="ghost" size="sm">Lihat Semua</x-button>@endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Peminjam</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Jurusan</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentLoans as $loan)
                            <tr class="transition-colors hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-700">
                                    @if (\Illuminate\Support\Facades\Route::has('loans.show'))<a href="{{ route('loans.show', $loan->id) }}" class="hover:text-blue-600">{{ $loan->code ?? '#' . $loan->id }}</a>@else{{ $loan->code ?? '#' . $loan->id }}@endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $loan->borrower_name ?? auth()->user()->name }}</td>
                                <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ $loan->workshop_code ?? '-' }}</td>
                                <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ isset($loan->request_date) ? \Illuminate\Support\Carbon::parse($loan->request_date)->format('d-m-Y') : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3"><x-badge variant="{{ $loanStatusVariant($loan->status) }}">{{ $loanStatusLabels[$loan->status] ?? ucfirst(str_replace('_', ' ', $loan->status)) }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada pengajuan peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Bahan Stok Minimum</h2>
                <p class="mt-0.5 text-sm text-slate-500">Bahan yang perlu segera ditambah.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($lowStockItems as $item)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('items.show') ? route('items.show', $item->id) : '#' }}" class="flex items-center justify-between gap-3 px-5 py-3 transition hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                            <p class="text-xs text-slate-500">{{ $item->code }} · {{ $item->workshop_code ?? '-' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-red-600">{{ $number($item->stock, 3) }} {{ $item->unit_name ?? '' }}</p>
                            <p class="text-xs text-slate-400">min. {{ $number($item->minimum_stock, 3) }}</p>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <x-empty-state icon="bi-check-circle" title="Stok aman" description="Tidak ada bahan dengan stok minimum." />
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($role !== 'siswa')
    {{-- Recent Movements + Active Damages --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-7">
        {{-- Recent Movements --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-4">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Pergerakan Stok Terbaru</h2>
                <p class="mt-0.5 text-sm text-slate-500">Aktivitas stok sesuai ruang lingkup dashboard.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentMovements as $movement)
                            <tr class="transition-colors hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ isset($movement->transaction_date) ? \Illuminate\Support\Carbon::parse($movement->transaction_date)->format('d-m-Y') : '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-900">{{ $movement->item_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $movement->item_code }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $movementLabels[$movement->type] ?? ucfirst(str_replace('_', ' ', $movement->type)) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold {{ in_array($movement->type, ['outgoing', 'adjustment_out', 'loan'], true) ? 'text-red-600' : 'text-emerald-600' }}">{{ in_array($movement->type, ['outgoing', 'adjustment_out', 'loan'], true) ? '-' : '+' }}{{ $number($movement->quantity, 3) }} {{ $movement->unit_name ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Active Damages --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ $role !== 'siswa' ? 'xl:col-span-3' : 'xl:col-span-7' }}">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Kerusakan Aktif</h2>
                    <p class="mt-0.5 text-sm text-slate-500">@if (in_array($role, ['guru', 'siswa'], true)) Laporan kerusakan yang Anda buat @else Laporan sesuai ruang lingkup @endif</p>
                </div>
                @if (\Illuminate\Support\Facades\Route::has('damage-reports.index'))<x-button href="{{ route('damage-reports.index') }}" variant="ghost" size="sm">Lihat Semua</x-button>@endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($openDamageReports as $damage)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('damage-reports.show') ? route('damage-reports.show', $damage->id) : '#' }}" class="flex items-center justify-between gap-3 px-5 py-3 transition hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $damage->item_name }}</p>
                            <p class="text-xs text-slate-500">{{ $damage->code ?? '#' . $damage->id }} · {{ $damage->workshop_code ?? '-' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <x-badge variant="warning">{{ $damageStatusLabels[$damage->status] ?? ucfirst(str_replace('_', ' ', $damage->status)) }}</x-badge>
                            @if (isset($damage->severity))<p class="mt-1 text-xs text-slate-400">{{ ucfirst(str_replace('_', ' ', $damage->severity)) }}</p>@endif
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <x-empty-state icon="bi-shield-check" title="Tidak ada kerusakan aktif" description="Semua alat dalam kondisi baik." />
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
