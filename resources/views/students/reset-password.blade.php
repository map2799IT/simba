@extends('layouts.app')

@section('title', 'Reset Password Siswa')
@section('page-title', 'Reset Password Siswa')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 page-heading">
        <div>
            <h1 class="page-title">Reset Password Siswa</h1>
            <p class="page-description mb-0">{{ $student->name }} · {{ $student->workshop?->code }} · NISN {{ $student->nisn }}</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <section class="content-card" style="max-width: 720px;">
        <div class="content-card-body">
            <div class="alert alert-warning">
                Password baru harus disampaikan langsung kepada siswa. Toolman hanya dapat mereset siswa pada jurusannya sendiri.
            </div>

            <form method="POST" action="{{ route('students.reset-password.update', $student->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8" required autocomplete="new-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" minlength="8" required autocomplete="new-password">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </section>
@endsection
