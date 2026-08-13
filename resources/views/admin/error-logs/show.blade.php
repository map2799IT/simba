@extends('layouts.app')

@section('title', 'Detail Error Log')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.error-logs.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Detail Error</h1>
            <p class="mt-1 text-sm text-slate-500">ID #{{ $log->id }} · {{ $log->created_at?->format('d M Y, H:i:s') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($log->is_resolved)
                <form method="POST" action="{{ route('admin.error-logs.unresolve', $log) }}" class="m-0">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm"><i class="bi bi-arrow-counterclockwise"></i> Tandai Baru</x-button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.error-logs.resolve', $log) }}" class="m-0">
                    @csrf
                    <x-button type="submit" variant="soft-success" size="sm"><i class="bi bi-check-lg"></i> Tandai Selesai</x-button>
                </form>
            @endif
            <x-confirm-modal title="Hapus Log?" description="Log error ini akan dihapus permanen."
                confirmLabel="Hapus" variant="danger"
                :formAction="route('admin.error-logs.destroy', $log)"
                :formMethod="'DELETE'"
                class="inline-flex min-h-9 items-center rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                <i class="bi bi-trash"></i> Hapus
            </x-confirm-modal>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {{-- User & Request --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Informasi Pengguna & Request</h2>
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-4 p-5">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">User</dt><dd class="mt-1 text-sm font-bold text-slate-900">{{ $log->user?->name ?? 'Guest/Anonymous' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt><dd class="mt-1 text-sm font-medium text-slate-700">{{ $log->user?->role ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">HTTP Status</dt><dd class="mt-1"><x-badge variant="{{ $log->http_status >= 500 ? 'danger' : ($log->http_status === 403 ? 'warning' : 'neutral') }}">{{ $log->http_status }}</x-badge></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Method</dt><dd class="mt-1 font-mono text-sm text-slate-700">{{ $log->method }}</dd></div>
                <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">URL</dt><dd class="mt-1 break-all font-mono text-sm text-slate-700">{{ $log->url }}</dd></div>
                <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Route Name</dt><dd class="mt-1 font-mono text-sm text-slate-700">{{ $log->route_name ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">IP Address</dt><dd class="mt-1 font-mono text-sm text-slate-700">{{ $log->ip_address }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status Penanganan</dt><dd class="mt-1"><x-badge variant="{{ $log->is_resolved ? 'success' : 'danger' }}" dot>{{ $log->is_resolved ? 'Selesai' : 'Baru' }}</x-badge></dd></div>
                <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">User Agent</dt><dd class="mt-1 text-xs text-slate-500 break-all">{{ $log->user_agent ?? '-' }}</dd></div>
            </dl>
        </div>

        {{-- Error Detail --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Detail Error</h2>
            </div>
            <dl class="grid grid-cols-1 gap-y-4 p-5">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exception Class</dt><dd class="mt-1 font-mono text-sm text-red-700 break-all">{{ $log->exception_class ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pesan Error</dt><dd class="mt-1 text-sm text-slate-800 break-words">{{ $log->message }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Waktu</dt><dd class="mt-1 text-sm text-slate-700">{{ $log->created_at?->format('d M Y, H:i:s') }} WIB</dd></div>
            </dl>
        </div>
    </div>

    {{-- Stack Trace --}}
    @if ($log->stack_trace)
        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Stack Trace Ringkas</h2>
            </div>
            <div class="p-5">
                <pre class="overflow-x-auto whitespace-pre-wrap rounded-xl bg-slate-950 p-4 text-[11px] leading-relaxed text-emerald-300">{{ $log->stack_trace }}</pre>
            </div>
        </div>
    @endif

    {{-- Request Data --}}
    @if ($log->request_data)
        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Request Data</h2>
                <p class="mt-0.5 text-sm text-slate-500">Data sensitif (password, token) sudah disaring.</p>
            </div>
            <div class="p-5">
                <pre class="overflow-x-auto whitespace-pre-wrap rounded-xl bg-slate-50 p-4 text-xs text-slate-700">{{ json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif

    {{-- Resolution Note --}}
    @if ($log->resolution_note)
        <div class="mt-5 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Catatan Penanganan</h2>
            </div>
            <div class="p-5">
                <p class="text-sm text-slate-700">{{ $log->resolution_note }}</p>
            </div>
        </div>
    @endif

    {{-- Add resolution note --}}
    @if (!$log->is_resolved)
        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Tambah Catatan & Selesaikan</h2>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('admin.error-logs.resolve', $log) }}" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Catatan Penanganan</label>
                    <textarea name="resolution_note" rows="3"
                        class="mb-3 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Catatan tindakan yang dilakukan...">{{ old('resolution_note') }}</textarea>
                    <button type="submit" :disabled="submitting"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                        <i class="bi bi-check-lg mr-1.5"></i> Tandai Selesai
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection