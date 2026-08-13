<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use App\Models\Workshop;
use App\Services\WorkshopDirectoryService;
use App\Traits\SortsIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkshopController extends Controller
{
    use SortsIndex;

    public function __construct(
        private readonly
            WorkshopDirectoryService
            $directory
    ) {
    }

    public function index(
        Request $request
    ): View {
        $this->authorizeAdmin(
            $request
        );

        [$sort, $direction, $perPage] = $this->indexSortParams(['code', 'name', 'is_active']);

        $query = Workshop::query()
            ->withoutGlobalScopes();

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

        if (
            $request->filled('status')
            && in_array(
                $request->input(
                    'status'
                ),
                [
                    'active',
                    'inactive',
                ],
                true
            )
        ) {
            $query->where(
                'is_active',
                $request->input(
                    'status'
                ) === 'active'
            );
        }

        $this->addCountColumns(
            $query
        );

        $workshops = $query
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->when($sort !== null, fn ($q) => $q->orderBy($sort, $direction))
            ->paginate($perPage)
            ->withQueryString();

        $summary = [
            'total' =>
                Workshop::query()
                    ->withoutGlobalScopes()
                    ->count(),

            'active' =>
                Workshop::query()
                    ->withoutGlobalScopes()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'inactive' =>
                Workshop::query()
                    ->withoutGlobalScopes()
                    ->where(
                        'is_active',
                        false
                    )
                    ->count(),

            'without_toolman' =>
                $this->countWithoutRole(
                    'toolman'
                ),

            'without_head' =>
                $this->countWithoutRole(
                    'kepala_bengkel'
                ),
        ];

        return view(
            'workshops.index',
            compact(
                'workshops',
                'summary',
                'sort',
                'direction',
                'perPage'
            )
        );
    }

    public function create(
        Request $request
    ): View {
        $this->authorizeAdmin(
            $request
        );

        return view(
            'workshops.create'
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $this->authorizeAdmin(
            $request
        );

        $data = $this->validatedData(
            $request
        );

        $createRootLocation =
            $request->boolean(
                'create_root_location'
            );

        $workshop = DB::transaction(
            function () use (
                $data,
                $createRootLocation
            ): Workshop {
                $workshop =
                    Workshop::query()
                        ->withoutGlobalScopes()
                        ->create($data);

                if (
                    $createRootLocation
                    && Schema::hasTable(
                        'storage_locations'
                    )
                ) {
                    StorageLocation::query()
                        ->withoutGlobalScopes()
                        ->create([
                            'workshop_id' =>
                                $workshop->id,

                            'parent_id' =>
                                null,

                            'code' =>
                                $this
                                    ->rootLocationCode(
                                        $workshop
                                    ),

                            'name' =>
                                'Ruang Utama '.
                                $workshop->code,

                            'type' =>
                                'room',

                            'description' =>
                                'Lokasi utama otomatis untuk '.
                                $workshop->name.
                                '.',

                            'is_active' =>
                                true,
                        ]);
                }

                return $workshop;
            }
        );

        return redirect()
            ->route(
                'workshops.index'
            )
            ->with(
                'success',
                'Jurusan '.
                $workshop->display_name.
                ' berhasil ditambahkan. Jurusan baru langsung tersedia pada pilihan data pengguna, inventaris, lokasi, siswa, laporan, dan peminjaman yang membaca tabel workshops.'
            );
    }

    public function edit(
        Request $request,
        Workshop $workshop
    ): View {
        $this->authorizeAdmin(
            $request
        );

        $workshop =
            Workshop::query()
                ->withoutGlobalScopes()
                ->findOrFail(
                    $workshop->id
                );

        $references =
            $this->referenceCounts(
                $workshop
            );

        return view(
            'workshops.edit',
            compact(
                'workshop',
                'references'
            )
        );
    }

    public function update(
        Request $request,
        Workshop $workshop
    ): RedirectResponse {
        $this->authorizeAdmin(
            $request
        );

        $workshop =
            Workshop::query()
                ->withoutGlobalScopes()
                ->findOrFail(
                    $workshop->id
                );

        $data = $this->validatedData(
            $request,
            $workshop
        );

        $newCode = strtoupper(
            trim(
                (string) $data['code']
            )
        );

        if (
            $newCode !==
                (string) $workshop->code
            && $this->referenceTotal(
                $workshop
            ) > 0
        ) {
            throw ValidationException::
                withMessages([
                    'code' =>
                        'Kode jurusan tidak dapat diubah karena jurusan sudah digunakan oleh pengguna, barang, unit alat, lokasi, siswa, atau peminjaman. Nama dan deskripsi tetap dapat diubah.',
                ]);
        }

        $workshop->update($data);

        return redirect()
            ->route(
                'workshops.index'
            )
            ->with(
                'success',
                'Data jurusan berhasil diperbarui.'
            );
    }

    public function toggleStatus(
        Request $request,
        Workshop $workshop
    ): RedirectResponse {
        $this->authorizeAdmin(
            $request
        );

        $workshop =
            Workshop::query()
                ->withoutGlobalScopes()
                ->findOrFail(
                    $workshop->id
                );

        $willDeactivate =
            (bool) $workshop->is_active;

        if (
            $willDeactivate
            && $this
                ->activeAssignedUserCount(
                    $workshop
                ) > 0
        ) {
            return back()->with(
                'warning',
                'Jurusan tidak dapat dinonaktifkan karena masih mempunyai pengguna aktif. Pindahkan atau nonaktifkan Kepala Bengkel, Toolman, dan Siswa terlebih dahulu.'
            );
        }

        $workshop->fill([
            'is_active' =>
                ! (bool)
                $workshop->is_active,
        ])->save();

        return back()->with(
            'success',
            'Status jurusan berhasil diubah menjadi '.
            (
                $workshop->is_active
                    ? 'aktif.'
                    : 'nonaktif.'
            )
        );
    }

    public function destroy(
        Request $request,
        Workshop $workshop
    ): RedirectResponse {
        $this->authorizeAdmin(
            $request
        );

        $workshop =
            Workshop::query()
                ->withoutGlobalScopes()
                ->findOrFail(
                    $workshop->id
                );

        $references =
            $this->referenceCounts(
                $workshop
            );

        if (
            array_sum(
                $references
            ) > 0
        ) {
            return back()->with(
                'warning',
                'Jurusan tidak dapat dihapus karena masih dipakai oleh data lain. Gunakan status Nonaktif setelah semua pengguna dipindahkan.'
            );
        }

        $label =
            $workshop->display_name;

        $workshop->delete();

        return redirect()
            ->route(
                'workshops.index'
            )
            ->with(
                'success',
                'Jurusan '.
                $label.
                ' berhasil dihapus.'
            );
    }

    private function validatedData(
        Request $request,
        ?Workshop $workshop = null
    ): array {
        $validated =
            $request->validate([
                'code' => [
                    'required',
                    'string',
                    'min:2',
                    'max:20',
                    'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                    Rule::unique(
                        'workshops',
                        'code'
                    )->ignore(
                        $workshop?->id
                    ),
                ],

                'name' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique(
                        'workshops',
                        'name'
                    )->ignore(
                        $workshop?->id
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

                'create_root_location' => [
                    'nullable',
                    'boolean',
                ],
            ], [
                'code.regex' =>
                    'Kode jurusan hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah.',

                'code.unique' =>
                    'Kode jurusan sudah digunakan.',

                'name.unique' =>
                    'Nama jurusan sudah digunakan.',
            ]);

        return [
            'code' =>
                $this->directory
                    ->normalizeCode(
                        $validated['code']
                    ),

            'name' =>
                trim(
                    (string)
                    $validated['name']
                ),

            'description' =>
                $request->filled(
                    'description'
                )
                    ? trim(
                        (string)
                        $validated[
                            'description'
                        ]
                    )
                    : null,

            'is_active' =>
                $request->boolean(
                    'is_active',
                    true
                ),
        ];
    }

    private function addCountColumns(
        Builder $query
    ): void {
        /*
         * selectSub() akan membuat daftar SELECT sendiri ketika
         * query belum mempunyai kolom terpilih. Tanpa baris ini,
         * MySQL hanya mengembalikan kolom *_count dan tidak
         * mengembalikan workshops.id, code, name, serta is_active.
         *
         * Akibatnya model Workshop tidak memiliki route key dan
         * route('workshops.edit', $workshop) gagal menghasilkan URL.
         */
        $query->select(
            'workshops.*'
        );

        $map = [
            'users_count' => [
                'table' => 'users',
                'column' => 'workshop_id',
            ],

            'locations_count' => [
                'table' => 'storage_locations',
                'column' => 'workshop_id',
            ],

            'items_count' => [
                'table' => 'item_stock_movements',
                'column' => 'workshop_id',
                'distinct' => 'item_id',
            ],

            'assets_count' => [
                'table' => 'item_assets',
                'column' => 'workshop_id',
            ],

            'students_count' => [
                'table' => 'students',
                'column' => 'workshop_id',
            ],
        ];

        foreach (
            $map
            as $alias => $definition
        ) {
            if (
                ! Schema::hasTable(
                    $definition['table']
                )
                || ! Schema::hasColumn(
                    $definition['table'],
                    $definition['column']
                )
            ) {
                $query->selectRaw(
                    "0 AS {$alias}"
                );

                continue;
            }

            $query->selectSub(
                DB::table($definition['table'])
                    ->selectRaw(
                        isset($definition['distinct'])
                            ? 'COUNT(DISTINCT '.$definition['distinct'].')'
                            : 'COUNT(*)'
                    )
                    ->whereColumn(
                        $definition['table'].
                        '.'.
                        $definition['column'],
                        'workshops.id'
                    ),
                $alias
            );
        }

        foreach (
            [
                'kepala_bengkel' =>
                    'heads_count',

                'toolman' =>
                    'toolmen_count',
            ]
            as $role => $alias
        ) {
            if (
                ! Schema::hasTable(
                    'users'
                )
                || ! Schema::hasColumn(
                    'users',
                    'workshop_id'
                )
                || ! Schema::hasColumn(
                    'users',
                    'role'
                )
            ) {
                $query->selectRaw(
                    "0 AS {$alias}"
                );

                continue;
            }

            $query->selectSub(
                DB::table('users')
                    ->selectRaw(
                        'COUNT(*)'
                    )
                    ->whereColumn(
                        'users.workshop_id',
                        'workshops.id'
                    )
                    ->where(
                        'users.role',
                        $role
                    ),
                $alias
            );
        }
    }

    private function referenceCounts(
        Workshop $workshop
    ): array {
        $references = [];

        $tables = [
            'users',
            'storage_locations',
            'items',
            'item_assets',
            'students',
            'loans',
        ];

        foreach ($tables as $table) {
            if (
                ! Schema::hasTable(
                    $table
                )
                || ! Schema::hasColumn(
                    $table,
                    'workshop_id'
                )
            ) {
                $references[$table] = 0;

                continue;
            }

            $references[$table] =
                DB::table($table)
                    ->where(
                        'workshop_id',
                        $workshop->id
                    )
                    ->count();
        }

        return $references;
    }

    private function referenceTotal(
        Workshop $workshop
    ): int {
        return array_sum(
            $this->referenceCounts(
                $workshop
            )
        );
    }

    private function activeAssignedUserCount(
        Workshop $workshop
    ): int {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return 0;
        }

        $query = DB::table('users')
            ->where(
                'workshop_id',
                $workshop->id
            );

        if (
            Schema::hasColumn(
                'users',
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        return $query->count();
    }

    private function countWithoutRole(
        string $role
    ): int {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
            || ! Schema::hasColumn(
                'users',
                'role'
            )
        ) {
            return Workshop::query()
                ->withoutGlobalScopes()
                ->where(
                    'is_active',
                    true
                )
                ->count();
        }

        return Workshop::query()
            ->withoutGlobalScopes()
            ->where(
                'is_active',
                true
            )
            ->whereNotExists(
                function (
                    $query
                ) use ($role): void {
                    $query
                        ->selectRaw('1')
                        ->from('users')
                        ->whereColumn(
                            'users.workshop_id',
                            'workshops.id'
                        )
                        ->where(
                            'users.role',
                            $role
                        );

                    if (
                        Schema::hasColumn(
                            'users',
                            'is_active'
                        )
                    ) {
                        $query->where(
                            'users.is_active',
                            true
                        );
                    }
                }
            )
            ->count();
    }

    private function rootLocationCode(
        Workshop $workshop
    ): string {
        $base =
            $workshop->code.
            '-R01';

        $code = $base;
        $sequence = 1;

        while (
            DB::table(
                'storage_locations'
            )
                ->where(
                    'code',
                    $code
                )
                ->exists()
        ) {
            $sequence++;

            $code =
                $base.
                '-'.
                $sequence;
        }

        return $code;
    }

    public function bulkToggleStatus(
        Request $request
    ): RedirectResponse {
        $this->authorizeAdmin(
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

        $count = Workshop::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->get()
            ->each(
                function (Workshop $workshop): void {
                    $workshop->fill([
                        'is_active' => ! $workshop->is_active,
                    ])->save();
                }
            )
            ->count();

        return back()->with(
            'success',
            "Status {$count} jurusan berhasil diubah."
        );
    }

    private function authorizeAdmin(
        Request $request
    ): void {
        abort_unless(
            (string)
            $request->user()?->role
                === 'admin',
            403,
            'Pengelolaan jurusan hanya tersedia untuk Administrator.'
        );
    }
}
