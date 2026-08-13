<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopLoanTransactionService
{
    public function __construct(
        private readonly WorkshopLoanInventoryService $inventory
    ) {
    }

    public function approve(Loan $loan, User $actor): Loan
    {
        return DB::transaction(function () use ($loan, $actor): Loan {
            $locked = $this->lockedLoan($loan);

            if (
                (string) $actor->role
                    === 'toolman'
                && (
                    $actor->workshop_id
                        === null
                    || (int)
                        $actor->workshop_id
                        !== (int)
                            $locked
                                ->workshop_id
                )
            ) {
                throw ValidationException::
                    withMessages([
                        'loan' =>
                            'Toolman hanya dapat menyetujui peminjaman pada jurusannya.',
                    ]);
            }

            if ($locked->status !== Loan::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'loan' => 'Hanya pengajuan menunggu yang dapat disetujui.',
                ]);
            }

            $locked->load('items');

            foreach ($locked->items as $loanItem) {
                if ($loanItem->is_consumable) {
                    continue;
                }

                $asset = ItemAsset::query()->withoutGlobalScopes()
                    ->lockForUpdate()->findOrFail($loanItem->item_asset_id);

                if (! $asset->is_active || $asset->status !== ItemAsset::STATUS_AVAILABLE) {
                    throw ValidationException::withMessages([
                        'items' => "Unit {$asset->asset_number} tidak lagi tersedia.",
                    ]);
                }

                $asset->fill(['status' => ItemAsset::STATUS_RESERVED])->save();
            }

            $locked->fill([
                'status' => Loan::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'assigned_toolman_id' =>
                    (string) $actor->role
                        === 'toolman'
                            ? $actor->id
                            : $locked
                                ->assigned_toolman_id,
                'approved_at' => now(),
            ])->save();

            return $locked->fresh();
        }, attempts: 3);
    }

    public function reject(Loan $loan, User $actor, string $reason): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $reason): Loan {
            $locked = $this->lockedLoan($loan);

            if (! in_array($locked->status, [Loan::STATUS_PENDING, Loan::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages([
                    'loan' => 'Pengajuan ini tidak dapat ditolak.',
                ]);
            }

            $this->releaseReservations($locked);

            $locked->fill([
                'status' => Loan::STATUS_REJECTED,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $locked->fresh();
        }, attempts: 3);
    }

    public function cancel(
        Loan $loan,
        ?User $actor = null
    ): Loan
    {
        return DB::transaction(
            function () use (
                $loan,
                $actor
            ): Loan {
            $locked = $this->lockedLoan($loan);

            if (! in_array($locked->status, [Loan::STATUS_PENDING, Loan::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages([
                    'loan' => 'Peminjaman yang sudah diserahkan tidak dapat dibatalkan.',
                ]);
            }

            $this->releaseReservations($locked);
            $locked->fill(['status' => Loan::STATUS_CANCELLED])->save();

            return $locked->fresh();
        },
            attempts: 3
        );
    }

    public function checkout(Loan $loan, User $actor): Loan
    {
        return DB::transaction(function () use ($loan, $actor): Loan {
            $locked = $this->lockedLoan($loan);

            if ($locked->status !== Loan::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'loan' => 'Peminjaman harus disetujui sebelum serah terima.',
                ]);
            }

            $scheduledAt =
                $locked->scheduled_at
                    ? Carbon::parse(
                        $locked->scheduled_at
                    )
                    : Carbon::parse(
                        $locked
                            ->request_date
                            ->format('Y-m-d').
                        ' 00:00:00'
                    );

            if (now()->lt($scheduledAt)) {
                throw ValidationException::
                    withMessages([
                        'loan' =>
                            'Serah terima belum dapat dilakukan. Jadwal peminjaman: '.
                            $scheduledAt
                                ->format('d-m-Y H:i').
                            '.',
                    ]);
            }

            $locked->load(['items.item', 'items.itemAsset', 'borrower']);
            $hasTool = false;

            foreach ($locked->items->groupBy('item_id') as $itemId => $rows) {
                $item = Item::query()->withoutGlobalScopes()
                    ->with('unit')->lockForUpdate()->findOrFail($itemId);

                $quantity = round((float) $rows->sum(
                    fn (LoanItem $row): float => (float) $row->quantity
                ), 3);

                if ($item->isTool()) {
                    $hasTool = true;

                    foreach ($rows as $row) {
                        $asset = ItemAsset::query()->withoutGlobalScopes()
                            ->lockForUpdate()->findOrFail($row->item_asset_id);

                        if (
                            ! $asset->is_active
                            || ! in_array(
                                $asset->status,
                                [ItemAsset::STATUS_RESERVED, ItemAsset::STATUS_AVAILABLE],
                                true
                            )
                            || (int) $asset->workshop_id !== (int) $locked->workshop_id
                        ) {
                            throw ValidationException::withMessages([
                                'items' => "Unit {$asset->asset_number} tidak dapat diserahkan.",
                            ]);
                        }

                        $asset->fill(['status' => ItemAsset::STATUS_BORROWED])->save();
                    }

                    $movement = $this->decrease(
                        $item,
                        $quantity,
                        $locked,
                        $actor,
                        ItemStockMovement::TYPE_LOAN,
                        'Peminjaman alat'
                    );

                    foreach ($rows as $row) {
                        $row->fill([
                            'issued_at' => now(),
                            'stock_movement_id' => $movement->id,
                        ])->save();
                    }

                    continue;
                }

                $available = $this->inventory->materialAvailable(
                    $item->id,
                    (int) $locked->workshop_id,
                    true
                );

                if ($quantity > $available + 0.000001) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$item->name} pada jurusan hanya {$available}.",
                    ]);
                }

                $movement = $this->decrease(
                    $item,
                    $quantity,
                    $locked,
                    $actor,
                    ItemStockMovement::TYPE_OUTGOING,
                    'Bahan habis pakai melalui peminjaman'
                );

                foreach ($rows as $row) {
                    $row->fill([
                        'issued_at' => now(),
                        'stock_movement_id' => $movement->id,
                        'returned_at' => now(),
                        'returned_by' => $actor->id,
                        'returned_quantity' => 0,
                        'return_status' => 'consumed',
                        'return_notes' => 'Bahan habis pakai langsung mengurangi stok dan tidak dikembalikan.',
                    ])->save();
                }
            }

            $locked->fill([
                'status' => $hasTool ? Loan::STATUS_BORROWED : Loan::STATUS_COMPLETED,
                'borrowed_at' => now(),
                'returned_at' => $hasTool ? null : now(),
                'returned_by' => $hasTool ? null : $actor->id,
            ])->save();

            return $locked->fresh();
        }, attempts: 3);
    }

    public function returnItems(Loan $loan, array $returns, User $actor): Loan
    {
        return DB::transaction(function () use ($loan, $returns, $actor): Loan {
            $locked = $this->lockedLoan($loan);

            if (! in_array($locked->status, [Loan::STATUS_BORROWED, Loan::STATUS_PARTIAL], true)) {
                throw ValidationException::withMessages([
                    'loan' => 'Peminjaman ini tidak sedang dalam proses pengembalian.',
                ]);
            }

            $count = 0;

            foreach ($returns as $loanItemId => $data) {
                if (empty($data['selected'])) {
                    continue;
                }

                $loanItem = LoanItem::query()
                    ->where('loan_id', $locked->id)
                    ->whereKey($loanItemId)
                    ->where('is_consumable', false)
                    ->whereNull('returned_at')
                    ->lockForUpdate()->firstOrFail();

                $asset = ItemAsset::query()->withoutGlobalScopes()
                    ->lockForUpdate()->findOrFail($loanItem->item_asset_id);

                if ($asset->status !== ItemAsset::STATUS_BORROWED) {
                    throw ValidationException::withMessages([
                        'items' => "Status unit {$asset->asset_number} bukan Dipinjam.",
                    ]);
                }

                $item = Item::query()->withoutGlobalScopes()
                    ->lockForUpdate()->findOrFail($loanItem->item_id);

                $condition = $data['condition'] ?? ItemAsset::CONDITION_GOOD;

                $asset->fill([
                    'condition' => $condition,
                    'status' => ItemAsset::STATUS_AVAILABLE,
                    'is_active' => true,
                ])->save();

                $this->increase($item, 1, $locked, $actor, 'Pengembalian alat');

                $loanItem->fill([
                    'returned_at' => now(),
                    'returned_by' => $actor->id,
                    'condition_in' => $condition,
                    'return_condition' => $condition,
                    'returned_quantity' => 1,
                    'return_status' => 'returned',
                    'return_notes' => $data['notes'] ?? null,
                ])->save();

                $count++;
            }

            if ($count === 0) {
                throw ValidationException::withMessages([
                    'items' => 'Pilih minimal satu unit alat yang dikembalikan.',
                ]);
            }

            $remaining = LoanItem::query()
                ->where('loan_id', $locked->id)
                ->where('is_consumable', false)
                ->whereNull('returned_at')->count();

            $locked->fill([
                'status' => $remaining === 0 ? Loan::STATUS_RETURNED : Loan::STATUS_PARTIAL,
                'returned_at' => $remaining === 0 ? now() : null,
                'returned_by' => $remaining === 0 ? $actor->id : null,
            ])->save();

            return $locked->fresh();
        }, attempts: 3);
    }

    private function decrease(
        Item $item,
        float $quantity,
        Loan $loan,
        User $actor,
        string $type,
        string $label
    ): ItemStockMovement {
        $before = round(max(0, (float) $item->stock), 3);

        if ($quantity > $before + 0.000001) {
            throw ValidationException::withMessages([
                'items' => "Stok global {$item->name} tidak mencukupi.",
            ]);
        }

        $after = round(max(0, $before - $quantity), 3);

        $item->fill([
            'stock' => $after,
            'status' => $after > 0 ? 'available' : 'out_of_stock',
        ])->save();

        $movement = new ItemStockMovement();
        $movement->fill([
            'item_id' => $item->id,
            'user_id' => $actor->id,
            'workshop_id' => $loan->workshop_id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'transaction_date' => now()->toDateString(),
            'reference_number' => $loan->code,
            'destination' => $loan->borrower?->name,
            'purpose' => $loan->purpose,
            'description' => "{$label} {$loan->code}",
        ])->save();

        return $movement;
    }

    private function increase(
        Item $item,
        float $quantity,
        Loan $loan,
        User $actor,
        string $label
    ): ItemStockMovement {
        $before = round(max(0, (float) $item->stock), 3);
        $after = round($before + $quantity, 3);

        $item->fill(['stock' => $after, 'status' => 'available'])->save();

        $movement = new ItemStockMovement();
        $movement->fill([
            'item_id' => $item->id,
            'user_id' => $actor->id,
            'workshop_id' => $loan->workshop_id,
            'type' => ItemStockMovement::TYPE_RETURN,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'transaction_date' => now()->toDateString(),
            'reference_number' => $loan->code,
            'source' => $loan->borrower?->name,
            'purpose' => $loan->purpose,
            'description' => "{$label} {$loan->code}",
        ])->save();

        return $movement;
    }

    private function releaseReservations(Loan $loan): void
    {
        $loan->load('items');

        foreach ($loan->items as $row) {
            if ($row->is_consumable || $row->item_asset_id === null) {
                continue;
            }

            $asset = ItemAsset::query()->withoutGlobalScopes()
                ->lockForUpdate()->find($row->item_asset_id);

            if ($asset !== null && $asset->status === ItemAsset::STATUS_RESERVED) {
                $asset->fill(['status' => ItemAsset::STATUS_AVAILABLE])->save();
            }
        }
    }

    public function replaceAsset(
        Loan $loan,
        LoanItem $loanItem,
        int $newAssetId,
        User $actor
    ): LoanItem {
        return DB::transaction(function () use ($loan, $loanItem, $newAssetId, $actor): LoanItem {
            $locked = $this->lockedLoan($loan);

            if (! in_array($locked->status, [Loan::STATUS_APPROVED, Loan::STATUS_BORROWED, Loan::STATUS_PARTIAL], true)) {
                throw ValidationException::withMessages([
                    'loan' => 'Penggantian unit hanya dapat dilakukan saat peminjaman aktif atau sudah disetujui.',
                ]);
            }

            $lockedItem = LoanItem::query()
                ->where('loan_id', $locked->id)
                ->whereKey($loanItem->id)
                ->where('is_consumable', false)
                ->whereNull('returned_at')
                ->lockForUpdate()
                ->firstOrFail();

            $oldAsset = ItemAsset::query()->withoutGlobalScopes()
                ->lockForUpdate()->findOrFail($lockedItem->item_asset_id);

            if (! in_array($oldAsset->status, [ItemAsset::STATUS_RESERVED, ItemAsset::STATUS_BORROWED, ItemAsset::STATUS_DAMAGED], true)) {
                throw ValidationException::withMessages([
                    'asset' => "Unit {$oldAsset->asset_number} tidak sedang dipinjam atau direservasi.",
                ]);
            }

            $newAsset = ItemAsset::query()->withoutGlobalScopes()
                ->where('item_id', $lockedItem->item_id)
                ->where('workshop_id', $locked->workshop_id)
                ->whereKey($newAssetId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                ! $newAsset->is_active
                || $newAsset->status !== ItemAsset::STATUS_AVAILABLE
                || $newAsset->condition !== ItemAsset::CONDITION_GOOD
            ) {
                throw ValidationException::withMessages([
                    'asset' => "Unit {$newAsset->asset_number} tidak tersedia atau tidak dalam kondisi baik.",
                ]);
            }

            $oldAssetStatus = $locked->status === Loan::STATUS_APPROVED
                ? ItemAsset::STATUS_DAMAGED
                : ItemAsset::STATUS_DAMAGED;

            $newAssetStatus = $locked->status === Loan::STATUS_APPROVED
                ? ItemAsset::STATUS_RESERVED
                : ItemAsset::STATUS_BORROWED;

            $oldAsset->fill([
                'status' => $oldAssetStatus,
            ])->save();

            $newAsset->fill([
                'status' => $newAssetStatus,
            ])->save();

            $lockedItem->fill([
                'item_asset_id' => $newAsset->id,
                'condition_out' => $newAsset->condition,
            ])->save();

            return $lockedItem->fresh();
        }, attempts: 3);
    }

    private function lockedLoan(Loan $loan): Loan
    {
        return Loan::query()->withoutGlobalScopes()
            ->with('borrower')->lockForUpdate()->findOrFail($loan->id);
    }
}
