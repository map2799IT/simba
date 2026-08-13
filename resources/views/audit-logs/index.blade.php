@extends('layouts.app')

@section('title', 'Audit Aktivitas')
@section('page-title', 'Audit Aktivitas')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Audit Aktivitas Sistem
        </h1>

        <p class="page-description">
            Riwayat perubahan data dan aktivitas pengguna SIMBA.
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-header">
            <form
                method="GET"
                action="{{ route('audit-logs.index') }}"
            >
                <div class="row g-3">
                    <div class="col-12 col-xl-4">
                        <label
                            for="search"
                            class="form-label"
                        >
                            Pencarian
                        </label>

                        <input
                            id="search"
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Objek, pengguna, route, URL, atau IP"
                        >
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label
                            for="event"
                            class="form-label"
                        >
                            Aktivitas
                        </label>

                        <select
                            id="event"
                            name="event"
                            class="form-select"
                        >
                            <option value="">
                                Semua
                            </option>

                            @foreach ($events as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        request('event') === $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label
                            for="auditable_type"
                            class="form-label"
                        >
                            Modul
                        </label>

                        <select
                            id="auditable_type"
                            name="auditable_type"
                            class="form-select"
                        >
                            <option value="">
                                Semua
                            </option>

                            @foreach ($models as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        request('auditable_type')
                                        === $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-4">
                        <label
                            for="user_id"
                            class="form-label"
                        >
                            Pengguna
                        </label>

                        <select
                            id="user_id"
                            name="user_id"
                            class="form-select"
                        >
                            <option value="">
                                Semua pengguna
                            </option>

                            @foreach ($users as $user)
                                <option
                                    value="{{ $user->id }}"
                                    @selected(
                                        request('user_id')
                                        == $user->id
                                    )
                                >
                                    {{ $user->name }}

                                    @if ($user->username)
                                        — {{ $user->username }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label
                            for="date_from"
                            class="form-label"
                        >
                            Dari tanggal
                        </label>

                        <input
                            id="date_from"
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label
                            for="date_to"
                            class="form-label"
                        >
                            Sampai tanggal
                        </label>

                        <input
                            id="date_to"
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-search me-2"></i>
                            Cari
                        </button>

                        <a
                            href="{{ route('audit-logs.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            <i
                                class="bi bi-arrow-counterclockwise me-2"
                            ></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                        <th>Modul</th>
                        <th>Objek</th>
                        <th>Route</th>
                        <th>IP</th>
                        <th class="text-end">
                            Detail
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $log->created_at
                                        ->format('d-m-Y') }}
                                </div>

                                <small class="text-secondary">
                                    {{ $log->created_at
                                        ->format('H:i:s') }}
                                </small>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $log->user?->name
                                        ?? 'Sistem' }}
                                </div>

                                @if ($log->user?->username)
                                    <small class="text-secondary">
                                        {{ $log->user->username }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                <span
                                    class="badge {{
                                        $log->eventBadgeClass()
                                    }}"
                                >
                                    {{ $log->eventLabel() }}
                                </span>
                            </td>

                            <td>
                                {{ $log->modelLabel() }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $log->auditable_label
                                        ?: '#' . $log->auditable_id }}
                                </div>

                                <small class="text-secondary">
                                    ID: {{ $log->auditable_id }}
                                </small>
                            </td>

                            <td>
                                <code>
                                    {{ $log->route_name ?: '-' }}
                                </code>

                                @if ($log->method)
                                    <div>
                                        <small class="text-secondary">
                                            {{ $log->method }}
                                        </small>
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $log->ip_address ?: '-' }}
                            </td>

                            <td class="text-end">
                                <a
                                    href="{{ route(
                                        'audit-logs.show',
                                        $log
                                    ) }}"
                                    class="btn btn-sm
                                        btn-outline-primary"
                                    title="Lihat detail"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="text-center
                                    text-secondary py-5"
                            >
                                <i
                                    class="bi bi-journal-text
                                        fs-1 d-block mb-2"
                                ></i>

                                Belum ada aktivitas yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="content-card-body border-top">
                {{ $logs->links() }}
            </div>
        @endif
    </section>
@endsection