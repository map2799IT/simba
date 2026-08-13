<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStockReceiptRequest;
use App\Models\Item;
use App\Models\ItemStockMovement;
use App\Models\StockReceiptChangeRequest;
use App\Models\StorageLocation;
use App\Models\Workshop;
use App\Services\StockReceiptMutationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StockReceiptWorkflowController extends StockReceiptController
{
    public function index(Request $request): View
    {
        $this->authorizeRead($request);

        $user = $request->user();
        $search = trim((string) $request->input('search'));

        // Sorting: kode / tanggal / nama (ASC / DESC), kompatibel pagination.
        $sort = $request->input('sort', 'tanggal');
        $dir = strtolower((string) $request->input('dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $movements = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->with([
                'item.category',
                'item.unit',
                'workshop',
                'storageLocation',
                'user',
                'pendingChangeRequest.requester',
            ])
            ->where('type', ItemStockMovement::TYPE_INCOMING)
            ->when(
                (string) $user?->role !== 'admin',
                fn (Builder $query): Builder =>
                    $query->where('workshop_id', $user?->workshop_id)
            )
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('receipt_code', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            // Nama barang dari input Barang Masuk (brand/model), bukan master.
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhereHas(
                                'item',
                                function (Builder $itemQuery) use ($search): void {
                                    $itemQuery
                                        ->withoutGlobalScopes()
                                        ->where('code', 'like', "%{$search}%")
                                        ->orWhere('name', 'like', "%{$search}%");
                                }
                            );
                    });
                }
            )
            ->when(
                $request->filled('workshop_id')
                && (string) $user?->role === 'admin',
                fn (Builder $query): Builder =>
                    $query->where(
                        'workshop_id',
                        $request->integer('workshop_id')
                    )
            )
            ->when($sort === 'kode', function (Builder $query) use ($dir): void {
                $query->orderBy('receipt_code', $dir)->orderBy('id', $dir);
            })
            ->when($sort === 'nama', function (Builder $query) use ($dir): void {
                // Nama dari input Barang Masuk = brand/model.
                $query->orderBy('brand', $dir)->orderBy('model', $dir)->orderBy('id', $dir);
            })
            ->when(! in_array($sort, ['kode', 'nama'], true), function (Builder $query) use ($dir): void {
                $query->orderBy('transaction_date', $dir)->orderBy('id', $dir);
            })
            ->paginate(20)
            ->withQueryString();

        return view('stock-receipts.index', [
            'movements' => $movements,
            'workshops' => $this->visibleWorkshops($request),
            'canCreate' => in_array(
                (string) $user?->role,
                ['admin', 'toolman'],
                true
            ),
            'canReview' => in_array(
                (string) $user?->role,
                ['admin', 'kepala_bengkel'],
                true
            ),
            'isAdmin' => (string) $user?->role === 'admin',
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeCreate($request);

        return parent::create($request);
    }

    public function show(
        Request $request,
        ItemStockMovement $stockReceipt
    ): View {
        $this->authorizeMovement($request, $stockReceipt);

        $stockReceipt->load([
            'item.category',
            'item.unit',
            'workshop',
            'storageLocation',
            'user',
            'changeRequests.requester',
            'changeRequests.reviewer',
        ]);

        // Muat unit alat yang dihasilkan dari Barang Masuk ini
        $itemAssets = \App\Models\ItemAsset::query()
            ->withoutGlobalScopes()
            ->where('receipt_code', $stockReceipt->receipt_code)
            ->with(['storageLocation'])
            ->orderBy('asset_number')
            ->get();

        return view('stock-receipts.show', [
            'stockReceipt' => $stockReceipt,
            'itemAssets' => $itemAssets,
            'canReview' => $this->canReview($request, $stockReceipt),
            'canDelete' => (string) $request->user()?->role === 'admin',
        ]);
    }

    public function edit(
        Request $request,
        ItemStockMovement $stockReceipt
    ): View {
        $this->authorizeMovement($request, $stockReceipt);

        $stockReceipt->load([
            'item.category',
            'item.unit',
            'workshop',
            'storageLocation',
            'pendingChangeRequest.requester',
        ]);

        $workshops = $this->visibleWorkshops($request);

        $locations = StorageLocation::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->whereIn('workshop_id', $workshops->pluck('id'))
            ->orderBy('workshop_id')
            ->orderBy('code')
            ->get();

        return view('stock-receipts.edit', [
            'stockReceipt' => $stockReceipt,
            'workshops' => $workshops,
            'locations' => $locations,
            'conditions' => Item::conditionOptions(),
            'isAdmin' => (string) $request->user()?->role === 'admin',
            'requiresApproval' =>
                (string) $request->user()?->role === 'toolman',
        ]);
    }

    public function update(
        UpdateStockReceiptRequest $request,
        ItemStockMovement $stockReceipt,
        StockReceiptMutationService $mutationService
    ): RedirectResponse {
        $payload = $request->safe()->except([
            'photo',
            'change_reason',
        ]);

        $newPhotoPath = null;

        if ($request->hasFile('photo')) {
            $directory = (string) $request->user()?->role === 'toolman'
                ? 'stock-receipt-change-requests'
                : 'stock-receipts';

            $newPhotoPath = $request->file('photo')
                ->store($directory, 'public');

            $payload['photo_path'] = $newPhotoPath;
            $payload['replace_photo'] = true;
        } else {
            $payload['photo_path'] = $stockReceipt->photo_path;
            $payload['replace_photo'] = false;
        }

        if ((string) $request->user()?->role === 'toolman') {
            try {
                DB::transaction(function () use (
                    $request,
                    $stockReceipt,
                    $mutationService,
                    $payload
                ): void {
                    $pending = StockReceiptChangeRequest::query()
                        ->where(
                            'item_stock_movement_id',
                            $stockReceipt->id
                        )
                        ->where(
                            'status',
                            StockReceiptChangeRequest::STATUS_PENDING
                        )
                        ->lockForUpdate()
                        ->first();

                    if (
                        $pending !== null
                        && ! empty(
                            $pending->requested_payload['replace_photo']
                        )
                    ) {
                        $oldPendingPhoto =
                            $pending->requested_payload['photo_path'] ?? null;

                        if (
                            $oldPendingPhoto
                            && $oldPendingPhoto
                                !== ($payload['photo_path'] ?? null)
                        ) {
                            Storage::disk('public')->delete($oldPendingPhoto);
                        }
                    }

                    $values = [
                        'requested_by_user_id' => $request->user()->id,
                        'status' =>
                            StockReceiptChangeRequest::STATUS_PENDING,
                        'original_payload' =>
                            $mutationService->snapshot($stockReceipt),
                        'requested_payload' => $payload,
                        'request_note' =>
                            $request->input('change_reason'),
                        'reviewed_by_user_id' => null,
                        'review_note' => null,
                        'reviewed_at' => null,
                    ];

                    if ($pending !== null) {
                        $pending->update($values);
                    } else {
                        StockReceiptChangeRequest::query()->create(
                            array_merge(
                                $values,
                                [
                                    'item_stock_movement_id' =>
                                        $stockReceipt->id,
                                ]
                            )
                        );
                    }
                }, attempts: 3);
            } catch (Throwable $exception) {
                if ($newPhotoPath) {
                    Storage::disk('public')->delete($newPhotoPath);
                }

                throw $exception;
            }

            return redirect()
                ->route('stock-receipts.show', $stockReceipt)
                ->with(
                    'success',
                    'Permintaan perubahan dikirim. Stok dan unit belum berubah sampai disetujui Kepala Bengkel atau Administrator.'
                );
        }

        try {
            $updated = $mutationService->apply(
                $stockReceipt,
                $payload,
                $request->user()
            );

            $this->closePendingAfterDirectEdit(
                $stockReceipt,
                $request
            );
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('stock-receipts.show', $updated)
            ->with('success', 'Barang Masuk berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        ItemStockMovement $stockReceipt,
        StockReceiptMutationService $mutationService
    ): RedirectResponse {
        abort_unless(
            (string) $request->user()?->role === 'admin',
            403,
            'Penghapusan Barang Masuk hanya dapat dilakukan Administrator.'
        );

        $mutationService->delete(
            $stockReceipt,
            $request->user()
        );

        return redirect()
            ->route('stock-receipts.index')
            ->with(
                'success',
                'Barang Masuk berhasil dihapus dan stok/unit dikembalikan secara aman.'
            );
    }

    public function approvalIndex(Request $request): View
    {
        $this->authorizeReviewer($request);

        $user = $request->user();

        $requests = StockReceiptChangeRequest::query()
            ->with([
                'movement.item.unit',
                'movement.workshop',
                'movement.storageLocation',
                'requester',
            ])
            ->where(
                'status',
                StockReceiptChangeRequest::STATUS_PENDING
            )
            ->when(
                (string) $user?->role !== 'admin',
                fn (Builder $query): Builder =>
                    $query->whereHas(
                        'movement',
                        fn (Builder $movement): Builder =>
                            $movement->where(
                                'workshop_id',
                                $user?->workshop_id
                            )
                    )
            )
            ->latest()
            ->paginate(20);

        return view('stock-receipts.approvals', [
            'requests' => $requests,
        ]);
    }

    public function approveEdit(
        Request $request,
        ItemStockMovement $stockReceipt,
        StockReceiptMutationService $mutationService
    ): RedirectResponse {
        $this->authorizeReviewer($request, $stockReceipt);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use (
            $request,
            $stockReceipt,
            $mutationService,
            $validated
        ): void {
            $changeRequest = StockReceiptChangeRequest::query()
                ->where(
                    'item_stock_movement_id',
                    $stockReceipt->id
                )
                ->where(
                    'status',
                    StockReceiptChangeRequest::STATUS_PENDING
                )
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            $mutationService->apply(
                $stockReceipt,
                $changeRequest->requested_payload,
                $request->user()
            );

            $changeRequest->update([
                'status' =>
                    StockReceiptChangeRequest::STATUS_APPROVED,
                'reviewed_by_user_id' => $request->user()->id,
                'review_note' => $validated['review_note'] ?? null,
                'reviewed_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()
            ->route('stock-receipts.show', $stockReceipt)
            ->with(
                'success',
                'Perubahan Toolman disetujui dan diterapkan.'
            );
    }

    public function rejectEdit(
        Request $request,
        ItemStockMovement $stockReceipt
    ): RedirectResponse {
        $this->authorizeReviewer($request, $stockReceipt);

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use (
            $request,
            $stockReceipt,
            $validated
        ): void {
            $changeRequest = StockReceiptChangeRequest::query()
                ->where(
                    'item_stock_movement_id',
                    $stockReceipt->id
                )
                ->where(
                    'status',
                    StockReceiptChangeRequest::STATUS_PENDING
                )
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            $pendingPhoto = ! empty(
                $changeRequest->requested_payload['replace_photo']
            )
                ? (
                    $changeRequest->requested_payload['photo_path']
                    ?? null
                )
                : null;

            $changeRequest->update([
                'status' =>
                    StockReceiptChangeRequest::STATUS_REJECTED,
                'reviewed_by_user_id' => $request->user()->id,
                'review_note' => $validated['review_note'],
                'reviewed_at' => now(),
            ]);

            if ($pendingPhoto) {
                DB::afterCommit(
                    fn () =>
                        Storage::disk('public')->delete($pendingPhoto)
                );
            }
        }, attempts: 3);

        return redirect()
            ->route('stock-receipts.show', $stockReceipt)
            ->with('success', 'Permintaan perubahan ditolak.');
    }

    private function authorizeRead(Request $request): void
    {
        abort_unless(
            in_array(
                (string) $request->user()?->role,
                ['admin', 'toolman', 'kepala_bengkel'],
                true
            ),
            403
        );
    }

    private function authorizeCreate(Request $request): void
    {
        abort_unless(
            in_array(
                (string) $request->user()?->role,
                ['admin', 'toolman'],
                true
            ),
            403,
            'Barang Masuk hanya dapat dibuat Toolman atau Administrator.'
        );

        if ((string) $request->user()?->role === 'toolman') {
            abort_if(
                $request->user()?->workshop_id === null,
                403,
                'Akun Toolman belum mempunyai jurusan.'
            );
        }
    }

    private function authorizeMovement(
        Request $request,
        ItemStockMovement $movement
    ): void {
        $this->authorizeRead($request);

        if ((string) $request->user()?->role === 'admin') {
            return;
        }

        abort_unless(
            $request->user()?->workshop_id !== null
            && (int) $request->user()->workshop_id
                === (int) $movement->workshop_id,
            403
        );
    }

    private function authorizeReviewer(
        Request $request,
        ?ItemStockMovement $movement = null
    ): void {
        abort_unless(
            in_array(
                (string) $request->user()?->role,
                ['admin', 'kepala_bengkel'],
                true
            ),
            403,
            'Persetujuan hanya dapat dilakukan Kepala Bengkel atau Administrator.'
        );

        if (
            $movement !== null
            && (string) $request->user()?->role !== 'admin'
        ) {
            abort_unless(
                (int) $request->user()?->workshop_id
                    === (int) $movement->workshop_id,
                403
            );
        }
    }

    private function canReview(
        Request $request,
        ItemStockMovement $movement
    ): bool {
        return (string) $request->user()?->role === 'admin'
            || (
                (string) $request->user()?->role === 'kepala_bengkel'
                && (int) $request->user()?->workshop_id
                    === (int) $movement->workshop_id
            );
    }

    private function visibleWorkshops(Request $request)
    {
        return Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when(
                (string) $request->user()?->role !== 'admin',
                fn (Builder $query): Builder =>
                    $query->whereKey(
                        $request->user()?->workshop_id
                    )
            )
            ->orderBy('code')
            ->get();
    }

    private function closePendingAfterDirectEdit(
        ItemStockMovement $movement,
        Request $request
    ): void {
        $pending = StockReceiptChangeRequest::query()
            ->where('item_stock_movement_id', $movement->id)
            ->where(
                'status',
                StockReceiptChangeRequest::STATUS_PENDING
            )
            ->get();

        foreach ($pending as $changeRequest) {
            $pendingPhoto = ! empty(
                $changeRequest->requested_payload['replace_photo']
            )
                ? (
                    $changeRequest->requested_payload['photo_path']
                    ?? null
                )
                : null;

            $changeRequest->update([
                'status' =>
                    StockReceiptChangeRequest::STATUS_REJECTED,
                'reviewed_by_user_id' => $request->user()->id,
                'review_note' =>
                    'Dibatalkan karena data diedit langsung oleh Kepala Bengkel/Administrator.',
                'reviewed_at' => now(),
            ]);

            if ($pendingPhoto) {
                Storage::disk('public')->delete($pendingPhoto);
            }
        }
    }
}
