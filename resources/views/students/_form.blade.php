@php
    $editing = $student->exists;
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="nisn" class="form-label">NISN</label>
        <input
            id="nisn"
            type="text"
            name="nisn"
            value="{{ old('nisn', $student->nisn) }}"
            class="form-control @error('nisn') is-invalid @enderror"
            inputmode="numeric"
            maxlength="10"
            pattern="[0-9]{10}"
            required
        >
        <div class="form-text">Tepat 10 digit dan menjadi identitas registrasi akun siswa.</div>
        @error('nisn')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="nis" class="form-label">NIS</label>
        <input
            id="nis"
            type="text"
            name="nis"
            value="{{ old('nis', $student->nis) }}"
            class="form-control @error('nis') is-invalid @enderror"
            maxlength="50"
        >
        @error('nis')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="name" class="form-label">Nama Lengkap</label>
        <input
            id="name"
            type="text"
            name="name"
            value="{{ old('name', $student->name) }}"
            class="form-control @error('name') is-invalid @enderror"
            maxlength="150"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="workshop_id" class="form-label">Jurusan</label>

        @if ($isAdmin)
            <select
                id="workshop_id"
                name="workshop_id"
                class="form-select @error('workshop_id') is-invalid @enderror"
                required
            >
                <option value="">Pilih jurusan</option>
                @foreach ($workshops as $workshop)
                    <option
                        value="{{ $workshop->id }}"
                        @selected(
                            (string) old('workshop_id', $student->workshop_id)
                            === (string) $workshop->id
                        )
                    >
                        {{ $workshop->code }} — {{ $workshop->name }}
                    </option>
                @endforeach
            </select>
        @else
            @php($workshop = $workshops->first())
            <input
                type="text"
                class="form-control"
                value="{{ $workshop ? $workshop->code.' — '.$workshop->name : 'Jurusan belum ditetapkan' }}"
                disabled
            >
            <input
                type="hidden"
                name="workshop_id"
                value="{{ $workshop?->id }}"
            >
        @endif

        @error('workshop_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="class_name" class="form-label">Kelas/Rombel</label>
        <input
            id="class_name"
            type="text"
            name="class_name"
            value="{{ old('class_name', $student->class_name) }}"
            class="form-control @error('class_name') is-invalid @enderror"
            placeholder="Contoh: XI TKJ 1"
            maxlength="100"
            required
        >
        @error('class_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="gender" class="form-label">Jenis Kelamin</label>
        <select
            id="gender"
            name="gender"
            class="form-select @error('gender') is-invalid @enderror"
            required
        >
            <option value="">Pilih</option>
            <option value="L" @selected(old('gender', $student->gender) === 'L')>Laki-laki</option>
            <option value="P" @selected(old('gender', $student->gender) === 'P')>Perempuan</option>
        </select>
        @error('gender')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="birth_date" class="form-label">Tanggal Lahir</label>
        <input
            id="birth_date"
            type="date"
            name="birth_date"
            value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}"
            class="form-control @error('birth_date') is-invalid @enderror"
            required
        >
        <div class="form-text">Dipakai bersama NISN untuk verifikasi registrasi.</div>
        @error('birth_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="school_year" class="form-label">Tahun Ajaran</label>
        <input
            id="school_year"
            type="text"
            name="school_year"
            value="{{ old('school_year', $student->school_year) }}"
            class="form-control @error('school_year') is-invalid @enderror"
            placeholder="2026/2027"
            maxlength="20"
        >
        @error('school_year')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Email Awal (opsional)</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email', $student->email) }}"
            class="form-control @error('email') is-invalid @enderror"
            maxlength="255"
        >
        <div class="form-text">Siswa tetap dapat mengisi email sendiri saat registrasi.</div>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="phone" class="form-label">Telepon</label>
        <input
            id="phone"
            type="text"
            name="phone"
            value="{{ old('phone', $student->phone) }}"
            class="form-control @error('phone') is-invalid @enderror"
            maxlength="30"
        >
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input
                id="is_active"
                type="checkbox"
                name="is_active"
                value="1"
                class="form-check-input"
                @checked(old('is_active', $student->is_active ?? true))
            >
            <label for="is_active" class="form-check-label">Data siswa aktif</label>
        </div>
    </div>
</div>
