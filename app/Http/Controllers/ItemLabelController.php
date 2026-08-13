<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\ItemLabelCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ItemLabelController extends Controller
{
    public function single(
        Item $item,
        ItemLabelCodeService $labelCodeService
    ): View {
        $this->loadAvailableRelations($item);

        return view('items.labels.single', [
            'item' => $item,
            'label' => $labelCodeService->forItem($item),
            'metadata' => $this->metadata($item),
        ]);
    }

    public function bulk(
        Request $request,
        ItemLabelCodeService $labelCodeService
    ): View {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'item_ids.*' => ['required', 'integer', 'distinct', 'exists:items,id'],
        ], [
            'item_ids.required' => 'Pilih minimal satu barang.',
            'item_ids.array' => 'Daftar barang tidak valid.',
            'item_ids.min' => 'Pilih minimal satu barang.',
            'item_ids.max' => 'Maksimal 100 label dalam sekali cetak.',
            'item_ids.*.exists' => 'Salah satu barang tidak ditemukan.',
        ]);

        $items = Item::query()
            ->whereIn('id', $validated['item_ids'])
            ->orderBy('code')
            ->get();

        if ($items->isNotEmpty()) {
            $relations = $this->availableRelations($items->first());

            if ($relations !== []) {
                $items->load($relations);
            }
        }

        $labels = $items->map(
            fn (Item $item): array => [
                'item' => $item,
                'codes' => $labelCodeService->forItem($item),
                'metadata' => $this->metadata($item),
            ]
        );

        return view('items.labels.bulk', [
            'labels' => $labels,
        ]);
    }

    private function loadAvailableRelations(Item $item): void
    {
        $relations = $this->availableRelations($item);

        if ($relations !== []) {
            $item->loadMissing($relations);
        }
    }

    private function availableRelations(Item $item): array
    {
        return array_values(array_filter(
            [
                'category',
                'unit',
                'workshop',
                'storageLocation',
                'location',
            ],
            fn (string $relation): bool => method_exists($item, $relation)
        ));
    }

    private function metadata(Item $item): array
    {
        $category = $item->relationLoaded('category')
            ? $item->getRelation('category')
            : null;

        $unit = $item->relationLoaded('unit')
            ? $item->getRelation('unit')
            : null;

        $workshop = $item->relationLoaded('workshop')
            ? $item->getRelation('workshop')
            : null;

        $location = null;

        if ($item->relationLoaded('storageLocation')) {
            $location = $item->getRelation('storageLocation');
        } elseif ($item->relationLoaded('location')) {
            $location = $item->getRelation('location');
        }

        $unitLabel = $unit?->name;

        if (! empty($unit?->symbol)) {
            $unitLabel = $unitLabel
                ? $unitLabel.' ('.$unit->symbol.')'
                : $unit->symbol;
        }

        return [
            'category' => $category?->name,
            'unit' => $unitLabel,
            'workshop' => $workshop?->name,
            'location' => $location?->name,
        ];
    }
}
