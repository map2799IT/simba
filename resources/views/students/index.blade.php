@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Data Siswa</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola data induk siswa, registrasi akun berdasarkan NISN, dan reset password siswa.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button href="{{ route('students.template') }}" variant="secondary"><i class="bi bi-file-earmark-arrow-down"></i> Template</x-button>
            <x-button href="{{ route('students.import.create') }}" variant="soft-success"><i class="bi bi-file-earmark-spreadsheet"></i> Import</x-button>
            <x-button href="{{ route('students.export', request()->query()) }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Export</x-button>
            <x-button href="{{ route('students.create') }}" variant="primary"><i class="bi bi-person-plus"></i> Tambah Siswa</x-button>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('students.index') }}" class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ request('search') }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="NISN, NIS, nama, atau kelas">
                </div>
                @if ($isAdmin)
                    <div class="w-full sm:w-48">
                        <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Jurusan</label>
                        <select id="workshop_id" name="workshop_id" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua jurusan</option>
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}" @selected((string) request('workshop_id') === (string) $workshop->id)>{{ $workshop->code }} — {{ $workshop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="w-full sm:w-44">
                    <label for="registration_status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status Akun</label>
                    <select id="registration_status" name="registration_status" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        <option value="registered" @selected(request('registration_status') === 'registered')>Sudah registrasi</option>
                        <option value="unregistered" @selected(request('registration_status') === 'unregistered')>Belum registrasi</option>
                    </select>
                </div>
                <div class="w-full sm:w-36">
                    <label for="active" class="mb-1.5 block text-sm font-semibold text-slate-700">Status Data</label>
                    <select id="active" name="active" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua</option>
                        <option value="1" @selected(request('active') === '1')>Aktif</option>
                        <option value="0" @selected(request('active') === '0')>Nonaktif</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Cari</x-button>
                    @if (request()->query())
                        <x-button href="{{ route('students.index') }}" variant="secondary"><i class="bi bi-arrow-counterclockwise"></i></x-button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Desktop Table --}}
    <div class="table-desktop overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NISN / NIS</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Siswa</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan / Kelas</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tgl Lahir</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-3.5">
                                <div class="font-mono text-sm font-semibold text-slate-900">{{ $student->nisn }}</div>
                                <div class="text-xs text-slate-500">{{ $student->nis ?: '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ $student->name }}</div>
                                <div class="text-xs text-slate-500">{{ $student->genderLabel() }} · {{ $student->email ?: 'Email belum diisi' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="text-sm font-semibold text-slate-800">{{ $student->workshop?->code ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $student->class_name }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $student->birth_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="mb-1">
                                    @if ($student->is_active)
                                        <x-badge variant="success" dot>Aktif</x-badge>
                                    @else
                                        <x-badge variant="neutral" dot>Nonaktif</x-badge>
                                    @endif
                                </div>
                                @if ($student->user_id)
                                    <x-badge variant="info">Terdaftar</x-badge>
                                @else
                                    <x-badge variant="warning">Belum Registrasi</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('students.edit', $student->id) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @if ($student->user_id)
                                        <a href="{{ route('students.reset-password.edit', $student->id) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50" title="Reset Password"><i class="bi bi-key"></i></a>
                                    @endif
                                    @if ($student->is_active)
                                        <x-confirm-modal
                                            title="Nonaktifkan Data Siswa?"
                                            description="{{ $student->name }} tidak akan dapat login."
                                            confirmLabel="Nonaktifkan"
                                            variant="danger"
                                            :formAction="route('students.destroy', $student->id)"
                                            :formMethod="('DELETE')"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                            <i class="bi bi-toggle-off"></i>
                                        </x-confirm-modal>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10">
                            <x-empty-state icon="bi-mortarboard" title="Belum ada data siswa" description="Tidak ada data siswa yang sesuai dengan filter." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-slate-500">Menampilkan {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }}</p>
                    {{ $students->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="card-mobile space-y-3">
        @forelse ($students as $student)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $student->name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $student->nisn }} · {{ $student->nis ?: '-' }}</p>
                    </div>
                    @if ($student->is_active)<x-badge variant="success" dot>Aktif</x-badge>@else<x-badge variant="neutral" dot>Nonaktif</x-badge>@endif
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Jurusan</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $student->workshop?->code ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kelas</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $student->class_name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Akun</dt><dd class="mt-0.5">@if ($student->user_id)<x-badge variant="info">Terdaftar</x-badge>@else<x-badge variant="warning">Belum</x-badge>@endif</dd></div>
                    <div><dt class="text-xs text-slate-500">Tgl Lahir</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $student->birth_date?->format('d-m-Y') ?? '-' }}</dd></div>
                </dl>
                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('students.edit', $student->id) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-pencil mr-1.5"></i> Edit</a>
                    @if ($student->user_id)
                        <a href="{{ route('students.reset-password.edit', $student->id) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"><i class="bi bi-key mr-1.5"></i> Reset</a>
                    @endif
                    @if ($student->is_active)
                        <x-confirm-modal
                            title="Nonaktifkan Data Siswa?"
                            description="{{ $student->name }} tidak akan dapat login."
                            confirmLabel="Nonaktifkan"
                            variant="danger"
                            :formAction="route('students.destroy', $student->id)"
                            :formMethod="('DELETE')"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            <i class="bi bi-toggle-off"></i>
                        </x-confirm-modal>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-empty-state icon="bi-mortarboard" title="Belum ada data siswa" description="Tidak ada data siswa yang sesuai dengan filter." />
            </div>
        @endforelse
        @if ($students->hasPages())
            <div class="pt-2">{{ $students->links() }}</div>
        @endif
    </div>
@endsection