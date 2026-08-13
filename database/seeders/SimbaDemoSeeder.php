<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SimbaDemoSeeder extends Seeder
{
    /**
     * Cache nama kolom setiap tabel agar Schema tidak dipanggil berulang.
     *
     * @var array<string, array<int, string>>
     */
    private array $tableColumns = [];

    /**
     * ID data yang dibuat oleh seeder.
     *
     * @var array<string, int>
     */
    private array $ids = [];

    private string $now;

    private string $year;

    public function run(): void
    {
        $this->now = now()->toDateTimeString();
        $this->year = now()->format('Y');

        $this->ensureRequiredTablesExist();

        DB::transaction(function (): void {
            $this->seedRoles();
            $this->seedWorkshops();
            $this->seedUsers();

            if ($this->tableExists('students')) {
                $this->seedStudents();
            }

            $this->seedLocations();
            $this->seedCategories();
            $this->seedUnits();
            $this->seedItems();
            $this->syncItemCodeSequences();

            if ($this->tableExists('item_assets')) {
                $this->seedItemAssets();
            }

            if ($this->tableExists('item_stock_movements')) {
                $this->seedStockMovements();
            }

            if (
                $this->tableExists('loans')
                && $this->tableExists('loan_items')
            ) {
                $this->seedLoans();
            }

            if ($this->tableExists('damage_reports')) {
                $this->seedDamageReports();
            }

            if ($this->tableExists('audit_logs')) {
                $this->seedAuditLogs();
            }
        });

        $this->command?->newLine();
        $this->command?->info('Dummy data SIMBA berhasil dibuat.');

        foreach ([
            'users',
            'roles',
            'workshops',
            'students',
            'storage_locations',
            'item_categories',
            'units',
            'items',
            'item_assets',
            'item_stock_movements',
            'loans',
            'loan_items',
            'damage_reports',
            'audit_logs',
        ] as $table) {
            if ($this->tableExists($table)) {
                $this->command?->line(
                    sprintf(
                        '- %-24s %d baris',
                        $table,
                        DB::table($table)->count()
                    )
                );
            }
        }

        $this->command?->newLine();

        foreach ($this->demoAccounts() as $account) {
            $this->command?->line(
                sprintf(
                    '- %-16s %-28s username: %-10s password: %s',
                    $account['role'],
                    $account['email'],
                    $account['username'],
                    $account['password']
                )
            );
        }
    }

    private function ensureRequiredTablesExist(): void
    {
        $requiredTables = [
            'users',
            'workshops',
            'storage_locations',
            'item_categories',
            'units',
            'items',
        ];

        $missingTables = array_values(array_filter(
            $requiredTables,
            fn (string $table): bool => ! $this->tableExists($table)
        ));

        if ($missingTables !== []) {
            throw new RuntimeException(
                'Seeder dihentikan. Tabel berikut belum tersedia: '
                .implode(', ', $missingTables)
                .'. Jalankan migration terlebih dahulu.'
            );
        }
    }

    /**
     * Akun demo dibuat dinamis dari daftar jurusan pada
     * config/simba_seed.php.
     *
     * @return array<int, array<string, mixed>>
     */
    private function demoAccounts(): array
    {
        $accounts = [
            [
                'key' => 'admin',
                'role' => 'admin',
                'name' => 'Administrator SIMBA',
                'username' => 'admin',
                'email' => 'admin@simba.local',
                'password' => 'Admin123!',
                'workshop_key' => null,
            ],
            [
                'key' => 'guru',
                'role' => 'guru',
                'name' => 'Guru Produktif SIMBA',
                'username' => 'guru',
                'email' => 'guru@simba.local',
                'password' => 'Password123!',
                'workshop_key' => null,
            ],
        ];

        $defaultPassword = (string) config(
            'simba_seed.default_password',
            'Password123!'
        );

        foreach (
            $this->workshopDefinitions()
            as $key => $workshop
        ) {
            $code = strtoupper(
                (string) $workshop['code']
            );

            $slug = strtolower(
                preg_replace(
                    '/[^a-z0-9]+/i',
                    '_',
                    $code
                )
            );

            $accounts[] = [
                'key' => 'kepala_bengkel_'.$key,
                'role' => 'kepala_bengkel',
                'name' => 'Kepala Bengkel '.$code,
                'username' => 'kabeng_'.$slug,
                'email' => 'kabeng.'.$slug.'@simba.local',
                'password' => $defaultPassword,
                'workshop_key' => $key,
            ];

            $accounts[] = [
                'key' => 'toolman_'.$key,
                'role' => 'toolman',
                'name' => 'Toolman '.$code,
                'username' => 'toolman_'.$slug,
                'email' => 'toolman.'.$slug.'@simba.local',
                'password' => $defaultPassword,
                'workshop_key' => $key,
            ];

            /*
             * Satu akun siswa aktif per jurusan untuk pengujian
             * pembatasan data dan peminjaman.
             */
            $accounts[] = [
                'key' => 'siswa_'.$key,
                'role' => 'siswa',
                'name' => 'Siswa '.$code,
                'username' => 'siswa_'.$slug,
                'email' => 'siswa.'.$slug.'@simba.local',
                'password' => $defaultPassword,
                'workshop_key' => $key,
            ];
        }

        return $accounts;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function workshopDefinitions(): array
    {
        $workshops = config(
            'simba_seed.workshops',
            []
        );

        if (
            ! is_array($workshops)
            || $workshops === []
        ) {
            throw new RuntimeException(
                'Daftar jurusan config/simba_seed.php kosong.'
            );
        }

        return $workshops;
    }

    private function seedRoles(): void
    {
        if (! $this->tableExists('roles')) {
            return;
        }

        $roles = [
            'admin' => [
                'label' => 'Administrator',
                'description' => 'Akses penuh ke seluruh modul SIMBA.',
            ],
            'kepala_bengkel' => [
                'label' => 'Kepala Bengkel',
                'description' => 'Memantau inventaris, transaksi, laporan, dan audit.',
            ],
            'toolman' => [
                'label' => 'Toolman',
                'description' => 'Mengelola master inventaris dan transaksi bengkel.',
            ],
            'guru' => [
                'label' => 'Guru',
                'description' => 'Melihat inventaris dan mengajukan peminjaman.',
            ],
            'siswa' => [
                'label' => 'Siswa',
                'description' => 'Melihat inventaris dan mengajukan peminjaman.',
            ],
        ];

        $columns = $this->columnsFor('roles');

        $identityColumn = $this->firstExistingColumn(
            $columns,
            ['name', 'slug', 'code', 'key']
        );

        if ($identityColumn === null) {
            $this->command?->warn(
                'Tabel roles ditemukan, tetapi kolom identitas role tidak dikenali.'
            );

            return;
        }

        foreach ($roles as $role => $data) {
            $identityValue = $identityColumn === 'code'
                ? strtoupper($role)
                : $role;

            $this->ids['role_'.$role] = $this->upsertAndGetId(
                'roles',
                [$identityColumn => $identityValue],
                [
                    'name' => $role,
                    'slug' => $role,
                    'code' => strtoupper($role),
                    'key' => $role,
                    'label' => $data['label'],
                    'display_name' => $data['label'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedUsers(): void
    {
        foreach ($this->demoAccounts() as $account) {
            $workshopKey =
                $account['workshop_key'];

            $workshopId =
                $workshopKey !== null
                    ? $this->ids[
                        'workshop_'.
                        $workshopKey
                    ]
                    : null;

            $id = $this->upsertAndGetId(
                'users',
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'role' => $account['role'],
                    'role_id' =>
                        $this->ids[
                            'role_'.
                            $account['role']
                        ]
                        ?? null,

                    'workshop_id' =>
                        $workshopId,

                    'email_verified_at' =>
                        $this->now,

                    'password' =>
                        Hash::make(
                            $account['password']
                        ),

                    'is_active' => true,
                ]
            );

            $this->ids[
                'user_'.$account['key']
            ] = $id;

            /*
             * Alias role pertama dipertahankan agar data demo lama
             * yang memakai user_toolman/user_siswa tetap kompatibel.
             */
            $genericKey =
                'user_'.$account['role'];

            if (
                ! isset(
                    $this->ids[$genericKey]
                )
            ) {
                $this->ids[$genericKey] =
                    $id;
            }

            if (
                $workshopId !== null
            ) {
                $this->ids[
                    'user_'.
                    $account['role'].
                    '_workshop_'.
                    $workshopId
                ] = $id;
            }
        }

        /*
         * Hubungkan field penanggung jawab pada tabel workshops,
         * bila migration project menyediakan kolom tersebut.
         */
        foreach (
            $this->workshopDefinitions()
            as $key => $workshop
        ) {
            $workshopId =
                $this->ids[
                    'workshop_'.$key
                ];

            $headId =
                $this->ids[
                    'user_kepala_bengkel_'.
                    $key
                ];

            $values = $this->filterColumns(
                'workshops',
                [
                    'manager_id' =>
                        $headId,

                    'head_id' =>
                        $headId,

                    'head_user_id' =>
                        $headId,

                    'responsible_user_id' =>
                        $headId,
                ]
            );

            if ($values !== []) {
                DB::table('workshops')
                    ->where(
                        'id',
                        $workshopId
                    )
                    ->update(
                        $this->withUpdatedAt(
                            'workshops',
                            $values
                        )
                    );
            }
        }
    }

    private function seedStudents(): void
    {
        $schoolYear =
            now()->year.
            '/'.
            (now()->year + 1);

        $index = 1;

        foreach (
            $this->workshopDefinitions()
            as $key => $workshop
        ) {
            $code = strtoupper(
                (string)
                $workshop['code']
            );

            $userId =
                $this->ids[
                    'user_siswa_'.$key
                ];

            $registeredNisn =
                sprintf(
                    '009%07d',
                    $index
                );

            $this->ids[
                'student_'.$key
            ] = $this->upsertAndGetId(
                'students',
                [
                    'nisn' =>
                        $registeredNisn,
                ],
                [
                    'nis' =>
                        sprintf(
                            '%d%03d',
                            now()->year,
                            $index
                        ),

                    'name' =>
                        'Siswa '.$code,

                    'nama' =>
                        'Siswa '.$code,

                    'workshop_id' =>
                        $this->ids[
                            'workshop_'.$key
                        ],

                    'class_name' =>
                        'XI '.$code.' 1',

                    'kelas' =>
                        'XI '.$code.' 1',

                    'gender' =>
                        $index % 2 === 0
                            ? 'P'
                            : 'L',

                    'jenis_kelamin' =>
                        $index % 2 === 0
                            ? 'P'
                            : 'L',

                    'birth_date' =>
                        now()
                            ->subYears(17)
                            ->subDays($index)
                            ->toDateString(),

                    'tanggal_lahir' =>
                        now()
                            ->subYears(17)
                            ->subDays($index)
                            ->toDateString(),

                    'email' =>
                        'siswa.'.
                        strtolower($code).
                        '@simba.local',

                    'phone' =>
                        '081200000'.
                        sprintf(
                            '%03d',
                            $index
                        ),

                    'telepon' =>
                        '081200000'.
                        sprintf(
                            '%03d',
                            $index
                        ),

                    'school_year' =>
                        $schoolYear,

                    'tahun_ajaran' =>
                        $schoolYear,

                    'user_id' =>
                        $userId,

                    'registered_at' =>
                        $this->now,

                    'is_active' =>
                        true,
                ]
            );

            /*
             * Data siswa kedua belum memiliki user_id agar halaman
             * registrasi NISN dapat langsung diuji.
             */
            $pendingNisn =
                sprintf(
                    '008%07d',
                    $index
                );

            $this->upsertAndGetId(
                'students',
                [
                    'nisn' =>
                        $pendingNisn,
                ],
                [
                    'nis' =>
                        sprintf(
                            '%d%03d',
                            now()->year,
                            $index + 100
                        ),

                    'name' =>
                        'Calon Akun Siswa '.$code,

                    'nama' =>
                        'Calon Akun Siswa '.$code,

                    'workshop_id' =>
                        $this->ids[
                            'workshop_'.$key
                        ],

                    'class_name' =>
                        'X '.$code.' 1',

                    'kelas' =>
                        'X '.$code.' 1',

                    'gender' =>
                        'L',

                    'jenis_kelamin' =>
                        'L',

                    'birth_date' =>
                        now()
                            ->subYears(16)
                            ->subDays($index)
                            ->toDateString(),

                    'tanggal_lahir' =>
                        now()
                            ->subYears(16)
                            ->subDays($index)
                            ->toDateString(),

                    'email' =>
                        null,

                    'phone' =>
                        null,

                    'telepon' =>
                        null,

                    'school_year' =>
                        $schoolYear,

                    'tahun_ajaran' =>
                        $schoolYear,

                    'user_id' =>
                        null,

                    'registered_at' =>
                        null,

                    'is_active' =>
                        true,
                ]
            );

            $index++;
        }
    }

    private function seedWorkshops(): void
    {
        foreach (
            $this->workshopDefinitions()
            as $key => $workshop
        ) {
            $this->ids[
                'workshop_'.$key
            ] = $this->upsertAndGetId(
                'workshops',
                [
                    'code' =>
                        strtoupper(
                            trim(
                                (string)
                                $workshop['code']
                            )
                        ),
                ],
                [
                    'name' =>
                        trim(
                            (string)
                            $workshop['name']
                        ),

                    'description' =>
                        $workshop[
                            'description'
                        ]
                        ?? null,

                    'is_active' =>
                        (bool) (
                            $workshop[
                                'is_active'
                            ]
                            ?? true
                        ),
                ]
            );
        }
    }

    private function seedLocations(): void
    {
        $locations = [
            [
                'key' => 'tkr_room',
                'workshop' => 'tkr',
                'parent' => null,
                'code' => 'TKR-R01',
                'name' => 'Ruang Alat TKR',
                'type' => 'room',
            ],
            [
                'key' => 'tkr_cabinet_a',
                'workshop' => 'tkr',
                'parent' => 'tkr_room',
                'code' => 'TKR-LA',
                'name' => 'Lemari Alat A',
                'type' => 'cabinet',
            ],
            [
                'key' => 'tkr_shelf_a1',
                'workshop' => 'tkr',
                'parent' => 'tkr_cabinet_a',
                'code' => 'TKR-LA-R1',
                'name' => 'Rak A1',
                'type' => 'shelf',
            ],
            [
                'key' => 'tkr_shelf_a2',
                'workshop' => 'tkr',
                'parent' => 'tkr_cabinet_a',
                'code' => 'TKR-LA-R2',
                'name' => 'Rak A2',
                'type' => 'shelf',
            ],
            [
                'key' => 'tkr_cabinet_b',
                'workshop' => 'tkr',
                'parent' => 'tkr_room',
                'code' => 'TKR-LB',
                'name' => 'Lemari Bahan B',
                'type' => 'cabinet',
            ],
            [
                'key' => 'tkr_shelf_b1',
                'workshop' => 'tkr',
                'parent' => 'tkr_cabinet_b',
                'code' => 'TKR-LB-R1',
                'name' => 'Rak Bahan B1',
                'type' => 'shelf',
            ],
            [
                'key' => 'tsm_room',
                'workshop' => 'tsm',
                'parent' => null,
                'code' => 'TSM-R01',
                'name' => 'Ruang Alat TSM',
                'type' => 'room',
            ],
            [
                'key' => 'tsm_cabinet_a',
                'workshop' => 'tsm',
                'parent' => 'tsm_room',
                'code' => 'TSM-LA',
                'name' => 'Lemari Utama TSM',
                'type' => 'cabinet',
            ],
            [
                'key' => 'tsm_shelf_a1',
                'workshop' => 'tsm',
                'parent' => 'tsm_cabinet_a',
                'code' => 'TSM-LA-R1',
                'name' => 'Rak TSM A1',
                'type' => 'shelf',
            ],
            [
                'key' => 'tkj_room',
                'workshop' => 'tkj',
                'parent' => null,
                'code' => 'TKJ-LAB1',
                'name' => 'Laboratorium Jaringan 1',
                'type' => 'room',
            ],
            [
                'key' => 'tkj_cabinet_a',
                'workshop' => 'tkj',
                'parent' => 'tkj_room',
                'code' => 'TKJ-LA',
                'name' => 'Lemari Perangkat Jaringan',
                'type' => 'cabinet',
            ],
            [
                'key' => 'tkj_shelf_a1',
                'workshop' => 'tkj',
                'parent' => 'tkj_cabinet_a',
                'code' => 'TKJ-LA-R1',
                'name' => 'Rak Komponen Jaringan',
                'type' => 'shelf',
            ],
        ];

        foreach ($locations as $location) {
            $parentId = $location['parent'] === null
                ? null
                : $this->ids['location_'.$location['parent']];

            $this->ids['location_'.$location['key']] = $this->upsertAndGetId(
                'storage_locations',
                ['code' => $location['code']],
                [
                    'workshop_id' => $this->ids['workshop_'.$location['workshop']],
                    'parent_id' => $parentId,
                    'name' => $location['name'],
                    'type' => $location['type'],
                    'description' => 'Lokasi dummy untuk pengujian SIMBA.',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            'hand_tools' => [
                'code' => 'ALT-TANGAN',
                'name' => 'Alat Tangan',
                'type' => 'tool',
            ],
            'measuring_tools' => [
                'code' => 'ALT-UKUR',
                'name' => 'Alat Ukur',
                'type' => 'tool',
            ],
            'power_tools' => [
                'code' => 'ALT-MESIN',
                'name' => 'Mesin dan Power Tools',
                'type' => 'tool',
            ],
            'diagnostic_tools' => [
                'code' => 'ALT-DIAG',
                'name' => 'Alat Diagnostik',
                'type' => 'tool',
            ],
            'network_tools' => [
                'code' => 'ALT-JARINGAN',
                'name' => 'Alat Jaringan',
                'type' => 'tool',
            ],
            'lubricants' => [
                'code' => 'BHN-PELUMAS',
                'name' => 'Pelumas',
                'type' => 'material',
            ],
            'cleaning' => [
                'code' => 'BHN-BERSIH',
                'name' => 'Bahan Pembersih',
                'type' => 'material',
            ],
            'fasteners' => [
                'code' => 'BHN-PENGIKAT',
                'name' => 'Baut dan Pengikat',
                'type' => 'material',
            ],
            'network_materials' => [
                'code' => 'BHN-JARINGAN',
                'name' => 'Bahan Jaringan',
                'type' => 'material',
            ],
            'electronics' => [
                'code' => 'BHN-ELEKTRONIK',
                'name' => 'Bahan Elektronik',
                'type' => 'material',
            ],
        ];

        foreach ($categories as $key => $category) {
            $this->ids['category_'.$key] = $this->upsertAndGetId(
                'item_categories',
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'item_type' => $category['type'],
                    'applies_to' => $category['type'],
                    'description' => 'Kategori dummy untuk pengujian SIMBA.',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedUnits(): void
    {
        $units = [
            'pcs' => [
                'code' => 'PCS',
                'name' => 'Buah',
                'symbol' => 'pcs',
                'allows_decimal' => false,
            ],
            'set' => [
                'code' => 'SET',
                'name' => 'Set',
                'symbol' => 'set',
                'allows_decimal' => false,
            ],
            'ltr' => [
                'code' => 'LTR',
                'name' => 'Liter',
                'symbol' => 'L',
                'allows_decimal' => true,
            ],
            'kg' => [
                'code' => 'KG',
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'allows_decimal' => true,
            ],
            'mtr' => [
                'code' => 'MTR',
                'name' => 'Meter',
                'symbol' => 'm',
                'allows_decimal' => true,
            ],
            'box' => [
                'code' => 'BOX',
                'name' => 'Kotak',
                'symbol' => 'box',
                'allows_decimal' => false,
            ],
            'roll' => [
                'code' => 'ROLL',
                'name' => 'Gulungan',
                'symbol' => 'roll',
                'allows_decimal' => false,
            ],
        ];

        foreach ($units as $key => $unit) {
            $this->ids['unit_'.$key] = $this->upsertAndGetId(
                'units',
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'allows_decimal' => $unit['allows_decimal'],
                    'description' => 'Satuan dummy SIMBA.',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedItems(): void
    {
        $items = [
            [
                'key' => 'tool_wrench_set',
                'code' => "ALT-{$this->year}-0001",
                'type' => 'tool',
                'name' => 'Kunci Ring Pas Set',
                'category' => 'hand_tools',
                'unit' => 'set',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a1',
                'brand' => 'Tekiro',
                'model' => '8-24 mm',
                'serial_number' => 'TKR-KRP-001',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 850000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_screwdriver_set',
                'code' => "ALT-{$this->year}-0002",
                'type' => 'tool',
                'name' => 'Obeng Plus Minus Set',
                'category' => 'hand_tools',
                'unit' => 'set',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a1',
                'brand' => 'Stanley',
                'model' => '12 Pieces',
                'serial_number' => 'TKR-OBG-002',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 475000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_multimeter',
                'code' => "ALT-{$this->year}-0003",
                'type' => 'tool',
                'name' => 'Multimeter Digital',
                'category' => 'measuring_tools',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a2',
                'brand' => 'Sanwa',
                'model' => 'CD800a',
                'serial_number' => 'SN-CD800A-003',
                'condition' => 'good',
                'status' => 'borrowed',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 1250000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_torque_wrench',
                'code' => "ALT-{$this->year}-0004",
                'type' => 'tool',
                'name' => 'Torque Wrench',
                'category' => 'measuring_tools',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a2',
                'brand' => 'Tekiro',
                'model' => '20-110 Nm',
                'serial_number' => 'TKR-TW-004',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 1450000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_compression_tester',
                'code' => "ALT-{$this->year}-0005",
                'type' => 'tool',
                'name' => 'Compression Tester',
                'category' => 'diagnostic_tools',
                'unit' => 'set',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a2',
                'brand' => 'JTC',
                'model' => 'Gasoline Engine',
                'serial_number' => 'TKR-CT-005',
                'condition' => 'good',
                'status' => 'borrowed',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 1950000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_floor_jack',
                'code' => "ALT-{$this->year}-0006",
                'type' => 'tool',
                'name' => 'Dongkrak Buaya 3 Ton',
                'category' => 'power_tools',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_room',
                'brand' => 'Krisbow',
                'model' => '3 Ton',
                'serial_number' => 'TKR-DB-006',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 2350000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_grinder',
                'code' => "ALT-{$this->year}-0007",
                'type' => 'tool',
                'name' => 'Gerinda Tangan',
                'category' => 'power_tools',
                'unit' => 'pcs',
                'workshop' => 'tsm',
                'location' => 'tsm_shelf_a1',
                'brand' => 'Bosch',
                'model' => 'GWS 060',
                'serial_number' => 'TSM-GRD-007',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 925000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_drill',
                'code' => "ALT-{$this->year}-0008",
                'type' => 'tool',
                'name' => 'Bor Tangan Listrik',
                'category' => 'power_tools',
                'unit' => 'pcs',
                'workshop' => 'tsm',
                'location' => 'tsm_shelf_a1',
                'brand' => 'Makita',
                'model' => 'M0801B',
                'serial_number' => 'TSM-BOR-008',
                'condition' => 'minor_damage',
                'status' => 'damaged',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 1150000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'tool_welder',
                'code' => "ALT-{$this->year}-0009",
                'type' => 'tool',
                'name' => 'Mesin Las Inverter',
                'category' => 'power_tools',
                'unit' => 'pcs',
                'workshop' => 'tsm',
                'location' => 'tsm_room',
                'brand' => 'Lakoni',
                'model' => 'Falcon 120e',
                'serial_number' => 'TSM-LAS-009',
                'condition' => 'maintenance',
                'status' => 'maintenance',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 1850000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'tool_obd_scanner',
                'code' => "ALT-{$this->year}-0010",
                'type' => 'tool',
                'name' => 'Scanner OBD-II',
                'category' => 'diagnostic_tools',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a2',
                'brand' => 'Launch',
                'model' => 'CRP123',
                'serial_number' => 'TKR-OBD-010',
                'condition' => 'unfit',
                'status' => 'damaged',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 4250000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'tool_combination_pliers',
                'code' => "ALT-{$this->year}-0011",
                'type' => 'tool',
                'name' => 'Tang Kombinasi',
                'category' => 'hand_tools',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a1',
                'brand' => 'Knipex',
                'model' => '180 mm',
                'serial_number' => 'TKR-TK-011',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 575000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_caliper',
                'code' => "ALT-{$this->year}-0012",
                'type' => 'tool',
                'name' => 'Jangka Sorong Digital',
                'category' => 'measuring_tools',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_a2',
                'brand' => 'Mitutoyo',
                'model' => '150 mm',
                'serial_number' => 'TKR-JS-012',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 1,
                'minimum_stock' => 0,
                'unit_price' => 2350000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_crimping',
                'code' => "ALT-{$this->year}-0013",
                'type' => 'tool',
                'name' => 'Tang Crimping RJ45',
                'category' => 'network_tools',
                'unit' => 'pcs',
                'workshop' => 'tkj',
                'location' => 'tkj_shelf_a1',
                'brand' => 'Proskit',
                'model' => '8P8C',
                'serial_number' => 'TKJ-CRP-013',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 10,
                'minimum_stock' => 0,
                'unit_price' => 425000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'tool_lan_tester',
                'code' => "ALT-{$this->year}-0014",
                'type' => 'tool',
                'name' => 'LAN Cable Tester',
                'category' => 'network_tools',
                'unit' => 'pcs',
                'workshop' => 'tkj',
                'location' => 'tkj_shelf_a1',
                'brand' => 'Noyafa',
                'model' => 'NF-468',
                'serial_number' => 'TKJ-LAN-014',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 3,
                'minimum_stock' => 0,
                'unit_price' => 650000,
                'is_borrowable' => true,
            ],
            [
                'key' => 'material_engine_oil',
                'code' => "BHN-{$this->year}-0001",
                'type' => 'material',
                'name' => 'Oli Mesin 15W-40',
                'category' => 'lubricants',
                'unit' => 'ltr',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_b1',
                'brand' => 'Pertamina',
                'model' => 'Mesran',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 18,
                'minimum_stock' => 10,
                'unit_price' => 85000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_grease',
                'code' => "BHN-{$this->year}-0002",
                'type' => 'material',
                'name' => 'Grease Serbaguna',
                'category' => 'lubricants',
                'unit' => 'kg',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_b1',
                'brand' => 'Shell',
                'model' => 'Gadus',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 4,
                'minimum_stock' => 5,
                'unit_price' => 145000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_rags',
                'code' => "BHN-{$this->year}-0003",
                'type' => 'material',
                'name' => 'Kain Majun',
                'category' => 'cleaning',
                'unit' => 'kg',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_b1',
                'brand' => null,
                'model' => null,
                'condition' => 'good',
                'status' => 'available',
                'stock' => 25,
                'minimum_stock' => 20,
                'unit_price' => 18000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_bolt_m8',
                'code' => "BHN-{$this->year}-0004",
                'type' => 'material',
                'name' => 'Baut M8 x 30 mm',
                'category' => 'fasteners',
                'unit' => 'pcs',
                'workshop' => 'tkr',
                'location' => 'tkr_shelf_b1',
                'brand' => null,
                'model' => 'M8x30',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 8,
                'minimum_stock' => 15,
                'unit_price' => 2500,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_utp',
                'code' => "BHN-{$this->year}-0005",
                'type' => 'material',
                'name' => 'Kabel UTP Cat6',
                'category' => 'network_materials',
                'unit' => 'mtr',
                'workshop' => 'tkj',
                'location' => 'tkj_shelf_a1',
                'brand' => 'Belden',
                'model' => 'Cat6',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 120,
                'minimum_stock' => 50,
                'unit_price' => 8500,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_rj45',
                'code' => "BHN-{$this->year}-0006",
                'type' => 'material',
                'name' => 'Konektor RJ45',
                'category' => 'network_materials',
                'unit' => 'pcs',
                'workshop' => 'tkj',
                'location' => 'tkj_shelf_a1',
                'brand' => 'AMP',
                'model' => 'Cat6',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 40,
                'minimum_stock' => 25,
                'unit_price' => 3500,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_solder',
                'code' => "BHN-{$this->year}-0007",
                'type' => 'material',
                'name' => 'Timah Solder',
                'category' => 'electronics',
                'unit' => 'roll',
                'workshop' => 'tkj',
                'location' => 'tkj_shelf_a1',
                'brand' => 'Paragon',
                'model' => '0.8 mm',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 2,
                'minimum_stock' => 3,
                'unit_price' => 95000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_cleaner',
                'code' => "BHN-{$this->year}-0008",
                'type' => 'material',
                'name' => 'Cairan Pembersih Komponen',
                'category' => 'cleaning',
                'unit' => 'ltr',
                'workshop' => 'tsm',
                'location' => 'tsm_shelf_a1',
                'brand' => 'Wurth',
                'model' => 'Parts Cleaner',
                'condition' => 'good',
                'status' => 'out_of_stock',
                'stock' => 0,
                'minimum_stock' => 5,
                'unit_price' => 125000,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_cable_tie',
                'code' => "BHN-{$this->year}-0009",
                'type' => 'material',
                'name' => 'Cable Tie 20 cm',
                'category' => 'network_materials',
                'unit' => 'pcs',
                'workshop' => 'tkj',
                'location' => 'tkj_shelf_a1',
                'brand' => null,
                'model' => '200 mm',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 65,
                'minimum_stock' => 30,
                'unit_price' => 800,
                'is_borrowable' => false,
            ],
            [
                'key' => 'material_brake_cleaner',
                'code' => "BHN-{$this->year}-0010",
                'type' => 'material',
                'name' => 'Brake Cleaner',
                'category' => 'cleaning',
                'unit' => 'pcs',
                'workshop' => 'tsm',
                'location' => 'tsm_shelf_a1',
                'brand' => 'Prestone',
                'model' => '500 ml',
                'condition' => 'good',
                'status' => 'available',
                'stock' => 12,
                'minimum_stock' => 6,
                'unit_price' => 78000,
                'is_borrowable' => false,
            ],
        ];

        foreach ($items as $item) {
            $locationId = $this->ids['location_'.$item['location']];

            $this->ids['item_'.$item['key']] = $this->upsertAndGetId(
                'items',
                ['code' => $item['code']],
                [
                    'type' => $item['type'],
                    'workshop_id' => $this->ids['workshop_'.$item['workshop']],
                    'item_category_id' => $this->ids['category_'.$item['category']],
                    'unit_id' => $this->ids['unit_'.$item['unit']],
                    'storage_location_id' => $locationId,
                    'location_id' => $locationId,
                    'name' => $item['name'],
                    'brand' => $item['brand'],
                    'model' => $item['model'],
                    'serial_number' => $item['serial_number'] ?? null,
                    'specification' => 'Spesifikasi dummy untuk pengujian modul SIMBA.',
                    'description' => 'Data dummy untuk pengujian modul SIMBA.',
                    'condition' => $item['condition'],
                    'status' => $item['status'],
                    'stock' => $item['stock'],
                    'minimum_stock' => $item['minimum_stock'],
                    'unit_price' => $item['unit_price'],
                    'purchase_price' => $item['unit_price'],
                    'acquisition_source' => 'Dana BOS dan bantuan sekolah',
                    'fund_source' => 'Dana BOS',
                    'received_date' => now()->subMonths(8)->toDateString(),
                    'is_borrowable' => $item['is_borrowable'],
                    'is_active' => true,
                    'notes' => 'Data dibuat oleh SimbaDemoSeeder.',
                ]
            );
        }
    }

    private function seedItemAssets(): void
    {
        $toolKeys = [
            'tool_wrench_set',
            'tool_screwdriver_set',
            'tool_multimeter',
            'tool_torque_wrench',
            'tool_compression_tester',
            'tool_floor_jack',
            'tool_grinder',
            'tool_drill',
            'tool_welder',
            'tool_obd_scanner',
            'tool_combination_pliers',
            'tool_caliper',
            'tool_crimping',
            'tool_lan_tester',
        ];

        $sequence = 1;

        foreach ($toolKeys as $key) {
            $itemId =
                $this->ids[
                    'item_'.$key
                ];

            $item = DB::table('items')
                ->where(
                    'id',
                    $itemId
                )
                ->first();

            if ($item === null) {
                continue;
            }

            $workshopCode = strtoupper(
                (string) (
                    DB::table(
                        'workshops'
                    )
                        ->where(
                            'id',
                            $item->workshop_id
                        )
                        ->value('code')
                    ?: 'SIMBA'
                )
            );

            $quantity = max(
                1,
                (int) round(
                    (float) (
                        $item->stock
                        ?? 1
                    )
                )
            );

            $assetStatus = match (
                (string) $item->status
            ) {
                'borrowed' =>
                    'borrowed',

                'damaged' =>
                    'damaged',

                'maintenance' =>
                    'under_repair',

                'lost' =>
                    'lost',

                'retired' =>
                    'retired',

                default =>
                    'available',
            };

            $assetCondition = match (
                (string) $item->condition
            ) {
                'minor_damage',
                'maintenance' =>
                    'minor_damage',

                'major_damage',
                'unfit' =>
                    'major_damage',

                default =>
                    'good',
            };

            for (
                $unit = 1;
                $unit <= $quantity;
                $unit++
            ) {
                $assetNumber = sprintf(
                    'ALT-%s-%s-%06d',
                    $workshopCode,
                    $this->year,
                    $sequence
                );

                $serialNumber =
                    $unit === 1
                    && filled(
                        $item->serial_number
                        ?? null
                    )
                        ? (string)
                            $item->serial_number
                        : sprintf(
                            'SN-%s-%s-%06d',
                            $workshopCode,
                            $this->year,
                            $sequence
                        );

                $assetId =
                    $this->upsertAndGetId(
                        'item_assets',
                        [
                            'asset_number' =>
                                $assetNumber,
                        ],
                        [
                            'item_id' =>
                                $itemId,

                            'barcode_value' =>
                                $assetNumber,

                            'serial_number' =>
                                $serialNumber,

                            'workshop_id' =>
                                $item->workshop_id,

                            'storage_location_id' =>
                                $item
                                    ->storage_location_id
                                ??
                                $item
                                    ->location_id
                                ??
                                null,

                            'condition' =>
                                $assetCondition,

                            'status' =>
                                $assetStatus,

                            'received_date' =>
                                $item->received_date
                                ??
                                now()
                                    ->subMonths(8)
                                    ->toDateString(),

                            'unit_price' =>
                                $item->unit_price
                                ?? null,

                            'notes' =>
                                'Unit alat dibuat oleh SimbaDemoSeeder.',

                            'is_active' =>
                                $assetStatus
                                    !== 'retired',
                        ]
                    );

                $this->ids[
                    'asset_'.$key.'_'.$unit
                ] = $assetId;

                if ($unit === 1) {
                    $this->ids[
                        'asset_'.$key
                    ] = $assetId;
                }

                $sequence++;
            }
        }
    }

    private function seedStockMovements(): void
    {
        DB::table('item_stock_movements')
            ->where('reference_number', 'like', 'SEED-%')
            ->delete();

        $toolKeys = [
            'tool_wrench_set',
            'tool_screwdriver_set',
            'tool_multimeter',
            'tool_torque_wrench',
            'tool_compression_tester',
            'tool_floor_jack',
            'tool_grinder',
            'tool_drill',
            'tool_welder',
            'tool_obd_scanner',
            'tool_combination_pliers',
            'tool_caliper',
            'tool_crimping',
            'tool_lan_tester',
        ];

        foreach ($toolKeys as $index => $key) {
            $itemId = $this->ids['item_'.$key];

            $quantity = (float) (
                DB::table('items')
                    ->where('id', $itemId)
                    ->value('stock')
                ?? 0
            );

            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $this->ids['user_admin'],
                'type' => 'initial',
                'quantity' => $quantity,
                'stock_before' => 0,
                'stock_after' => $quantity,
                'transaction_date' => now()->subMonths(8)->addDays($index)->toDateString(),
                'reference_number' => sprintf('SEED-INITIAL-ALT-%03d', $index + 1),
                'source' => 'Pengadaan awal sekolah',
                'destination' => null,
                'purpose' => 'Saldo awal alat',
                'description' => 'Saldo awal alat dari SimbaDemoSeeder.',
            ]);
        }

        $materials = [
            'material_engine_oil' => ['incoming' => 25, 'outgoing' => 7],
            'material_grease' => ['incoming' => 8, 'outgoing' => 4],
            'material_rags' => ['incoming' => 35, 'outgoing' => 10],
            'material_bolt_m8' => ['incoming' => 30, 'outgoing' => 22],
            'material_utp' => ['incoming' => 150, 'outgoing' => 30],
            'material_rj45' => ['incoming' => 60, 'outgoing' => 20],
            'material_solder' => ['incoming' => 5, 'outgoing' => 3],
            'material_cleaner' => ['incoming' => 8, 'outgoing' => 8],
            'material_cable_tie' => ['incoming' => 100, 'outgoing' => 35],
            'material_brake_cleaner' => ['incoming' => 20, 'outgoing' => 8],
        ];

        $sequence = 1;

        foreach ($materials as $key => $movement) {
            $itemId = $this->ids['item_'.$key];

            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $this->ids['user_admin'],
                'type' => 'initial',
                'quantity' => 0,
                'stock_before' => 0,
                'stock_after' => 0,
                'transaction_date' => now()->subMonths(6)->toDateString(),
                'reference_number' => sprintf('SEED-INITIAL-BHN-%03d', $sequence),
                'source' => 'Saldo awal',
                'destination' => null,
                'purpose' => 'Aktivasi modul stok',
                'description' => 'Saldo awal bahan dari SimbaDemoSeeder.',
            ]);

            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $this->toolmanIdForItem($itemId),
                'type' => 'incoming',
                'quantity' => $movement['incoming'],
                'stock_before' => 0,
                'stock_after' => $movement['incoming'],
                'transaction_date' => now()->subMonths(2)->addDays($sequence)->toDateString(),
                'reference_number' => sprintf('SEED-IN-%03d', $sequence),
                'source' => 'Gudang sekolah',
                'destination' => null,
                'purpose' => 'Pengadaan bahan praktik',
                'description' => 'Penerimaan bahan dummy.',
            ]);

            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $this->toolmanIdForItem($itemId),
                'type' => 'outgoing',
                'quantity' => $movement['outgoing'],
                'stock_before' => $movement['incoming'],
                'stock_after' => $movement['incoming'] - $movement['outgoing'],
                'transaction_date' => now()->subWeeks(2)->addDays($sequence)->toDateString(),
                'reference_number' => sprintf('SEED-OUT-%03d', $sequence),
                'source' => null,
                'destination' => 'Kelas praktik',
                'purpose' => 'Kegiatan praktik siswa',
                'description' => 'Pengeluaran bahan dummy.',
            ]);

            $sequence++;
        }
    }

    private function seedLoans(): void
    {
        $existingLoanIds = DB::table('loans')
            ->where('code', 'like', 'PJM-SEED-%')
            ->pluck('id');

        if ($existingLoanIds->isNotEmpty()) {
            DB::table('loan_items')
                ->whereIn('loan_id', $existingLoanIds)
                ->delete();

            DB::table('loans')
                ->whereIn('id', $existingLoanIds)
                ->delete();
        }

        $pendingLoanId = $this->insertAndGetId('loans', [
            'code' => 'PJM-SEED-001',
            'workshop_id' => $this->ids['workshop_tkr'],
            'assigned_toolman_id' => $this->ids['user_toolman_tkr'],
            'borrower_id' => $this->ids['user_siswa'],
            'approved_by' => null,
            'rejected_by' => null,
            'returned_by' => null,
            'status' => 'pending',
            'request_date' => now()->subDay()->toDateString(),
            'due_at' => now()->addDays(3)->setTime(15, 0)->toDateTimeString(),
            'approved_at' => null,
            'borrowed_at' => null,
            'rejected_at' => null,
            'returned_at' => null,
            'purpose' => 'Praktik tune up mesin bensin',
            'notes' => 'Menunggu persetujuan toolman.',
            'rejection_reason' => null,
        ]);

        $this->ids['loan_pending'] = $pendingLoanId;

        $this->insertFiltered('loan_items', [
            'loan_id' => $pendingLoanId,
            'item_id' => $this->ids['item_tool_combination_pliers'],
            'item_asset_id' => $this->ids['asset_tool_combination_pliers'] ?? null,
            'quantity' => 1,
            'returned_by' => null,
            'condition_out' => 'good',
            'condition_in' => null,
            'returned_at' => null,
            'return_notes' => null,
        ]);

        $this->insertFiltered('loan_items', [
            'loan_id' => $pendingLoanId,
            'item_id' => $this->ids['item_tool_screwdriver_set'],
            'item_asset_id' => $this->ids['asset_tool_screwdriver_set'] ?? null,
            'quantity' => 1,
            'returned_by' => null,
            'condition_out' => 'good',
            'condition_in' => null,
            'returned_at' => null,
            'return_notes' => null,
        ]);

        $borrowedLoanId = $this->insertAndGetId('loans', [
            'code' => 'PJM-SEED-002',
            'workshop_id' => $this->ids['workshop_tkr'],
            'assigned_toolman_id' => $this->ids['user_toolman_tkr'],
            'borrower_id' => $this->ids['user_guru'],
            'approved_by' => $this->ids['user_toolman'],
            'rejected_by' => null,
            'returned_by' => null,
            'status' => 'borrowed',
            'request_date' => now()->subDays(2)->toDateString(),
            'due_at' => now()->addDay()->setTime(12, 0)->toDateTimeString(),
            'approved_at' => now()->subDays(2)->setTime(8, 0)->toDateTimeString(),
            'borrowed_at' => now()->subDays(2)->setTime(8, 15)->toDateTimeString(),
            'rejected_at' => null,
            'returned_at' => null,
            'purpose' => 'Pemeriksaan sistem kelistrikan kendaraan',
            'notes' => 'Multimeter dipakai untuk demonstrasi guru.',
            'rejection_reason' => null,
        ]);

        $this->ids['loan_borrowed'] = $borrowedLoanId;

        $this->insertFiltered('loan_items', [
            'loan_id' => $borrowedLoanId,
            'item_id' => $this->ids['item_tool_multimeter'],
            'item_asset_id' => $this->ids['asset_tool_multimeter'] ?? null,
            'quantity' => 1,
            'returned_by' => null,
            'condition_out' => 'good',
            'condition_in' => null,
            'returned_at' => null,
            'return_notes' => null,
        ]);

        $partialLoanId = $this->insertAndGetId('loans', [
            'code' => 'PJM-SEED-003',
            'workshop_id' => $this->ids['workshop_tkr'],
            'assigned_toolman_id' => $this->ids['user_toolman_tkr'],
            'borrower_id' => $this->ids['user_siswa'],
            'approved_by' => $this->ids['user_toolman'],
            'rejected_by' => null,
            'returned_by' => null,
            'status' => 'partially_returned',
            'request_date' => now()->subDays(4)->toDateString(),
            'due_at' => now()->addDays(2)->setTime(14, 0)->toDateTimeString(),
            'approved_at' => now()->subDays(4)->setTime(7, 30)->toDateTimeString(),
            'borrowed_at' => now()->subDays(4)->setTime(8, 0)->toDateTimeString(),
            'rejected_at' => null,
            'returned_at' => null,
            'purpose' => 'Praktik pemeriksaan tekanan kompresi',
            'notes' => 'Dongkrak sudah kembali, compression tester masih dipakai.',
            'rejection_reason' => null,
        ]);

        $this->ids['loan_partial'] = $partialLoanId;

        $this->insertFiltered('loan_items', [
            'loan_id' => $partialLoanId,
            'item_id' => $this->ids['item_tool_compression_tester'],
            'item_asset_id' => $this->ids['asset_tool_compression_tester'] ?? null,
            'quantity' => 1,
            'returned_by' => null,
            'condition_out' => 'good',
            'condition_in' => null,
            'returned_at' => null,
            'return_notes' => null,
        ]);

        $this->insertFiltered('loan_items', [
            'loan_id' => $partialLoanId,
            'item_id' => $this->ids['item_tool_floor_jack'],
            'item_asset_id' => $this->ids['asset_tool_floor_jack'] ?? null,
            'quantity' => 1,
            'returned_by' => $this->ids['user_toolman'],
            'condition_out' => 'good',
            'condition_in' => 'good',
            'returned_at' => now()->subDay()->setTime(11, 0)->toDateTimeString(),
            'return_notes' => 'Dikembalikan dalam kondisi baik.',
        ]);

        $returnedLoanId = $this->insertAndGetId('loans', [
            'code' => 'PJM-SEED-004',
            'workshop_id' => $this->ids['workshop_tkr'],
            'assigned_toolman_id' => $this->ids['user_toolman_tkr'],
            'borrower_id' => $this->ids['user_guru'],
            'approved_by' => $this->ids['user_toolman'],
            'rejected_by' => null,
            'returned_by' => $this->ids['user_toolman'],
            'status' => 'returned',
            'request_date' => now()->subDays(9)->toDateString(),
            'due_at' => now()->subDays(5)->setTime(15, 0)->toDateTimeString(),
            'approved_at' => now()->subDays(9)->setTime(8, 0)->toDateTimeString(),
            'borrowed_at' => now()->subDays(9)->setTime(8, 20)->toDateTimeString(),
            'rejected_at' => null,
            'returned_at' => now()->subDays(6)->setTime(13, 30)->toDateTimeString(),
            'purpose' => 'Praktik pengencangan baut kepala silinder',
            'notes' => 'Peminjaman selesai tepat waktu.',
            'rejection_reason' => null,
        ]);

        $this->ids['loan_returned'] = $returnedLoanId;

        $this->insertFiltered('loan_items', [
            'loan_id' => $returnedLoanId,
            'item_id' => $this->ids['item_tool_torque_wrench'],
            'item_asset_id' => $this->ids['asset_tool_torque_wrench'] ?? null,
            'quantity' => 1,
            'returned_by' => $this->ids['user_toolman'],
            'condition_out' => 'good',
            'condition_in' => 'good',
            'returned_at' => now()->subDays(6)->setTime(13, 30)->toDateTimeString(),
            'return_notes' => 'Kondisi baik dan lengkap.',
        ]);

        $rejectedLoanId = $this->insertAndGetId('loans', [
            'code' => 'PJM-SEED-005',
            'workshop_id' => $this->ids['workshop_tkr'],
            'assigned_toolman_id' => $this->ids['user_toolman_tkr'],
            'borrower_id' => $this->ids['user_siswa'],
            'approved_by' => null,
            'rejected_by' => $this->ids['user_toolman'],
            'returned_by' => null,
            'status' => 'rejected',
            'request_date' => now()->subDays(3)->toDateString(),
            'due_at' => now()->subDay()->setTime(14, 0)->toDateTimeString(),
            'approved_at' => null,
            'borrowed_at' => null,
            'rejected_at' => now()->subDays(3)->setTime(10, 0)->toDateTimeString(),
            'returned_at' => null,
            'purpose' => 'Pengukuran komponen di luar bengkel',
            'notes' => null,
            'rejection_reason' => 'Alat tidak diperbolehkan dibawa keluar lingkungan sekolah.',
        ]);

        $this->ids['loan_rejected'] = $rejectedLoanId;

        $this->insertFiltered('loan_items', [
            'loan_id' => $rejectedLoanId,
            'item_id' => $this->ids['item_tool_caliper'],
            'item_asset_id' => $this->ids['asset_tool_caliper'] ?? null,
            'quantity' => 1,
            'returned_by' => null,
            'condition_out' => 'good',
            'condition_in' => null,
            'returned_at' => null,
            'return_notes' => null,
        ]);
    }

    private function seedDamageReports(): void
    {
        DB::table('damage_reports')
            ->where('code', 'like', 'RSK-SEED-%')
            ->delete();

        $this->ids['damage_reported'] = $this->insertAndGetId('damage_reports', [
            'code' => 'RSK-SEED-001',
            'item_id' => $this->ids['item_tool_drill'],
            'item_asset_id' => $this->ids['asset_tool_drill'] ?? null,
            'loan_item_id' => null,
            'reported_by' => $this->ids['user_guru'],
            'handled_by' => null,
            'completed_by' => null,
            'status' => 'reported',
            'severity' => 'minor_damage',
            'reported_at' => now()->subDay()->setTime(9, 15)->toDateTimeString(),
            'started_at' => null,
            'completed_at' => null,
            'condition_before' => 'good',
            'condition_after' => null,
            'description' => 'Putaran bor tidak stabil dan terdengar suara kasar pada bagian bearing.',
            'diagnosis' => null,
            'action_taken' => null,
            'vendor' => null,
            'repair_cost' => null,
            'notes' => 'Alat ditemukan bermasalah saat persiapan praktik.',
            'resolution_notes' => null,
        ]);

        $this->ids['damage_in_repair'] = $this->insertAndGetId('damage_reports', [
            'code' => 'RSK-SEED-002',
            'item_id' => $this->ids['item_tool_welder'],
            'item_asset_id' => $this->ids['asset_tool_welder'] ?? null,
            'loan_item_id' => null,
            'reported_by' => $this->ids['user_toolman_tsm'],
            'handled_by' => $this->ids['user_toolman_tsm'],
            'completed_by' => null,
            'status' => 'in_repair',
            'severity' => 'maintenance',
            'reported_at' => now()->subDays(5)->setTime(8, 30)->toDateTimeString(),
            'started_at' => now()->subDays(4)->setTime(9, 0)->toDateTimeString(),
            'completed_at' => null,
            'condition_before' => 'good',
            'condition_after' => null,
            'description' => 'Kipas pendingin mesin las berbunyi dan aliran udara melemah.',
            'diagnosis' => 'Kipas pendingin kotor dan bearing mulai aus.',
            'action_taken' => 'Pembersihan bagian dalam dan pemesanan kipas pengganti.',
            'vendor' => 'Teknisi Internal Sekolah',
            'repair_cost' => 0,
            'notes' => 'Jangan digunakan sampai perbaikan selesai.',
            'resolution_notes' => null,
        ]);

        $this->ids['damage_repaired'] = $this->insertAndGetId('damage_reports', [
            'code' => 'RSK-SEED-003',
            'item_id' => $this->ids['item_tool_grinder'],
            'item_asset_id' => $this->ids['asset_tool_grinder'] ?? null,
            'loan_item_id' => null,
            'reported_by' => $this->ids['user_siswa_tsm'],
            'handled_by' => $this->ids['user_toolman_tsm'],
            'completed_by' => $this->ids['user_toolman_tkr'],
            'status' => 'repaired',
            'severity' => 'minor_damage',
            'reported_at' => now()->subDays(20)->setTime(10, 0)->toDateTimeString(),
            'started_at' => now()->subDays(19)->setTime(8, 0)->toDateTimeString(),
            'completed_at' => now()->subDays(17)->setTime(14, 0)->toDateTimeString(),
            'condition_before' => 'minor_damage',
            'condition_after' => 'good',
            'description' => 'Sakelar gerinda kadang tidak merespons.',
            'diagnosis' => 'Kontak sakelar aus akibat pemakaian.',
            'action_taken' => 'Mengganti sakelar dan melakukan pengujian tanpa beban.',
            'vendor' => 'CV Teknik Jaya',
            'repair_cost' => 185000,
            'notes' => null,
            'resolution_notes' => 'Alat kembali normal dan dapat digunakan.',
        ]);

        $this->ids['damage_unrepairable'] = $this->insertAndGetId('damage_reports', [
            'code' => 'RSK-SEED-004',
            'item_id' => $this->ids['item_tool_obd_scanner'],
            'item_asset_id' => $this->ids['asset_tool_obd_scanner'] ?? null,
            'loan_item_id' => null,
            'reported_by' => $this->ids['user_toolman_tkr'],
            'handled_by' => $this->ids['user_toolman_tkr'],
            'completed_by' => $this->ids['user_admin'],
            'status' => 'unrepairable',
            'severity' => 'unfit',
            'reported_at' => now()->subMonths(2)->setTime(9, 0)->toDateTimeString(),
            'started_at' => now()->subMonths(2)->addDay()->setTime(9, 0)->toDateTimeString(),
            'completed_at' => now()->subMonths(2)->addDays(5)->setTime(15, 0)->toDateTimeString(),
            'condition_before' => 'major_damage',
            'condition_after' => 'unfit',
            'description' => 'Scanner tidak dapat menyala meskipun sumber daya normal.',
            'diagnosis' => 'Mainboard mengalami korsleting dan komponen pengganti tidak tersedia.',
            'action_taken' => 'Pemeriksaan vendor dan pengajuan penghapusan aset.',
            'vendor' => 'PT Diagnostik Otomotif',
            'repair_cost' => 250000,
            'notes' => null,
            'resolution_notes' => 'Tidak ekonomis untuk diperbaiki.',
        ]);
    }

    private function seedAuditLogs(): void
    {
        DB::table('audit_logs')
            ->where('route_name', 'like', 'seeder.%')
            ->delete();

        $logs = [
            [
                'user_id' => $this->ids['user_admin'],
                'event' => 'created',
                'auditable_type' => 'App\\Models\\Workshop',
                'auditable_id' => $this->ids['workshop_tkr'],
                'auditable_label' => 'TKR — Teknik Kendaraan Ringan',
                'route_name' => 'seeder.workshops',
                'old_values' => null,
                'new_values' => ['code' => 'TKR', 'name' => 'Teknik Kendaraan Ringan'],
                'created_at' => now()->subMonths(8)->toDateTimeString(),
            ],
            [
                'user_id' => $this->ids['user_admin'],
                'event' => 'created',
                'auditable_type' => 'App\\Models\\Item',
                'auditable_id' => $this->ids['item_tool_wrench_set'],
                'auditable_label' => "ALT-{$this->year}-0001 — Kunci Ring Pas Set",
                'route_name' => 'seeder.items',
                'old_values' => null,
                'new_values' => ['status' => 'available', 'condition' => 'good'],
                'created_at' => now()->subMonths(8)->addDay()->toDateTimeString(),
            ],
        ];

        if ($this->tableExists('item_stock_movements')) {
            $movementId = DB::table('item_stock_movements')
                ->where('reference_number', 'SEED-IN-001')
                ->value('id');

            if ($movementId !== null) {
                $logs[] = [
                    'user_id' => $this->ids['user_toolman'],
                    'event' => 'created',
                    'auditable_type' => 'App\\Models\\ItemStockMovement',
                    'auditable_id' => (int) $movementId,
                    'auditable_label' => 'SEED-IN-001',
                    'route_name' => 'seeder.stock-receipts',
                    'old_values' => null,
                    'new_values' => ['type' => 'incoming', 'quantity' => 25],
                    'created_at' => now()->subMonths(2)->toDateTimeString(),
                ];
            }
        }

        if (isset($this->ids['loan_pending'], $this->ids['loan_borrowed'])) {
            $logs[] = [
                'user_id' => $this->ids['user_siswa'],
                'event' => 'created',
                'auditable_type' => 'App\\Models\\Loan',
                'auditable_id' => $this->ids['loan_pending'],
                'auditable_label' => 'PJM-SEED-001',
                'route_name' => 'seeder.loans.pending',
                'old_values' => null,
                'new_values' => ['status' => 'pending'],
                'created_at' => now()->subDay()->toDateTimeString(),
            ];

            $logs[] = [
                'user_id' => $this->ids['user_toolman'],
                'event' => 'updated',
                'auditable_type' => 'App\\Models\\Loan',
                'auditable_id' => $this->ids['loan_borrowed'],
                'auditable_label' => 'PJM-SEED-002',
                'route_name' => 'seeder.loans.approve',
                'old_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'borrowed'],
                'created_at' => now()->subDays(2)->toDateTimeString(),
            ];
        }

        if (isset($this->ids['damage_reported'], $this->ids['damage_repaired'])) {
            $logs[] = [
                'user_id' => $this->ids['user_guru'],
                'event' => 'created',
                'auditable_type' => 'App\\Models\\DamageReport',
                'auditable_id' => $this->ids['damage_reported'],
                'auditable_label' => 'RSK-SEED-001',
                'route_name' => 'seeder.damage-reports',
                'old_values' => null,
                'new_values' => ['status' => 'reported', 'severity' => 'minor_damage'],
                'created_at' => now()->subDay()->setTime(9, 15)->toDateTimeString(),
            ];

            $logs[] = [
                'user_id' => $this->ids['user_toolman'],
                'event' => 'updated',
                'auditable_type' => 'App\\Models\\DamageReport',
                'auditable_id' => $this->ids['damage_repaired'],
                'auditable_label' => 'RSK-SEED-003',
                'route_name' => 'seeder.damage-reports.resolve',
                'old_values' => ['status' => 'in_repair'],
                'new_values' => ['status' => 'repaired', 'condition_after' => 'good'],
                'created_at' => now()->subDays(17)->toDateTimeString(),
            ];
        }

        foreach ($logs as $log) {
            $log['url'] = null;
            $log['method'] = 'CLI';
            $log['ip_address'] = null;
            $log['user_agent'] = 'SIMBA Demo Seeder';
            $log['old_values'] = $log['old_values'] === null
                ? null
                : json_encode($log['old_values'], JSON_UNESCAPED_UNICODE);
            $log['new_values'] = $log['new_values'] === null
                ? null
                : json_encode($log['new_values'], JSON_UNESCAPED_UNICODE);

            $this->insertFiltered('audit_logs', $log, false);
        }
    }

    private function toolmanIdForItem(
        int $itemId
    ): int {
        $workshopId =
            DB::table('items')
                ->where(
                    'id',
                    $itemId
                )
                ->value(
                    'workshop_id'
                );

        if ($workshopId !== null) {
            $key =
                'user_toolman_workshop_'.
                (int) $workshopId;

            if (
                isset(
                    $this->ids[$key]
                )
            ) {
                return $this->ids[$key];
            }
        }

        return $this->ids[
            'user_toolman'
        ];
    }

    private function syncItemCodeSequences(): void
    {
        if (! $this->tableExists('item_code_sequences')) {
            return;
        }

        $columns = $this->columnsFor('item_code_sequences');

        $numberColumn = $this->firstExistingColumn(
            $columns,
            ['last_number', 'current_number', 'last_sequence', 'sequence']
        );

        if ($numberColumn === null) {
            $this->command?->warn(
                'Tabel item_code_sequences ditemukan, tetapi kolom nomor urut tidak dikenali.'
            );

            return;
        }

        $typeColumn = $this->firstExistingColumn(
            $columns,
            ['type', 'item_type']
        );

        $prefixColumn = $this->firstExistingColumn(
            $columns,
            ['prefix', 'code_prefix']
        );

        $yearColumn = $this->firstExistingColumn(
            $columns,
            ['year', 'sequence_year']
        );

        foreach ([
            ['type' => 'tool', 'prefix' => 'ALT', 'last' => 14],
            ['type' => 'material', 'prefix' => 'BHN', 'last' => 10],
        ] as $sequence) {
            $identity = [];

            if ($typeColumn !== null) {
                $identity[$typeColumn] = $sequence['type'];
            }

            if ($prefixColumn !== null) {
                $identity[$prefixColumn] = $sequence['prefix'];
            }

            if ($yearColumn !== null) {
                $identity[$yearColumn] = (int) $this->year;
            }

            if ($identity === []) {
                continue;
            }

            $this->upsertWithoutId(
                'item_code_sequences',
                $identity,
                [$numberColumn => $sequence['last']]
            );
        }
    }

    private function upsertWithoutId(
        string $table,
        array $identity,
        array $values
    ): void {
        $identity = $this->filterColumns($table, $identity);
        $values = $this->filterColumns($table, $values);

        if ($identity === []) {
            return;
        }

        $query = DB::table($table);

        foreach ($identity as $column => $value) {
            $value === null
                ? $query->whereNull($column)
                : $query->where($column, $value);
        }

        if ($query->exists()) {
            $query->update($this->withUpdatedAt($table, $values));

            return;
        }

        $this->insertFiltered(
            $table,
            array_merge($identity, $values)
        );
    }

    /**
     * Memasukkan atau memperbarui satu baris, lalu mengembalikan ID-nya.
     */
    private function upsertAndGetId(
        string $table,
        array $identity,
        array $values
    ): int {
        $identity = $this->filterColumns($table, $identity);
        $values = $this->filterColumns($table, $values);

        if ($identity === []) {
            throw new RuntimeException(
                "Tidak ada kolom identitas yang cocok untuk tabel {$table}."
            );
        }

        $query = DB::table($table);

        foreach ($identity as $column => $value) {
            $value === null
                ? $query->whereNull($column)
                : $query->where($column, $value);
        }

        $existingId = $query->value('id');

        if ($existingId !== null) {
            $updateValues = $this->withUpdatedAt($table, $values);

            if ($updateValues !== []) {
                $query->update($updateValues);
            }

            return (int) $existingId;
        }

        return $this->insertAndGetId(
            $table,
            array_merge($identity, $values)
        );
    }

    private function insertAndGetId(
        string $table,
        array $values
    ): int {
        $values = $this->filterColumns($table, $values);
        $values = $this->withTimestamps($table, $values);

        return (int) DB::table($table)->insertGetId($values);
    }

    private function insertFiltered(
        string $table,
        array $values,
        bool $withTimestamps = true
    ): void {
        $values = $this->filterColumns($table, $values);

        if ($withTimestamps) {
            $values = $this->withTimestamps($table, $values);
        }

        DB::table($table)->insert($values);
    }

    private function withTimestamps(
        string $table,
        array $values
    ): array {
        $columns = $this->columnsFor($table);

        if (
            in_array('created_at', $columns, true)
            && ! array_key_exists('created_at', $values)
        ) {
            $values['created_at'] = $this->now;
        }

        if (
            in_array('updated_at', $columns, true)
            && ! array_key_exists('updated_at', $values)
        ) {
            $values['updated_at'] = $this->now;
        }

        return $values;
    }

    private function withUpdatedAt(
        string $table,
        array $values
    ): array {
        if (
            in_array('updated_at', $this->columnsFor($table), true)
            && ! array_key_exists('updated_at', $values)
        ) {
            $values['updated_at'] = $this->now;
        }

        return $values;
    }

    private function filterColumns(
        string $table,
        array $values
    ): array {
        $allowedColumns = array_flip(
            $this->columnsFor($table)
        );

        return array_intersect_key(
            $values,
            $allowedColumns
        );
    }

    /**
     * @return array<int, string>
     */
    private function columnsFor(string $table): array
    {
        if (! isset($this->tableColumns[$table])) {
            $this->tableColumns[$table] = Schema::getColumnListing($table);
        }

        return $this->tableColumns[$table];
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $candidates
     */
    private function firstExistingColumn(
        array $columns,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
