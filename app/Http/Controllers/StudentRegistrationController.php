<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StudentRegistrationController
    extends Controller
{
    public function create(): View
    {
        return view(
            'auth.student-register'
        );
    }

    public function lookup(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'nisn' => [
                'required',
                'regex:/^\d{10}$/',
            ],
        ]);

        $student = Student::query()
            ->withoutGlobalScopes()
            ->with('workshop')
            ->where(
                'nisn',
                $validated['nisn']
            )
            ->first();

        if (
            $student === null
            || ! $student->is_active
        ) {
            return response()->json(
                [
                    'message' =>
                        'NISN tidak ditemukan pada data siswa aktif.',
                ],
                404
            );
        }

        if ($student->user_id !== null) {
            return response()->json(
                [
                    'message' =>
                        'NISN ini sudah mempunyai akun. Silakan masuk atau gunakan halaman lupa password.',
                ],
                409
            );
        }

        return response()->json([
            'message' =>
                'Data siswa ditemukan.',

            'student' => [
                'nisn' =>
                    $student->nisn,

                'nis' =>
                    $student->nis
                    ?: '-',

                'name' =>
                    $student->name,

                'class_name' =>
                    $student->class_name,

                'workshop_code' =>
                    $student->workshop?->code
                    ?: '-',

                'workshop_name' =>
                    $student->workshop?->name
                    ?: '-',

                'school_year' =>
                    $student->school_year
                    ?: '-',
            ],
        ]);
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'nisn' => [
                'required',
                'regex:/^\d{10}$/',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
        ]);

        $userId = DB::transaction(
            function () use (
                $validated
            ): int {
                $student = Student::query()
                    ->withoutGlobalScopes()
                    ->where(
                        'nisn',
                        $validated['nisn']
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    $student === null
                    || ! $student->is_active
                ) {
                    throw ValidationException::
                        withMessages([
                            'nisn' =>
                                'NISN tidak ditemukan pada data siswa aktif.',
                        ]);
                }

                if ($student->user_id !== null) {
                    throw ValidationException::
                        withMessages([
                            'nisn' =>
                                'NISN ini sudah mempunyai akun. Gunakan halaman login atau lupa password.',
                        ]);
                }

                $email =
                    $this->accountEmail(
                        $student
                    );

                $values = [
                    'name' =>
                        $student->name,

                    'email' =>
                        $email,

                    'username' =>
                        $student->nisn,

                    'password' =>
                        Hash::make(
                            $validated['password']
                        ),

                    'role' =>
                        'siswa',

                    'role_id' =>
                        $this->studentRoleId(),

                    'workshop_id' =>
                        $student->workshop_id,

                    'email_verified_at' =>
                        now(),

                    'is_active' =>
                        true,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ];

                $values =
                    $this->filterColumns(
                        'users',
                        $values
                    );

                $userId =
                    (int)
                    DB::table('users')
                        ->insertGetId(
                            $values
                        );

                $studentValues =
                    $this->filterColumns(
                        'students',
                        [
                            'user_id' =>
                                $userId,

                            'registered_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]
                    );

                DB::table('students')
                    ->where(
                        'id',
                        $student->id
                    )
                    ->update(
                        $studentValues
                    );

                return $userId;
            }
        );

        Auth::loginUsingId(
            $userId
        );

        $request->session()
            ->regenerate();

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Akun siswa berhasil dibuat. Login berikutnya dapat menggunakan NISN.'
            );
    }

    private function accountEmail(
        Student $student
    ): string {
        $candidate = filled(
            $student->email
        )
            ? Str::lower(
                trim(
                    (string)
                    $student->email
                )
            )
            : '';

        if (
            $candidate !== ''
            && filter_var(
                $candidate,
                FILTER_VALIDATE_EMAIL
            )
            && ! DB::table('users')
                ->where(
                    'email',
                    $candidate
                )
                ->exists()
        ) {
            return $candidate;
        }

        $base =
            'nisn.'.
            $student->nisn.
            '@siswa.simba.local';

        $email = $base;
        $sequence = 1;

        while (
            DB::table('users')
                ->where(
                    'email',
                    $email
                )
                ->exists()
        ) {
            $sequence++;

            $email =
                'nisn.'.
                $student->nisn.
                '.'.
                $sequence.
                '@siswa.simba.local';
        }

        return $email;
    }

    private function studentRoleId(): ?int
    {
        if (! Schema::hasTable('roles')) {
            return null;
        }

        $columns =
            Schema::getColumnListing(
                'roles'
            );

        foreach (
            [
                'name',
                'slug',
                'key',
                'code',
            ]
            as $column
        ) {
            if (
                ! in_array(
                    $column,
                    $columns,
                    true
                )
            ) {
                continue;
            }

            $value =
                $column === 'code'
                    ? 'SISWA'
                    : 'siswa';

            $id = DB::table('roles')
                ->where(
                    $column,
                    $value
                )
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    private function filterColumns(
        string $table,
        array $values
    ): array {
        return array_intersect_key(
            $values,
            array_flip(
                Schema::getColumnListing(
                    $table
                )
            )
        );
    }
}
