<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserJurusanAssignment
{
    private const REQUIRED_ROLES = [
        'kepala_bengkel',
        'toolman',
        'siswa',
    ];

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (
            ! in_array(
                $request->route()?->getName(),
                [
                    'admin.users.store',
                    'admin.users.update',
                ],
                true
            )
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return $next($request);
        }

        $role = (string)
            $request->input('role');

        if (
            in_array(
                $role,
                self::REQUIRED_ROLES,
                true
            )
        ) {
            $request->validate([
                'workshop_id' => [
                    'required',
                    'integer',
                    'exists:workshops,id',
                ],
            ]);
        } else {
            /*
             * Guru dan admin tidak dikunci ke satu jurusan.
             */
            $request->merge([
                'workshop_id' => null,
            ]);
        }

        if (
            $role === 'kepala_bengkel'
            && $request->filled(
                'workshop_id'
            )
        ) {
            $routeUser =
                $request->route('user');

            $currentId =
                $routeUser instanceof User
                    ? $routeUser->id
                    : (
                        is_numeric(
                            $routeUser
                        )
                            ? (int) $routeUser
                            : null
                    );

            $existingHead =
                User::query()
                    ->withoutGlobalScopes()
                    ->where(
                        'role',
                        'kepala_bengkel'
                    )
                    ->where(
                        'workshop_id',
                        $request->integer(
                            'workshop_id'
                        )
                    )
                    ->when(
                        $currentId !== null,
                        fn ($query) =>
                            $query->whereKeyNot(
                                $currentId
                            )
                    )
                    ->first();

            if ($existingHead !== null) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            'Jurusan ini sudah memiliki kepala bengkel: '.
                            $existingHead->name.
                            '.',
                    ]);
            }
        }

        return $next($request);
    }
}
