@extends('layouts.app')

@section('title', 'Hak Akses')
@section('page-title', 'Hak Akses')

@section('content')
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 page-heading"
    >
        <div>
            <h1 class="page-title">
                Hak Akses Pengguna
            </h1>

            <p class="page-description">
                Matriks akses setiap peran terhadap modul SIMBA.
            </p>
        </div>

        <a
            href="{{ route('admin.users.index') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-people-fill me-2"></i>
            Kelola Pengguna
        </a>
    </div>

    <div class="row g-4 mb-4">
        @foreach ($roles as $roleName => $role)
            <div class="col-12 col-md-6 col-xl">
                <section class="content-card h-100">
                    <div class="content-card-body">
                        <div
                            class="d-flex align-items-center
                                gap-3 mb-3"
                        >
                            <div class="avatar-circle">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>

                            <div>
                                <div class="fw-bold">
                                    {{ $role['label'] }}
                                </div>

                                <small class="text-secondary">
                                    {{ $roleName }}
                                </small>
                            </div>
                        </div>

                        <p class="small text-secondary mb-0">
                            {{ $role['description'] }}
                        </p>
                    </div>
                </section>
            </div>
        @endforeach
    </div>

    <section class="content-card">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-1">
                Matriks Hak Akses
            </h2>

            <p class="small text-secondary mb-0">
                Tanda centang menunjukkan peran yang dapat
                mengakses atau menjalankan fitur tersebut.
            </p>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                    table-bordered align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th style="min-width: 250px">
                            Modul atau Aktivitas
                        </th>

                        @foreach ($roles as $role)
                            <th
                                class="text-center"
                                style="min-width: 130px"
                            >
                                {{ $role['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($permissions as $permission)
                        <tr>
                            <td>
                                <div
                                    class="d-flex
                                        align-items-center gap-2"
                                >
                                    <i
                                        class="bi {{
                                            $permission['icon']
                                        }} text-primary"
                                    ></i>

                                    <span class="fw-semibold">
                                        {{ $permission['module'] }}
                                    </span>
                                </div>
                            </td>

                            @foreach ($roles as $roleName => $role)
                                <td class="text-center">
                                    @if (
                                        in_array(
                                            $roleName,
                                            $permission['roles'],
                                            true
                                        )
                                    )
                                        <span
                                            class="d-inline-flex
                                                align-items-center
                                                justify-content-center
                                                rounded-circle
                                                text-bg-success"
                                            style="
                                                width: 28px;
                                                height: 28px;
                                            "
                                            title="Diizinkan"
                                        >
                                            <i class="bi bi-check-lg"></i>
                                        </span>
                                    @else
                                        <span
                                            class="d-inline-flex
                                                align-items-center
                                                justify-content-center
                                                rounded-circle
                                                text-bg-light
                                                border text-secondary"
                                            style="
                                                width: 28px;
                                                height: 28px;
                                            "
                                            title="Tidak diizinkan"
                                        >
                                            <i class="bi bi-dash-lg"></i>
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="content-card-body border-top">
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle-fill me-2"></i>

                Peran pengguna ditentukan melalui menu
                <strong>Pengguna</strong>. Matriks ini mengikuti aturan
                middleware pada setiap route aplikasi.
            </div>
        </div>
    </section>
@endsection