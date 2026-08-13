@extends('layouts.app')

@section('title', 'Tambah Jurusan')
@section('page-title', 'Tambah Jurusan')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Tambah Jurusan
        </h1>

        <p class="page-description">
            Jurusan baru langsung disimpan ke database
            dan tidak memerlukan perubahan kode aplikasi.
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route(
                    'workshops.store'
                ) }}"
            >
                @csrf

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
                        <i class="bi bi-save me-2"></i>
                        Simpan Jurusan
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
