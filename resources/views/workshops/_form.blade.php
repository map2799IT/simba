{{-- SIMBA WORKSHOP ACCOUNT OPTIONS START --}}
@php
    $headUsers =
        isset($headUsers)
            ? collect($headUsers)
            : \App\Models\User::query()
                ->withoutGlobalScopes()
                ->where(
                    'role',
                    'kepala_bengkel'
                )
                ->orderBy('name')
                ->orderBy('username')
                ->get();

    $toolmanUsers =
        isset($toolmanUsers)
            ? collect($toolmanUsers)
            : \App\Models\User::query()
                ->withoutGlobalScopes()
                ->where(
                    'role',
                    'toolman'
                )
                ->orderBy('name')
                ->orderBy('username')
                ->get();
@endphp
{{-- SIMBA WORKSHOP ACCOUNT OPTIONS END --}}
@php
    $isEdit = isset($workshop)
        && $workshop->exists;

    $selectedActive = (string) old(
        'is_active',
        $isEdit
            ? (int) $workshop->is_active
            : 1
    );
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-2">
            Data belum dapat disimpan:
        </div>

        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <section class="content-card h-100">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-1">
                    Informasi Bengkel
                </h2>

                <p class="small text-secondary mb-0">
                    Masukkan identitas dan lokasi bengkel.
                </p>
            </div>

            <div class="content-card-body">
                <div class="row g-4">
                    <div class="col-12 col-md-4">
                        <label
                            for="code"
                            class="form-label"
                        >
                            Kode Bengkel
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            id="code"
                            type="text"
                            name="code"
                            value="{{ old(
                                'code',
                                $isEdit
                                    ? $workshop->code
                                    : ''
                            ) }}"
                            class="form-control
                                text-uppercase
                                @error('code') is-invalid @enderror"
                            placeholder="Contoh: TKR"
                            maxlength="30"
                            autofocus
                            required
                        >

                        @error('code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-8">
                        <label
                            for="name"
                            class="form-label"
                        >
                            Nama Bengkel
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old(
                                'name',
                                $isEdit
                                    ? $workshop->name
                                    : ''
                            ) }}"
                            class="form-control
                                @error('name') is-invalid @enderror"
                            placeholder="Contoh: Teknik Kendaraan Ringan"
                            maxlength="150"
                            required
                        >

                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label
                            for="department"
                            class="form-label"
                        >
                            Jurusan atau Program Keahlian
                        </label>

                        <input
                            id="department"
                            type="text"
                            name="department"
                            value="{{ old(
                                'department',
                                $isEdit
                                    ? $workshop->department
                                    : ''
                            ) }}"
                            class="form-control
                                @error('department') is-invalid @enderror"
                            placeholder="Contoh: Teknik Otomotif"
                            maxlength="150"
                        >

                        @error('department')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label
                            for="physical_location"
                            class="form-label"
                        >
                            Lokasi Fisik
                        </label>

                        <input
                            id="physical_location"
                            type="text"
                            name="physical_location"
                            value="{{ old(
                                'physical_location',
                                $isEdit
                                    ? $workshop
                                        ->physical_location
                                    : ''
                            ) }}"
                            class="form-control
                                @error('physical_location') is-invalid @enderror"
                            placeholder="Contoh: Gedung B, lantai 1"
                            maxlength="255"
                        >

                        @error('physical_location')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label
                            for="description"
                            class="form-label"
                        >
                            Deskripsi
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            class="form-control
                                @error('description') is-invalid @enderror"
                            placeholder="Jelaskan fungsi dan kegiatan utama bengkel"
                        >{{ old(
                            'description',
                            $isEdit
                                ? $workshop->description
                                : ''
                        ) }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-4">
        <section class="content-card mb-4">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-1">
                    Kepala Bengkel
                </h2>

                <p class="small text-secondary mb-0">
                    Pilih pengguna aktif dengan peran
                    Kepala Bengkel atau Administrator.
                </p>
            </div>

            <div class="content-card-body">
                <label
                    for="head_user_id"
                    class="form-label"
                >
                    Penanggung Jawab
                </label>

                <select
                    id="head_user_id"
                    name="head_user_id"
                    class="form-select
                        @error('head_user_id') is-invalid @enderror"
                >
                    <option value="">
                        Belum ditentukan
                    </option>

                    @foreach ($headUsers as $headUser)
                        <option
                            value="{{ $headUser->id }}"
                            @selected(
                                old(
                                    'head_user_id',
                                    $isEdit
                                        ? $workshop
                                            ->head_user_id
                                        : null
                                ) == $headUser->id
                            )
                        >
                            {{ $headUser->name }}
                            — {{ $headUser->roleLabel() }}
                        </option>
                    @endforeach
                </select>

                @error('head_user_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

                @if ($headUsers->isEmpty())
                    <div class="alert alert-warning mt-3 mb-0">
                        Belum tersedia pengguna aktif dengan
                        peran Kepala Bengkel.
                    </div>
                @endif
            </div>
        </section>

        <section class="content-card">
            <div class="content-card-header">
                <h2 class="h6 fw-bold mb-1">
                    Status Bengkel
                </h2>

                <p class="small text-secondary mb-0">
                    Bengkel nonaktif tidak ditampilkan pada
                    pilihan transaksi baru.
                </p>
            </div>

            <div class="content-card-body">
                <input
                    type="hidden"
                    name="is_active"
                    value="0"
                >

                <div class="form-check form-switch">
                    <input
                        id="is_active"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="form-check-input"
                        role="switch"
                        @checked(
                            $selectedActive === '1'
                        )
                    >

                    <label
                        for="is_active"
                        class="form-check-label fw-semibold"
                    >
                        Bengkel aktif
                    </label>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const codeInput =
                    document.getElementById('code');

                if (!codeInput) {
                    return;
                }

                codeInput.addEventListener(
                    'input',
                    function () {
                        this.value = this.value
                            .toUpperCase()
                            .replace(
                                /[^A-Z0-9_-]/g,
                                ''
                            );
                    }
                );
            }
        );
    </script>
@endpush