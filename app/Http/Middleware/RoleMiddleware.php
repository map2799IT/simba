<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return redirect()
                ->route('login');
        }

        if (! $user->is_active) {
            auth()->logout();

            $request
                ->session()
                ->invalidate();

            $request
                ->session()
                ->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'login' =>
                        'Akun Anda sedang tidak aktif.',
                ]);
        }

        abort_unless(
            $user->hasRole(...$roles),
            403,
            'Anda tidak memiliki hak akses ke halaman ini.'
        );

        return $next($request);
    }
}