<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLE_WAKIL_SARPRAS =
        'wakil_sarpras';

    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $selectedRole = $this->normalizeRole(
            $request->input('role')
        );

        $users = User::query()
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $subquery
                        ) use ($search): void {
                            $subquery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'username',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('role'),
                function (
                    Builder $query
                ) use ($selectedRole): void {
                    $query->where(
                        'role',
                        $selectedRole
                    );
                }
            )
            ->when(
                $request->filled('status'),
                function (
                    Builder $query
                ) use ($request): void {
                    $query->where(
                        'is_active',
                        $request->input('status')
                            === 'active'
                    );
                }
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => $this->roleOptions(),
            'workshops' => Workshop::query()->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $request->merge([
            'role' => $this->normalizeRole(
                $request->input('role')
            ),
        ]);

        $data = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'username' => [
                    'required',
                    'string',
                    'max:100',
                    'alpha_dash',
                    Rule::unique(
                        'users',
                        'username'
                    ),
                ],

                'email' => [
                    'required',
                    'email',
                    'max:191',
                    Rule::unique(
                        'users',
                        'email'
                    ),
                ],

                'role' => [
                    'required',
                    'string',
                    Rule::in(
                        $this->allowedRoles()
                    ),
                ],

                'workshop_id' => [
                    'nullable',
                    'integer',
                    'exists:workshops,id',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            $this->validationMessages()
        );

        $workshopId = $this->resolveWorkshopId(
            $data['role'],
            $data['workshop_id'] ?? null
        );

        $user = User::query()->create([
            'name' => trim($data['name']),
            'username' => trim($data['username']),
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active', true),
            'workshop_id' => $workshopId,
            'email_verified_at' => now(),
        ]);

        $this->clearWakilSarprasWorkshop(
            $user
        );

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'Pengguna berhasil ditambahkan.'
            );
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->roleOptions(),
            'workshops' => Workshop::query()->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $request->merge([
            'role' => $this->normalizeRole(
                $request->input('role')
            ),
        ]);

        $data = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'username' => [
                    'required',
                    'string',
                    'max:100',
                    'alpha_dash',
                    Rule::unique(
                        'users',
                        'username'
                    )->ignore($user->id),
                ],

                'email' => [
                    'required',
                    'email',
                    'max:191',
                    Rule::unique(
                        'users',
                        'email'
                    )->ignore($user->id),
                ],

                'role' => [
                    'required',
                    'string',
                    Rule::in(
                        $this->allowedRoles()
                    ),
                ],

                'workshop_id' => [
                    'nullable',
                    'integer',
                    'exists:workshops,id',
                ],

                'password' => [
                    'nullable',
                    'string',
                    'min:8',
                    'confirmed',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            $this->validationMessages()
        );

        $authenticatedUser = $request->user();

        if (
            $authenticatedUser !== null
            && $authenticatedUser->is($user)
            && $user->role === User::ROLE_ADMIN
            && $data['role'] !== User::ROLE_ADMIN
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Administrator tidak dapat mengubah perannya sendiri.'
                );
        }

        $workshopId = $this->resolveWorkshopId(
            $data['role'],
            $data['workshop_id'] ?? null
        );

        $attributes = [
            'name' => trim($data['name']),
            'username' => trim($data['username']),
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'workshop_id' => $workshopId,
            'is_active' => $request->boolean('is_active'),
        ];

        if (
            isset($data['password'])
            && trim($data['password']) !== ''
        ) {
            $attributes['password'] = Hash::make(
                $data['password']
            );
        }

        $user->update($attributes);

        $this->clearWakilSarprasWorkshop(
            $user
        );

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'Pengguna berhasil diperbarui.'
            );
    }

    public function toggleStatus(
        Request $request,
        User $user
    ): RedirectResponse {
        $authenticatedUser = $request->user();

        if (
            $authenticatedUser !== null
            && $authenticatedUser->is($user)
        ) {
            return back()->with(
                'error',
                'Anda tidak dapat menonaktifkan akun sendiri.'
            );
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? 'Pengguna berhasil diaktifkan.'
                : 'Pengguna berhasil dinonaktifkan.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function roleOptions(): array
    {
        $roles = User::roleOptions();

        /*
         * Hapus nama role lama apabila masih ada.
         */
        unset(
            $roles['waka_sarpras']
        );

        $roles[self::ROLE_WAKIL_SARPRAS] =
            'Wakil Sarana dan Prasarana';

        return $roles;
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoles(): array
    {
        return array_keys(
            $this->roleOptions()
        );
    }

    private function normalizeRole(
        mixed $role
    ): string {
        $normalized = strtolower(
            trim((string) $role)
        );

        $normalized = str_replace(
            [
                '-',
                ' ',
            ],
            '_',
            $normalized
        );

        $normalized = preg_replace(
            '/_+/',
            '_',
            $normalized
        ) ?? $normalized;

        $aliases = [
            'waka_sarpras' =>
                self::ROLE_WAKIL_SARPRAS,

            'wakil_sarpras' =>
                self::ROLE_WAKIL_SARPRAS,

            'wakil_sarana_prasarana' =>
                self::ROLE_WAKIL_SARPRAS,

            'wakil_sarana_dan_prasarana' =>
                self::ROLE_WAKIL_SARPRAS,

            'waka_sarana_prasarana' =>
                self::ROLE_WAKIL_SARPRAS,

            'waka_sarana_dan_prasarana' =>
                self::ROLE_WAKIL_SARPRAS,
        ];

        return $aliases[$normalized]
            ?? $normalized;
    }

    private function clearWakilSarprasWorkshop(
        User $user
    ): void {
        if (
            $user->role
                !== self::ROLE_WAKIL_SARPRAS
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return;
        }

        if ($user->workshop_id === null) {
            return;
        }

        $user->fill([
            'workshop_id' => null,
        ])->saveQuietly();
    }

    /**
     * Role yang membutuhkan workshop_id: toolman, kepala_bengkel, siswa.
     * Admin, guru, wakil_sarpras tidak perlu workshop_id.
     */
    private function resolveWorkshopId(string $role, mixed $workshopId): ?int
    {
        $rolesWithWorkshop = ['toolman', 'kepala_bengkel', 'siswa'];

        if (! in_array($role, $rolesWithWorkshop, true)) {
            return null;
        }

        return $workshopId !== null
            ? (int) $workshopId
            : null;
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'name.required' =>
                'Nama pengguna wajib diisi.',

            'name.string' =>
                'Nama pengguna harus berupa teks.',

            'name.max' =>
                'Nama pengguna maksimal 150 karakter.',

            'username.required' =>
                'Username wajib diisi.',

            'username.string' =>
                'Username harus berupa teks.',

            'username.max' =>
                'Username maksimal 100 karakter.',

            'username.alpha_dash' =>
                'Username hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.',

            'username.unique' =>
                'Username sudah digunakan.',

            'email.required' =>
                'Email wajib diisi.',

            'email.email' =>
                'Format email tidak valid.',

            'email.max' =>
                'Email maksimal 191 karakter.',

            'email.unique' =>
                'Email sudah digunakan.',

            'role.required' =>
                'Peran pengguna wajib dipilih.',

            'role.string' =>
                'Peran pengguna tidak valid.',

            'role.in' =>
                'Peran pengguna tidak valid.',

            'password.required' =>
                'Password wajib diisi.',

            'password.string' =>
                'Password harus berupa teks.',

            'password.min' =>
                'Password minimal 8 karakter.',

            'password.confirmed' =>
                'Konfirmasi password tidak sesuai.',

            'is_active.boolean' =>
                'Status pengguna tidak valid.',
        ];
    }
}