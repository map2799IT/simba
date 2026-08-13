<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Workshop;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockIssueController extends Controller
{
    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        $movements =
            ItemStockMovement::query()
                ->with([
                    'item.category',
                    'item.unit',
                    'item.workshop',
                    'user',
                ])
                ->where(
                    'type',
                    ItemStockMovement::
                        TYPE_OUTGOING
                )
                ->when(
                    $search !== '',
                    function (
                        Builder $query
                    ) use ($search): void {
                        $query->where(
                            function (
                                Builder
                                $movementQuery
                            ) use ($search): void {
                                $movementQuery
                                    ->where(
                                        'reference_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'destination',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'purpose',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereHas(
                                        'item',
                                        function (
                                            Builder
                                            $itemQuery
                                        ) use (
                                            $search
                                        ): void {
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
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $request->filled(
                        'workshop_id'
                    ),
                    function (
                        Builder $query
                    ) use ($request): void {
                        $query->whereHas(
                            'item',
                            fn (
                                Builder
                                $itemQuery
                            ): Builder =>
                                $itemQuery
                                    ->where(
                                        'workshop_id',
                                        $request
                                            ->integer(
                                                'workshop_id'
                                            )
                                    )
                        );
                    }
                )
                ->when(
                    $request->filled(
                        'date_from'
                    ),
                    fn (
                        Builder $query
                    ): Builder =>
                        $query->whereDate(
                            'transaction_date',
                            '>=',
                            $request->input(
                                'date_from'
                            )
                        )
                )
                ->when(
                    $request->filled(
                        'date_to'
                    ),
                    fn (
                        Builder $query
                    ): Builder =>
                        $query->whereDate(
                            'transaction_date',
                            '<=',
                            $request->input(
                                'date_to'
                            )
                        )
                )
                ->orderByDesc(
                    'transaction_date'
                )
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

        return view(
            'stock-issues.index',
            [
                'movements' =>
                    $movements,

                'workshops' =>
                    Workshop::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->orderBy('code')
                        ->get(),
            ]
        );
    }

    public function create(): View
    {
        $this->authorizeInventoryManager();

        $items = Item::query()
            ->with([
                'unit',
                'workshop',
                'location',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        $assets = ItemAsset::query()
            ->with([
                'item',
                'workshop',
                'storageLocation',
            ])
            ->available()
            ->orderBy('asset_number')
            ->get();

        return view(
            'stock-issues.create',
            [
                'items' => $items,
                'assets' => $assets,
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $this->authorizeInventoryManager();

        $data = $request->validate([
            'transaction_date' => [
                'required',
                'date',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'destination' => [
                'nullable',
                'string',
                'max:150',
            ],

            'purpose' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],

            'items.*.item_id' => [
                'required',
                'integer',
                'exists:items,id',
            ],

            'items.*.quantity' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:99999999999.999',
            ],

            'items.*.asset_ids' => [
                'nullable',
                'array',
            ],

            'items.*.asset_ids.*' => [
                'integer',
                'exists:item_assets,id',
            ],

            'items.*.notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $reference =
            ! empty(
                $data['reference_number']
            )
                ? strtoupper(
                    trim(
                        $data[
                            'reference_number'
                        ]
                    )
                )
                : 'BK-'.
                    now()->format(
                        'Ymd-His'
                    ).
                    '-'.
                    Str::upper(
                        Str::random(4)
                    );

        $userId =
            $request->user()?->id;

        $count = DB::transaction(
            function () use (
                $data,
                $reference,
                $userId
            ): int {
                $processed = 0;

                foreach (
                    $data['items']
                    as $index => $row
                ) {
                    $item = Item::query()
                        ->with('unit')
                        ->lockForUpdate()
                        ->findOrFail(
                            $row['item_id']
                        );

                    if (! $item->is_active) {
                        throw ValidationException::
                            withMessages([
                                "items.{$index}.item_id" =>
                                    'Barang sudah tidak aktif.',
                            ]);
                    }

                    $stockBefore = round(
                        (float) $item->stock,
                        3
                    );

                    $assetNumbers = [];

                    if ($item->isTool()) {
                        $assetIds =
                            array_values(
                                array_unique(
                                    array_map(
                                        'intval',
                                        $row[
                                            'asset_ids'
                                        ]
                                        ?? []
                                    )
                                )
                            );

                        if ($assetIds === []) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.asset_ids" =>
                                        'Pilih minimal satu unit alat berdasarkan nomor inventaris/QR Code.',
                                ]);
                        }

                        $assets =
                            ItemAsset::query()
                                ->where(
                                    'item_id',
                                    $item->id
                                )
                                ->whereIn(
                                    'id',
                                    $assetIds
                                )
                                ->lockForUpdate()
                                ->get();

                        if (
                            $assets->count()
                            !== count(
                                $assetIds
                            )
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.asset_ids" =>
                                        'Ada unit alat yang tidak sesuai dengan master barang.',
                                ]);
                        }

                        foreach ($assets as $asset) {
                            if (
                                ! $asset->is_active
                                || $asset->status
                                    !== ItemAsset::
                                        STATUS_AVAILABLE
                            ) {
                                throw ValidationException::
                                    withMessages([
                                        "items.{$index}.asset_ids" =>
                                            "Unit {$asset->asset_number} tidak tersedia untuk Barang Keluar.",
                                    ]);
                            }
                        }

                        $quantity =
                            $assets->count();

                        if (
                            $quantity
                            > $stockBefore
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.asset_ids" =>
                                        'Jumlah unit alat melebihi stok master. Sinkronkan data unit alat terlebih dahulu.',
                                ]);
                        }

                        foreach ($assets as $asset) {
                            $assetNumbers[] =
                                $asset->asset_number;

                            $asset->fill([
                                'status' =>
                                    ItemAsset::
                                        STATUS_RETIRED,

                                'is_active' =>
                                    false,

                                'notes' =>
                                    $this
                                        ->assetNotes(
                                            $asset->notes,
                                            $reference,
                                            $row[
                                                'notes'
                                            ]
                                            ?? null
                                        ),
                            ])->save();
                        }
                    } else {
                        $quantity = round(
                            (float) (
                                $row['quantity']
                                ?? 0
                            ),
                            3
                        );

                        if ($quantity <= 0) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.quantity" =>
                                        'Jumlah bahan wajib lebih dari nol.',
                                ]);
                        }

                        if (
                            $item->unit !== null
                            && ! $item
                                ->unit
                                ->allows_decimal
                            && abs(
                                $quantity
                                - round($quantity)
                            ) > 0.000001
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.quantity" =>
                                        'Satuan bahan ini tidak mengizinkan jumlah desimal.',
                                ]);
                        }

                        if (
                            $quantity
                            > $stockBefore
                        ) {
                            throw ValidationException::
                                withMessages([
                                    "items.{$index}.quantity" =>
                                        'Jumlah keluar melebihi stok yang tersedia.',
                                ]);
                        }
                    }

                    $stockAfter = round(
                        $stockBefore
                        - $quantity,
                        3
                    );

                    $item->fill([
                        'stock' =>
                            $stockAfter,

                        'status' =>
                            $stockAfter > 0
                                ? 'available'
                                : 'out_of_stock',
                    ])->save();

                    ItemStockMovement::query()
                        ->create([
                            'item_id' =>
                                $item->id,

                            'user_id' =>
                                $userId,

                            'type' =>
                                ItemStockMovement::
                                    TYPE_OUTGOING,

                            'quantity' =>
                                $quantity,

                            'stock_before' =>
                                $stockBefore,

                            'stock_after' =>
                                $stockAfter,

                            'transaction_date' =>
                                $data[
                                    'transaction_date'
                                ],

                            'reference_number' =>
                                $reference,

                            'destination' =>
                                $data[
                                    'destination'
                                ]
                                ?? null,

                            'purpose' =>
                                $data['purpose']
                                ?? null,

                            'description' =>
                                $this
                                    ->movementNotes(
                                        $data,
                                        $row,
                                        $assetNumbers
                                    ),
                        ]);

                    $processed++;
                }

                return $processed;
            },
            attempts: 3
        );

        return redirect()
            ->route(
                'stock-issues.index',
                [
                    'search' =>
                        $reference,
                ]
            )
            ->with(
                'success',
                "{$count} baris Barang Keluar berhasil diproses."
            );
    }

    public function show(
        string|int $stockIssue
    ): RedirectResponse {
        return redirect()
            ->route(
                'stock-issues.index',
                [
                    'search' =>
                        $stockIssue,
                ]
            );
    }

    public function edit(
        string|int $stockIssue
    ): RedirectResponse {
        return redirect()
            ->route('stock-issues.index')
            ->with(
                'warning',
                'Transaksi stok tidak diedit langsung. Buat transaksi koreksi agar jejak audit tetap utuh.'
            );
    }

    public function update(
        Request $request,
        string|int $stockIssue
    ): RedirectResponse {
        return redirect()
            ->route('stock-issues.index')
            ->with(
                'warning',
                'Transaksi stok tidak diedit langsung.'
            );
    }

    public function post(
        string|int $stockIssue
    ): RedirectResponse {
        return redirect()
            ->route('stock-issues.index')
            ->with(
                'warning',
                'Barang Keluar langsung tercatat saat disimpan.'
            );
    }

    public function cancel(
        string|int $stockIssue
    ): RedirectResponse {
        return redirect()
            ->route('stock-issues.index')
            ->with(
                'warning',
                'Barang Keluar tidak dibatalkan langsung agar jejak audit tidak hilang.'
            );
    }

    private function authorizeInventoryManager(): void
    {
        abort_unless(
            auth()->user()?->hasRole(
                'admin',
                'toolman'
            ),
            403
        );
    }

    private function movementNotes(
        array $header,
        array $row,
        array $assetNumbers
    ): ?string {
        $parts = array_filter([
            $header['description']
                ?? null,

            $row['notes']
                ?? null,

            $assetNumbers !== []
                ? 'Unit alat: '.
                    implode(
                        ', ',
                        $assetNumbers
                    )
                : null,
        ]);

        return $parts === []
            ? null
            : implode(
                ' | ',
                $parts
            );
    }

    private function assetNotes(
        ?string $oldNotes,
        string $reference,
        ?string $rowNotes
    ): string {
        return implode(
            ' | ',
            array_filter([
                $oldNotes,
                'Barang keluar permanen '.
                    $reference,
                $rowNotes,
            ])
        );
    }
}
