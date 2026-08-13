<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStockMovement;
use App\Models\Unit;
use App\Services\ItemCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $items = Item::query()
            ->withoutGlobalScopes()
            ->with([
                'category',
                'unit',
            ])
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(
                        function (Builder $searchQuery) use ($search): void {
                            $searchQuery
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhereHas(
                                    'category',
                                    fn (Builder $categoryQuery): Builder =>
                                        $categoryQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('type'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'type',
                        $request->input('type')
                    )
            )
            ->when(
                $request->filled('item_category_id'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'item_category_id',
                        $request->input('item_category_id')
                    )
            )
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'types' => Item::typeOptions(),
            'categories' => ItemCategory::query()
                ->where('is_active', true)
                ->whereIn('applies_to', ['tool', 'material'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Item $item): View
    {
        $item->load([
            'category',
            'unit',
        ]);

        $receipts = ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->with([
                'workshop',
                'storageLocation',
                'user',
            ])
            ->where('item_id', $item->id)
            ->where(
                'type',
                ItemStockMovement::TYPE_INCOMING
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('items.show', [
            'item' => $item,
            'receipts' => $receipts,
        ]);
    }

    public function create(): View
    {
        $this->authorizeInventoryManager();

        return view('items.create', [
            'categories' => ItemCategory::query()
                ->where('is_active', true)
                ->whereIn('applies_to', ['tool', 'material'])
                ->orderBy('applies_to')
                ->orderBy('name')
                ->get(),
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        StoreItemRequest $request,
        ItemCodeService $codeService
    ): RedirectResponse {
        $data = $request->validated();

        $category = ItemCategory::query()
            ->findOrFail($data['item_category_id']);

        $type = (string) $category->applies_to;

        $item = DB::transaction(
            function () use (
                $data,
                $type,
                $codeService
            ): Item {
                $item = new Item();

                $item->fill([
                    'type' => $type,
                    'code' => $codeService->generate($type),
                    'name' => $data['name'],
                    'item_category_id' =>
                        $data['item_category_id'],
                    'unit_id' => $data['unit_id'],

                    /*
                     * Master katalog tidak mempunyai
                     * bengkel maupun lokasi penempatan.
                     */
                    'workshop_id' => null,
                    'storage_location_id' => null,
                    'brand' => null,
                    'model' => null,
                    'serial_number' => null,
                    'specification' => null,
                    'received_date' => null,
                    'acquisition_source' => null,
                    'fund_source' => null,
                    'unit_price' => null,
                    'condition' => 'good',
                    'status' => 'out_of_stock',
                    'stock' => 0,
                    'minimum_stock' => 0,
                    'is_borrowable' => $type === 'tool',
                    'photo_path' => null,
                    'description' => null,
                    'is_active' => true,
                ]);

                $item->save();

                return $item;
            },
            attempts: 3
        );

        return redirect()
            ->route('items.index', [
                'type' => $item->type,
            ])
            ->with(
                'success',
                "Master {$item->code} berhasil dibuat. Merek, model, spesifikasi, tahun, bengkel, lokasi, harga, dan kondisi dicatat saat Barang Masuk."
            );
    }

    public function bulkCreate(): RedirectResponse
    {
        return redirect()
            ->route('items.create')
            ->with(
                'info',
                'Master dibuat satu kali berdasarkan nama, kategori, dan satuan. Variasi merek/model dicatat melalui Barang Masuk.'
            );
    }

    public function bulkStore(): RedirectResponse
    {
        return redirect()
            ->route('items.create');
    }

    public function edit(Item $item): View
    {
        $this->authorizeInventoryManager();

        $item->load([
            'category',
            'unit',
        ]);

        return view('items.edit', [
            'item' => $item,
            'categories' => ItemCategory::query()
                ->where('is_active', true)
                ->where('applies_to', $item->type)
                ->orderBy('name')
                ->get(),
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        UpdateItemRequest $request,
        Item $item
    ): RedirectResponse {
        $data = $request->validated();

        $item->fill([
            'name' => $data['name'],
            'item_category_id' =>
                $data['item_category_id'],
            'unit_id' => $data['unit_id'],
        ])->save();

        return redirect()
            ->route('items.index')
            ->with(
                'success',
                "Master {$item->code} berhasil diperbarui."
            );
    }

    public function toggleStatus(
        Item $item
    ): RedirectResponse {
        $this->authorizeInventoryManager();

        $item->fill([
            'is_active' => ! $item->is_active,
        ])->save();

        return back()->with(
            'success',
            $item->is_active
                ? "Master {$item->code} berhasil diaktifkan."
                : "Master {$item->code} berhasil dinonaktifkan."
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
}
