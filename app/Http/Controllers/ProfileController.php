<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(
        Request $request
    ): View {
        $user = $request->user();

        $student = null;

        if (
            (string) $user->role === 'siswa'
            && Schema::hasTable('students')
            && Schema::hasColumn(
                'students',
                'user_id'
            )
        ) {
            $student = \Illuminate\Support\Facades\DB::table(
                'students'
            )
                ->where(
                    'user_id',
                    $user->id
                )
                ->first();
        }

        $workshop = null;

        if (
            Schema::hasTable('workshops')
            && Schema::hasColumn(
                'users',
                'workshop_id'
            )
            && $user->workshop_id
        ) {
            $workshop = \Illuminate\Support\Facades\DB::table(
                'workshops'
            )
                ->where(
                    'id',
                    $user->workshop_id
                )
                ->first([
                    'id',
                    'code',
                    'name',
                ]);
        }

        return view(
            'profile.edit',
            [
                'user' => $user,
                'student' => $student,
                'workshop' => $workshop,
                'hasUsername' =>
                    Schema::hasColumn(
                        'users',
                        'username'
                    ),
                'hasPhone' =>
                    Schema::hasColumn(
                        'users',
                        'phone'
                    ),
            ]
        );
    }

    public function update(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $user->id
                ),
            ],
        ];

        if (
            Schema::hasColumn(
                'users',
                'username'
            )
        ) {
            $rules['username'] = [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(
                    'users',
                    'username'
                )->ignore(
                    $user->id
                ),
            ];
        }

        if (
            Schema::hasColumn(
                'users',
                'phone'
            )
        ) {
            $rules['phone'] = [
                'nullable',
                'string',
                'max:30',
            ];
        }

        $validated = $request->validate(
            $rules
        );

        $originalEmail =
            (string) $user->email;

        $user->fill(
            array_intersect_key(
                $validated,
                array_flip(
                    $user->getFillable()
                )
            )
        );

        /*
         * Kolom profil standar disimpan melalui fillable.
         */
        $profileValues = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (
            array_key_exists(
                'username',
                $validated
            )
        ) {
            $profileValues['username'] =
                $validated['username'];
        }

        if (
            array_key_exists(
                'phone',
                $validated
            )
        ) {
            $profileValues['phone'] =
                $validated['phone'];
        }

        if (
            $originalEmail
            !==
            (string) $validated['email']
            && Schema::hasColumn(
                'users',
                'email_verified_at'
            )
        ) {
            $profileValues[
                'email_verified_at'
            ] = null;
        }

        $user->fill(
            $profileValues
        )->save();

        return redirect()
            ->route('profile.edit')
            ->with(
                'success',
                'Profil berhasil diperbarui.'
            );
    }

    public function updatePassword(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ], [
            'current_password.required' =>
                'Password saat ini wajib diisi.',

            'password.required' =>
                'Password baru wajib diisi.',

            'password.confirmed' =>
                'Konfirmasi password baru tidak sama.',

            'password.min' =>
                'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();

        if (
            ! Hash::check(
                $validated['current_password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' =>
                    'Password saat ini tidak benar.',
            ]);
        }

        if (
            Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    'Password baru harus berbeda dari password saat ini.',
            ]);
        }

        $user->fill([
            'password' => Hash::make(
                $validated['password']
            ),
        ])->save();

        /*
         * Pertahankan sesi aktif, tetapi perbarui token CSRF.
         */
        $request->session()
            ->regenerateToken();

        return redirect()
            ->route('profile.edit')
            ->with(
                'password_success',
                'Password berhasil diubah.'
            );
    }
}
