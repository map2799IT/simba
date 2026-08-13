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
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Master Barang</p>
                <p class="mt-1.5 text-xl font-bold text-slate-900 sm:text-2xl">{{ $number($stats['total_items']) }}</p>
                <p class="mt-0.5 text-xs text-slate-400">{{ $stats['tool_masters'] }} alat · {{ $stats['material_masters'] }} bahan</p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="bi bi-box-seam text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Unit Alat</p>
                <p class="mt-1.5 text-xl font-bold text-blue-600 sm:text-2xl">{{ $number($stats['tool_units']) }}</p>
                <p class="mt-0.5 text-xs text-slate-400">{{ $stats['available_units'] }} tersedia</p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="bi bi-qr-code-scan text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Dipinjam/Dipesan</p>
                <p class="mt-1.5 text-xl font-bold text-amber-600 sm:text-2xl">{{ $number($stats['borrowed_units']) }}</p>
                <p class="mt-0.5 text-xs text-slate-400">unit alat</p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i class="bi bi-arrow-left-right text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Unit Bermasalah</p>
                <p class="mt-1.5 text-xl font-bold text-red-600 sm:text-2xl">{{ $number($stats['problem_units']) }}</p>
                <p class="mt-0.5 text-xs text-slate-400">rusak/perbaikan/hilang</p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"><i class="bi bi-exclamation-octagon text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Peminjaman Menunggu</p>
                <p class="mt-1.5 text-xl font-bold text-amber-600 sm:text-2xl">{{ $number($stats['pending_loans']) }}</p>
                <p class="mt-0.5 text-xs text-slate-400">sesuai hak akses</p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i class="bi bi-hourglass-split text-lg"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Kerusakan Aktif</p>
                <p class="mt-1.5 text-xl font-bold text-red-600 sm:text-2xl">{{ $number($stats['open_damages']) }}</p>
                <p class="mt-0.5 text-xs text-slate-400">belum selesai</p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"><i class="bi bi-wrench text-lg"></i></div>
        </div>
    </div>

    {{-- KPI Cards Row 2 --}}
    <div class="mb-5 grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Nilai Inventaris</p>
            <p class="mt-1.5 text-lg font-bold text-slate-900">{{ $money($stats['inventory_value']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Stok Minimum</p>
            <p class="mt-1.5 text-lg font-bold text-red-600">{{ $number($stats['low_stock_materials']) }} bahan</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Barang Masuk Bulan Ini</p>
            <p class="mt-1.5 text-lg font-bold text-emerald-600">+{{ $number($stats['incoming_this_month'], 3) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Barang Keluar Bulan Ini</p>
            <p class="mt-1.5 text-lg font-bold text-red-600">-{{ $number($stats['outgoing_this_month'], 3) }}</p>
        </div>
    </div>

    {{-- Quick Access --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Akses Cepat</h2>
        </div>
        <div class="flex flex-wrap gap-2 p-5">
            @if (\Illuminate\Support\Facades\Route::has('items.index'))<x-button href="{{ route('items.index') }}" variant="soft"><i class="bi bi-tools"></i> Data Inventaris</x-button>@endif
            @if (\Illuminate\Support\Facades\Route::has('item-assets.index'))<x-button href="{{ route('item-assets.index') }}" variant="soft"><i class="bi bi-qr-code-scan"></i> Unit Alat & QR</x-button>@endif
            @if (in_array($role, ['admin', 'toolman'], true) && \Illuminate\Support\Facades\Route::has('stock-receipts.create'))<x-button href="{{ route('stock-receipts.create') }}" variant="soft-success"><i class="bi bi-box-arrow-in-down"></i> Barang Masuk</x-button>@endif
            @if (in_array($role, ['admin', 'toolman'], true) && \Illuminate\Support\Facades\Route::has('stock-issues.create'))<x-button href="{{ route('stock-issues.create') }}" variant="soft-danger"><i class="bi bi-box-arrow-up"></i> Barang Keluar</x-button>@endif
            @if (\Illuminate\Support\Facades\Route::has('loans.create'))<x-button href="{{ route('loans.create') }}" variant="primary"><i class="bi bi-journal-plus"></i> Ajukan Peminjaman</x-button>@endif
            @if (\Illuminate\Support\Facades\Route::has('loans.index'))<x-button href="{{ route('loans.index') }}" variant="soft"><i class="bi bi-journal-text"></i> Daftar Peminjaman</x-button>@endif
            @if (\Illuminate\Support\Facades\Route::has('damage-reports.create'))<x-button href="{{ route('damage-reports.create') }}" variant="soft" class="bg-amber-50 text-amber-700 hover:bg-amber-100"><i class="bi bi-exclamation-triangle"></i> Laporkan Kerusakan</x-button>@endif
            @if (\Illuminate\Support\Facades\Route::has('reports.inventory'))<x-button href="{{ route('reports.inventory', $dashboardQuery) }}" variant="secondary"><i class="bi bi-bar-chart-line"></i> Laporan Inventaris</x-button>@endif
            @if ($role === 'admin' && \Illuminate\Support\Facades\Route::has('admin.users.index'))<x-button href="{{ route('admin.users.index') }}" variant="secondary"><i class="bi bi-people"></i> Pengguna</x-button>@endif
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
