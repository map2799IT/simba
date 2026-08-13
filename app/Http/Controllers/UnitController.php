<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $status = $request->input('status');

        $units = Unit::query()
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

        return view('units.index', [
            'units' => $units,
        ]);
    }

    public function create(): View
    {
        return view('units.create');
    }

    public function store(
        StoreUnitRequest $request
    ): RedirectResponse {
        Unit::query()->create(
            $request->validated()
        );

        return redirect()
            ->route('units.index')
            ->with(
                'success',
                'Satuan berhasil ditambahkan.'
            );
    }

    public function edit(Unit $unit): View
    {
        return view('units.edit', [
            'unit' => $unit,
        ]);
    }

    public function update(
        UpdateUnitRequest $request,
        Unit $unit
    ): RedirectResponse {
        $unit->update(
            $request->validated()
        );

        return redirect()
            ->route('units.index')
            ->with(
                'success',
                'Satuan berhasil diperbarui.'
            );
    }

    public function toggleStatus(
        Unit $unit
    ): RedirectResponse {
        $unit->update([
            'is_active' => ! $unit->is_active,
        ]);

        $message = $unit->is_active
            ? 'Satuan berhasil diaktifkan.'
            : 'Satuan berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }
}