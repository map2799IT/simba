<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Workshop;
use Carbon\Carbon;
use App\Services\AssetNumberService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StockReceiptMutationService
{
    public function __construct(
        private readonly BulkItemAssetService $assetService,
        private readonly AssetNumberService $numberService
    ) {
    }

    public function snapshot(ItemStockMovement $movement): array
    {
        return [
            'workshop_id' => (int) $movement->workshop_id,
            'storage_location_id' => (int) $movement->storage_location_id,
            'receipt_date' => $movement->transaction_date?->format('Y-m-d'),
            'document_number' => $movement->reference_number,
            'source' => $movement->source,
            'fund_source' => $movement->fund_source,
            'quantity' => (float) $movement->quantity,
            'brand' => $movement->brand,
            'model' => $movement->model,
            'specification' => $movement->specification,
            'unit_price' => $movement->unit_price !== null
                ? (float) $movement->unit_price
                : null,
            'condition' => $movement->condition ?: 'good',
            'photo_path' => $movement->photo_path,
            'replace_photo' => false,
            'notes' => $movement->description,
        ];
    }

    public function apply(
        ItemStockMovement $movement,
        array $payload,
        User $actor
    ): ItemStockMovement {
        return DB::transaction(function () use (
            $movement,
            $payload,
            $actor
        ): ItemStockMovement {
            $lockedMovement = ItemStockMovement::query()
                ->withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($movement->id);

            $this->assertIncoming($lockedMovement);

            $item = Item::query()
                ->withoutGlobalScopes()
                ->with('unit')
                ->lockForUpdate()
                ->findOrFail($lockedMovement->item_id);

            $workshopId = (int) $payload['workshop_id'];
            $locationId = (int) $payload['storage_location_id'];

            $workshop = Workshop::query()
                ->withoutGlobalScopes()
                ->findOrFail($workshopId);

            $location = StorageLocation::query()
                ->withoutGlobalScopes()
                ->findOrFail($locationId);

            if ((int) $location->workshop_id !== $workshopId) {
                throw ValidationException::withMessages([
                    'storage_location_id' =>
                        'Lokasi tidak berada pada jurusan tujuan.',
                ]);
            }

            $quantity = round((float) $payload['quantity'], 3);

            if ($item->isTool()) {
                $quantity = (int) round($quantity);
            }

            $receiptDate = Carbon::parse(
                $payload['receipt_date']
            )->toDateString();

            $this->assertLedgerValid(
                $item->id,
                $lockedMovement->id,
                $quantity,
                $receiptDate
            );

            $oldPhoto = $lockedMovement->photo_path;

            $newPhoto = ! empty($payload['replace_photo'])
                ? ($payload['photo_path'] ?? null)
                : $oldPhoto;

            if ($item->isTool()) {
                $this->synchronizeToolAssets(
                    $lockedMovement,
                    $item,
                    $quantity,
                    [
                        'receipt_code' => $lockedMovement->receipt_code,
                        'workshop_id' => $workshop->id,
                        'storage_location_id' => $location->id,
                        'received_date' => $receiptDate,
                        'brand' => $payload['brand'] ?? null,
                        'model' => $payload['model'] ?? null,
                        'specification' =>
                            $payload['specification'] ?? null,
                        'acquisition_source' => $payload['source'] ?? null,
                        'fund_source' => $payload['fund_source'] ?? null,
                        'unit_price' => $payload['unit_price'] ?? null,
                        'condition' => $payload['condition'] ?? 'good',
                        'photo_path' => $newPhoto,
                        'status' => ItemAsset::STATUS_AVAILABLE,
                        'notes' => $payload['notes'] ?? null,
                    ]
                );
            }

            $lockedMovement->fill([
                'workshop_id' => $workshop->id,
                'storage_location_id' => $location->id,
                'quantity' => $quantity,
                'transaction_date' => $receiptDate,
                'reference_number' =>
                    $payload['document_number'] ?? null,
                'source' => $payload['source'] ?? null,
                'brand' => $payload['brand'] ?? null,
                'model' => $payload['model'] ?? null,
                'specification' =>
                    $payload['specification'] ?? null,
                'fund_source' => $payload['fund_source'] ?? null,
                'unit_price' => $payload['unit_price'] ?? null,
                'condition' => $payload['condition'] ?? 'good',
                'photo_path' => $newPhoto,
                'description' => $payload['notes'] ?? null,
            ])->save();

            $this->recalculateLedger($item);

            if (
                ! empty($payload['replace_photo'])
                && $oldPhoto
                && $oldPhoto !== $newPhoto
            ) {
                DB::afterCommit(
                    fn () => $this->deletePhotoIfUnused($oldPhoto)
                );
            }

            return $lockedMovement->fresh([
                'item.unit',
                'workshop',
                'storageLocation',
                'user',
            ]);
        }, attempts: 3);
    }

    public function delete(
        ItemStockMovement $movement,
        User $actor
    ): void {
        DB::transaction(function () use ($movement, $actor): void {
            $lockedMovement = ItemStockMovement::query()
                ->withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($movement->id);

            $this->assertIncoming($lockedMovement);

            $item = Item::query()
                ->withoutGlobalScopes()
                ->with('unit')
                ->lockForUpdate()
                ->findOrFail($lockedMovement->item_id);

            $this->assertLedgerValid(
                $item->id,
                $lockedMovement->id,
                null,
                null,
                true
            );

            if ($item->isTool()) {
                $assets = $this->receiptAssets($lockedMovement);
                $expected = (int) round(
                    (float) $lockedMovement->quantity
                );

                if ($assets->count() !== $expected) {
                    throw ValidationException::withMessages([
                        'delete' =>
                            'Jumlah unit fisik tidak sama dengan jumlah Barang Masuk. Penghapusan dibatalkan untuk menjaga data.',
                    ]);
                }

                $this->assertAssetsRemovable($assets);

                foreach ($assets as $asset) {
                    $asset->delete();
                }
            }

            $photoPath = $lockedMovement->photo_path;
            $lockedMovement->delete();
            $this->recalculateLedger($item);

            if ($photoPath) {
                DB::afterCommit(
                    fn () => $this->deletePhotoIfUnused($photoPath)
                );
            }
        }, attempts: 3);
    }

    private function synchronizeToolAssets(
        ItemStockMovement $movement,
        Item $item,
        int $newQuantity,
        array $attributes
    ): void {
        $assets = $this->receiptAssets($movement);
        $oldCount = $assets->count();

        if (
            (int) $movement->workshop_id
            !== (int) $attributes['workshop_id']
        ) {
            $this->assertAssetsRemovable($assets, false);
        }

        if ($newQuantity > $oldCount) {
            $this->assetService->generate(
                $item,
                $newQuantity - $oldCount,
                $attributes
            );
        }

        if ($newQuantity < $oldCount) {
            $removeCount = $oldCount - $newQuantity;

            $removable = ItemAsset::query()
                ->withoutGlobalScopes()
                ->where('receipt_code', $movement->receipt_code)
                ->where('is_active', true)
                ->where('status', ItemAsset::STATUS_AVAILABLE)
                ->whereDoesntHave('loanItems')
                ->whereDoesntHave('damageReports')
                ->orderByDesc('id')
                ->limit($removeCount)
                ->lockForUpdate()
                ->get();

            if ($removable->count() !== $removeCount) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Jumlah unit tidak dapat dikurangi karena sebagian unit sudah dipinjam, rusak, atau memiliki riwayat.',
                ]);
            }

            foreach ($removable as $asset) {
                $asset->delete();
            }
        }

        /*
         * Simpan tahun perolehan asli unit SEBELUM bulk update,
         * agar regenerasi kode dapat mendeteksi perubahan tahun.
         */
        $originalYear = null;
        $originalAssets = $this->receiptAssets($movement);
        if ($originalAssets->isNotEmpty()) {
            $first = $originalAssets->first();
            $originalYear = $first->received_date
                ? (int) date('Y', strtotime((string) $first->received_date))
                : null;
        }

        ItemAsset::query()
            ->withoutGlobalScopes()
            ->where('receipt_code', $movement->receipt_code)
            ->update([
                'workshop_id' => $attributes['workshop_id'],
                'storage_location_id' =>
                    $attributes['storage_location_id'],
                'brand' => $attributes['brand'],
                'model' => $attributes['model'],
                'specification' => $attributes['specification'],
                'acquisition_source' =>
                    $attributes['acquisition_source'],
                'fund_source' => $attributes['fund_source'],
                'received_date' => $attributes['received_date'],
                'unit_price' => $attributes['unit_price'],
                'condition' => $attributes['condition'],
                'photo_path' => $attributes['photo_path'],
                'notes' => $attributes['notes'],
                'updated_at' => now(),
            ]);

        /*
         * TODO 3: Bila tahun perolehan berubah, kode unit dan nilai
         * barcode/QR turunan ikut diperbarui HANYA untuk unit yang aman
         * (available, aktif, tanpa riwayat peminjaman/kerusakan).
         * Unit yang sudah memiliki riwayat TIDAK diubah agar relasi
         * historis tidak putus.
         */
        $this->regenerateAssetCodesIfYearChanged(
            $movement,
            $item,
            (int) $attributes['workshop_id'],
            $attributes['received_date'],
            $originalYear
        );
    }

    /**
     * Regenerasi asset_number + barcode_value saat tahun perolehan berubah.
     * Hanya untuk unit yang masih eligible (available & tanpa riwayat).
     */
    private function regenerateAssetCodesIfYearChanged(
        ItemStockMovement $movement,
        Item $item,
        int $workshopId,
        ?string $newReceivedDate,
        ?int $originalYear = null
    ): void {
        $assets = $this->receiptAssets($movement);

        if ($assets->isEmpty()) {
            return;
        }

        $newYear = $newReceivedDate
            ? (int) date('Y', strtotime($newReceivedDate))
            : (int) now()->format('Y');

        /*
         * Bandingkan tahun ASLI sebelum update dengan tahun baru.
         * Jangan membandingkan received_date saat ini (sudah ter-update).
         */
        $yearChanged = false;
        if ($originalYear !== null) {
            $yearChanged = $originalYear !== $newYear;
        } else {
            $yearChanged = $assets->contains(
                fn (ItemAsset $asset): bool =>
                    ((int) date('Y', strtotime((string) ($asset->received_date ?? now())))) !== $newYear
            );
        }

        if (! $yearChanged) {
            return;
        }

        foreach ($assets as $asset) {
            $hasLoan = $asset->loanItems()->exists();
            $hasDamage = $asset->damageReports()->exists();
            $eligible = $asset->is_active
                && $asset->status === ItemAsset::STATUS_AVAILABLE
                && ! $hasLoan
                && ! $hasDamage;

            if (! $eligible) {
                continue;
            }

            $newNumber = $this->numberService->next(
                $item,
                $newYear,
                $workshopId
            );

            $asset->fill([
                'asset_number' => $newNumber,
                'barcode_value' => $newNumber,
            ])->save();
        }
    }

    private function receiptAssets(
        ItemStockMovement $movement
    ): Collection {
        return ItemAsset::query()
            ->withoutGlobalScopes()
            ->where('receipt_code', $movement->receipt_code)
            ->lockForUpdate()
            ->get();
    }

    private function assertAssetsRemovable(
        Collection $assets,
        bool $requireAvailable = true
    ): void {
        foreach ($assets as $asset) {
            $hasLoan = $asset->loanItems()->exists();
            $hasDamage = $asset->damageReports()->exists();

            $invalidStatus = $requireAvailable
                && (
                    ! $asset->is_active
                    || $asset->status !== ItemAsset::STATUS_AVAILABLE
                );

            if ($hasLoan || $hasDamage || $invalidStatus) {
                throw ValidationException::withMessages([
                    'delete' =>
                        'Unit '.$asset->asset_number.' memiliki status atau riwayat yang tidak mengizinkan perubahan/penghapusan.',
                ]);
            }
        }
    }

    private function assertIncoming(
        ItemStockMovement $movement
    ): void {
        if ($movement->type !== ItemStockMovement::TYPE_INCOMING) {
            throw ValidationException::withMessages([
                'stock_receipt' =>
                    'Data yang dipilih bukan Barang Masuk.',
            ]);
        }
    }

    private function assertLedgerValid(
        int $itemId,
        int $targetMovementId,
        ?float $replacementQuantity,
        ?string $replacementDate,
        bool $removeTarget = false
    ): void {
        $movements = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where('item_id', $itemId)
            ->get();

        $rows = [];

        foreach ($movements as $movement) {
            if ($removeTarget && $movement->id === $targetMovementId) {
                continue;
            }

            $rows[] = [
                'id' => (int) $movement->id,
                'type' => (string) $movement->type,
                'quantity' =>
                    $movement->id === $targetMovementId
                    && $replacementQuantity !== null
                        ? $replacementQuantity
                        : (float) $movement->quantity,
                'date' =>
                    $movement->id === $targetMovementId
                    && $replacementDate !== null
                        ? $replacementDate
                        : (
                            $movement->transaction_date?->format('Y-m-d')
                            ?: '1970-01-01'
                        ),
                'fallback_difference' =>
                    (float) $movement->stock_after
                    - (float) $movement->stock_before,
            ];
        }

        usort(
            $rows,
            static fn (array $left, array $right): int =>
                [$left['date'], $left['id']]
                <=>
                [$right['date'], $right['id']]
        );

        $running = 0.0;

        foreach ($rows as $row) {
            $running += $this->signedQuantity(
                $row['type'],
                $row['quantity'],
                $row['fallback_difference']
            );

            if ($running < -0.000001) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Perubahan dibatalkan karena akan membuat riwayat stok menjadi negatif. Barang tersebut sudah digunakan pada transaksi setelahnya.',
                ]);
            }
        }
    }

    private function recalculateLedger(Item $item): void
    {
        $movements = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $running = 0.0;

        foreach ($movements as $movement) {
            $before = round($running, 3);

            $running += $this->signedQuantity(
                (string) $movement->type,
                (float) $movement->quantity,
                (float) $movement->stock_after
                    - (float) $movement->stock_before
            );

            $after = round($running, 3);

            $movement->fill([
                'stock_before' => $before,
                'stock_after' => $after,
            ])->saveQuietly();
        }

        $item->fill([
            'stock' => round(max(0, $running), 3),
            'status' => $running > 0
                ? 'available'
                : 'out_of_stock',
        ])->save();
    }

    private function signedQuantity(
        string $type,
        float $quantity,
        float $fallbackDifference = 0
    ): float {
        return match ($type) {
            ItemStockMovement::TYPE_INITIAL,
            ItemStockMovement::TYPE_INCOMING,
            ItemStockMovement::TYPE_ADJUSTMENT_IN,
            ItemStockMovement::TYPE_RETURN =>
                abs($quantity),

            ItemStockMovement::TYPE_OUTGOING,
            ItemStockMovement::TYPE_ADJUSTMENT_OUT,
            ItemStockMovement::TYPE_LOAN =>
                -abs($quantity),

            default => $fallbackDifference,
        };
    }

    private function deletePhotoIfUnused(string $path): void
    {
        $movementUses = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where('photo_path', $path)
            ->exists();

        $assetUses = ItemAsset::query()
            ->withoutGlobalScopes()
            ->where('photo_path', $path)
            ->exists();

        if (! $movementUses && ! $assetUses) {
            Storage::disk('public')->delete($path);
        }
    }
}
