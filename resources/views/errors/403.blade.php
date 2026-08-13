@extends('layouts.guest')

@section('title', '403 — Akses Ditolak')

@section('content')
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 text-center">
        <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-red-100 text-red-600">
            <i class="bi bi-lock-fill text-4xl"></i>
        </div>
        <h1 class="mt-6 text-3xl font-bold text-slate-900">Akses Ditolak</h1>
        <p class="mt-3 max-w-md text-base text-slate-500">Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator jika Anda merasa ini adalah kesalahan.</p>
        <div class="mt-8">
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                <i class="bi bi-house mr-2"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
@endsection
