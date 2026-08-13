<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopInventoryAvailabilityService
{
    public function selectedWorkshop(
        Request $request
    ): Workshop {
        $user = $request->user();

        if (
            $user !== null
            && (string) $user->role
                !== 'admin'
        ) {
            if ($user->workshop_id === null) {
                throw ValidationException::
                    withMessages([
                        'workshop_id' =>
                            'Akun belum mempunyai jurusan.',
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

        $query = Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('code');

        if ($requestedId > 0) {
            return $query
                ->whereKey($requestedId)
                ->firstOrFail();
        }

        $workshop = $query->first();

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
        $user = $request->user();

        return Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when(
                $user !== null
                && (string) $user->role
                    !== 'admin',
                fn ($query) =>
                    $query->whereKey(
                        $user->workshop_id
                    )
            )
            ->orderBy('code')
            ->get();
    }

    public function itemsForWorkshop(
        int $workshopId
    ): Collection {
        $toolStock =
            DB::table('item_assets')
                ->where(
                    'workshop_id',
                    $workshopId
                )
                ->where('is_active', true)
                ->where(
                    'status',
                    ItemAsset::
                        STATUS_AVAILABLE
                )
                ->groupBy('item_id')
                ->pluck(
                    DB::raw(
                        'COUNT(id)'
                    ),
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

        $eligibleIds =
            $toolStock
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

        if ($eligibleIds->isEmpty()) {
            return new Collection();
        }

        return Item::query()
            ->withoutGlobalScopes()
            ->with([
                'unit',
                'category',
            ])
            ->where('is_active', true)
            ->whereIn(
                'id',
                $eligibleIds
            )
            ->orderBy('type')
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

                    /*
                     * View lama membaca atribut stock.
                     * Nilainya di sini khusus stok jurusan,
                     * bukan stock global pada master.
                     */
                    $item->setAttribute(
                        'stock',
                        round(
                            max(
                                0,
                                (float) $available
                            ),
                            3
                        )
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
            ->values();
    }

    public function assetsForWorkshop(
        int $workshopId
    ): Collection {
        return ItemAsset::query()
            ->withoutGlobalScopes()
            ->with([
                'item',
                'workshop',
                'storageLocation',
            ])
            ->where(
                'workshop_id',
                $workshopId
            )
            ->where('is_active', true)
            ->where(
                'status',
                ItemAsset::
                    STATUS_AVAILABLE
            )
            ->orderBy('asset_number')
            ->get();
    }

    public function availableQuantity(
        Item $item,
        int $workshopId,
        bool $lock = false
    ): float {
        if ($item->isTool()) {
            $query = ItemAsset::query()
                ->withoutGlobalScopes()
                ->where(
                    'item_id',
                    $item->id
                )
                ->where(
                    'workshop_id',
                    $workshopId
                )
                ->where('is_active', true)
                ->where(
                    'status',
                    ItemAsset::
                        STATUS_AVAILABLE
                );

            if ($lock) {
                return (float)
                    $query
                        ->lockForUpdate()
                        ->get(['id'])
                        ->count();
            }

            return (float)
                $query->count();
        }

        $query =
            ItemStockMovement::query()
                ->withoutGlobalScopes()
                ->where(
                    'item_id',
                    $item->id
                )
                ->where(
                    'workshop_id',
                    $workshopId
                )
                ->orderBy(
                    'transaction_date'
                )
                ->orderBy('id');

        $movements =
            $lock
                ? $query
                    ->lockForUpdate()
                    ->get()
                : $query->get();

        return round(
            max(
                0,
                $movements->sum(
                    fn (
                        ItemStockMovement
                        $movement
                    ): float =>
                        $this
                            ->signedMovement(
                                $movement
                            )
                )
            ),
            3
        );
    }

    private function materialStockMap(
        int $workshopId
    ) {
        return ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where(
                'workshop_id',
                $workshopId
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
                                    $this
                                        ->signedMovement(
                                            $movement
                                        )
                            )
                        ),
                        3
                    );
                }
            );
    }

    private function signedMovement(
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
