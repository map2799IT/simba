@extends('layouts.app')

@section('title', 'Tambah Lokasi')
@section('page-title', 'Tambah Lokasi')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Tambah Lokasi Penyimpanan
        </h1>

        <p class="page-description">
            Administrator, Kepala Bengkel, dan Toolman dapat membuat
            lokasi induk maupun lokasi turunan pada jurusan yang menjadi
            kewenangannya.
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route(
                    'locations.store'
                ) }}"
            >
                @csrf

                @include('locations._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a
                        href="{{ route(
                            'locations.index'
                        ) }}"
                        class="btn btn-outline-secondary"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check-circle me-2"></i>
                        Simpan Lokasi
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
