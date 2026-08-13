<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemCategoryRequest;
use App\Http\Requests\UpdateItemCategoryRequest;
use App\Models\ItemCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $appliesTo = $request->input('applies_to');
        $status = $request->input('status');

        $categories = ItemCategory::query()
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        function ($subquery) use ($search) {
                            $subquery
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
                                    'description',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $appliesTo,
                fn ($query) => $query->where(
                    'applies_to',
                    $appliesTo
                )
            )
            ->when(
                $status === 'active',
                fn ($query) => $query->where(
                    'is_active',
                    true
                )
            )
            ->when(
                $status === 'inactive',
                fn ($query) => $query->where(
                    'is_active',
                    false
                )
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'appliesToOptions' =>
                ItemCategory::appliesToOptions(),
        ]);
    }

    public function create(): View
    {
        return view('categories.create', [
            'appliesToOptions' =>
                ItemCategory::appliesToOptions(),
        ]);
    }

    public function store(
        StoreItemCategoryRequest $request
    ): RedirectResponse {
        ItemCategory::query()->create(
            $request->validated()
        );

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Kategori barang berhasil ditambahkan.'
            );
    }

    public function edit(
        ItemCategory $itemCategory
    ): View {
        return view('categories.edit', [
            'itemCategory' => $itemCategory,

            'appliesToOptions' =>
                ItemCategory::appliesToOptions(),
        ]);
    }

    public function update(
        UpdateItemCategoryRequest $request,
        ItemCategory $itemCategory
    ): RedirectResponse {
        $itemCategory->update(
            $request->validated()
        );

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Kategori barang berhasil diperbarui.'
            );
    }

    public function toggleStatus(
        ItemCategory $itemCategory
    ): RedirectResponse {
        $itemCategory->update([
            'is_active' => ! $itemCategory->is_active,
        ]);

        $message = $itemCategory->is_active
            ? 'Kategori berhasil diaktifkan.'
            : 'Kategori berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }
}