<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class InventoryAccessService
{
    /**
     * Role yang hanya boleh melihat inventaris kelas/bengkelnya.
     *
     * Administrator tetap dapat melihat seluruh bengkel.
     */
    private const GLOBAL_ROLES = [
        'admin',
        'wakil_sarpras',
    ];

    public function isRestricted(
        ?User $user
    ): bool {
        if ($user === null) {
            return true;
        }

        return ! in_array(
            (string) $user->role,
            self::GLOBAL_ROLES,
            true
        );
    }

    /**
     * Bengkel/kelas yang ditetapkan pada akun.
     *
     * Paket ini menggunakan users.workshop_id sebagai relasi kelas.
     */
    public function assignedWorkshopId(
        ?User $user
    ): ?int {
        if ($user === null) {
            return null;
        }

        if (
            ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return null;
        }

        $value = $user->getAttribute(
            'workshop_id'
        );

        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Workshop efektif untuk filter laporan.
     *
     * Admin boleh memilih workshop.
     * Role terbatas selalu dipaksa ke workshop akunnya.
     */
    public function effectiveWorkshopId(
        ?User $user,
        int|string|null $requestedWorkshopId
    ): ?int {
        if ($this->isRestricted($user)) {
            return $this->assignedWorkshopId(
                $user
            );
        }

        if (
            $requestedWorkshopId === null
            || $requestedWorkshopId === ''
        ) {
            return null;
        }

        return (int) $requestedWorkshopId;
    }

    /**
     * Menerapkan batas akses ke query Item.
     *
     * Fail closed:
     * role terbatas tanpa workshop_id tidak melihat data bengkel lain.
     */
    public function applyItemVisibility(
        Builder $query,
        ?User $user
    ): Builder {
        if (! $this->isRestricted($user)) {
            return $query;
        }

        $workshopId =
            $this->assignedWorkshopId(
                $user
            );

        if ($workshopId === null) {
            return $query->whereRaw(
                '1 = 0'
            );
        }

        return $query->where(
            'items.workshop_id',
            $workshopId
        );
    }

    /**
     * Daftar workshop yang boleh muncul pada filter.
     */
    public function visibleWorkshops(
        ?User $user
    ): Collection {
        $query = Workshop::query()
            ->where('is_active', true)
            ->orderBy('code');

        if (! $this->isRestricted($user)) {
            return $query->get([
                'id',
                'code',
                'name',
            ]);
        }

        $workshopId =
            $this->assignedWorkshopId(
                $user
            );

        if ($workshopId === null) {
            return collect();
        }

        return $query
            ->where('id', $workshopId)
            ->get([
                'id',
                'code',
                'name',
            ]);
    }

    /**
     * Memastikan pengguna boleh membuka laporan lokasi.
     */
    public function authorizeWorkshop(
        ?User $user,
        int $workshopId
    ): void {
        if (! $this->isRestricted($user)) {
            return;
        }

        abort_unless(
            $this->assignedWorkshopId($user)
                === $workshopId,
            403,
            'Anda hanya dapat melihat inventaris kelas/bengkel yang ditetapkan pada akun.'
        );
    }

    public function assignmentWarning(
        ?User $user
    ): ?string {
        if (
            ! $this->isRestricted($user)
            || $this->assignedWorkshopId(
                $user
            ) !== null
        ) {
            return null;
        }

        return
            'Akun ini belum memiliki kelas/bengkel. '.
            'Administrator perlu mengisi users.workshop_id terlebih dahulu.';
    }
}
