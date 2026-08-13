@extends('layouts.app')

@section('title', 'Detail Audit')
@section('page-title', 'Detail Audit')

@section('content')
    @php
        $formatAuditValue = function (mixed $value): string {
            if ($value === null) {
                return '-';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_array($value) || is_object($value)) {
                return json_encode(
                    $value,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                );
            }

            return (string) $value;
        };
    @endphp

    <div
        class="d-flex flex-column flex-md-row
            justify-content-between align-items-md-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Detail Audit #{{ $log->id }}
            </h1>

            <p class="page-description">
                {{ $log->modelLabel() }}
                · {{ $log->auditable_label }}
            </p>
        </div>

        <a
            href="{{ route('audit-logs.index') }}"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-2"></i>
            Kembali
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <section class="content-card h-100">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Informasi Aktivitas
                    </h2>
                </div>

                <div class="content-card-body">
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Waktu
                            </div>

                            <div class="fw-semibold">
                                {{ $log->created_at
                                    ->format('d-m-Y H:i:s') }}
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Pengguna
                            </div>

                            <div class="fw-semibold">
                                {{ $log->user?->name
                                    ?? 'Sistem' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Aktivitas
                            </div>

                            <span
                                class="badge {{
                                    $log->eventBadgeClass()
                                }}"
                            >
                                {{ $log->eventLabel() }}
                            </span>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="small text-secondary">
                                Modul
                            </div>

                            <div>
                                {{ $log->modelLabel() }}
                            </div>
                        </div>

                        <div class="col-12 col-md-8">
                            <div class="small text-secondary">
                                Objek
                            </div>

                            <div class="fw-semibold">
                                {{ $log->auditable_label
                                    ?: '-' }}
                            </div>

                            <small class="text-secondary">
                                {{ $log->auditable_type }}
                                #{{ $log->auditable_id }}
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="content-card h-100">
                <div class="content-card-header">
                    <h2 class="h6 fw-bold mb-0">
                        Informasi Request
                    </h2>
                </div>

                <div class="content-card-body">
                    <div class="mb-3">
                        <div class="small text-secondary">
                            Route
                        </div>

                        <code>
                            {{ $log->route_name ?: '-' }}
                        </code>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary">
                            Method
                        </div>

                        <div>
                            {{ $log->method ?: '-' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary">
                            Alamat IP
                        </div>

                        <div>
                            {{ $log->ip_address ?: '-' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary">
                            URL
                        </div>

                        <div class="text-break">
                            {{ $log->url ?: '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="small text-secondary">
                            User Agent
                        </div>

                        <div class="small text-break">
                            {{ $log->user_agent ?: '-' }}
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-1">
                Perubahan Data
            </h2>

            <p class="small text-secondary mb-0">
                Nilai sebelum dan sesudah aktivitas dilakukan.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 24%">
                            Kolom
                        </th>

                        <th style="width: 38%">
                            Sebelum
                        </th>

                        <th style="width: 38%">
                            Sesudah
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($log->changedFields() as $change)
                        <tr>
                            <td>
                                <code>
                                    {{ $change['field'] }}
                                </code>
                            </td>

                            <td>
                                @if ($change['has_old'])
                                    <pre
                                        class="mb-0 small text-wrap"
                                    >{{ $formatAuditValue(
                                        $change['old']
                                    ) }}</pre>
                                @else
                                    <span class="text-secondary">
                                        Tidak ada
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($change['has_new'])
                                    <pre
                                        class="mb-0 small text-wrap"
                                    >{{ $formatAuditValue(
                                        $change['new']
                                    ) }}</pre>
                                @else
                                    <span class="text-secondary">
                                        Tidak ada
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="3"
                                class="text-center
                                    text-secondary py-5"
                            >
                                Tidak ada detail perubahan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection