@extends('layouts.app')

@section('title', 'Edit Jurusan')
@section('page-title', 'Edit Jurusan')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Edit Jurusan
        </h1>

        <p class="page-description">
            {{ $workshop->display_name }}
        </p>
    </div>

    @if (array_sum($references) > 0)
        <div class="alert alert-info">
            Jurusan sudah digunakan oleh data lain.
            Kode jurusan dikunci untuk menjaga konsistensi
            nomor inventaris dan relasi data.
        </div>
    @endif

    <section class="content-card">
        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route(
                    'workshops.update',
                    $workshop
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('workshops._form')

                <div
                    class="d-flex justify-content-end
                        gap-2 mt-4"
                >
                    <a
                        href="{{ route(
                            'workshops.index'
                        ) }}"
                        class="btn btn-outline-secondary"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="content-card mt-4">
        <div class="content-card-header">
            <h2 class="h6 fw-bold mb-0">
                Data yang Menggunakan Jurusan
            </h2>
        </div>

        <div class="content-card-body">
            <div class="row g-3">
                @foreach (
                    [
                        'users' => 'Pengguna',
                        'storage_locations' => 'Lokasi',
                        'items' => 'Barang',
                        'item_assets' => 'Unit Alat',
                        'students' => 'Siswa',
                        'loans' => 'Peminjaman',
                    ]
                    as $key => $label
                )
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-secondary">
                                {{ $label }}
                            </div>

                            <div class="fs-4 fw-bold">
                                {{ $references[$key] ?? 0 }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
