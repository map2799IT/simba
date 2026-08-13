@extends('layouts.app')
@section('title', 'Jurusan')
@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold tracking-tight text-slate-900">Data Jurusan</h1><p class="mt-1 text-sm text-slate-500">Kelola jurusan bengkel.</p></div>
        @if (\Illuminate\Support\Facades\Route::has('workshops.create'))
            <x-button href="{{ route('workshops.create') }}" variant="primary"><i class="bi bi-plus-lg"></i> Tambah Jurusan</x-button>
        @endif
    </div>
    <div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
        @php $cards=[["Total Jurusan",$summary["total"]??0,"text-slate-900"],["Aktif",$summary["active"]??0,"text-emerald-600"],["Nonaktif",$summary["inactive"]??0,"text-slate-500"],["Tanpa Toolman",$summary["without_toolman"]??0,"text-amber-600"],["Tanpa Kabeng",$summary["without_head"]??0,"text-amber-600"]]; @endphp
        @foreach ($cards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ $card[0] }}</p><p class="mt-2 text-2xl font-bold {{ $card[2] }}">{{ $card[1] }}</p></div>
        @endforeach
    </div>
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('workshops.index') }}" class="p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1"><input type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cari kode / nama jurusan"></div>
                <div class="flex gap-2"><x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Cari</x-button><x-button href="{{ route('workshops.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button></div>
            </div>
        </form>
    </div>
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50"><tr><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase text-slate-500">Kode</th><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase text-slate-500">Nama Jurusan</th><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th class="px-5 py-3.5 text-right text-xs font-semibold uppercase text-slate-500">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($workshops as $workshop)
                <tr class="transition-colors hover:bg-slate-50">
                    <td class="px-5 py-3.5"><x-badge variant="neutral" class="font-mono">{{ $workshop->code }}</x-badge></td>
                    <td class="px-5 py-3.5"><div class="text-sm font-semibold text-slate-900">{{ $workshop->name }}</div><div class="text-xs text-slate-500">{{ $workshop->code }}</div></td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $workshop->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right"><a href="{{ route('workshops.edit', ['workshop' => $workshop->getRouteKey()]) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-50" title="Edit"><i class="bi bi-pencil"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10"><x-empty-state icon="bi-building" title="Belum ada data jurusan" description="Tambahkan jurusan pertama." /></td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if (method_exists($workshops, 'hasPages') && $workshops->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $workshops->links() }}</div>
        @endif
    </div>
    <div class="card-mobile space-y-3">
        @forelse ($workshops as $workshop)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $workshop->name }}</p><p class="font-mono text-xs text-slate-500">{{ $workshop->code }}</p></div><span>{{ $workshop->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
                <div class="mt-4"><a href="{{ route('workshops.edit', ['workshop' => $workshop->getRouteKey()]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50"><i class="bi bi-pencil mr-1.5"></i> Edit</a></div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6"><x-empty-state icon="bi-building" title="Belum ada data jurusan" description="Tambahkan jurusan pertama." /></div>
        @endforelse
    </div>
@endsection