<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OfficialDocumentService
{
    /**
     * Menyiapkan identitas dokumen resmi dan pejabat penandatangan.
     */
    public function make(
        ?int $workshopId,
        ?User $printedBy,
        string $documentType,
        ?string $subjectCode = null,
        ?Carbon $generatedAt = null
    ): array {
        $generatedAt ??= now();

        $workshop = null;

        if (
            $workshopId !== null
            && Schema::hasTable('workshops')
        ) {
            $workshop = Workshop::query()
                ->withoutGlobalScopes()
                ->find($workshopId);
        }

        $head = $this->findSigner(
            $workshopId,
            'kepala_bengkel'
        );

        $toolman = $this->findSigner(
            $workshopId,
            'toolman'
        );

        /*
         * Pengguna aktif yang sedang mencetak dapat menjadi fallback
         * untuk role yang sama bila data signer belum tersimpan lengkap.
         */
        if (
            $printedBy !== null
            && $workshopId !== null
            && (int) $printedBy->workshop_id
                === $workshopId
        ) {
            /*
             * Bila dokumen dicetak langsung oleh Kepala Bengkel atau
             * Toolman, prioritaskan pengguna tersebut sebagai signer.
             */
            if (
                (string) $printedBy->role
                    === 'kepala_bengkel'
            ) {
                $head = $printedBy;
            }

            if (
                (string) $printedBy->role
                    === 'toolman'
            ) {
                $toolman = $printedBy;
            }
        }

        $workshopCode =
            $workshop?->code
            ?: 'GLOBAL';

        $subject =
            $subjectCode
                ? Str::upper(
                    Str::slug(
                        $subjectCode,
                        '-'
                    )
                )
                : 'DOKUMEN';

        $type =
            Str::upper(
                Str::slug(
                    $documentType,
                    '-'
                )
            );

        return [
            'number' =>
                implode(
                    '/',
                    [
                        'SIMBA',
                        $type,
                        $workshopCode,
                        $generatedAt->format(
                            'Ymd'
                        ),
                        $subject,
                    ]
                ),

            'generatedAt' =>
                $generatedAt,

            'workshop' =>
                $workshop,

            'printedBy' =>
                $this->personData(
                    $printedBy,
                    'Petugas Cetak'
                ),

            'toolman' =>
                $this->personData(
                    $toolman,
                    'Toolman'
                ),

            'head' =>
                $this->personData(
                    $head,
                    'Kepala Bengkel'
                ),

            'isWorkshopDocument' =>
                $workshopId !== null,

            'workshopLabel' =>
                $workshop
                    ? $workshop->code.
                        ' — '.
                        $workshop->name
                    : 'Seluruh Jurusan',

            'schoolName' =>
                (string) config(
                    'app.school_name',
                    'Sekolah Menengah Kejuruan'
                ),

            'systemName' =>
                'SIMBA',

            'systemSubtitle' =>
                'Sistem Inventaris dan Peminjaman Bengkel',
        ];
    }

    private function findSigner(
        ?int $workshopId,
        string $role
    ): ?User {
        if (
            $workshopId === null
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return null;
        }

        $query = User::query()
            ->withoutGlobalScopes()
            ->where(
                'role',
                $role
            )
            ->where(
                'workshop_id',
                $workshopId
            );

        if (
            Schema::hasColumn(
                'users',
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        return $query
            ->orderBy('id')
            ->first();
    }

    private function personData(
        ?User $user,
        string $position
    ): array {
        if ($user === null) {
            return [
                'name' =>
                    '________________________',

                'position' =>
                    $position,

                'identifier' =>
                    null,

                'available' =>
                    false,
            ];
        }

        $identifier = null;

        foreach (
            [
                'nip',
                'employee_number',
                'username',
                'email',
            ]
            as $column
        ) {
            if (
                Schema::hasColumn(
                    'users',
                    $column
                )
                && filled(
                    $user->getAttribute(
                        $column
                    )
                )
            ) {
                $identifier =
                    (string)
                    $user->getAttribute(
                        $column
                    );

                break;
            }
        }

        return [
            'name' =>
                (string) $user->name,

            'position' =>
                $position,

            'identifier' =>
                $identifier,

            'available' =>
                true,
        ];
    }
}
