<?php

namespace App\Services;

use App\Models\Workshop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WorkshopDirectoryService
{
    /**
     * Seluruh jurusan aktif selalu dibaca dari database.
     * Tidak ada daftar TKR/TSM/TKJ yang ditulis manual di service ini.
     */
    public function active(): Collection
    {
        return Workshop::query()
            ->withoutGlobalScopes()
            ->active()
            ->ordered()
            ->get([
                'id',
                'code',
                'name',
                'description',
            ]);
    }

    public function forUser(
        User $user
    ): Collection {
        if (
            in_array(
                (string) $user->role,
                [
                    'admin',
                    'guru',
                ],
                true
            )
        ) {
            return $this->active();
        }

        if (
            ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
            || ! $user->workshop_id
        ) {
            return collect();
        }

        return Workshop::query()
            ->withoutGlobalScopes()
            ->whereKey(
                (int) $user->workshop_id
            )
            ->where(
                'is_active',
                true
            )
            ->get([
                'id',
                'code',
                'name',
                'description',
            ]);
    }

    public function findActiveByCode(
        string $code
    ): ?Workshop {
        return Workshop::query()
            ->withoutGlobalScopes()
            ->where(
                'code',
                $this->normalizeCode(
                    $code
                )
            )
            ->where(
                'is_active',
                true
            )
            ->first();
    }

    public function normalizeCode(
        string $code
    ): string {
        return strtoupper(
            trim($code)
        );
    }
}
