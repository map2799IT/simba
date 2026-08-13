@extends('layouts.app')

@section('title', 'Edit Siswa')
@section('page-title', 'Edit Siswa')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 page-heading">
        <div>
            <h1 class="page-title">Edit Siswa</h1>
            <p class="page-description mb-0">{{ $student->name }} · NISN {{ $student->nisn }}</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <section class="content-card">
        <div class="content-card-body">
            <form method="POST" action="{{ route('students.update', $student->id) }}">
                @csrf
                @method('PUT')
                @include('students._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </section>
@endsection
