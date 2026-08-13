<?php

namespace App\Services;

use App\Models\ItemAsset;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LoanJurusanRoutingService
{
    private const SINGLE_WORKSHOP_ROLES = [
        'kepala_bengkel',
        'toolman',
        'siswa',
    ];

    /**
     * Menentukan jurusan pengajuan sebelum controller menyimpan Loan.
     *
     * Master Item adalah katalog global, sehingga item_id tidak boleh
     * digunakan untuk menentukan jurusan. Jurusan berasal dari:
     *
     * 1. workshop_id pada form;
     * 2. users.workshop_id untuk siswa/toolman/kepala bengkel;
     * 3. item_assets.workshop_id bila form lama masih mengirim asset_id.
     */
    public function prepareRequest(
        Request $request
    ): void {
        if (
            $request->route()?->getName()
                !== 'loans.store'
        ) {
            return;
        }

        $user = $request->user();

        if ($user === null) {
            return;
        }

        $assetWorkshopIds =
            $this->extractAssetWorkshopIds(
                $request
            );

        if (
            count(
                $assetWorkshopIds
            ) > 1
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Satu pengajuan hanya boleh berisi unit alat dari satu jurusan.',
                ]);
        }

        $assetWorkshopId =
            $assetWorkshopIds[0]
            ?? null;

        $explicitWorkshopId =
            $request->filled(
                'workshop_id'
            )
                ? $request->integer(
                    'workshop_id'
                )
                : null;

        $role =
            (string) $user->role;

        if (
            in_array(
                $role,
                self::
                    SINGLE_WORKSHOP_ROLES,
                true
            )
        ) {
            $assignedWorkshopId =
                $this
                    ->requiredUserWorkshopId(
                        $user
                    );

            if (
                $explicitWorkshopId
                    !== null
                && $explicitWorkshopId
                    !== $assignedWorkshopId
            ) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            $role === 'siswa'
                                ? 'Siswa hanya dapat mengajukan peminjaman pada jurusannya sendiri.'
                                : 'Jurusan pengajuan tidak sesuai dengan jurusan akun.',
                    ]);
            }

            if (
                $assetWorkshopId !== null
                && $assetWorkshopId
                    !== $assignedWorkshopId
            ) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            'Unit alat yang dipilih bukan milik jurusan akun.',
                    ]);
            }

            $effectiveWorkshopId =
                $assignedWorkshopId;
        } else {
            /*
             * Guru dan Admin dapat memilih jurusan.
             * Form baru tidak mengirim asset_id karena unit dipilih
             * otomatis oleh backend setelah jumlah dimasukkan.
             */
            $effectiveWorkshopId =
                $explicitWorkshopId
                ?? $assetWorkshopId;

            if (
                $effectiveWorkshopId
                    === null
            ) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            'Pilih jurusan tujuan pengajuan peminjaman.',
                    ]);
            }

            if (
                $explicitWorkshopId
                    !== null
                && $assetWorkshopId
                    !== null
                && $explicitWorkshopId
                    !== $assetWorkshopId
            ) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            'Jurusan pengajuan tidak sesuai dengan unit alat yang dipilih.',
                    ]);
            }
        }

        if (
            $this->activeToolmanId(
                $effectiveWorkshopId
            ) === null
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Jurusan yang dipilih belum mempunyai Toolman aktif. Hubungi Administrator.',
                ]);
        }

        /*
         * Jangan menentukan assigned_toolman_id saat pengajuan dibuat.
         * Toolman yang menyetujui akan dicatat ketika approval.
         */
        $request->merge([
            'workshop_id' =>
                $effectiveWorkshopId,
        ]);
    }

    /**
     * Validasi tambahan ketika LoanItem mempunyai item_asset_id.
     */
    public function routeLoanFromItem(
        Loan $loan,
        int $itemWorkshopId
    ): void {
        $borrower =
            User::query()
                ->withoutGlobalScopes()
                ->find(
                    $loan->borrower_id
                );

        if ($borrower !== null) {
            $role =
                (string)
                $borrower->role;

            if (
                in_array(
                    $role,
                    self::
                        SINGLE_WORKSHOP_ROLES,
                    true
                )
                && $this
                    ->requiredUserWorkshopId(
                        $borrower
                    )
                    !== $itemWorkshopId
            ) {
                throw ValidationException::
                    withMessages([
                        'items' =>
                            'Peminjam hanya dapat menggunakan unit alat dari jurusannya.',
                    ]);
            }
        }

        $currentWorkshopId =
            $loan->getAttribute(
                'workshop_id'
            );

        if (
            $currentWorkshopId !== null
            && (int)
                $currentWorkshopId
                !== $itemWorkshopId
        ) {
            throw ValidationException::
                withMessages([
                    'items' =>
                        'Satu pengajuan hanya boleh berisi alat dari satu jurusan.',
                ]);
        }

        if (
            $this->activeToolmanId(
                $itemWorkshopId
            ) === null
        ) {
            throw ValidationException::
                withMessages([
                    'items' =>
                        'Jurusan alat belum mempunyai Toolman aktif.',
                ]);
        }

        if (
            Schema::hasColumn(
                'loans',
                'workshop_id'
            )
            && $currentWorkshopId
                === null
        ) {
            $loan
                ->fill([
                    'workshop_id' =>
                        $itemWorkshopId,
                ])
                ->saveQuietly();
        }
    }

    public function activeToolmanId(
        int $workshopId
    ): ?int {
        $query =
            User::query()
                ->withoutGlobalScopes()
                ->where(
                    'role',
                    'toolman'
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

        $id =
            $query
                ->orderBy('id')
                ->value('id');

        return $id === null
            ? null
            : (int) $id;
    }

    public function requiredUserWorkshopId(
        User $user
    ): int {
        $workshopId =
            $user->getAttribute(
                'workshop_id'
            );

        if (
            $workshopId === null
            || $workshopId === ''
        ) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Akun belum ditetapkan pada satu jurusan.',
                ]);
        }

        return (int) $workshopId;
    }

    /**
     * Hanya unit fisik yang boleh digunakan untuk menyimpulkan jurusan.
     * item_id sengaja diabaikan karena Master Barang bersifat global.
     */
    private function extractAssetWorkshopIds(
        Request $request
    ): array {
        $assetIds = [];

        $this->collectAssetIds(
            $request->all(),
            null,
            $assetIds
        );

        if (
            $assetIds === []
            || ! class_exists(
                ItemAsset::class
            )
        ) {
            return [];
        }

        return ItemAsset::query()
            ->withoutGlobalScopes()
            ->whereIn(
                'id',
                array_values(
                    array_unique(
                        $assetIds
                    )
                )
            )
            ->pluck(
                'workshop_id'
            )
            ->filter(
                static fn (
                    mixed $id
                ): bool =>
                    $id !== null
                    && $id !== ''
            )
            ->map(
                static fn (
                    mixed $id
                ): int =>
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();
    }

    private function collectAssetIds(
        mixed $value,
        ?string $key,
        array &$assetIds
    ): void {
        if (is_array($value)) {
            $normalizedKey =
                strtolower(
                    (string) $key
                );

            if (
                in_array(
                    $normalizedKey,
                    [
                        'item_asset_ids',
                        'asset_ids',
                        'selected_asset_ids',
                    ],
                    true
                )
            ) {
                foreach ($value as $id) {
                    if (is_numeric($id)) {
                        $assetIds[] =
                            (int) $id;
                    }
                }

                return;
            }

            foreach (
                $value
                as $childKey =>
                    $childValue
            ) {
                $this->collectAssetIds(
                    $childValue,
                    (string)
                    $childKey,
                    $assetIds
                );
            }

            return;
        }

        if (
            ! is_numeric($value)
            || $key === null
        ) {
            return;
        }

        if (
            in_array(
                strtolower($key),
                [
                    'item_asset_id',
                    'asset_id',
                    'selected_asset_id',
                ],
                true
            )
        ) {
            $assetIds[] =
                (int) $value;
        }
    }
}
