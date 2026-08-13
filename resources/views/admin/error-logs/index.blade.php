@extends('layouts.app')

@section('title', 'Audit Sistem')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Audit Sistem</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau error dan exception yang dialami pengguna SIMBA.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button href="{{ route('admin.error-logs.export-pdf', request()->query()) }}" variant="soft-danger" size="sm"><i class="bi bi-file-earmark-pdf"></i> PDF</x-button>
            <x-button href="{{ route('admin.error-logs.export-excel', request()->query()) }}" variant="soft-success" size="sm"><i class="bi bi-file-earmark-excel"></i> Excel</x-button>
            @if ($stats['resolved'] > 0)
                <form method="POST" action="{{ route('admin.error-logs.clear-resolved') }}" class="m-0">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="soft-danger" size="sm" onclick="return confirm('Hapus semua log yang sudah selesai?')">
                        <i class="bi bi-trash"></i> Hapus Selesai ({{ $stats['resolved'] }})
                    </x-button>
                </form>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total Error</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-red-600">Baru</p>
            <p class="mt-2 text-2xl font-bold text-red-700">{{ number_format($stats['unresolved']) }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-600">Selesai</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['resolved']) }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-600">User Terdampak</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($stats['users_affected']) }}</p>
        </div>
    </div>

    {{-- Top errors --}}
    @if ($topErrors->isNotEmpty())
        <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Error Paling Sering</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($topErrors as $error)
                    <div class="flex items-center justify-between px-5 py-3">
                        <p class="text-sm font-mono text-slate-700 truncate">{{ class_basename($error->exception_class) }}</p>
                        <x-badge variant="danger">{{ $error->count }}Ã—</x-badge>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('admin.error-logs.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ $search }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="URL, exception, route, atau pesan error">
                </div>
                <div class="w-full sm:w-36">
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="unresolved" @selected($status === 'unresolved')>Baru/Diproses</option>
                        <option value="resolved" @selected($status === 'resolved')>Selesai</option>
                        <option value="all" @selected($status === 'all')>Semua</option>
                    </select>
                </div>
                <div class="w-full sm:w-32">
                    <label for="user_id" class="mb-1.5 block text-sm font-semibold text-slate-700">User ID</label>
                    <input type="number" id="user_id" name="user_id" value="{{ $userId }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="User ID">
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Filter</x-button>
                    <x-button href="{{ route('admin.error-logs.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <x-sortable-header label="Waktu" :sort-key="'created_at'" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User / Role</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">URL</th>
                        <x-sortable-header label="Error" :sort-key="'message'" :sort="$sort" :direction="$direction" />
                        <x-sortable-header label="Status" :sort-key="'is_resolved'" :sort="$sort" :direction="$direction" />
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $log->created_at?->format('d-m-Y H:i:s') }}</td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ $log->user?->name ?? 'Guest' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user?->role ?? '-' }} Â· {{ $log->ip_address }}</div>
                            </td>
                            <td class="px-5 py-3.5 max-w-[200px]">
                                <div class="text-sm text-slate-700 truncate">{{ $log->url }}</div>
                                <div class="text-xs text-slate-400">{{ $log->method }} {{ $log->route_name }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <x-badge variant="{{ $log->http_status >= 500 ? 'danger' : ($log->http_status === 403 ? 'warning' : 'neutral') }}">
                                    {{ $log->http_status }}
                                </x-badge>
                                <div class="mt-1 text-xs text-slate-500 truncate max-w-[180px]">{{ \Illuminate\Support\Str::limit($log->message, 60) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if ($log->is_resolved)
                                    <x-badge variant="success" dot>Selesai</x-badge>
                                @else
                                    <x-badge variant="danger" dot>Baru</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.error-logs.show', $log) }}"
                                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10">
                            <x-empty-state icon="bi-shield-check" title="Tidak ada error tercatat" description="Sistem berjalan normal." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $logs->firstItem() ?? 0 }}â€“{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }}</p>
                    <div class="flex items-center gap-3">
                        <x-per-page-selector :per-page="$perPage" />
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile cards --}}
    <div class="card-mobile space-y-3">
        @forelse ($logs as $log)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $log->user?->name ?? 'Guest' }}</p>
                        <p class="text-xs text-slate-500">{{ $log->created_at?->format('d-m-Y H:i') }}</p>
                    </div>
                    <x-badge variant="{{ $log->http_status >= 500 ? 'danger' : 'warning' }}">{{ $log->http_status }}</x-badge>
                </div>
                <p class="mt-2 text-xs text-slate-600 truncate">{{ $log->url }}</p>
                <div class="mt-4">
                    <a href="{{ route('admin.error-logs.show', $log) }}"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        <i class="bi bi-eye mr-1.5"></i> Detail
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-shield-check" title="Tidak ada error tercatat" description="Sistem berjalan normal." />
            </div>
        @endforelse
    </div>
@endsection