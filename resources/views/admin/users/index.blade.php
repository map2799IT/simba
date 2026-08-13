@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')

@section('content')
    @php
        $currentUser = auth()->user();
        $activeCount = $users->getCollection()->where('is_active', true)->count();
        $inactiveCount = $users->getCollection()->where('is_active', false)->count();

        $roleVariants = [
            'admin' => 'danger',
            'wakil_sarpras' => 'info',
            'kepala_bengkel' => 'primary',
            'toolman' => 'success',
            'guru' => 'info',
            'siswa' => 'neutral',
        ];
    @endphp

    <x-page-header title="Data Pengguna" description="Kelola akun, peran, dan status pengguna SIMBA." :breadcrumb="['Administrasi', 'Pengguna']">
        @if (\Illuminate\Support\Facades\Route::has('admin.access.index'))
            <x-button href="{{ route('admin.access.index') }}" variant="secondary">
                <i class="bi bi-shield-lock"></i> Hak Akses
            </x-button>
        @endif
        <x-button href="{{ route('admin.users.create') }}" variant="primary">
            <i class="bi bi-person-plus"></i> Tambah Pengguna
        </x-button>
    </x-page-header>

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-sm font-medium text-slate-500">Total Pengguna</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($users->total(), 0, ',', '.') }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><i class="bi bi-people-fill text-xl"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-sm font-medium text-slate-500">Aktif</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($activeCount, 0, ',', '.') }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600"><i class="bi bi-person-check-fill text-xl"></i></div>
        </div>
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-sm font-medium text-slate-500">Nonaktif</p>
                <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($inactiveCount, 0, ',', '.') }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600"><i class="bi bi-person-x-fill text-xl"></i></div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                        <input id="search" type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Nama, username, atau email">
                    </div>
                </div>
                <div class="w-full sm:w-44">
                    <label for="role" class="mb-1.5 block text-sm font-semibold text-slate-700">Peran</label>
                    <select id="role" name="role" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="wakil_sarpras" @selected(request('role') === 'wakil_sarpras')>Wakil Sarana dan Prasarana</option>
                    </select>
                </div>
                <div class="w-full sm:w-36">
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Filter</x-button>
                    <x-button href="{{ route('admin.users.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Desktop table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pengguna</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Terdaftar</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="transition-colors hover:bg-blue-50/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-500">
                                            @if ($currentUser && $currentUser->is($user)) <span class="text-blue-600">Akun Anda</span> @else ID: {{ $user->id }} @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $user->email }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <x-badge variant="{{ $roleVariants[$user->role] ?? 'neutral' }}">{{ $user->roleLabel() }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($user->is_active)
                                    <x-badge variant="success" dot>Aktif</x-badge>
                                @else
                                    <x-badge variant="danger" dot>Nonaktif</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="text-sm text-slate-600">{{ $user->created_at?->format('d-m-Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $user->created_at?->format('H:i') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if (! $currentUser || ! $currentUser->is($user))
                                        <x-confirm-modal
                                            title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Pengguna?"
                                            description="{{ $user->is_active ? 'Pengguna ini tidak akan dapat masuk ke SIMBA.' : 'Pengguna ini akan dapat masuk kembali.' }}"
                                            :confirmLabel="($user->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan')"
                                            :variant="($user->is_active ? 'danger' : 'primary')"
                                            :formAction="route('admin.users.toggle-status', $user)"
                                            :formMethod="('PATCH')"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium transition hover:bg-slate-50"
                                        >
                                            @if ($user->is_active)
                                                <i class="bi bi-toggle-on text-emerald-500"></i>
                                            @else
                                                <i class="bi bi-toggle-off text-slate-400"></i>
                                            @endif
                                        </x-confirm-modal>
                                    @else
                                        <span class="inline-flex min-h-9 cursor-not-allowed items-center rounded-lg border border-slate-100 px-2.5 py-1.5 text-xs text-slate-300" title="Akun sendiri tidak dapat dinonaktifkan"><i class="bi bi-lock"></i></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8">
                            <x-empty-state icon="bi-people" title="Data pengguna tidak ditemukan" description="Coba ubah kata kunci pencarian atau filter." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile card list --}}
    <div class="card-mobile space-y-3">
        @forelse ($users as $user)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>
                    @if ($user->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="danger" dot>Nonaktif</x-badge>@endif
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Peran</dt><dd class="mt-0.5"><x-badge variant="{{ $roleVariants[$user->role] ?? 'neutral' }}">{{ $user->roleLabel() }}</x-badge></dd></div>
                    <div><dt class="text-xs text-slate-500">Terdaftar</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $user->created_at?->format('d-m-Y') }}</dd></div>
                </dl>
                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil mr-1.5"></i> Edit</a>
                    @if (! $currentUser || ! $currentUser->is($user))
                        <x-confirm-modal
                            title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Pengguna?"
                            description="{{ $user->is_active ? 'Pengguna ini tidak akan dapat masuk ke SIMBA.' : 'Pengguna ini akan dapat masuk kembali.' }}"
                            :confirmLabel="($user->is_active ? 'Nonaktifkan' : 'Aktifkan')"
                            :variant="($user->is_active ? 'danger' : 'primary')"
                            :formAction="route('admin.users.toggle-status', $user)"
                            :formMethod="('PATCH')"
                            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium transition hover:bg-slate-50"
                        >
                            @if ($user->is_active)
                                <i class="bi bi-toggle-on text-emerald-500 mr-1.5"></i> Nonaktifkan
                            @else
                                <i class="bi bi-toggle-off text-slate-400 mr-1.5"></i> Aktifkan
                            @endif
                        </x-confirm-modal>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-people" title="Data pengguna tidak ditemukan" description="Coba ubah kata kunci pencarian atau filter." />
            </div>
        @endforelse
    </div>

    @if ($users->hasPages())
        <div class="mt-5 flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p class="text-sm text-slate-500">Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }}</p>
            {{ $users->links() }}
        </div>
    @endif
@endsection
