<?php

namespace App\Http\Middleware;

use App\Support\SimbaRoleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSimbaRoleAccess
{
    public function __construct(
        private readonly SimbaRoleAccess $access
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (
            property_exists($user, 'is_active')
            && ! $user->is_active
        ) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
        }

        abort_unless(
            $this->access->canRoute(
                $user,
                $request->route()?->getName(),
                $request->method(),
                $request->path()
            ),
            403,
            $this->access->denialMessage(
                $user
            )
        );

        return $next($request);
    }
}
