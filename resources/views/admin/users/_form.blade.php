@php
    $isEdit = isset($user) && $user->exists;
    $selectedRole = old('role', $isEdit ? $user->role : \App\Models\User::ROLE_SISWA);
    $selectedActive = (string) old('is_active', $isEdit ? (int) $user->is_active : 1);
    $selectedWorkshopId = old('workshop_id', $isEdit ? $user->workshop_id : null);
    $rolesWithWorkshop = ['toolman', 'kepala_bengkel', 'siswa'];
    $roleDescriptions = [
        'admin' => 'Mengelola seluruh data dan konfigurasi sistem.',
        'wakil_sarpras' => 'Memantau inventaris, menyetujui pengajuan, dan melihat laporan.',
        'kepala_bengkel' => 'Memantau inventaris, transaksi, laporan, dan audit. Wajib pilih jurusan.',
        'toolman' => 'Mengelola barang, stok, peminjaman, dan perbaikan. Wajib pilih jurusan.',
        'guru' => 'Melihat inventaris dan melakukan pengajuan peminjaman.',
        'siswa' => 'Melihat inventaris dan melakukan pengajuan peminjaman. Wajib pilih jurusan.',
    ];
@endphp

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="text-sm text-red-800">
            <p class="font-semibold">Data belum dapat disimpan</p>
            <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    {{-- Main form --}}
    <div class="lg:col-span-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Informasi Pengguna</h2>
                <p class="mt-0.5 text-sm text-slate-500">Masukkan identitas dan informasi akun pengguna.</p>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-full">
                        <label for="name" class="block text-sm font-semibold text-slate-700">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name', $isEdit ? $user->name : '') }}" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" placeholder="Masukkan nama lengkap" maxlength="150" autofocus required>
                        @error('name')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-semibold text-slate-700">Username <span class="text-red-500">*</span></label>
                        <input id="username" type="text" name="username" value="{{ old('username', $isEdit ? $user->username : '') }}" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('username') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" placeholder="Contoh: budi123" maxlength="100" autocomplete="username" required>
                        <p class="mt-1 text-xs text-slate-500">Gunakan huruf, angka, tanda hubung, atau garis bawah.</p>
                        @error('username')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700">Email <span class="text-red-500">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email', $isEdit ? $user->email : '') }}" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" placeholder="nama@sekolah.sch.id" maxlength="191" autocomplete="email" required>
                        @error('email')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password @unless($isEdit)<span class="text-red-500">*</span>@endunless</label>
                        <input id="password" type="password" name="password" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" autocomplete="new-password" @required(! $isEdit) @if ($isEdit) readonly @endif>
                        <p class="mt-1 text-xs text-slate-500">@if ($isEdit) Kosongkan apabila password tidak diubah. @else Password minimal 8 karakter. @endif</p>
                        @error('password')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Konfirmasi Password @unless($isEdit)<span class="text-red-500">*</span>@endunless</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" autocomplete="new-password" @required(! $isEdit) @if ($isEdit) readonly @endif>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar: Role & Status --}}
    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Peran Pengguna</h2>
                <p class="mt-0.5 text-sm text-slate-500">Peran menentukan modul yang dapat diakses.</p>
            </div>
            <div class="p-5">
                <label for="role" class="block text-sm font-semibold text-slate-700">Pilih Peran <span class="text-red-500">*</span></label>
                <select id="role" name="role" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror" required>
                    <option value="">Pilih peran pengguna</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                <div class="mt-3">
                    @foreach ($roleDescriptions as $value => $desc)
                        <div class="mb-2 text-xs text-slate-500 @if ($selectedRole !== $value) hidden @endif" data-role-description="{{ $value }}">
                            <strong class="text-slate-700">{{ $desc }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Jurusan (hanya untuk toolman, kepala_bengkel, siswa) --}}
        <div id="workshop-section" class="{{ in_array($selectedRole, $rolesWithWorkshop) ? '' : 'hidden' }} overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 shadow-sm">
            <div class="border-b border-blue-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Jurusan / Bengkel</h2>
                <p class="mt-0.5 text-sm text-slate-500">Wajib diisi untuk Toolman, Kepala Bengkel, dan Siswa.</p>
            </div>
            <div class="p-5">
                <label for="workshop_id" class="block text-sm font-semibold text-slate-700">
                    Pilih Jurusan <span class="text-red-500">*</span>
                </label>
                <select id="workshop_id" name="workshop_id"
                    class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('workshop_id') border-red-400 focus:border-red-500 focus:ring-red-500 @enderror">
                    <option value="">— Pilih jurusan —</option>
                    @foreach ($workshops as $workshop)
                        <option value="{{ $workshop->id }}" @selected((string) $selectedWorkshopId === (string) $workshop->id)>
                            {{ $workshop->code }} — {{ $workshop->name }}
                        </option>
                    @endforeach
                </select>
                @error('workshop_id')
                    <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
                <p class="mt-1.5 text-xs text-slate-500">
                    Siswa, Toolman, dan Kepala Bengkel harus terhubung ke jurusan agar dapat mengakses data yang sesuai.
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Status Akun</h2>
                <p class="mt-0.5 text-sm text-slate-500">Akun nonaktif tidak dapat masuk ke SIMBA.</p>
            </div>
            <div class="p-5">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input id="is_active" type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-blue-500" @checked($selectedActive === '1')>
                    <span class="text-sm font-semibold text-slate-700">Pengguna aktif</span>
                </label>
                <p class="mt-2 text-xs text-slate-500">Nonaktifkan akun ketika pengguna sudah tidak diperbolehkan mengakses aplikasi.</p>
            </div>
        </div>
    </div>
</div>

<div class="sticky bottom-0 mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end sm:px-6">
    <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <i class="bi bi-save mr-1.5"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' }}
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const workshopSection = document.getElementById('workshop-section');
    const workshopSelect = document.getElementById('workshop_id');
    const rolesWithWorkshop = ['toolman', 'kepala_bengkel', 'siswa'];
    const descriptions = document.querySelectorAll('[data-role-description]');

    function updateRoleUI() {
        const role = roleSelect?.value ?? '';

        // Tampil/sembunyikan deskripsi role
        descriptions.forEach(el =>
            el.classList.toggle('hidden', el.dataset.roleDescription !== role)
        );

        // Tampil/sembunyikan section jurusan
        if (workshopSection) {
            const needsWorkshop = rolesWithWorkshop.includes(role);
            workshopSection.classList.toggle('hidden', !needsWorkshop);

            // Wajib select jurusan untuk role tertentu
            if (workshopSelect) {
                if (needsWorkshop) {
                    workshopSelect.setAttribute('required', 'required');
                } else {
                    workshopSelect.removeAttribute('required');
                    workshopSelect.value = '';
                }
            }
        }
    }

    roleSelect?.addEventListener('change', updateRoleUI);
    updateRoleUI();

    // Lepas readonly pada field password saat edit agar user bisa isi password baru
    ['password', 'password_confirmation'].forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('focus', function () {
            el.removeAttribute('readonly');
        });
    });
});
</script>
@endpush
