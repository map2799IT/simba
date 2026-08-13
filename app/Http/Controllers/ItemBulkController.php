<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBulkItemRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\StorageLocation;
use App\Models\Unit;
use App\Models\Workshop;
use App\Services\ItemCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ItemBulkController extends Controller
{
    public function create(): View
    {
        return view('items.bulk-create', [
            'types' => Item::typeOptions(),

            'conditions' =>
                Item::conditionOptions(),

            'categories' => ItemCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'workshops' => Workshop::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'locations' => StorageLocation::query()
                ->with([
                    'workshop',
                    'parent.parent.parent',
                ])
                ->where('is_active', true)
                ->orderBy('workshop_id')
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function store(
        StoreBulkItemRequest $request,
        ItemCodeService $codeService
    ): RedirectResponse {
        $data = $request->validated();

        $quantity = $data['type'] === 'tool'
            ? (int) $data['quantity']
            : 1;

        $serialNumbers = $this->parseSerialNumbers(
            $data['serial_numbers'] ?? null
        );

        unset(
            $data['quantity'],
            $data['serial_numbers']
        );

        $createdItems = DB::transaction(
            function () use (
                $data,
                $quantity,
                $serialNumbers,
                $codeService
            ): array {
                $workshop = Workshop::query()
                    ->findOrFail($data['workshop_id']);

                $category = ItemCategory::query()
                    ->findOrFail(
                        $data['item_category_id']
                    );

                $createdCodes = [];

                if ($data['type'] === 'tool') {
                    for (
                        $index = 0;
                        $index < $quantity;
                        $index++
                    ) {
                        $condition = $data['condition'];

                        $item = Item::query()->create([
                            ...$data,

                            'code' => $codeService->generate(
                                'tool',
                                $workshop,
                                $category
                            ),

                            'serial_number' =>
                                $serialNumbers[$index]
                                    ?? null,

                            'condition' => $condition,

                            'status' =>
                                $this->statusFromCondition(
                                    $condition
                                ),

                            'stock' => 1,
                            'minimum_stock' => 0,
                        ]);

                        $createdCodes[] = $item->code;
                    }

                    return $createdCodes;
                }

                $stock = (float) ($data['stock'] ?? 0);

                $item = Item::query()->create([
                    ...$data,

                    'code' => $codeService->generate(
                        'material',
                        $workshop,
                        $category
                    ),

                    'serial_number' => null,
                    'condition' => 'good',
                    'is_borrowable' => false,

                    'status' => $stock > 0
                        ? 'available'
                        : 'out_of_stock',
                ]);

                return [$item->code];
            },
            attempts: 3
        );

        $count = count($createdItems);

        return redirect()
            ->route('items.index', [
                'type' => $data['type'],
            ])
            ->with(
                'success',
                "{$count} data barang berhasil ditambahkan."
            );
    }

    private function parseSerialNumbers(
        ?string $value
    ): array {
        if (! $value) {
            return [];
        }

        return collect(
            preg_split('/\R+/', $value) ?: []
        )
            ->map(
                fn (string $serial) =>
                    strtoupper(trim($serial))
            )
            ->filter()
            ->values()
            ->all();
    }

    private function statusFromCondition(
        string $condition
    ): string {
        return match ($condition) {
            'maintenance' => 'maintenance',

            'minor_damage',
            'major_damage',
            'unfit' => 'damaged',

            default => 'available',
        };
    }
}