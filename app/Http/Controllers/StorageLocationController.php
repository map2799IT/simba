<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\StorageLocation;
use App\Models\Workshop;
use App\Traits\SortsIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorageLocationController extends Controller
{
    use SortsIndex;

    public function index(
        Request $request
    ): View {
        $this->authorizeViewRole(
            $request
        );

        [$sort, $direction, $perPage] = $this->indexSortParams(['code', 'name', 'type']);

        $user = $request->user();
        $workshopId =
            $this->effectiveWorkshopId(
                $request
            );

        $query = StorageLocation::query()
            ->withoutGlobalScopes()
            ->with([
                'workshop',
                'parent',
            ])
            ->withCount([
                'children',
                'itemAssets as tool_units_count' =>
                    fn (Builder $query): Builder =>
                        $query->where(
                            'is_active',
                            true
                        ),

                'items as material_items_count' =>
                    fn (Builder $query): Builder =>
                        $query->where(
                            'type',
                            'material'
                        )
                        ->where(
                            'is_active',
                            true
                        ),
            ]);

        $this->applyWorkshopScope(
            $query,
            $user,
            $workshopId
        );

        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        if ($search !== '') {
            $query->where(
                function (
                    Builder $subquery
                ) use ($search): void {
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

        if ($request->filled('type')) {
            $query->where(
                'type',
                (string) $request->input(
                    'type'
                )
            );
        }

        $locations = $query
            ->orderBy('workshop_id')
            ->orderByRaw(
                'CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END'
            )
            ->orderBy('code')
            ->when($sort !== null, fn ($q) => $q->orderBy($sort, $direction))
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'locations.index',
            [
                'locations' =>
                    $locations,

                'workshops' =>
                    $this->visibleWorkshops(
                        $user
                    ),

                'typeOptions' =>
                    StorageLocation::typeOptions(),

                'selectedWorkshopId' =>
                    $workshopId,

                'canManage' =>
                    $this->canManage(
                        $user
                    ),

                'canPrint' =>
                    $this->canPrint(
                        $user
                    ),

                'sort' =>
                    $sort,

                'direction' =>
                    $direction,

                'perPage' =>
                    $perPage,
            ]
        );
    }

    public function create(
        Request $request
    ): View {
        $this->authorizeManage(
            $request
        );

        return view(
            'locations.create',
            $this->formData(
                $request
            )
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $this->authorizeManage(
            $request
        );

        $data =
            $this->validatedData(
                $request
            );

        $location =
            StorageLocation::query()
                ->withoutGlobalScopes()
                ->create($data);

        $message =
            $location->parent_id === null
                ? 'Lokasi induk berhasil ditambahkan.'
                : 'Lokasi turunan berhasil ditambahkan.';

        return redirect()
            ->route(
                'locations.show',
                $location
            )
            ->with(
                'success',
                $message
            );
    }

    public function show(
        Request $request,
        StorageLocation $location
    ): View {
        $this->authorizeViewRole(
            $request
        );

        $this->authorizeLocation(
            $request,
            $location
        );

        $location->load([
            'workshop',
            'parent.parent.parent',
            'children' =>
                fn ($query) =>
                    $query->orderBy('code'),
        ]);

        $inventory =
            $this->inventoryData(
                $location,
                false
            );

        return view(
            'locations.show',
            array_merge(
                $inventory,
                [
                    'location' =>
                        $location,

                    'canManage' =>
                        $this->canManage(
                            $request->user()
                        ),

                    'canPrint' =>
                        $this->canPrint(
                            $request->user()
                        ),
                ]
            )
        );
    }

    public function edit(
        Request $request,
        StorageLocation $location
    ): View {
        $this->authorizeManage(
            $request
        );

        $this->authorizeLocation(
            $request,
            $location
        );

        $location->load([
            'workshop',
            'parent',
        ]);

        return view(
            'locations.edit',
            array_merge(
                $this->formData(
                    $request,
                    $location
                ),
                [
                    'location' =>
                        $location,
                ]
            )
        );
    }

    public function update(
        Request $request,
        StorageLocation $location
    ): RedirectResponse {
        $this->authorizeManage(
            $request
        );

        $this->authorizeLocation(
            $request,
            $location
        );

        $data =
            $this->validatedData(
                $request,
                $location
            );

        $location->update($data);

        return redirect()
            ->route(
                'locations.show',
                $location
            )
            ->with(
                'success',
                'Lokasi penyimpanan berhasil diperbarui.'
            );
    }

    public function destroy(
        Request $request,
        StorageLocation $location
    ): RedirectResponse {
        $this->authorizeManage(
            $request
        );

        $this->authorizeLocation(
            $request,
            $location
        );

        $used = $location
            ->children()
            ->exists()
            || $location
                ->items()
                ->exists()
            || $location
                ->itemAssets()
                ->exists();

        if ($used) {
            return back()->with(
                'warning',
                'Lokasi masih memiliki turunan atau inventaris. Pindahkan isinya sebelum menghapus.'
            );
        }

        $location->delete();

        return redirect()
            ->route(
                'locations.index'
            )
            ->with(
                'success',
                'Lokasi penyimpanan berhasil dihapus.'
            );
    }

    public function inventoryPrint(
        Request $request,
        StorageLocation $storageLocation
    ): View {
        $this->authorizePrint(
            $request
        );

        $this->authorizeLocation(
            $request,
            $storageLocation
        );

        $includeChildren =
            $request->boolean(
                'include_children'
            );

        $storageLocation->load([
            'workshop',
            'parent.parent.parent',
        ]);

        return view(
            'locations.inventory-print',
            array_merge(
                $this->inventoryData(
                    $storageLocation,
                    $includeChildren
                ),
                [
                    'location' =>
                        $storageLocation,

                    'includeChildren' =>
                        $includeChildren,

                    'generatedAt' =>
                        now(),

                    'pdfMode' =>
                        false,
                ]
            )
        );
    }

    /**
     * Route lama locations.inventory.pdf tetap digunakan.
     */
    public function inventoryPdf(
        Request $request,
        StorageLocation $location
    ): mixed {
        $this->authorizePrint(
            $request
        );

        $this->authorizeLocation(
            $request,
            $location
        );

        $includeChildren =
            $request->boolean(
                'include_children'
            );

        $location->load([
            'workshop',
            'parent.parent.parent',
        ]);

        $data = array_merge(
            $this->inventoryData(
                $location,
                $includeChildren
            ),
            [
                'location' =>
                    $location,

                'includeChildren' =>
                    $includeChildren,

                'generatedAt' =>
                    now(),

                'pdfMode' =>
                    true,
            ]
        );

        if (
            class_exists(
                \Barryvdh\DomPDF\Facade\Pdf::class
            )
        ) {
            return \Barryvdh\DomPDF\Facade\Pdf::
                loadView(
                    'locations.inventory-print',
                    $data
                )
                ->setPaper(
                    'a4',
                    'landscape'
                )
                ->download(
                    'inventaris-lokasi-'.
                    $location->code.
                    '-'.
                    now()->format('Ymd-His').
                    '.pdf'
                );
        }

        return response()
            ->view(
                'locations.inventory-print',
                array_merge(
                    $data,
                    [
                        'pdfMode' =>
                            false,

                        'pdfFallback' =>
                            true,
                    ]
                )
            );
    }

    private function formData(
        Request $request,
        ?StorageLocation $location = null
    ): array {
        $user = $request->user();

        $workshopId =
            $this->effectiveWorkshopId(
                $request,
                $location?->workshop_id
            );

        $parentQuery =
            StorageLocation::query()
                ->withoutGlobalScopes()
                ->where(
                    'is_active',
                    true
                )
                ->orderByRaw(
                    'CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END'
                )
                ->orderBy('code');

        if ($workshopId !== null) {
            $parentQuery->where(
                'workshop_id',
                $workshopId
            );
        }

        if ($location !== null) {
            $excludedIds =
                $this->locationIds(
                    $location
                );

            $parentQuery->whereNotIn(
                'id',
                $excludedIds
            );
        }

        $requestedParentId =
            old(
                'parent_id',
                $location?->parent_id
                    ?? $request->query(
                        'parent_id'
                    )
            );

        $requestedMode =
            old(
                'location_mode',
                $request->query(
                    'mode',
                    $requestedParentId
                        ? 'child'
                        : 'root'
                )
            );

        if (
            ! in_array(
                $requestedMode,
                [
                    'root',
                    'child',
                ],
                true
            )
        ) {
            $requestedMode =
                $requestedParentId
                    ? 'child'
                    : 'root';
        }

        return [
            'workshops' =>
                $this->visibleWorkshops(
                    $user
                ),

            'parents' =>
                $parentQuery->get([
                    'id',
                    'code',
                    'name',
                    'type',
                    'workshop_id',
                    'parent_id',
                ]),

            'typeOptions' =>
                StorageLocation::typeOptions(),

            'selectedWorkshopId' =>
                $workshopId,

            'selectedParentId' =>
                $requestedParentId,

            'selectedLocationMode' =>
                $requestedMode,

            'isAdmin' =>
                (string) $user->role
                    === 'admin',

            'isToolman' =>
                (string) $user->role
                    === 'toolman',
        ];
    }

    private function validatedData(
        Request $request,
        ?StorageLocation $location = null
    ): array {
        $user = $request->user();

        $forcedWorkshopId =
            in_array(
                (string) $user->role,
                [
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            )
                ? $this->requiredWorkshopId(
                    $user
                )
                : null;

        if ($forcedWorkshopId !== null) {
            $request->merge([
                'workshop_id' =>
                    $forcedWorkshopId,
            ]);
        }

        $locationMode =
            (string) $request->input(
                'location_mode',
                $request->filled(
                    'parent_id'
                )
                    ? 'child'
                    : 'root'
            );

        $request->merge([
            'location_mode' =>
                $locationMode,
        ]);

        $data = $request->validate([
            'location_mode' => [
                'required',
                Rule::in([
                    'root',
                    'child',
                ]),
            ],

            'workshop_id' => [
                'required',
                'integer',
                Rule::exists(
                    'workshops',
                    'id'
                ),
            ],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'storage_locations',
                    'id'
                ),
            ],

            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique(
                    'storage_locations',
                    'code'
                )->ignore(
                    $location?->id
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'type' => [
                'required',
                Rule::in(
                    array_keys(
                        StorageLocation::
                            typeOptions()
                    )
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $data['code'] = strtoupper(
            trim(
                (string) $data['code']
            )
        );

        $data['name'] = trim(
            (string) $data['name']
        );

        $data['description'] =
            $request->filled(
                'description'
            )
                ? trim(
                    (string) $data[
                        'description'
                    ]
                )
                : null;

        if (
            $data['location_mode']
            === 'root'
        ) {
            $data['parent_id'] = null;
        } else {
            if (
                ! $request->filled(
                    'parent_id'
                )
            ) {
                throw ValidationException::
                    withMessages([
                        'parent_id' =>
                            'Pilih lokasi induk untuk membuat lokasi turunan.',
                    ]);
            }

            $data['parent_id'] =
                (int) $data[
                    'parent_id'
                ];
        }

        $data['is_active'] =
            $request->boolean(
                'is_active',
                true
            );

        if ($data['parent_id'] !== null) {
            $parent =
                StorageLocation::query()
                    ->withoutGlobalScopes()
                    ->find(
                        $data['parent_id']
                    );

            if (
                $parent === null
                || (int) $parent->workshop_id
                    !== (int) $data[
                        'workshop_id'
                    ]
            ) {
                throw ValidationException::
                    withMessages([
                        'parent_id' =>
                            'Lokasi induk harus berada pada jurusan yang sama.',
                    ]);
            }

            if (
                $location !== null
                && in_array(
                    (int) $parent->id,
                    $this->locationIds(
                        $location
                    ),
                    true
                )
            ) {
                throw ValidationException::
                    withMessages([
                        'parent_id' =>
                            'Lokasi tidak dapat ditempatkan di dalam dirinya sendiri atau lokasi turunannya.',
                    ]);
            }
        }

        unset(
            $data['location_mode']
        );

        return $data;
    }

    private function inventoryData(
        StorageLocation $location,
        bool $includeChildren
    ): array {
        $locationIds =
            $includeChildren
                ? $this->locationIds(
                    $location
                )
                : [
                    (int) $location->id,
                ];

        $materials = Item::query()
            ->withoutGlobalScopes()
            ->with([
                'unit',
                'workshop',
                'location',
            ])
            ->where(
                'type',
                'material'
            )
            ->whereIn(
                'storage_location_id',
                $locationIds
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        $assets = ItemAsset::query()
            ->withoutGlobalScopes()
            ->with([
                'item',
                'workshop',
                'storageLocation',
            ])
            ->whereIn(
                'storage_location_id',
                $locationIds
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('item_id')
            ->orderBy('asset_number')
            ->get();

        $toolGroups =
            $assets->groupBy(
                'item_id'
            );

        return [
            'materials' =>
                $materials,

            'assets' =>
                $assets,

            'toolGroups' =>
                $toolGroups,

            'locationIds' =>
                $locationIds,

            'summary' => [
                'material_types' =>
                    $materials->count(),

                'material_stock' =>
                    (float) $materials
                        ->sum('stock'),

                'tool_types' =>
                    $toolGroups->count(),

                'tool_units' =>
                    $assets->count(),
            ],
        ];
    }

    private function locationIds(
        StorageLocation $location
    ): array {
        $ids = [
            (int) $location->id,
        ];

        $frontier = $ids;

        while ($frontier !== []) {
            $children =
                DB::table(
                    'storage_locations'
                )
                    ->whereIn(
                        'parent_id',
                        $frontier
                    )
                    ->pluck('id')
                    ->map(
                        static fn (
                            mixed $id
                        ): int => (int) $id
                    )
                    ->all();

            $children =
                array_values(
                    array_diff(
                        $children,
                        $ids
                    )
                );

            if ($children === []) {
                break;
            }

            $ids = array_merge(
                $ids,
                $children
            );

            $frontier = $children;
        }

        return $ids;
    }

    private function effectiveWorkshopId(
        Request $request,
        int|string|null $fallback = null
    ): ?int {
        $user = $request->user();

        if (
            in_array(
                (string) $user->role,
                [
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            )
        ) {
            return $this->requiredWorkshopId(
                $user
            );
        }

        $value =
            $request->input(
                'workshop_id',
                $fallback
            );

        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return (int) $value;
    }

    private function visibleWorkshops(
        object $user
    ): Collection {
        $query = Workshop::query()
            ->withoutGlobalScopes()
            ->where(
                'is_active',
                true
            )
            ->orderBy('code');

        if (
            in_array(
                (string) $user->role,
                [
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            )
        ) {
            $query->whereKey(
                $this->requiredWorkshopId(
                    $user
                )
            );
        }

        return $query->get([
            'id',
            'code',
            'name',
        ]);
    }

    private function applyWorkshopScope(
        Builder $query,
        object $user,
        ?int $workshopId
    ): void {
        if ($workshopId !== null) {
            $query->where(
                'workshop_id',
                $workshopId
            );

            return;
        }

        if (
            (string) $user->role
                !== 'admin'
        ) {
            $query->whereRaw('1 = 0');
        }
    }

    private function authorizeViewRole(
        Request $request
    ): void {
        abort_unless(
            in_array(
                (string)
                $request->user()->role,
                [
                    'admin',
                    'kepala_bengkel',
                    'toolman',
                ],
                true
            ),
            403,
            'Anda tidak memiliki hak akses lokasi penyimpanan.'
        );
    }

    public function bulkToggleStatus(
        Request $request
    ): RedirectResponse {
        $this->authorizeManage(
            $request
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

        $count = StorageLocation::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->get()
            ->each(
                function (StorageLocation $location): void {
                    $location->fill([
                        'is_active' => ! $location->is_active,
                    ])->save();
                }
            )
            ->count();

        return back()->with(
            'success',
            "Status {$count} lokasi berhasil diubah."
        );
    }

    private function authorizeManage(
        Request $request
    ): void {
        abort_unless(
            $this->canManage(
                $request->user()
            ),
            403,
            'Lokasi penyimpanan hanya dapat dikelola Administrator, Kepala Bengkel, atau Toolman pada jurusannya.'
        );
    }

    private function authorizePrint(
        Request $request
    ): void {
        abort_unless(
            $this->canPrint(
                $request->user()
            ),
            403,
            'Daftar inventaris lokasi hanya dapat dicetak Administrator, Kepala Bengkel, atau Toolman pada jurusannya.'
        );
    }

    private function authorizeLocation(
        Request $request,
        StorageLocation $location
    ): void {
        $user = $request->user();

        if (
            (string) $user->role
                === 'admin'
        ) {
            return;
        }

        abort_unless(
            (int) $location->workshop_id
                ===
                $this->requiredWorkshopId(
                    $user
                ),
            403,
            'Lokasi tersebut berada di jurusan lain.'
        );
    }

    private function canManage(
        object $user
    ): bool {
        return in_array(
            (string) $user->role,
            [
                'admin',
                'kepala_bengkel',
                'toolman',
            ],
            true
        );
    }

    private function canPrint(
        object $user
    ): bool {
        return $this->canManage(
            $user
        );
    }

    private function requiredWorkshopId(
        object $user
    ): int {
        $value = $user->workshop_id;

        abort_if(
            $value === null
            || $value === '',
            403,
            'Akun belum mempunyai jurusan.'
        );

        return (int) $value;
    }
}
