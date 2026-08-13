<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Traits\SortsIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use SortsIndex;

    public function index(Request $request): View
    {
        [$sort, $direction, $perPage] = $this->indexSortParams(['code', 'name']);

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
            ->when($sort !== null, fn ($q) => $q->orderBy($sort, $direction))
            ->paginate($perPage)
            ->withQueryString();

        return view('units.index', [
            'units' => $units,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
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

    public function bulkToggleStatus(
        Request $request
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->hasRole(
                'admin',
                'toolman'
            ),
            403
        );

        $ids = $request->input(
            'ids',
            []
        );

        if (is_string($ids)) {
            $ids = array_filter(
                array_map(
                    'intval',
                    array_map(
                        'trim',
                        explode(',', $ids)
                    )
                )
            );
        } else {
            $ids = array_map(
                'intval',
                (array) $ids
            );
        }

        $ids = array_values($ids);

        if (empty($ids)) {
            return back()->with(
                'warning',
                'Tidak ada data yang dipilih.'
            );
        }

        $count = Unit::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(
                function (Unit $unit): void {
                    $unit->fill([
                        'is_active' => ! $unit->is_active,
                    ])->save();
                }
            )
            ->count();

        return back()->with(
            'success',
            "Status {$count} satuan berhasil diubah."
        );
    }
}