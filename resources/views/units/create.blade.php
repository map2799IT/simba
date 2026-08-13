@extends('layouts.app')

@section('title', 'Tambah Satuan')
@section('page-title', 'Tambah Satuan')

@section('content')
    <x-page-header title="Tambah Satuan" description="Tambahkan satuan yang digunakan oleh alat atau bahan." :breadcrumb="['Master Sistem', 'Satuan', 'Tambah']">
        <x-button href="{{ route('units.index') }}" variant="secondary"><i class="bi bi-arrow-left"></i> Kembali</x-button>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="text-sm text-red-800"><p class="font-semibold">Data belum lengkap</p><p class="mt-1 text-red-700">Periksa kembali field yang ditandai merah.</p></div>
        </div>
    @endif

    <form method="POST" action="{{ route('units.store') }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @include('units._form')
    </form>
@endsection
