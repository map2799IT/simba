<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\StorageLocation;
use App\Models\Workshop;
use App\Services\ItemAssetQrCodeService;
use App\Traits\SortsIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemAssetController extends Controller
{
    use SortsIndex;

    /**
     * Daftar unit alat.
     *
     * Modul ini hanya untuk monitoring. Unit fisik dibuat otomatis
     * melalui transaksi Barang Masuk atau import Excel.
     */
    public function index(
        Request $request
    ): View {
        [$sort, $direction, $perPage] = $this->indexSortParams(['asset_number', 'serial_number', 'received_date']);

        $assets = ItemAsset::query()
            ->with([
                'item',
                'workshop',
                'storageLocation',
            ])
            ->when(
                $request->filled('item_id'),
                fn (
                    Builder $query
                ): Builder => $query->where(
                    'item_id',
                    $request->integer('item_id')
                )
            )
            ->when(
                $request->filled('search'),
                function (
                    Builder $query
                ) use ($request): void {
                    $search = trim(
                        (string) $request->input('search')
                    );

                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            $searchQuery
                                ->where(
                                    'asset_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'barcode_value',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'serial_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'brand',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'model',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('workshop_id'),
                fn (
                    Builder $query
                ): Builder => $query->where(
                    'workshop_id',
                    $request->integer('workshop_id')
                )
            )
            ->when(
                $request->filled('status'),
                fn (
                    Builder $query
                ): Builder => $query->where(
                    'status',
                    (string) $request->input('status')
                )
            )
            ->when(
                $request->filled('condition'),
                fn (
                    Builder $query
                ): Builder => $query->where(
                    'condition',
                    (string) $request->input('condition')
                )
            )
            ->orderBy('asset_number')
            ->when($sort !== null, fn ($q) => $q->orderBy($sort, $direction))
            ->paginate($perPage)
            ->withQueryString();

        $selectedItem = null;

        if ($request->filled('item_id')) {
            $selectedItem = Item::query()
                ->where('type', 'tool')
                ->find(
                    $request->integer('item_id')
                );
        }

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
            ]);

        return view(
            'item-assets.index',
            [
                'assets' => $assets,
                'selectedItem' => $selectedItem,
                'workshops' => $workshops,
                'statusOptions' =>
                    ItemAsset::statusOptions(),
                'conditionOptions' =>
                    ItemAsset::conditionOptions(),
                'sort' => $sort,
                'direction' => $direction,
                'perPage' => $perPage,
            ]
        );
    }

    /**
     * Detail satu unit alat.
     */
    public function show(
        ItemAsset $itemAsset
    ): View {
        $itemAsset->load([
            'item',
            'workshop',
            'storageLocation',
            'loanItems.loan.borrower',
            'damageReports',
        ]);

        return view(
            'item-assets.show',
            [
                'asset' => $itemAsset,
            ]
        );
    }

    /**
     * Form koreksi data unit.
     *
     * Tidak dipakai untuk membuat unit baru.
     */
    public function edit(
        ItemAsset $itemAsset
    ): View {
        $itemAsset->load([
            'item',
            'workshop',
            'storageLocation',
        ]);

        return view(
            'item-assets.edit',
            $this->formData($itemAsset)
        );
    }

    /**
     * Memperbarui identitas, lokasi, kondisi, dan status unit.
     */
    public function update(
        Request $request,
        ItemAsset $itemAsset
    ): RedirectResponse {
        $data = $this->validatedUpdateData(
            $request,
            $itemAsset
        );

        $data['barcode_value'] =
            $data['asset_number'];

        $itemAsset->update($data);

        return redirect()
            ->route(
                'item-assets.show',
                $itemAsset
            )
            ->with(
                'success',
                'Data unit alat berhasil diperbarui.'
            );
    }

    /**
     * Label QR Code satu unit alat.
     */
    public function label(
        ItemAsset $itemAsset,
        ItemAssetQrCodeService $qrCodeService
    ): View {
        $itemAsset->load([
            'item',
            'workshop',
            'storageLocation',
        ]);

        return view(
            'item-assets.label',
            [
                'asset' => $itemAsset,
                'qrSvg' =>
                    $qrCodeService->svg($itemAsset),
                'qrDataUri' =>
                    $qrCodeService->dataUri($itemAsset),
                'qrPayload' =>
                    $qrCodeService->payload($itemAsset),
            ]
        );
    }

    private function formData(
        ItemAsset $asset
    ): array {
        return [
            'asset' => $asset,

            'workshops' => Workshop::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                ]),

            'locations' =>
                StorageLocation::query()
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->get([
                        'id',
                        'code',
                        'name',
                        'workshop_id',
                    ]),

            'statusOptions' =>
                ItemAsset::statusOptions(),

            'conditionOptions' =>
                ItemAsset::conditionOptions(),
        ];
    }

    private function validatedUpdateData(
        Request $request,
        ItemAsset $asset
    ): array {
        $data = $request->validate([
            'asset_number' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'item_assets',
                    'asset_number'
                )->ignore($asset->id),
            ],

            'serial_number' => [
                'nullable',
                'string',
                'max:150',

                Rule::unique(
                    'item_assets',
                    'serial_number'
                )->ignore($asset->id),
            ],

            'workshop_id' => [
                'required',
                'integer',
                Rule::exists(
                    'workshops',
                    'id'
                ),
            ],

            'storage_location_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'storage_locations',
                    'id'
                ),
            ],

            'condition' => [
                'required',
                Rule::in(
                    array_keys(
                        ItemAsset::conditionOptions()
                    )
                ),
            ],

            'status' => [
                'required',
                Rule::in(
                    array_keys(
                        ItemAsset::statusOptions()
                    )
                ),
            ],

            'received_date' => [
                'nullable',
                'date',
            ],

            'unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        if (
            ! empty($data['storage_location_id'])
        ) {
            $location = StorageLocation::query()
                ->find(
                    $data['storage_location_id']
                );

            if (
                $location
                && (int) $location->workshop_id
                    !== (int) $data['workshop_id']
            ) {
                throw ValidationException::withMessages([
                    'storage_location_id' =>
                        'Lokasi harus berada pada bengkel yang dipilih.',
                ]);
            }
        }

        $data['is_active'] =
            $request->boolean('is_active');

        return $data;
    }
}
