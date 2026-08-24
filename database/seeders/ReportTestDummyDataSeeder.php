<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ReportTestDummyDataSeeder extends Seeder
{
    private array $tableColumns = [];
    private array $ids = [];
    private string $now;

    public function run(): void
    {
        $this->now = now()->toDateTimeString();

        $this->ensureRequiredTablesExist();

        DB::transaction(function (): void {
            $this->seedItemsForReportTesting();
            $this->seedStockMovementsForReportTesting();
            $this->seedLoansForReportTesting();
            $this->seedDamageReportsForReportTesting();
        });

        $this->command?->newLine();
        $this->command?->info('Data testing laporan (diverse) berhasil dibuat.');

        $tables = [
            'items' => 'code',
            'item_stock_movements' => 'reference_number',
            'loans' => 'code',
            'loan_items' => 'id',
            'damage_reports' => 'code',
        ];

        foreach ($tables as $table => $col) {
            if (! $this->tableExists($table)) {
                continue;
            }

            try {
                $count = DB::table($table)
                    ->where("{$col}", 'like', 'TEST-%')
                    ->orWhere("{$col}", 'like', 'PJM-TEST-%')
                    ->orWhere("{$col}", 'like', 'RSK-TEST-%')
                    ->orWhere("{$col}", 'like', 'SEED-TEST-%')
                    ->count();
            } catch (\Throwable) {
                $count = 0;
            }

            if ($count > 0) {
                $this->command?->line(
                    sprintf('- %-24s %d baris', $table, $count)
                );
            }
        }

        $this->command?->newLine();
    }

    private function ensureRequiredTablesExist(): void
    {
        $required = [
            'users', 'workshops', 'storage_locations',
            'item_categories', 'units', 'items',
        ];

        $missing = array_values(array_filter(
            $required,
            fn (string $t): bool => ! $this->tableExists($t)
        ));

        if ($missing !== []) {
            throw new RuntimeException(
                'Seeder dihentikan. Tabel berikut belum tersedia: '
                . implode(', ', $missing)
                . '. Jalankan migration terlebih dahulu.'
            );
        }
    }

    /**
     * Data item per jurusan. Kode jurusan: TKJ, RPL, DPIB, TAV, TTIL, TP, TSM, TKR.
     * Nama alat/bahan dibuat spesifik dan kontekstual sesuai jurusan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemDefinitions(): array
    {
        return [
            // ---- TKJ : Teknik Komputer dan Jaringan ----
            [
                'key' => 'tool_crimping_rj45',
                'code' => 'TEST-ALT-0001',
                'name' => 'Crimping Tool RJ45',
                'type' => 'tool',
                'category' => 'network_tools',
                'unit' => 'pcs',
                'workshop' => 'TKJ',
                'brand' => 'Proskit',
                'model' => 'HT-309',
                'specification' => 'Ratcheting 8P/6P',
                'year' => 2024,
                'price' => 185000,
                'stock' => 6,
                'min_stock' => 2,
            ],
            [
                'key' => 'material_kabel_utp',
                'code' => 'TEST-BHN-0001',
                'name' => 'Kabel UTP Cat5e (rol 305m)',
                'type' => 'material',
                'category' => 'network_materials',
                'unit' => 'meter',
                'workshop' => 'TKJ',
                'brand' => 'Belden',
                'model' => 'Cat5e UTP',
                'specification' => 'Twisted pair 4 pasang, 305m/rol',
                'year' => 2023,
                'price' => 2300,
                'stock' => 820,
                'min_stock' => 200,
            ],
            [
                'key' => 'material_connector_rj45',
                'code' => 'TEST-BHN-0002',
                'name' => 'Konektor RJ45',
                'type' => 'material',
                'category' => 'network_materials',
                'unit' => 'pcs',
                'workshop' => 'TKJ',
                'brand' => 'AMP',
                'model' => '8P8C',
                'specification' => 'Bentuk pas untuk kabel UTP',
                'year' => 2024,
                'price' => 1500,
                'stock' => 1450,
                'min_stock' => 300,
            ],

            // ---- TAV : Teknik Audio Video ----
            [
                'key' => 'tool_osiloskop',
                'code' => 'TEST-ALT-0002',
                'name' => 'Oscilloscope 2-Channel',
                'type' => 'tool',
                'category' => 'measuring_tools',
                'unit' => 'pcs',
                'workshop' => 'TAV',
                'brand' => 'GW Instek',
                'model' => 'GDS-1054B',
                'specification' => '50 MHz, 2ch, LCD',
                'year' => 2023,
                'price' => 3500000,
                'stock' => 4,
                'min_stock' => 1,
            ],
            [
                'key' => 'tool_solder_smd',
                'code' => 'TEST-ALT-0003',
                'name' => 'Solder Iron SMD Rework',
                'type' => 'tool',
                'category' => 'power_tools',
                'unit' => 'pcs',
                'workshop' => 'TAV',
                'brand' => 'Yihua',
                'model' => '853A',
                'specification' => 'Desoldering + soldering station',
                'year' => 2025,
                'price' => 780000,
                'stock' => 3,
                'min_stock' => 1,
            ],
            [
                'key' => 'material_timah_solder',
                'code' => 'TEST-BHN-0003',
                'name' => 'Timah Solder 60/40 1kg',
                'type' => 'material',
                'category' => 'electronics',
                'unit' => 'kg',
                'workshop' => 'TAV',
                'brand' => 'Multicore',
                'model' => 'Sn60/Pb40',
                'specification' => '0.8mm diameter',
                'year' => 2023,
                'price' => 220000,
                'stock' => 8,
                'min_stock' => 2,
            ],

            // ---- TSM : Teknik Sepeda Motor ----
            [
                'key' => 'tool_compression_tester',
                'code' => 'TEST-ALT-0004',
                'name' => 'Compression Tester Mesin',
                'type' => 'tool',
                'category' => 'diagnostic_tools',
                'unit' => 'set',
                'workshop' => 'TSM',
                'brand' => 'Laser',
                'model' => '3717',
                'specification' => 'Kompresi silinder, adaptor M10/M12',
                'year' => 2024,
                'price' => 540000,
                'stock' => 2,
                'min_stock' => 1,
            ],
            [
                'key' => 'material_oli_2t',
                'code' => 'TEST-BHN-0004',
                'name' => 'Oli Mesin 2-Tak 1L',
                'type' => 'material',
                'category' => 'lubricants',
                'unit' => 'liter',
                'workshop' => 'TSM',
                'brand' => 'Shell',
                'model' => 'Advance 2T',
                'specification' => 'Sintetis untuk sepeda motor 2T',
                'year' => 2023,
                'price' => 45000,
                'stock' => 35,
                'min_stock' => 10,
            ],
            [
                'key' => 'material_busi_ngk',
                'code' => 'TEST-BHN-0005',
                'name' => 'Busi NGK C7HSA',
                'type' => 'material',
                'category' => 'fasteners',
                'unit' => 'pcs',
                'workshop' => 'TSM',
                'brand' => 'NGK',
                'model' => 'C7HSA',
                'specification' => 'Busi standar motor bebek',
                'year' => 2025,
                'price' => 18000,
                'stock' => 90,
                'min_stock' => 20,
            ],

            // ---- TKR : Teknik Kendaraan Ringan ----
            [
                'key' => 'tool_dongkrak_botol',
                'code' => 'TEST-ALT-0005',
                'name' => 'Hydraulic Bottle Jack 12T',
                'type' => 'tool',
                'category' => 'hand_tools',
                'unit' => 'pcs',
                'workshop' => 'TKR',
                'brand' => 'Totem',
                'model' => 'T-HB12T',
                'specification' => 'Kapasitas 12 ton',
                'year' => 2024,
                'price' => 1250000,
                'stock' => 3,
                'min_stock' => 1,
            ],
            [
                'key' => 'material_minyak_rem',
                'code' => 'TEST-BHN-0006',
                'name' => 'Minyak Rem DOT 4 500ml',
                'type' => 'material',
                'category' => 'lubricants',
                'unit' => 'botol',
                'workshop' => 'TKR',
                'brand' => 'Repsol',
                'model' => 'DOT 4',
                'specification' => 'Titik didih kering 260C',
                'year' => 2023,
                'price' => 38000,
                'stock' => 28,
                'min_stock' => 8,
            ],

            // ---- DPIB : Desain Pemodelan dan Informasi Bangunan ----
            [
                'key' => 'tool_total_station',
                'code' => 'TEST-ALT-0006',
                'name' => 'Total Station Topcon ES-105',
                'type' => 'tool',
                'category' => 'measuring_tools',
                'unit' => 'pcs',
                'workshop' => 'DPIB',
                'brand' => 'Topcon',
                'model' => 'ES-105',
                'specification' => 'Pemetaan dan pengukuran lahan',
                'year' => 2023,
                'price' => 24500000,
                'stock' => 1,
                'min_stock' => 1,
            ],
            [
                'key' => 'tool_printer_a0',
                'code' => 'TEST-ALT-0007',
                'name' => 'Plotter Printer A0',
                'type' => 'tool',
                'category' => 'power_tools',
                'unit' => 'pcs',
                'workshop' => 'DPIB',
                'brand' => 'Epson',
                'model' => 'SureColor T5270',
                'specification' => 'Cetak gambar teknik A0',
                'year' => 2025,
                'price' => 18500000,
                'stock' => 1,
                'min_stock' => 1,
            ],
            [
                'key' => 'material_kertas_a3',
                'code' => 'TEST-BHN-0007',
                'name' => 'Kertas Gambar A3 80gsm',
                'type' => 'material',
                'category' => 'cleaning',
                'unit' => 'rim',
                'workshop' => 'DPIB',
                'brand' => 'Sidu',
                'model' => '80gsm',
                'specification' => 'Kertas putih untuk gambar kerja',
                'year' => 2024,
                'price' => 55000,
                'stock' => 40,
                'min_stock' => 10,
            ],

            // ---- TP : Teknik Pemesinan ----
            [
                'key' => 'tool_vernier_caliper',
                'code' => 'TEST-ALT-0008',
                'name' => 'Digital Vernier Caliper 200mm',
                'type' => 'tool',
                'category' => 'measuring_tools',
                'unit' => 'pcs',
                'workshop' => 'TP',
                'brand' => 'Mitutoyo',
                'model' => '500-197',
                'specification' => 'Akurasi 0.01mm, LCD',
                'year' => 2023,
                'price' => 850000,
                'stock' => 8,
                'min_stock' => 3,
            ],
            [
                'key' => 'material_besi_poros',
                'code' => 'TEST-BHN-0008',
                'name' => 'Besi Poros Ø 25mm',
                'type' => 'material',
                'category' => 'fasteners',
                'unit' => 'meter',
                'workshop' => 'TP',
                'brand' => 'Lokal',
                'model' => 'S45C',
                'specification' => 'Baja karbon S45C, diameter 25mm',
                'year' => 2025,
                'price' => 65000,
                'stock' => 15,
                'min_stock' => 5,
            ],

            // ---- TTIL : Teknik Tenaga Instalasi Listrik ----
            [
                'key' => 'tool_insulation_tester',
                'code' => 'TEST-ALT-0009',
                'name' => 'Insulation Resistance Tester',
                'type' => 'tool',
                'category' => 'diagnostic_tools',
                'unit' => 'pcs',
                'workshop' => 'TTIL',
                'brand' => 'Kyoritsu',
                'model' => '3132A',
                'specification' => '1000V, pengukur tahanan isolasi',
                'year' => 2024,
                'price' => 2100000,
                'stock' => 2,
                'min_stock' => 1,
            ],
            [
                'key' => 'material_kabel_nya',
                'code' => 'TEST-BHN-0009',
                'name' => 'Kabel NYA 2.5mm (roll 100m)',
                'type' => 'material',
                'category' => 'electronics',
                'unit' => 'meter',
                'workshop' => 'TTIL',
                'brand' => 'Kabelindo',
                'model' => 'NYYM 2.5mm',
                'specification' => 'Instalasi listrik rumah, 100m',
                'year' => 2023,
                'price' => 3800,
                'stock' => 450,
                'min_stock' => 150,
            ],

            // ---- RPL : Rekayasa Perangkat Lunak ----
            [
                'key' => 'tool_raspberry_pi',
                'code' => 'TEST-ALT-0010',
                'name' => 'Raspberry Pi 4 Model B 4GB',
                'type' => 'tool',
                'category' => 'diagnostic_tools',
                'unit' => 'pcs',
                'workshop' => 'RPL',
                'brand' => 'Raspberry',
                'model' => '4B 4GB',
                'specification' => 'SBC untuk pengembangan IoT',
                'year' => 2025,
                'price' => 1450000,
                'stock' => 10,
                'min_stock' => 4,
            ],
            [
                'key' => 'material_ssd_256',
                'code' => 'TEST-BHN-0010',
                'name' => 'SSD NVMe 256GB',
                'type' => 'material',
                'category' => 'electronics',
                'unit' => 'pcs',
                'workshop' => 'RPL',
                'brand' => 'Samsung',
                'model' => '980',
                'specification' => 'PCIe 3.0 NVMe M.2',
                'year' => 2024,
                'price' => 390000,
                'stock' => 22,
                'min_stock' => 6,
            ],
        ];
    }

    private function seedItemsForReportTesting(): void
    {
        // Bersihkan data test lama (hapus child terlebih dahulu karena FK)
        $oldItemIds = DB::table('items')
            ->where('code', 'like', 'TEST-ALT-%')
            ->orWhere('code', 'like', 'TEST-BHN-%')
            ->pluck('id');

        if ($oldItemIds->isNotEmpty()) {
            if ($this->tableExists('item_stock_movements')) {
                DB::table('item_stock_movements')
                    ->whereIn('item_id', $oldItemIds)
                    ->delete();
            }
            if ($this->tableExists('loan_items')) {
                DB::table('loan_items')
                    ->whereIn('item_id', $oldItemIds)
                    ->delete();
            }
            if ($this->tableExists('damage_reports')) {
                DB::table('damage_reports')
                    ->whereIn('item_id', $oldItemIds)
                    ->delete();
            }
            if ($this->tableExists('item_assets')) {
                DB::table('item_assets')
                    ->whereIn('item_id', $oldItemIds)
                    ->delete();
            }
            DB::table('items')->whereIn('id', $oldItemIds)->delete();
        }

        $workshops = DB::table('workshops')
            ->pluck('id', 'code')
            ->toArray();

        if (empty($workshops)) {
            $this->command?->warn('Tidak ada workshop tersedia.');
            return;
        }

        $categories = DB::table('item_categories')
            ->pluck('id', 'code')
            ->toArray();

        $units = DB::table('units')
            ->pluck('id', 'code')
            ->toArray();

        $locations = DB::table('storage_locations')
            ->pluck('id')
            ->toArray();

        if (empty($locations)) {
            $locations = [null];
        }

        foreach ($this->itemDefinitions() as $def) {
            $workshopId = $workshops[$def['workshop']] ?? array_values($workshops)[0];

            $catCode = $this->categoryCodeFor($def['category']);
            $categoryId = $categories[$catCode] ?? array_values($categories)[0];

            $unitCode = $def['unit'];
            $unitId = $units[$unitCode] ?? array_values($units)[0];

            $year = (int) $def['year'];
            $month = random_int(1, 12);
            $day = random_int(1, 28);

            $itemId = $this->insertAndGetId('items', [
                'code' => $def['code'],
                'type' => $def['type'],
                'name' => $def['name'],
                'item_category_id' => $categoryId,
                'unit_id' => $unitId,
                'workshop_id' => $workshopId,
                'storage_location_id' => $locations[array_rand($locations)],
                'brand' => $def['brand'],
                'model' => $def['model'],
                'serial_number' => 'SN-' . $def['code'] . '-' . $year,
                'specification' => $def['specification'],
                'received_date' => "{$year}-{$month}-{$day}",
                'acquisition_source' => $year < now()->year
                    ? 'Pengadaan sekolah'
                    : 'Pengadaan tahun berjalan',
                'fund_source' => $year % 2 === 0 ? 'Rutin' : 'Bantuan',
                'unit_price' => $def['price'],
                'condition' => 'good',
                'status' => $def['stock'] > 0 ? 'available' : 'out_of_stock',
                'stock' => $def['stock'],
                'minimum_stock' => $def['min_stock'],
                'is_borrowable' => $def['type'] === 'tool',
                'description' => 'Data dummy jurusan ' . $def['workshop'] . '.',
                'is_active' => true,
            ]);

            $this->ids[$def['key']] = $itemId;
        }
    }

    private function categoryCodeFor(string $category): string
    {
        return match ($category) {
            'hand_tools' => 'ALT-TANGAN',
            'measuring_tools' => 'ALT-UKUR',
            'power_tools' => 'ALT-MESIN',
            'diagnostic_tools' => 'ALT-DIAG',
            'network_tools' => 'ALT-JARINGAN',
            'lubricants' => 'BHN-PELUMAS',
            'cleaning' => 'BHN-BERSIH',
            'fasteners' => 'BHN-PENGIKAT',
            'network_materials' => 'BHN-JARINGAN',
            'electronics' => 'BHN-ELEKTRONIK',
            default => 'ALT-TANGAN',
        };
    }

    private function seedStockMovementsForReportTesting(): void
    {
        DB::table('item_stock_movements')
            ->where('reference_number', 'like', 'TEST-IN-%')
            ->orWhere('reference_number', 'like', 'TEST-OUT-%')
            ->delete();

        $adminId = DB::table('users')
            ->where('role', 'admin')
            ->value('id');

        if (! $adminId) {
            $this->command?->warn('Admin user tidak ditemukan.');
            return;
        }

        $incomingRef = 1;
        $outgoingRef = 1;

        foreach ($this->itemDefinitions() as $def) {
            $itemId = $this->ids[$def['key']] ?? null;
            if (! $itemId) {
                continue;
            }

            $receivedYear = (int) $def['year'];

            // Penerimaan awal saat barang diterima
            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $adminId,
                'workshop_id' => DB::table('items')->where('id', $itemId)->value('workshop_id'),
                'type' => 'incoming',
                'quantity' => $def['stock'],
                'stock_before' => 0,
                'stock_after' => $def['stock'],
                'transaction_date' => "{$receivedYear}-03-15",
                'reference_number' => 'TEST-IN-' . str_pad($incomingRef, 4, '0', STR_PAD_LEFT),
                'source' => 'Pengadaan sekolah',
                'destination' => null,
                'purpose' => 'Pengadaan awal ' . $def['name'],
                'description' => 'Penerimaan barang ' . $def['name'],
                'condition' => 'good',
                'unit_price' => $def['price'],
            ]);
            $incomingRef++;

            // Penerimaan tambahan (tahun berikutnya)
            $extraYear = min($receivedYear + 1, 2026);
            $extraQty = $def['type'] === 'material' ? intdiv($def['stock'], 2) : 1;
            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $adminId,
                'workshop_id' => DB::table('items')->where('id', $itemId)->value('workshop_id'),
                'type' => 'incoming',
                'quantity' => max(1, $extraQty),
                'stock_before' => $def['stock'],
                'stock_after' => $def['stock'] + max(1, $extraQty),
                'transaction_date' => "{$extraYear}-08-20",
                'reference_number' => 'TEST-IN-' . str_pad($incomingRef, 4, '0', STR_PAD_LEFT),
                'source' => 'Pengadaan tambahan',
                'destination' => null,
                'purpose' => 'Tambah stok ' . $def['name'],
                'description' => 'Penerimaan tambahan barang ' . $def['name'],
                'condition' => 'good',
                'unit_price' => $def['price'],
            ]);
            $incomingRef++;
        }

        // Pengeluaran untuk bahan (dipakai praktik)
        $materialKeys = array_filter(
            array_keys($this->ids),
            fn (string $k): bool => str_starts_with($k, 'material_')
        );

        $defsByKey = [];
        foreach ($this->itemDefinitions() as $d) {
            $defsByKey[$d['key']] = $d;
        }

        foreach ($materialKeys as $key) {
            $itemId = $this->ids[$key];
            $def = $defsByKey[$key] ?? null;
            if (! $def) {
                continue;
            }

            $qty = max(1, intdiv($def['stock'], 4));
            $year = (int) $def['year'] + 1 > 2026 ? 2026 : (int) $def['year'] + 1;

            $this->insertFiltered('item_stock_movements', [
                'item_id' => $itemId,
                'user_id' => $adminId,
                'workshop_id' => DB::table('items')->where('id', $itemId)->value('workshop_id'),
                'type' => 'outgoing',
                'quantity' => $qty,
                'stock_before' => $def['stock'] + max(1, intdiv($def['stock'], 2)),
                'stock_after' => $def['stock'] + max(1, intdiv($def['stock'], 2)) - $qty,
                'transaction_date' => "{$year}-05-10",
                'reference_number' => 'TEST-OUT-' . str_pad($outgoingRef, 4, '0', STR_PAD_LEFT),
                'source' => null,
                'destination' => 'Kelas praktik',
                'purpose' => 'Kegiatan praktik siswa',
                'description' => 'Pengeluaran ' . $def['name'],
                'condition' => 'good',
            ]);
            $outgoingRef++;
        }
    }

    private function seedLoansForReportTesting(): void
    {
        if (! $this->tableExists('loans') || ! $this->tableExists('loan_items')) {
            return;
        }

        $oldLoanIds = DB::table('loans')
            ->where('code', 'like', 'PJM-TEST-%')
            ->pluck('id');

        if ($oldLoanIds->isNotEmpty()) {
            DB::table('loan_items')->whereIn('loan_id', $oldLoanIds)->delete();
            DB::table('loans')->whereIn('id', $oldLoanIds)->delete();
        }

        $tools = DB::table('items')
            ->where('code', 'like', 'TEST-ALT-%')
            ->where('is_borrowable', true)
            ->get(['id', 'workshop_id', 'name'])
            ->toArray();

        if (empty($tools)) {
            return;
        }

        $borrowers = DB::table('users')
            ->whereIn('role', ['siswa', 'guru'])
            ->limit(6)
            ->get(['id', 'role', 'name'])
            ->toArray();

        $toolmen = DB::table('users')
            ->where('role', 'toolman')
            ->limit(3)
            ->pluck('id')
            ->toArray();

        if (empty($borrowers) || empty($toolmen)) {
            $this->command?->warn('Borrower atau toolman tidak ditemukan.');
            return;
        }

        $loanSpecs = [
            ['status' => 'pending', 'months_ago' => 1],
            ['status' => 'approved', 'months_ago' => 2],
            ['status' => 'borrowed', 'months_ago' => 3],
            ['status' => 'returned', 'months_ago' => 4],
            ['status' => 'rejected', 'months_ago' => 5],
            ['status' => 'borrowed', 'months_ago' => 6],
            ['status' => 'returned', 'months_ago' => 7],
            ['status' => 'completed', 'months_ago' => 9],
        ];

        foreach ($loanSpecs as $index => $spec) {
            $tool = $tools[$index % count($tools)];
            $borrower = $borrowers[$index % count($borrowers)];
            $toolman = $toolmen[$index % count($toolmen)];
            $status = $spec['status'];

            $requestDate = now()->subMonths($spec['months_ago'])->startOfDay();

            $approved = in_array($status, ['approved', 'borrowed', 'returned', 'completed'], true);
            $borrowed = in_array($status, ['borrowed', 'returned', 'completed'], true);
            $returned = in_array($status, ['returned', 'completed'], true);

            $dueAt = $requestDate->copy()->addDays(7)->setTime(15, 0);
            $approvedAt = $approved ? $requestDate->copy()->addHours(2) : null;
            $borrowedAt = $borrowed ? $requestDate->copy()->addHours(2)->addMinutes(15) : null;
            $returnedAt = $returned ? $requestDate->copy()->addDays(6) : null;

            $loanId = $this->insertAndGetId('loans', [
                'code' => 'PJM-TEST-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'workshop_id' => $tool->workshop_id,
                'assigned_toolman_id' => $toolman,
                'borrower_id' => $borrower->id,
                'approved_by' => $approved ? $toolman : null,
                'rejected_by' => $status === 'rejected' ? $toolman : null,
                'returned_by' => $returned ? $toolman : null,
                'status' => $status,
                'request_date' => $requestDate->toDateString(),
                'due_at' => $dueAt->toDateTimeString(),
                'approved_at' => $approvedAt?->toDateTimeString(),
                'borrowed_at' => $borrowedAt?->toDateTimeString(),
                'rejected_at' => $status === 'rejected' ? $requestDate->copy()->addHours(1)->toDateTimeString() : null,
                'returned_at' => $returnedAt?->toDateTimeString(),
                'purpose' => 'Praktik menggunakan ' . $tool->name,
                'notes' => 'Peminjaman ' . $tool->name . ' untuk praktik jurusan.',
                'rejection_reason' => $status === 'rejected' ? 'Alat sedang dipakai kelas lain' : null,
            ]);

            $this->insertFiltered('loan_items', [
                'loan_id' => $loanId,
                'item_id' => $tool->id,
                'item_asset_id' => null,
                'quantity' => 1,
                'returned_by' => $returned ? $toolman : null,
                'condition_out' => 'good',
                'condition_in' => $returned ? 'good' : null,
                'returned_at' => $returnedAt?->toDateTimeString(),
                'return_notes' => $returned ? 'Dikembalikan dalam kondisi baik' : null,
            ]);
        }
    }

    private function seedDamageReportsForReportTesting(): void
    {
        if (! $this->tableExists('damage_reports')) {
            return;
        }

        DB::table('damage_reports')
            ->where('code', 'like', 'RSK-TEST-%')
            ->delete();

        $tools = DB::table('items')
            ->where('code', 'like', 'TEST-ALT-%')
            ->get(['id', 'workshop_id', 'name'])
            ->toArray();

        if (empty($tools)) {
            return;
        }

        $users = DB::table('users')
            ->whereIn('role', ['siswa', 'guru', 'toolman', 'admin'])
            ->limit(6)
            ->pluck('id')
            ->toArray();

        if (empty($users)) {
            return;
        }

        $severities = ['minor_damage', 'moderate_damage', 'major_damage', 'critical_damage'];
        $statuses = ['reported', 'in_repair', 'repaired', 'unrepairable'];

        foreach ($tools as $index => $tool) {
            $severity = $severities[$index % count($severities)];
            $status = $statuses[$index % count($statuses)];

            $now = now();
            $monthsAgo = ($index % 12);
            $reportedAt = $now->copy()->subMonths($monthsAgo)->subDays($index % 28)->setTime(9, 0);

            $startedAt = in_array($status, ['in_repair', 'repaired', 'unrepairable'], true)
                ? $reportedAt->copy()->addHours(4)
                : null;
            $completedAt = in_array($status, ['repaired', 'unrepairable'], true)
                ? $reportedAt->copy()->addDays(3)
                : null;

            $repairCost = match ($severity) {
                'minor_damage' => 100000 + ($index * 10000),
                'moderate_damage' => 250000 + ($index * 20000),
                'major_damage' => 500000 + ($index * 50000),
                'critical_damage' => 1000000 + ($index * 100000),
                default => 0,
            };

            $this->insertFiltered('damage_reports', [
                'code' => 'RSK-TEST-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'item_id' => $tool->id,
                'item_asset_id' => null,
                'loan_item_id' => null,
                'reported_by' => $users[$index % count($users)],
                'handled_by' => in_array($status, ['in_repair', 'repaired', 'unrepairable'], true)
                    ? $users[($index + 1) % count($users)]
                    : null,
                'completed_by' => in_array($status, ['repaired', 'unrepairable'], true)
                    ? $users[($index + 2) % count($users)]
                    : null,
                'status' => $status,
                'severity' => $severity,
                'reported_at' => $reportedAt->toDateTimeString(),
                'started_at' => $startedAt?->toDateTimeString(),
                'completed_at' => $completedAt?->toDateTimeString(),
                'condition_before' => $severity,
                'condition_after' => $status === 'repaired' ? 'good' : ($status === 'unrepairable' ? 'unfit' : null),
                'description' => 'Kerusakan pada ' . $tool->name,
                'diagnosis' => in_array($status, ['in_repair', 'repaired', 'unrepairable'], true)
                    ? 'Diagnosa kerusakan ' . $severity
                    : null,
                'action_taken' => in_array($status, ['in_repair', 'repaired', 'unrepairable'], true)
                    ? 'Tindakan perbaikan dilakukan'
                    : null,
                'vendor' => in_array($status, ['in_repair', 'repaired', 'unrepairable'], true)
                    ? ['Teknisi Internal', 'Vendor External', 'Pabrik'][$index % 3]
                    : null,
                'repair_cost' => in_array($status, ['in_repair', 'repaired', 'unrepairable'], true)
                    ? $repairCost
                    : null,
                'notes' => 'Laporan kerusakan alat ' . $tool->name,
                'resolution_notes' => $status === 'repaired'
                    ? 'Alat sudah diperbaiki dan siap digunakan'
                    : ($status === 'unrepairable' ? 'Alat tidak dapat diperbaiki lagi' : null),
            ]);
        }
    }

    private function insertAndGetId(string $table, array $values): int
    {
        $values = $this->filterColumns($table, $values);
        $values = $this->withTimestamps($table, $values);
        return (int) DB::table($table)->insertGetId($values);
    }

    private function insertFiltered(string $table, array $values, bool $withTimestamps = true): void
    {
        $values = $this->filterColumns($table, $values);
        if ($withTimestamps) {
            $values = $this->withTimestamps($table, $values);
        }
        DB::table($table)->insert($values);
    }

    private function withTimestamps(string $table, array $values): array
    {
        $columns = $this->columnsFor($table);
        if (in_array('created_at', $columns, true) && ! array_key_exists('created_at', $values)) {
            $values['created_at'] = $this->now;
        }
        if (in_array('updated_at', $columns, true) && ! array_key_exists('updated_at', $values)) {
            $values['updated_at'] = $this->now;
        }
        return $values;
    }

    private function filterColumns(string $table, array $values): array
    {
        return array_intersect_key($values, array_flip($this->columnsFor($table)));
    }

    private function columnsFor(string $table): array
    {
        if (! isset($this->tableColumns[$table])) {
            $this->tableColumns[$table] = Schema::getColumnListing($table);
        }
        return $this->tableColumns[$table];
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
