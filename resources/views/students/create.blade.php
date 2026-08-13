@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('page-title', 'Tambah Siswa')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 page-heading">
        <div>
            <h1 class="page-title">Tambah Siswa</h1>
            <p class="page-description mb-0">Input data induk siswa agar dapat registrasi akun menggunakan NISN.</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <section class="content-card">
        <div class="content-card-body">
            <form method="POST" action="{{ route('students.store') }}">
                @csrf
                @include('students._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Siswa</button>
                </div>
            </form>
        </div>
    </section>
@endsection
