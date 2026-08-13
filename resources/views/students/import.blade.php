@extends('layouts.app')

@section('title', 'Import Data Siswa')
@section('page-title', 'Import Data Siswa')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 page-heading">
        <div>
            <h1 class="page-title">Import Data Siswa</h1>
            <p class="page-description mb-0">Unggah file XLSX atau CSV menggunakan format template SIMBA.</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <section class="content-card">
                <div class="content-card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="fw-semibold mb-2">Import belum dijalankan karena terdapat kesalahan:</div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="file" class="form-label">File Data Siswa</label>
                        <input
                            id="file"
                            type="file"
                            name="file"
                            accept=".xlsx,.csv"
                            class="form-control @error('file') is-invalid @enderror"
                            required
                        >
                        <div class="form-text">Maksimal 10 MB. NISN harus tetap 10 digit.</div>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('students.template') }}" class="btn btn-outline-success">Download Template</a>
                            <button type="submit" class="btn btn-primary">Proses Import</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="content-card">
                <div class="content-card-header"><h2 class="h6 fw-bold mb-0">Ketentuan Import</h2></div>
                <div class="content-card-body">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Gunakan sheet <strong>Data Siswa</strong>.</li>
                        <li class="mb-2">Kolom wajib: NISN, nama, jurusan, kelas, jenis kelamin, dan tanggal lahir.</li>
                        <li class="mb-2">Toolman otomatis dibatasi ke jurusannya, walaupun kode lain ditulis dalam file.</li>
                        <li class="mb-2">NISN yang sudah ada akan diperbarui, bukan dibuat duplikat.</li>
                        <li>Siswa yang sudah registrasi tetap terhubung dengan akun lamanya.</li>
                    </ol>
                </div>
            </section>
        </div>
    </div>
@endsection
