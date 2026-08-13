@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')
    @php
        $sort = $sort ?? null;
        $direction = $direction ?? 'asc';
        $perPage = $perPage ?? 25;
    @endphp

    <div class="page-heading">
        <h1 class="page-title">
            Audit Log
        </h1>

        <p class="page-description mb-0">
            Riwayat aktivitas pengguna pada sistem.
        </p>
    </div>

    @if (! $tableAvailable)
        <div class="alert alert-warning">
            Tabel <code>audit_logs</code> belum tersedia.
            Controller sudah aktif sehingga daftar route dapat dibaca,
            tetapi data audit belum dapat ditampilkan.
        </div>
    @else
        <section class="content-card mb-4">
            <div class="content-card-header">
                <form
                    method="GET"
                    action="{{ url()->current() }}"
                >
                    <div class="row g-3">
                        <div class="col-12 col-lg-8">
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
                                placeholder="Cari aktivitas atau keterangan"
                            >
                        </div>

                        <div
                            class="col-12 col-lg-4
                                d-flex align-items-end gap-2"
                        >
                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Cari
                            </button>

                            <a
                                href="{{ url()->current() }}"
                                class="btn btn-outline-secondary"
                            >
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="content-card">
            <div class="table-responsive">
                <table
                    class="table table-hover align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <x-sortable-header label="ID" :sort-key="'id'" :sort="$sort" :direction="$direction" />
                            <th>Pengguna</th>
                            <x-sortable-header label="Aktivitas" :sort-key="'event'" :sort="$sort" :direction="$direction" />
                            <x-sortable-header label="Keterangan" :sort-key="'description'" :sort="$sort" :direction="$direction" />
                            <x-sortable-header label="Waktu" :sort-key="'created_at'" :sort="$sort" :direction="$direction" />
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $activity =
                                    data_get($log, 'event')
                                    ?? data_get($log, 'action')
                                    ?? data_get($log, 'activity')
                                    ?? data_get($log, 'type')
                                    ?? '-';

                                $description =
                                    data_get($log, 'description')
                                    ?? data_get($log, 'message')
                                    ?? data_get($log, 'notes')
                                    ?? '-';

                                $loggedAt =
                                    data_get($log, 'created_at')
                                    ?? data_get($log, 'logged_at')
                                    ?? data_get($log, 'performed_at');
                            @endphp

                            <tr>
                                <td>
                                    {{ $log->id ?? '-' }}
                                </td>

                                <td>
                                    {{ $log->user_name ?? 'Sistem' }}
                                </td>

                                <td>
                                    {{ ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) $activity
                                        )
                                    ) }}
                                </td>

                                <td>
                                    {{ $description }}
                                </td>

                                <td>
                                    {{ $loggedAt
                                        ? \Carbon\Carbon::parse(
                                            $loggedAt
                                        )->format('d-m-Y H:i:s')
                                        : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center text-secondary py-5"
                                >
                                    Belum ada audit log.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (
                method_exists($logs, 'hasPages')
                && $logs->hasPages()
            )
                <div class="content-card-body border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <x-per-page-selector :per-page="$perPage" />
                    {{ $logs->links() }}
                </div>
            @endif
        </section>
    @endif
@endsection
