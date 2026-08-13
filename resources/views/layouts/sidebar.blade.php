@auth
@php
    $user = auth()->user();
    $role = (string) $user->role;

    $roleLabels = [
        'admin' => 'Administrator',
        'wakil_sarpras' => 'Wakil Sarana dan Prasarana',
        'kepala_bengkel' => 'Kepala Bengkel',
        'toolman' => 'Toolman',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
    ];

    $roleLabel = $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role));

    $isAdmin = $role === 'admin';
    $isWakaSarpras = $role === 'wakil_sarpras';
    $isHead = $role === 'kepala_bengkel';
    $isToolman = $role === 'toolman';
    $isTeacher = $role === 'guru';
    $isStudent = $role === 'siswa';
    $isBorrowerOnly = $isTeacher || $isStudent;

    $canViewStock = in_array($role, ['admin', 'kepala_bengkel', 'toolman'], true);
    $canProcessStock = in_array($role, ['admin', 'toolman', 'kepala_bengkel'], true);
    $canProcessLoans = in_array($role, ['admin', 'toolman'], true);
    $canMonitorLoans = in_array($role, ['admin', 'kepala_bengkel', 'toolman'], true);

    $workshop = null;
    if ($user->workshop_id ?? null) {
        $workshop = \Illuminate\Support\Facades\DB::table('workshops')
            ->where('id', $user->workshop_id)
            ->first(['code', 'name']);
    }

    $groups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'active' => ['dashboard'], 'show' => true],
            ],
        ],
        [
            'label' => 'Inventaris',
            'items' => [
                ['route' => 'items.index', 'label' => 'Data Alat & Bahan', 'icon' => 'bi-tools', 'active' => ['items.index', 'items.show', 'items.history', 'items.create', 'items.edit', 'items.bulk.*', 'items.label.*', 'items.labels.*'], 'show' => ! $isBorrowerOnly && ! $isWakaSarpras],
                ['route' => 'item-assets.index', 'label' => 'Unit Alat & QR', 'icon' => 'bi-qr-code-scan', 'active' => ['item-assets.index', 'item-assets.show', 'item-assets.edit', 'item-assets.label'], 'show' => ! $isBorrowerOnly && ! $isWakaSarpras],
                ['route' => 'item-assets.qr-bulk.index', 'label' => 'Cetak QR Massal', 'icon' => 'bi-printer-fill', 'active' => ['item-assets.qr-bulk.*'], 'show' => $isAdmin || $isToolman, 'badge' => 'QR'],
            ],
        ],
        [
            'label' => 'Data Jurusan',
            'items' => [
                ['route' => $isWakaSarpras ? 'locations.inventory.menu' : (\Illuminate\Support\Facades\Route::has('locations.index') ? 'locations.index' : 'storage-locations.index'), 'label' => $isWakaSarpras ? 'Lokasi & Print' : 'Lokasi Penyimpanan', 'icon' => 'bi-geo-alt-fill', 'active' => ['locations.index', 'storage-locations.index', 'locations.create', 'locations.edit', 'locations.inventory.*', 'storage-locations.*'], 'show' => $isAdmin || $isWakaSarpras || $isHead || $isToolman, 'badge' => $isWakaSarpras ? 'Lihat/Print' : 'Kelola'],
            ],
        ],
        [
            'label' => 'Data Sekolah',
            'items' => [
                ['route' => 'students.index', 'label' => 'Data Siswa', 'icon' => 'bi-mortarboard-fill', 'active' => ['students.*'], 'show' => $isAdmin || $isToolman, 'badge' => 'Kelola'],
            ],
        ],
        [
            'label' => 'Transaksi Stok',
            'items' => [
                ['route' => 'stock-receipts.index', 'label' => 'Barang Masuk', 'icon' => 'bi-box-arrow-in-down', 'active' => ['stock-receipts.*'], 'show' => $canViewStock, 'badge' => $canProcessStock ? 'Proses' : 'Lihat'],
                ['route' => 'stock-issues.index', 'label' => 'Barang Keluar', 'icon' => 'bi-box-arrow-up', 'active' => ['stock-issues.*'], 'show' => $canViewStock, 'badge' => $canProcessStock ? 'Proses' : 'Lihat'],
                ['route' => 'stock-import.index', 'label' => 'Import Barang Masuk', 'icon' => 'bi-file-earmark-arrow-up', 'active' => ['stock-import.*'], 'show' => $canProcessStock, 'badge' => 'Import'],
                ['route' => 'stock-movements.index', 'label' => 'Pergerakan Stok', 'icon' => 'bi-arrow-left-right', 'active' => ['stock-movements.*'], 'show' => $canViewStock],
            ],
        ],
        [
            'label' => 'Peminjaman',
            'items' => [
                ['route' => 'loans.create', 'label' => 'Ajukan Peminjaman', 'icon' => 'bi-journal-plus', 'active' => ['loans.create'], 'show' => ! $isWakaSarpras],
                ['route' => 'loans.index', 'label' => $canMonitorLoans ? 'Kelola Peminjaman' : 'Peminjaman Saya', 'icon' => 'bi-journal-text', 'active' => ['loans.index', 'loans.show', 'loans.approve', 'loans.reject', 'loans.checkout', 'loans.complete', 'loans.cancel'], 'show' => ! $isWakaSarpras],
                ['route' => 'loans.returns.index', 'label' => 'Pengembalian', 'icon' => 'bi-arrow-return-left', 'active' => ['loans.returns.*', 'loans.return*', 'loans.items.return'], 'show' => $canProcessLoans],
                ['route' => 'loans.replacement-requests.index', 'label' => 'Penggantian Alat', 'icon' => 'bi-arrow-repeat', 'active' => ['loans.replacement-requests.*'], 'show' => $canProcessLoans, 'badge' => 'Ganti'],
            ],
        ],
        [
            'label' => 'Kerusakan',
            'items' => [
                ['route' => 'damage-reports.create', 'label' => 'Laporkan Kerusakan', 'icon' => 'bi-exclamation-triangle', 'active' => ['damage-reports.create'], 'show' => ! $isBorrowerOnly && ! $isWakaSarpras],
                ['route' => 'damage-reports.index', 'label' => ($isAdmin || $isToolman) ? 'Kelola Kerusakan' : 'Laporan Kerusakan', 'icon' => 'bi-wrench-adjustable-circle', 'active' => ['damage-reports.index', 'damage-reports.show', 'damage-reports.edit', 'damage-reports.verify', 'damage-reports.start-repair', 'damage-reports.complete-repair', 'damage-reports.close'], 'show' => ! $isBorrowerOnly && ! $isWakaSarpras],
            ],
        ],
        [
            'label' => 'Laporan',
            'items' => [
                ['route' => 'reports.inventory', 'label' => 'Laporan Inventaris', 'icon' => 'bi-bar-chart-line', 'active' => ['reports.inventory', 'reports.inventory.*', 'reports.export.*'], 'show' => ! $isBorrowerOnly || $isWakaSarpras],
                ['route' => 'reports.stock', 'label' => 'Laporan Stok', 'icon' => 'bi-boxes', 'active' => ['reports.stock', 'reports.stock.*'], 'show' => $canViewStock || $isWakaSarpras],
                ['route' => 'reports.loans', 'label' => 'Laporan Peminjaman', 'icon' => 'bi-clipboard-data', 'active' => ['reports.loans', 'reports.loans.*'], 'show' => ! $isBorrowerOnly || $isWakaSarpras],
                ['route' => 'reports.damages', 'label' => 'Laporan Kerusakan', 'icon' => 'bi-graph-down-arrow', 'active' => ['reports.damages', 'reports.damages.*'], 'show' => ! $isBorrowerOnly || $isWakaSarpras],
                ['route' => 'reports.stock-movements', 'label' => 'Laporan Pergerakan', 'icon' => 'bi-activity', 'active' => ['reports.stock-movements', 'reports.stock-movements.*'], 'show' => $canViewStock || $isWakaSarpras],
            ],
        ],
        [
            'label' => 'Master Sistem',
            'items' => [
                ['route' => 'workshops.index', 'label' => 'Bengkel / Jurusan', 'icon' => 'bi-building', 'active' => ['workshops.*'], 'show' => $isAdmin],
                ['route' => 'item-categories.index', 'label' => 'Kategori Barang', 'icon' => 'bi-tags', 'active' => ['item-categories.*'], 'show' => $isAdmin],
                ['route' => 'units.index', 'label' => 'Satuan', 'icon' => 'bi-rulers', 'active' => ['units.*'], 'show' => $isAdmin],
            ],
        ],
        [
            'label' => 'Administrasi',
            'items' => [
                ['route' => 'admin.users.index', 'label' => 'Pengguna', 'icon' => 'bi-people', 'active' => ['admin.users.*'], 'show' => $isAdmin],
                ['route' => 'admin.access.index', 'label' => 'Hak Akses', 'icon' => 'bi-shield-lock', 'active' => ['admin.access.*'], 'show' => $isAdmin],
                ['route' => 'admin.audit-logs.index', 'label' => 'Audit Aktivitas', 'icon' => 'bi-journal-check', 'active' => ['admin.audit-logs.*'], 'show' => $isAdmin],
                ['route' => 'admin.error-logs.index', 'label' => 'Audit Sistem', 'icon' => 'bi-bug', 'active' => ['admin.error-logs.*'], 'show' => $isAdmin, 'badge' => 'Error'],
            ],
        ],
    ];

    $groupVisible = static function (array $group): bool {
        foreach ($group['items'] as $item) {
            if (($item['show'] ?? false) && \Illuminate\Support\Facades\Route::has($item['route'])) {
                return true;
            }
        }
        return false;
    };

    $itemActive = static function (array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[300px] max-w-[88vw] flex-col border-r border-slate-800 bg-slate-950 text-slate-300 transition-transform duration-300 ease-in-out lg:w-[304px] lg:min-w-[304px] lg:max-w-[304px] lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Navigasi utama"
    data-simba-sidebar
>
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-800 px-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 no-underline hover:no-underline focus:no-underline" aria-label="SIMBA Dashboard" style="text-decoration:none!important">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
                <i class="bi bi-box-seam-fill text-lg leading-none"></i>
            </span>
            <div class="leading-tight">
                <div class="text-[14px] font-bold leading-tight text-white">SIMBA</div>
                <div class="text-[10px] font-normal leading-tight text-slate-400">Sistem Inventaris Bengkel</div>
            </div>
        </a>
        <button
            type="button"
            @click="sidebarOpen = false"
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
            aria-label="Tutup menu"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- User profile --}}
    <div class="shrink-0 border-b border-slate-800 px-4 py-3.5">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-800 text-[13px] font-bold text-white">
                {{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="text-[13px] font-semibold leading-tight text-slate-100">{{ $user->name }}</div>
                <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                    <span class="text-[10px] font-medium text-slate-400">{{ $roleLabel }}</span>
                    <span class="text-[10px] font-medium text-emerald-400">
                        ·
                        @if ($isTeacher || $isAdmin)
                            Semua Jurusan
                        @elseif ($workshop)
                            {{ $workshop->code }}
                        @else
                            Belum diatur
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

{{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-2" style="scrollbar-width: thin; scrollbar-color: rgba(71,85,105,.15) transparent;">
        <style>
            [data-simba-sidebar] nav::-webkit-scrollbar { width: 3px; }
            [data-simba-sidebar] nav::-webkit-scrollbar-track { background: transparent; }
            [data-simba-sidebar] nav::-webkit-scrollbar-thumb { background: rgba(71,85,105,.12); border-radius: 3px; }
            [data-simba-sidebar] nav::-webkit-scrollbar-thumb:hover { background: rgba(71,85,105,.2); }
        </style>
        @foreach ($groups as $group)
            @continue(! $groupVisible($group))
            <div class="{{ $loop->first ? 'mt-0' : 'mt-5' }}">
                @if ($group['label'])
                    <div class="whitespace-nowrap px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                        {{ $group['label'] }}
                    </div>
                @endif
                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        @continue(! ($item['show'] ?? false) || ! \Illuminate\Support\Facades\Route::has($item['route']))
                        @php $active = $itemActive($item['active']); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="relative flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-medium leading-5 no-underline hover:no-underline focus:no-underline visited:no-underline transition-colors duration-150
                                    @if ($active)
                                        bg-slate-800 text-white ring-1 ring-inset ring-slate-700
                                    @else
                                        text-slate-300 hover:bg-slate-800/60 hover:text-white
                                    @endif"
                                @if ($active) aria-current="page" @endif
                                style="text-decoration:none!important"
                            >
                                @if ($active)
                                    <span class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-blue-500" aria-hidden="true"></span>
                                @endif
                                <i class="bi {{ $item['icon'] }} w-[18px] h-[18px] shrink-0 text-[18px] leading-none
                                    @if ($active)
                                        text-blue-400
                                    @else
                                        text-slate-400 group-hover:text-slate-200
                                    @endif"></i>
                                <span class="min-w-0 flex-1 whitespace-nowrap">{{ $item['label'] }}</span>
                                @if (! empty($item['badge']))
                                    <span class="ml-auto inline-flex h-5 shrink-0 items-center rounded-md px-1.5 text-[9px] font-medium leading-none ring-1 ring-inset ring-slate-700
                                        @if ($item['badge'] === 'QR')
                                            bg-slate-800 text-blue-300
                                        @else
                                            bg-slate-800 text-slate-400
                                        @endif">
                                        {{ $item['badge'] }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Footer --}}
    <div class="shrink-0 border-t border-slate-800 px-3 py-2.5">
        <div class="flex items-center gap-2">
            @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                <a href="{{ route('profile.edit') }}" class="flex min-h-10 flex-1 items-center gap-3 rounded-lg px-3 py-2 text-[12px] font-medium leading-5 no-underline hover:no-underline focus:no-underline visited:no-underline text-slate-400 transition hover:bg-slate-800/60 hover:text-white" style="text-decoration:none!important">
                    <i class="bi bi-person-circle w-[18px] h-[18px] shrink-0 text-[18px] leading-none text-slate-400"></i>
                    <span>Profil Saya</span>
                </a>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" title="Keluar" class="flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-[12px] font-medium leading-5 no-underline hover:no-underline focus:no-underline visited:no-underline text-red-400 transition hover:bg-red-500/10 hover:text-red-300" style="text-decoration:none!important">
                        <i class="bi bi-box-arrow-right w-[18px] h-[18px] shrink-0 text-[18px] leading-none"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('[data-simba-sidebar]');
    if (!sidebar) return;

    sidebar.querySelectorAll('a[href]').forEach(function (anchor) {
        anchor.addEventListener('click', function () {
            if (window.innerWidth <= 1023) {
                const event = new Event('close-sidebar');
                document.dispatchEvent(event);
            }
        });
    });
});
</script>
@endauth
