@extends('layouts.app')

@section('title', 'Edit Unit Alat')
@section('page-title', 'Edit Unit Alat')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Edit Unit Alat
        </h1>

        <p class="page-description mb-0">
            Perbarui nomor seri, lokasi, kondisi, dan status unit.
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <div class="fw-bold font-monospace">
                    {{ $asset->asset_number }}
                </div>

                <div class="small text-secondary">
                    {{ $asset->item?->code }}
                    — {{ $asset->item?->name }}
                </div>
            </div>
        </div>

        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route(
                    'item-assets.update',
                    $asset
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('item-assets._form')

                <div
                    class="d-flex flex-wrap
                        justify-content-end gap-2 mt-4"
                >
                    <a
                        href="{{ route(
                            'item-assets.show',
                            $asset
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
@endsection
