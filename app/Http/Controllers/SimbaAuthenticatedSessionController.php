<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SimbaAuthenticatedSessionController
    extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'identity' => [
                'required',
                'string',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
            ],

            'remember' => [
                'nullable',
                'boolean',
            ],
        ]);

        $identity = trim(
            (string) $validated['identity']
        );

        $throttleKey =
            Str::transliterate(
                Str::lower($identity).
                '|'.
                $request->ip()
            );

        if (
            RateLimiter::tooManyAttempts(
                $throttleKey,
                5
            )
        ) {
            $seconds =
                RateLimiter::availableIn(
                    $throttleKey
                );

            throw ValidationException::
                withMessages([
                    'identity' =>
                        "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                ]);
        }

        $user = $this->resolveUser(
            $identity
        );

        $passwordValid =
            $user !== null
            && Hash::check(
                (string) $validated['password'],
                (string) $user->password
            );

        if (
            ! $passwordValid
            || ! $this->isActive($user)
        ) {
            RateLimiter::hit(
                $throttleKey,
                60
            );

            throw ValidationException::
                withMessages([
                    'identity' =>
                        'Identitas login atau password tidak sesuai.',
                ]);
        }

        RateLimiter::clear(
            $throttleKey
        );

        Auth::guard('web')->login(
            $user,
            $request->boolean(
                'remember'
            )
        );

        $request->session()
            ->regenerate();

        return redirect()
            ->intended(
                route('dashboard')
            );
    }

    public function destroy(
        Request $request
    ): RedirectResponse {
        Auth::guard('web')->logout();

        $request->session()
            ->invalidate();

        $request->session()
            ->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Anda berhasil keluar dari SIMBA.'
            );
    }

    private function resolveUser(
        string $identity
    ): ?User {
        $query = User::query()
            ->withoutGlobalScopes();

        $query->where(
            function ($builder) use (
                $identity
            ): void {
                if (
                    Schema::hasColumn(
                        'users',
                        'email'
                    )
                ) {
                    $builder->orWhere(
                        'email',
                        Str::lower($identity)
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
            || ! Schema::hasColumn(
                'students',
                'nisn'
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

        if ($student === null) {
            return null;
        }

        if (
            property_exists(
                $student,
                'user_id'
            )
            && $student->user_id !== null
        ) {
            return User::query()
                ->withoutGlobalScopes()
                ->find(
                    (int) $student->user_id
                );
        }

        if (
            Schema::hasColumn(
                'users',
                'username'
            )
        ) {
            return User::query()
                ->withoutGlobalScopes()
                ->where(
                    'username',
                    $identity
                )
                ->first();
        }

        return null;
    }

    private function isActive(
        ?User $user
    ): bool {
        if ($user === null) {
            return false;
        }

        if (
            ! Schema::hasColumn(
                'users',
                'is_active'
            )
        ) {
            return true;
        }

        return (bool)
            $user->getAttribute(
                'is_active'
            );
    }
}
