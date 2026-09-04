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
        // Gunakan relasi (sudah dimuat sekali per request) + cache untuk mencegah N+1
        $workshop = cache()->remember(
            'sidebar-workshop-' . $user->workshop_id,
            3600,
            static fn () => \Illuminate\Support\Facades\DB::table('workshops')
                ->where('id', $user->workshop_id)
                ->first(['code', 'name'])
        );
    }

    // Jumlah peminjaman yang menunggu persetujuan (badge notifikasi).
    $pendingLoanCount = null;
    if ($canMonitorLoans && \Illuminate\Support\Facades\Route::has('loans.index')) {
        try {
            $pendingLoanCount = cache()->remember(
                'sidebar-pending-loans-' . $user->id,
                60,
                static function () use ($isWakaSarpras, $user) {
                    $query = \App\Models\Loan::query()->where('status', \App\Models\Loan::STATUS_PENDING);
                    if (! $isWakaSarpras && ($user->workshop_id ?? null)) {
                        $query->where('workshop_id', $user->workshop_id);
                    }
                    $count = $query->count();
                    return $count > 0 ? (string) $count : null;
                }
            );
        } catch (\Throwable) {
            $pendingLoanCount = null;
        }
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
                ['route' => 'loans.index', 'label' => $canMonitorLoans ? 'Kelola Peminjaman' : 'Peminjaman Saya', 'icon' => 'bi-journal-text', 'active' => ['loans.index', 'loans.show', 'loans.approve', 'loans.reject', 'loans.checkout', 'loans.complete', 'loans.cancel'], 'show' => ! $isWakaSarpras, 'badge' => $canMonitorLoans ? $pendingLoanCount : null],
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
                ['route' => 'reports.inventory', 'label' => 'Laporan Inventaris', 'icon' => 'bi-bar-chart-line', 'active' => ['reports.inventory', 'reports.inventory.*', 'reports.export.*', 'reports.stock-receipts', 'reports.stock-receipts.*'], 'show' => ! $isBorrowerOnly || $isWakaSarpras, 'badge' => 'All'],
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

    $groupActive = static function (array $group) use ($itemActive): bool {
        foreach ($group['items'] as $item) {
            if (! empty($item['active']) && $itemActive($item['active'])) {
                return true;
            }
        }

        return false;
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[300px] max-w-[88vw] flex-col border-r border-slate-800 bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:w-[304px] lg:min-w-[304px] lg:max-w-[304px] lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Navigasi utama"
    data-simba-sidebar
    x-data="{ sidebarGroups: {} }"
    x-init="sidebarGroups = JSON.parse(localStorage.getItem('simba-sidebar-groups') || '{}')"
>
    {{-- Brand --}}
    <div class="flex h-[68px] shrink-0 items-center justify-between border-b border-white/5 px-5">
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 no-underline hover:no-underline focus:no-underline" aria-label="SIMBA Dashboard" style="text-decoration:none!important">
            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-900/40 transition-transform duration-200 group-hover:scale-105">
                <i class="bi bi-box-seam-fill text-lg leading-none"></i>
            </span>
            <div class="leading-tight">
                <div class="text-[15px] font-extrabold tracking-tight leading-tight text-white">SIMBA</div>
                <div class="mt-0.5 text-[10px] font-normal leading-tight text-slate-400">Sistem Inventaris Bengkel</div>
            </div>
        </a>
        <button
            type="button"
            @click="sidebarOpen = false"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
            aria-label="Tutup menu"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- User profile --}}
    <div class="shrink-0 border-b border-white/5 bg-white/[0.02] px-5 py-4">
        <div class="flex items-center gap-3">
            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-800 text-[13px] font-bold text-white ring-2 ring-white/10">
                {{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}
                <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-slate-900 bg-emerald-400" aria-hidden="true"></span>
            </span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-semibold leading-tight text-slate-100">{{ $user->name }}</div>
                <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-slate-400">{{ $roleLabel }}</span>
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
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-3" style="scrollbar-width: thin; scrollbar-color: rgba(71,85,105,.15) transparent;">
        <style>
            [data-simba-sidebar] nav::-webkit-scrollbar { width: 4px; }
            [data-simba-sidebar] nav::-webkit-scrollbar-track { background: transparent; }
            [data-simba-sidebar] nav::-webkit-scrollbar-thumb { background: rgba(71,85,105,.18); border-radius: 4px; }
            [data-simba-sidebar] nav::-webkit-scrollbar-thumb:hover { background: rgba(71,85,105,.3); }
        </style>
        @foreach ($groups as $groupIndex => $group)
            @continue(! $groupVisible($group))
            @php
                $groupKey = 'group-' . $groupIndex;
                $isExpanded = $groupActive($group);
            @endphp
            <div class="{{ $loop->first ? 'mt-0' : 'mt-4' }}" x-data="{ open: {{ $isExpanded ? 'true' : 'false' }} }" x-init="open = (localStorage.getItem('simba-sidebar-{{ $groupKey }}') ?? (open ? '1' : '0')) === '1'">
                @if ($group['label'])
                    <button type="button" class="group-label group mb-1.5 flex w-full items-center justify-between whitespace-nowrap rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 transition hover:text-slate-300" @click="open = !open; localStorage.setItem('simba-sidebar-{{ $groupKey }}', open ? '1' : '0')">
                        <span class="flex items-center gap-2">
                            <span class="h-px w-3 bg-slate-700 transition group-hover:bg-slate-500" aria-hidden="true"></span>
                            {{ $group['label'] }}
                        </span>
                        <svg class="h-3 w-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                @endif
                <ul x-show="open" x-cloak class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @continue(! ($item['show'] ?? false) || ! \Illuminate\Support\Facades\Route::has($item['route']))
                        @php $active = $itemActive($item['active']); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="relative flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-medium leading-5 no-underline hover:no-underline focus:no-underline visited:no-underline transition-all duration-150
                                    @if ($active)
                                        bg-slate-800/80 text-white shadow-sm shadow-slate-950/40 ring-1 ring-inset ring-slate-700/80
                                    @else
                                        text-slate-400 hover:bg-slate-800/50 hover:text-slate-100
                                    @endif"
                                @if ($active) aria-current="page" @endif
                                style="text-decoration:none!important"
                            >
                                @if ($active)
                                    <span class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-blue-500" aria-hidden="true"></span>
                                @endif
                                <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center">
                                    <i class="bi {{ $item['icon'] }} text-[17px] leading-none
                                        @if ($active)
                                            text-blue-400
                                        @else
                                            text-slate-500 transition-colors group-hover:text-slate-300
                                        @endif"></i>
                                </span>
                                <span class="min-w-0 flex-1 whitespace-nowrap">{{ $item['label'] }}</span>
                                @if (! empty($item['badge']))
                                    @php $isCounter = ctype_digit((string) $item['badge']); @endphp
                                    <span class="ml-auto inline-flex h-5 shrink-0 items-center rounded-full px-2 text-[9px] font-bold leading-none ring-1 ring-inset
                                        @if ($isCounter)
                                            bg-red-500/90 text-white ring-red-400/40
                                        @elseif ($item['badge'] === 'QR')
                                            bg-blue-500/15 text-blue-300 ring-blue-400/30
                                        @else
                                            bg-slate-700/40 text-slate-400 ring-slate-600/40
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
    <div class="shrink-0 border-t border-white/5 bg-white/[0.02] px-4 py-3">
        <div class="flex items-center gap-2">
            @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                <a href="{{ route('profile.edit') }}" class="flex min-h-10 flex-1 items-center gap-3 rounded-xl px-3 py-2 text-[12px] font-medium leading-5 no-underline hover:no-underline focus:no-underline visited:no-underline text-slate-400 transition hover:bg-slate-800/50 hover:text-white" style="text-decoration:none!important">
                    <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center">
                        <i class="bi bi-person-circle text-[17px] leading-none text-slate-500"></i>
                    </span>
                    <span>Profil Saya</span>
                </a>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" title="Keluar" class="flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-[12px] font-medium leading-5 no-underline hover:no-underline focus:no-underline visited:no-underline text-red-400 transition hover:bg-red-500/10 hover:text-red-300" style="text-decoration:none!important">
                        <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center">
                            <i class="bi bi-box-arrow-right text-[17px] leading-none"></i>
                        </span>
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
