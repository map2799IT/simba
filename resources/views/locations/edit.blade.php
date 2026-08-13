@extends('layouts.app')

@section('title', 'Edit Lokasi')
@section('page-title', 'Edit Lokasi')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Edit Lokasi Penyimpanan
        </h1>

        <p class="page-description">
            {{ $location->code }} — {{ $location->name }}
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route(
                    'locations.update',
                    $location
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('locations._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a
                        href="{{ route(
                            'locations.show',
                            $location
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

            <hr class="my-4">

            <form
                method="POST"
                action="{{ route(
                    'locations.destroy',
                    $location
                ) }}"
                onsubmit="
                    return confirm(
                        'Hapus lokasi ini? Lokasi yang masih memiliki isi atau turunan tidak dapat dihapus.'
                    )
                "
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="btn btn-outline-danger"
                >
                    <i class="bi bi-trash me-2"></i>
                    Hapus Lokasi
                </button>
            </form>
        </div>
    </section>
@endsection
