<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentAccessService
{
    public function authorizeManager(?User $user): void
    {
        abort_unless(
            $user !== null
            && in_array(
                (string) $user->role,
                ['admin', 'toolman'],
                true
            ),
            403,
            'Hanya Administrator dan Toolman yang dapat mengelola data siswa.'
        );

        if ((string) $user->role === 'toolman') {
            abort_unless(
                $this->assignedWorkshopId($user) !== null,
                403,
                'Akun Toolman belum ditetapkan pada satu jurusan.'
            );
        }
    }

    public function assignedWorkshopId(?User $user): ?int
    {
        if (
            $user === null
            || ! Schema::hasColumn('users', 'workshop_id')
        ) {
            return null;
        }

        $value = $user->getAttribute('workshop_id');

        return $value === null || $value === ''
            ? null
            : (int) $value;
    }

    public function isAdmin(?User $user): bool
    {
        return (string) $user?->role === 'admin';
    }

    public function applyVisibility(
        Builder $query,
        ?User $user
    ): Builder {
        $this->authorizeManager($user);

        if ($this->isAdmin($user)) {
            return $query;
        }

        return $query->where(
            'students.workshop_id',
            $this->assignedWorkshopId($user)
        );
    }

    public function findVisibleOrFail(
        int $studentId,
        ?User $user
    ): Student {
        $query = Student::query();
        $this->applyVisibility($query, $user);

        return $query->findOrFail($studentId);
    }

    public function visibleWorkshops(?User $user): Collection
    {
        $this->authorizeManager($user);

        $query = DB::table('workshops')
            ->orderBy('code');

        if (Schema::hasColumn('workshops', 'is_active')) {
            $query->where('is_active', true);
        }

        if (! $this->isAdmin($user)) {
            $query->where(
                'id',
                $this->assignedWorkshopId($user)
            );
        }

        return $query->get([
            'id',
            'code',
            'name',
        ]);
    }

    public function effectiveWorkshopId(
        ?User $user,
        int|string|null $requestedWorkshopId
    ): int {
        $this->authorizeManager($user);

        if (! $this->isAdmin($user)) {
            return (int) $this->assignedWorkshopId($user);
        }

        abort_if(
            $requestedWorkshopId === null
            || $requestedWorkshopId === '',
            422,
            'Jurusan wajib dipilih.'
        );

        return (int) $requestedWorkshopId;
    }

    public function authorizeWorkshop(
        ?User $user,
        int $workshopId
    ): void {
        $this->authorizeManager($user);

        if ($this->isAdmin($user)) {
            return;
        }

        abort_unless(
            $this->assignedWorkshopId($user) === $workshopId,
            403,
            'Toolman hanya dapat mengelola siswa pada jurusannya.'
        );
    }
}
