<?php

namespace App\Http\Controllers;

use App\Models\ItemStockMovement;
use App\Traits\SortsIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class StockMovementController extends Controller
{
    use SortsIndex;

    /**
     * Ringkasan seluruh pergerakan stok.
     *
     * Project ini menggunakan tabel item_stock_movements,
     * bukan stock_movements.
     */
    public function index(
        Request $request
    ): View {
        [$sort, $direction, $perPage] = $this->indexSortParams([
            'transaction_date', 'reference_number', 'quantity', 'stock_before', 'stock_after', 'type',
        ]);

        $user = $request->user();

        $movements = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->with([
                'item.unit',
                'item.workshop',
                'user',
            ])
            ->when(
                (string) $user?->role !== 'admin',
                fn (Builder $query): Builder => $query->where(
                    'workshop_id',
                    $user?->workshop_id
                )
            )
            ->when(
                $request->filled('type'),
                function (Builder $query) use ($request): void {
                    $types = (array) $request->input('type');
                    $types = array_values(array_filter(array_map(
                        'strval',
                        $types
                    )));

                    if (count($types) === 1) {
                        $query->where('type', $types[0]);
                    } elseif (count($types) > 1) {
                        $query->whereIn('type', $types);
                    }
                }
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $query): Builder => $query->where(
                    'transaction_date',
                    '>=',
                    (string) $request->input('date_from')
                )
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $query): Builder => $query->where(
                    'transaction_date',
                    '<=',
                    (string) $request->input('date_to')
                )
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->when($sort !== null, fn ($query) => $query->orderBy($sort, $direction))
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'stock-movements.index',
            [
                'movements' => $movements,
                'sort' => $sort,
                'direction' => $direction,
                'perPage' => $perPage,
                'tableAvailable' => true,
                'typeOptions' =>
                    ItemStockMovement::typeOptions(),

                'hasReceiptsRoute' =>
                    Route::has(
                        'stock-receipts.index'
                    ),

                'hasIssuesRoute' =>
                    Route::has(
                        'stock-issues.index'
                    ),
            ]
        );
    }

    public function show(
        ItemStockMovement $stockMovement
    ): View {
        $stockMovement->load([
            'item.unit',
            'item.workshop',
            'user',
        ]);

        return view(
            'stock-movements.show',
            [
                'movement' => $stockMovement,
            ]
        );
    }

    public function create(): RedirectResponse
    {
        if (
            Route::has(
                'stock-receipts.create'
            )
        ) {
            return redirect()->route(
                'stock-receipts.create'
            );
        }

        return redirect()
            ->route('stock-movements.index')
            ->with(
                'warning',
                'Gunakan menu Barang Masuk atau Barang Keluar.'
            );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        return redirect()
            ->route('stock-movements.index')
            ->with(
                'warning',
                'Pergerakan stok dibuat melalui Barang Masuk atau Barang Keluar.'
            );
    }

    public function edit(
        ItemStockMovement $stockMovement
    ): RedirectResponse {
        return redirect()
            ->route(
                'stock-movements.show',
                $stockMovement
            )
            ->with(
                'warning',
                'Pergerakan stok tidak diedit langsung.'
            );
    }

    public function update(
        Request $request,
        ItemStockMovement $stockMovement
    ): RedirectResponse {
        return redirect()
            ->route(
                'stock-movements.show',
                $stockMovement
            )
            ->with(
                'warning',
                'Pergerakan stok tidak diedit langsung.'
            );
    }

    public function destroy(
        ItemStockMovement $stockMovement
    ): RedirectResponse {
        return redirect()
            ->route('stock-movements.index')
            ->with(
                'warning',
                'Pergerakan stok tidak dihapus langsung.'
            );
    }
}
