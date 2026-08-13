@extends('layouts.app')

@section('title', 'Jurusan Belum Diatur')
@section('page-title', 'Ajukan Peminjaman')

@section('content')
    <div class="page-heading">
        <h1 class="page-title">
            Ajukan Peminjaman
        </h1>

        <p class="page-description mb-0">
            Peminjaman siswa mengikuti jurusan pada akun.
        </p>
    </div>

    <section class="content-card">
        <div class="content-card-body py-5 text-center">
            <div class="display-5 text-warning mb-3">
                <i class="bi bi-building-exclamation"></i>
            </div>

            <h2 class="h5 fw-bold">
                Jurusan akun belum diatur
            </h2>

            <p class="text-secondary mx-auto" style="max-width: 620px;">
                Akun siswa hanya dapat meminjam alat dan bahan dari jurusannya.
                Hubungi Administrator untuk menetapkan jurusan pada akun Anda.
            </p>

            <a
                href="{{ route('loans.index') }}"
                class="btn btn-outline-primary"
            >
                Kembali ke Peminjaman Saya
            </a>
        </div>
    </section>
@endsection
