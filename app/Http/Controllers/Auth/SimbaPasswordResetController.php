<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class SimbaPasswordResetController
    extends Controller
{
    public function requestForm(): View
    {
        return view(
            'auth.forgot-password'
        );
    }

    public function sendResetLink(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'identity' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $user = $this->resolveUser(
            trim(
                (string)
                $validated['identity']
            )
        );

        /*
         * Respons tetap umum untuk mencegah pengecekan akun secara massal.
         */
        $genericStatus =
            'Jika akun dan email terdaftar ditemukan, tautan pemulihan akan dikirim. Siswa yang belum memiliki email aktif dapat meminta Toolman jurusan mereset password.';

        if (
            $user === null
            || ! filled($user->email)
            || Str::endsWith(
                Str::lower(
                    (string) $user->email
                ),
                [
                    '@simba.local',
                    '@siswa.simba.local',
                ]
            )
        ) {
            return back()->with(
                'status',
                $genericStatus
            );
        }

        Password::broker()->sendResetLink([
            'email' =>
                (string) $user->email,
        ]);

        return back()->with(
            'status',
            $genericStatus
        );
    }

    public function resetForm(
        Request $request,
        string $token
    ): View {
        return view(
            'auth.reset-password',
            [
                'token' => $token,
                'email' =>
                    (string)
                    $request->query(
                        'email',
                        ''
                    ),
            ]
        );
    }

    public function reset(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
            ],

            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
        ]);

        $status = Password::broker()->reset(
            $validated,
            function (
                User $user,
                string $password
            ): void {
                $user->fill([
                    'password' =>
                        Hash::make(
                            $password
                        ),

                    'remember_token' =>
                        Str::random(60),
                ])->save();

                event(
                    new PasswordReset(
                        $user
                    )
                );
            }
        );

        if (
            $status ===
            Password::PASSWORD_RESET
        ) {
            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Password berhasil diperbarui. Silakan masuk menggunakan password baru.'
                );
        }

        return back()
            ->withInput(
                $request->only(
                    'email'
                )
            )
            ->withErrors([
                'email' =>
                    __($status),
            ]);
    }

    private function resolveUser(
        string $identity
    ): ?User {
        $identityLower =
            Str::lower($identity);

        $query = User::query()
            ->withoutGlobalScopes();

        $query->where(
            function ($builder) use (
                $identity,
                $identityLower
            ): void {
                if (
                    Schema::hasColumn(
                        'users',
                        'email'
                    )
                ) {
                    $builder->orWhere(
                        'email',
                        $identityLower
                    );
                }

                if (
                    Schema::hasColumn(
                        'users',
                        'username'
                    )
                ) {
                    $builder->orWhere(
                        'username',
                        $identity
                    );
                }
            }
        );

        $user = $query->first();

        if ($user !== null) {
            return $user;
        }

        if (
            ! preg_match(
                '/^\d{10}$/',
                $identity
            )
            || ! Schema::hasTable(
                'students'
            )
        ) {
            return null;
        }

        $student = DB::table('students')
            ->where(
                'nisn',
                $identity
            )
            ->first();

        if (
            $student === null
            || ! property_exists(
                $student,
                'user_id'
            )
            || $student->user_id === null
        ) {
            return null;
        }

        return User::query()
            ->withoutGlobalScopes()
            ->find(
                (int) $student->user_id
            );
    }
}
