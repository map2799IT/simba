<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Loan;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WorkshopLoanInventoryService
{
    private const GLOBAL_WORKSHOP_ROLES = [
        'admin',
        'guru',
    ];

    private const ACTIVE_ALLOCATION_STATUSES = [
        Loan::STATUS_PENDING,
        Loan::STATUS_APPROVED,
        Loan::STATUS_BORROWED,
        Loan::STATUS_PARTIAL,
    ];

    public function selectedWorkshop(
        Request $request
    ): Workshop {
        $user = $request->user();

        if ($user === null) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Silakan login terlebih dahulu.',
                ]);
        }

        $role =
            (string) $user->role;

        if (
            ! in_array(
                $role,
                self::GLOBAL_WORKSHOP_ROLES,
                true
            )
        ) {
            if ($user->workshop_id === null) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            $role === 'siswa'
                                ? 'Akun siswa belum terhubung dengan jurusan. Hubungi Administrator.'
                                : 'Akun belum mempunyai jurusan.',
                    ]);
            }

            return Workshop::query()
                ->withoutGlobalScopes()
                ->where('is_active', true)
                ->findOrFail(
                    $user->workshop_id
                );
        }

        $requestedId =
            $request->integer(
                'workshop_id'
            );

        if ($requestedId > 0) {
            return Workshop::query()
                ->withoutGlobalScopes()
                ->where('is_active', true)
                ->findOrFail(
                    $requestedId
                );
        }

        foreach (
            $this->activeWorkshops()
            as $workshop
        ) {
            if (
                $this->hasAvailableInventory(
                    (int) $workshop->id
                )
            ) {
                return $workshop;
            }
        }

        $workshop =
            $this->activeWorkshops()
                ->first();

        if ($workshop === null) {
            throw ValidationException::
                withMessages([
                    'workshop_id' =>
                        'Belum ada jurusan aktif.',
                ]);
        }

        return $workshop;
    }

    public function visibleWorkshops(
        Request $request
    ): Collection {
        $user =
            $request->user();

        $role =
            (string) $user?->role;

        return Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when(
                $user !== null
                && ! in_array(
                    $role,
                    self::GLOBAL_WORKSHOP_ROLES,
                    true
                ),
                fn (Builder $query): Builder =>
                    $query->whereKey(
                        $user->workshop_id
                    )
            )
            ->orderBy('code')
            ->get();
    }

    public function borrowers(
        Request $request,
        int $workshopId
    ): Collection {
        $user = $request->user();

        if ($user === null) {
            return new Collection();
        }

        $actorRole = (string) $user->role;

        // Guru/siswa: hanya diri sendiri
        if (in_array($actorRole, ['guru', 'siswa'], true)) {
            return User::query()
                ->withoutGlobalScopes()
                ->whereKey($user->id)
                ->get();
        }

        if (! in_array($actorRole, ['admin', 'toolman'], true)) {
            return new Collection();
        }

        $borrowableRoles = ['siswa', 'guru', 'toolman', 'admin', 'kepala_bengkel'];

        return User::query()
            ->withoutGlobalScopes()
            // Admin: semua user aktif dari semua jurusan
            // Toolman: semua user aktif (lintas jurusan diperbolehkan)
            ->when(
                Schema::hasColumn('users', 'is_active'),
                fn (Builder $query): Builder => $query->where('is_active', true)
            )
            ->whereIn('role', $borrowableRoles)
            ->orderByRaw("FIELD(role,'siswa','guru','toolman','kepala_bengkel','admin')")
            ->orderBy('name')
            ->get();
    }

    public function items(
        int $workshopId
    ): Collection {
        $toolStock =
            $this->allocatableAssetsQuery(
                $workshopId
            )
                ->selectRaw(
                    'item_id, COUNT(*) AS available_stock'
                )
                ->groupBy('item_id')
                ->pluck(
                    'available_stock',
                    'item_id'
                )
                ->map(
                    static fn (
                        mixed $quantity
                    ): float =>
                        (float) $quantity
                );

        $materialStock =
            $this->materialStockMap(
                $workshopId
            );

        $ids =
            $toolStock
                ->filter(
                    static fn (
                        float $quantity
                    ): bool =>
                        $quantity > 0
                )
                ->keys()
                ->merge(
                    $materialStock
                        ->filter(
                            static fn (
                                float $quantity
                            ): bool =>
                                $quantity > 0.000001
                        )
                        ->keys()
                )
                ->map(
                    static fn (
                        mixed $id
                    ): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        if ($ids->isEmpty()) {
            return new Collection();
        }

        return Item::query()
            ->withoutGlobalScopes()
            ->with([
                'unit',
                'category',
            ])
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->orderByRaw(
                "CASE WHEN type = 'tool' THEN 0 ELSE 1 END"
            )
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->map(
                function (
                    Item $item
                ) use (
                    $toolStock,
                    $materialStock
                ): Item {
                    $available =
                        $item->isTool()
                            ? (
                                $toolStock[
                                    $item->id
                                ]
                                ?? 0
                            )
                            : (
                                $materialStock[
                                    $item->id
                                ]
                                ?? 0
                            );

                    $item->setAttribute(
                        'workshop_available_stock',
                        round(
                            max(
                                0,
                                (float) $available
                            ),
                            3
                        )
                    );

                    return $item;
                }
            )
            ->filter(
                static fn (
                    Item $item
                ): bool =>
                    (float)
                    $item
                        ->workshop_available_stock
                    > 0.000001
            )
            ->values();
    }

    public function assets(
        int $workshopId
    ): Collection {
        return $this
            ->allocatableAssetsQuery(
                $workshopId
            )
            ->with([
                'item',
                'storageLocation',
            ])
            ->orderBy('received_date')
            ->orderBy('item_id')
            ->orderBy('asset_number')
            ->orderBy('id')
            ->get();
    }

    public function selectToolAssetsBySequence(
        int $itemId,
        int $workshopId,
        int $quantity,
        bool $lock = true
    ): Collection {
        $query =
            $this
                ->allocatableAssetsQuery(
                    $workshopId,
                    $itemId
                )
                ->orderBy(
                    'received_date'
                )
                ->orderBy(
                    'asset_number'
                )
                ->orderBy('id')
                ->limit($quantity);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    public function inventorySummary(
        int $workshopId
    ): array {
        $availableAssets =
            $this
                ->allocatableAssetsQuery(
                    $workshopId
                )
                ->count();

        $materialStock =
            $this->materialStockMap(
                $workshopId
            );

        return [
            'available_asset_units' =>
                $availableAssets,

            'available_material_items' =>
                $materialStock
                    ->filter(
                        static fn (
                            float $quantity
                        ): bool =>
                            $quantity > 0.000001
                    )
                    ->count(),

            'available_item_options' =>
                $this->items(
                    $workshopId
                )->count(),

            'movement_rows' =>
                ItemStockMovement::query()
                    ->withoutGlobalScopes()
                    ->where(
                        'workshop_id',
                        $workshopId
                    )
                    ->count(),
        ];
    }

    public function materialAvailable(
        int $itemId,
        int $workshopId,
        bool $lock = false
    ): float {
        $query =
            ItemStockMovement::query()
                ->withoutGlobalScopes()
                ->where(
                    'item_id',
                    $itemId
                )
                ->where(
                    'workshop_id',
                    $workshopId
                )
                ->orderBy(
                    'transaction_date'
                )
                ->orderBy('id');

        $rows =
            $lock
                ? $query
                    ->lockForUpdate()
                    ->get()
                : $query->get();

        return round(
            max(
                0,
                $rows->sum(
                    fn (
                        ItemStockMovement
                        $movement
                    ): float =>
                        $this->signed(
                            $movement
                        )
                )
            ),
            3
        );
    }

    public function toolmenForWorkshop(
        int $workshopId
    ): Collection {
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

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function hasToolmanForWorkshop(
        int $workshopId
    ): bool {
        return $this
            ->toolmenForWorkshop(
                $workshopId
            )
            ->isNotEmpty();
    }

    public function activeToolmanId(
        int $workshopId
    ): ?int {
        $id =
            $this
                ->toolmenForWorkshop(
                    $workshopId
                )
                ->first()
                ?->id;

        return $id === null
            ? null
            : (int) $id;
    }

    public function hasAvailableInventory(
        int $workshopId
    ): bool {
        if (
            $this
                ->allocatableAssetsQuery(
                    $workshopId
                )
                ->exists()
        ) {
            return true;
        }

        return $this
            ->materialStockMap(
                $workshopId
            )
            ->contains(
                static fn (
                    float $quantity
                ): bool =>
                    $quantity > 0.000001
            );
    }

    private function allocatableAssetsQuery(
        int $workshopId,
        ?int $itemId = null
    ): Builder {
        return ItemAsset::query()
            ->withoutGlobalScopes()
            ->where(
                'workshop_id',
                $workshopId
            )
            ->when(
                $itemId !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'item_id',
                        $itemId
                    )
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'status',
                ItemAsset::
                    STATUS_AVAILABLE
            )
            ->where(
                'condition',
                ItemAsset::
                    CONDITION_GOOD
            )
            ->whereDoesntHave(
                'loanItems',
                function (
                    Builder $loanItemQuery
                ): void {
                    $loanItemQuery
                        ->whereNull(
                            'returned_at'
                        )
                        ->whereHas(
                            'loan',
                            fn (
                                Builder $loanQuery
                            ): Builder =>
                                $loanQuery
                                    ->withoutGlobalScopes()
                                    ->whereIn(
                                        'status',
                                        self::
                                            ACTIVE_ALLOCATION_STATUSES
                                    )
                        );
                }
            );
    }

    private function activeWorkshops(): Collection
    {
        return Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function materialStockMap(
        int $workshopId
    ): SupportCollection {
        $materialIds =
            Item::query()
                ->withoutGlobalScopes()
                ->where(
                    'type',
                    'material'
                )
                ->pluck('id');

        if ($materialIds->isEmpty()) {
            return collect();
        }

        return ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where(
                'workshop_id',
                $workshopId
            )
            ->whereIn(
                'item_id',
                $materialIds
            )
            ->get()
            ->groupBy('item_id')
            ->map(
                function (
                    $movements
                ): float {
                    return round(
                        max(
                            0,
                            $movements->sum(
                                fn (
                                    ItemStockMovement
                                    $movement
                                ): float =>
                                    $this->signed(
                                        $movement
                                    )
                            )
                        ),
                        3
                    );
                }
            );
    }

    private function signed(
        ItemStockMovement $movement
    ): float {
        $quantity =
            abs(
                (float)
                $movement->quantity
            );

        return match (
            (string)
            $movement->type
        ) {
            'initial',
            'incoming',
            'adjustment_in',
            'return' =>
                $quantity,

            'outgoing',
            'adjustment_out',
            'loan' =>
                -$quantity,

            default =>
                (float)
                $movement->stock_after
                - (float)
                $movement->stock_before,
        };
    }
}
