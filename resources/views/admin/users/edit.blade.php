@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('page-title', 'Edit Pengguna')

@section('content')
    <x-page-header title="Edit Pengguna" description="{{ $user->name }} — {{ $user->roleLabel() }}" :breadcrumb="['Administrasi', 'Pengguna', 'Edit']">
        <x-button href="{{ route('admin.users.index') }}" variant="secondary"><i class="bi bi-arrow-left"></i> Kembali</x-button>
    </x-page-header>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" x-data="{ submitting: false }" @submit="submitting = true" autocomplete="off">
        @csrf
        @method('PUT')
        @include('admin.users._form')
    </form>
@endsection
