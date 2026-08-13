@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@section('content')
    <x-page-header title="Tambah Pengguna" description="Buat akun baru untuk mengakses SIMBA." :breadcrumb="['Administrasi', 'Pengguna', 'Tambah']">
        <x-button href="{{ route('admin.users.index') }}" variant="secondary"><i class="bi bi-arrow-left"></i> Kembali</x-button>
    </x-page-header>

    <form method="POST" action="{{ route('admin.users.store') }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @include('admin.users._form')
    </form>
@endsection
