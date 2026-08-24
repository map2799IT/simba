<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportItemsRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\StorageLocation;
use App\Models\Unit;
use App\Models\Workshop;
use App\Services\ItemCodeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ItemImportController extends Controller
{
    /**
     * Nama dan urutan kolom harus sama dengan template.
     */
    private const HEADERS = [
        'jenis',
        'nama',
        'jumlah_alat',
        'kode_kategori',
        'kode_satuan',
        'kode_bengkel',
        'kode_lokasi',
        'merek',
        'model',
        'nomor_seri',
        'tanggal_masuk',
        'sumber_perolehan',
        'sumber_dana',
        'harga_satuan',
        'kondisi',
        'stok_awal',
        'stok_minimum',
        'dapat_dipinjam',
        'keterangan',
        'aktif',
    ];

    public function create(): View
    {
        return view('items.import');
    }

    /**
     * Mengunduh template import beserta data referensi.
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $sheet->fromArray(
            self::HEADERS,
            null,
            'A1'
        );

        /*
         * Contoh alat.
         */
        $sheet->fromArray([
            'tool',
            'Kunci Ring 12 mm',
            3,
            'ALAT-TANGAN',
            'UNIT',
            'TKR',
            'LEMARI-A-R01',
            'Tekiro',
            'KR-12',
            'SN-001|SN-002|SN-003',
            now()->format('Y-m-d'),
            'Pembelian',
            'BOS',
            75000,
            'good',
            null,
            null,
            'ya',
            'Contoh import alat',
            'ya',
        ], null, 'A2');

        /*
         * Contoh bahan.
         */
        $sheet->fromArray([
            'material',
            'Oli Mesin',
            null,
            'BAHAN-HABIS',
            'LTR',
            'TKR',
            'LEMARI-B',
            'Pertamina',
            '10W-40',
            null,
            now()->format('Y-m-d'),
            'Pembelian',
            'BOS',
            65000,
            null,
            20,
            5,
            'tidak',
            'Contoh import bahan',
            'ya',
        ], null, 'A3');

        $sheet->getStyle('A1:T1')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A1:T1')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet->getStyle('A1:T1')
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFD9EAF7');

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:T1');

        foreach (range('A', 'T') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }

        /*
         * Lembar petunjuk.
         */
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');

        $guide->fromArray([
            ['Kolom', 'Keterangan'],
            ['jenis', 'tool untuk alat, material untuk bahan'],
            ['jumlah_alat', 'Jumlah unit alat. Kosongkan untuk bahan'],
            ['nomor_seri', 'Pisahkan nomor seri menggunakan tanda |'],
            ['tanggal_masuk', 'Gunakan format YYYY-MM-DD'],
            ['kondisi', 'good, minor_damage, major_damage, maintenance, unfit'],
            ['stok_awal', 'Wajib untuk bahan'],
            ['stok_minimum', 'Wajib untuk bahan'],
            ['dapat_dipinjam', 'ya atau tidak'],
            ['aktif', 'ya atau tidak'],
        ], null, 'A1');

        $guide->getStyle('A1:B1')
            ->getFont()
            ->setBold(true);

        $guide->getColumnDimension('A')
            ->setWidth(25);

        $guide->getColumnDimension('B')
            ->setWidth(70);

        /*
         * Lembar data referensi dari database.
         */
        $reference = $spreadsheet->createSheet();
        $reference->setTitle('Referensi');

        $reference->fromArray([
            'Jenis Referensi',
            'Kode',
            'Nama',
            'Keterangan',
        ], null, 'A1');

        $referenceRow = 2;

        foreach (
            ItemCategory::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get()
            as $category
        ) {
            $reference->fromArray([
                'KATEGORI',
                $category->code,
                $category->name,
                $category->appliesToLabel(),
            ], null, "A{$referenceRow}");

            $referenceRow++;
        }

        foreach (
            Unit::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get()
            as $unit
        ) {
            $reference->fromArray([
                'SATUAN',
                $unit->code,
                $unit->name,
                $unit->allows_decimal
                    ? 'Boleh desimal'
                    : 'Bilangan bulat',
            ], null, "A{$referenceRow}");

            $referenceRow++;
        }

        foreach (
            Workshop::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get()
            as $workshop
        ) {
            $reference->fromArray([
                'BENGKEL',
                $workshop->code,
                $workshop->name,
                null,
            ], null, "A{$referenceRow}");

            $referenceRow++;
        }

        foreach (
            StorageLocation::query()
                ->with([
                    'workshop',
                    'parent.parent.parent',
                ])
                ->where('is_active', true)
                ->orderBy('workshop_id')
                ->orderBy('code')
                ->get()
            as $location
        ) {
            $reference->fromArray([
                'LOKASI',
                $location->code,
                $location->full_path,
                $location->workshop->code,
            ], null, "A{$referenceRow}");

            $referenceRow++;
        }

        $reference->getStyle('A1:D1')
            ->getFont()
            ->setBold(true);

        $reference->freezePane('A2');
        $reference->setAutoFilter(
            "A1:D".max(2, $referenceRow - 1)
        );

        foreach (range('A', 'D') as $column) {
            $reference->getColumnDimension($column)
                ->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template-import-barang-simba.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /**
     * Membaca dan menyimpan data Excel.
     */
    public function store(
        ImportItemsRequest $request,
        ItemCodeService $codeService
    ): RedirectResponse {
        $uploadedFile = $request->file('file');

        try {
            $spreadsheet = IOFactory::load(
                $uploadedFile->getRealPath()
            );
        } catch (Throwable $exception) {
            return back()->with(
                'error',
                'Berkas Excel tidak dapat dibaca. Pastikan format berkas valid.'
            );
        }

        $sheet = $spreadsheet->getSheet(0);

        $rows = $sheet->toArray(
            null,
            true,
            true,
            false
        );

        if (count($rows) < 2) {
            return back()->with(
                'error',
                'Berkas Excel tidak memiliki data untuk diimport.'
            );
        }

        $actualHeaders = array_map(
            fn (mixed $header): string =>
                $this->normalizeHeader($header),
            array_pad(
                array_slice(
                    $rows[0],
                    0,
                    count(self::HEADERS)
                ),
                count(self::HEADERS),
                null
            )
        );

        if ($actualHeaders !== self::HEADERS) {
            return back()->with(
                'error',
                'Susunan kolom Excel tidak sesuai. Gunakan template terbaru dari SIMBA.'
            );
        }

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(
                fn (ItemCategory $category): string =>
                    strtoupper($category->code)
            );

        $units = Unit::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(
                fn (Unit $unit): string =>
                    strtoupper($unit->code)
            );

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(
                fn (Workshop $workshop): string =>
                    strtoupper($workshop->code)
            );

        $locations = StorageLocation::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(
                fn (StorageLocation $location): string =>
                    $location->workshop_id
                    .'|'
                    .strtoupper($location->code)
            );

        $parsedRows = [];
        $errors = [];
        $serialRows = [];

        foreach (
            array_slice(
                $rows,
                1,
                null,
                true
            )
            as $rowIndex => $row
        ) {
            $excelRow = $rowIndex + 1;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $row = array_pad(
                array_slice(
                    $row,
                    0,
                    count(self::HEADERS)
                ),
                count(self::HEADERS),
                null
            );

            $input = array_combine(
                self::HEADERS,
                $row
            );

            $rowErrors = [];

            $type = strtolower(
                trim((string) $input['jenis'])
            );

            if (
                ! in_array(
                    $type,
                    ['tool', 'material'],
                    true
                )
            ) {
                $rowErrors[] =
                    'Jenis harus tool atau material.';
            }

            $name = trim(
                (string) $input['nama']
            );

            if ($name === '') {
                $rowErrors[] =
                    'Nama barang wajib diisi.';
            }

            $categoryCode = strtoupper(
                trim(
                    (string) $input['kode_kategori']
                )
            );

            /** @var ItemCategory|null $category */
            $category = $categories->get(
                $categoryCode
            );

            if (! $category) {
                $rowErrors[] =
                    "Kode kategori {$categoryCode} tidak ditemukan.";
            } elseif (
                in_array(
                    $type,
                    ['tool', 'material'],
                    true
                )
                && ! in_array(
                    $category->applies_to,
                    [$type, 'both'],
                    true
                )
            ) {
                $rowErrors[] =
                    'Kategori tidak sesuai dengan jenis barang.';
            }

            $unitCode = strtoupper(
                trim(
                    (string) $input['kode_satuan']
                )
            );

            /** @var Unit|null $unit */
            $unit = $units->get($unitCode);

            if (! $unit) {
                $rowErrors[] =
                    "Kode satuan {$unitCode} tidak ditemukan.";
            }

            $workshopCode = strtoupper(
                trim(
                    (string) $input['kode_bengkel']
                )
            );

            /** @var Workshop|null $workshop */
            $workshop = $workshops->get(
                $workshopCode
            );

            if (! $workshop) {
                $rowErrors[] =
                    "Kode bengkel {$workshopCode} tidak ditemukan.";
            }

            $location = null;

            $locationCode = strtoupper(
                trim(
                    (string) $input['kode_lokasi']
                )
            );

            if (
                $locationCode !== ''
                && $workshop
            ) {
                $location = $locations->get(
                    $workshop->id
                    .'|'
                    .$locationCode
                );

                if (! $location) {
                    $rowErrors[] =
                        "Lokasi {$locationCode} tidak ditemukan pada bengkel {$workshopCode}.";
                }
            }

            $quantity = $type === 'tool'
                ? (int) $input['jumlah_alat']
                : 1;

            if (
                $type === 'tool'
                && (
                    $quantity < 1
                    || $quantity > 500
                )
            ) {
                $rowErrors[] =
                    'Jumlah alat harus antara 1 sampai 500.';
            }

            $serialNumbers =
                $this->parseSerialNumbers(
                    $input['nomor_seri']
                );

            if (
                $type === 'tool'
                && $serialNumbers !== []
                && count($serialNumbers) !== $quantity
            ) {
                $rowErrors[] =
                    'Jumlah nomor seri harus sama dengan jumlah alat.';
            }

            if (
                count($serialNumbers)
                !== count(array_unique($serialNumbers))
            ) {
                $rowErrors[] =
                    'Terdapat nomor seri ganda pada baris ini.';
            }

            foreach ($serialNumbers as $serialNumber) {
                $serialRows[$serialNumber][] =
                    $excelRow;
            }

            $receivedDate = $this->parseDate(
                $input['tanggal_masuk']
            );

            if (
                ! $this->isBlank(
                    $input['tanggal_masuk']
                )
                && $receivedDate === null
            ) {
                $rowErrors[] =
                    'Tanggal masuk tidak valid.';
            }

            $unitPrice = $this->parseNumber(
                $input['harga_satuan']
            );

            if (
                ! $this->isBlank(
                    $input['harga_satuan']
                )
                && $unitPrice === null
            ) {
                $rowErrors[] =
                    'Harga satuan harus berupa angka.';
            } elseif (
                $unitPrice !== null
                && $unitPrice < 0
            ) {
                $rowErrors[] =
                    'Harga satuan tidak boleh negatif.';
            }

            $condition = strtolower(
                trim(
                    (string) $input['kondisi']
                )
            );

            if ($type === 'tool') {
                $condition = $condition !== ''
                    ? $condition
                    : 'good';

                if (
                    ! array_key_exists(
                        $condition,
                        Item::conditionOptions()
                    )
                ) {
                    $rowErrors[] =
                        'Kondisi alat tidak valid.';
                }
            } else {
                $condition = 'good';
            }

            $stock = $this->parseNumber(
                $input['stok_awal']
            );

            $minimumStock = $this->parseNumber(
                $input['stok_minimum']
            );

            if ($type === 'material') {
                if ($stock === null) {
                    $rowErrors[] =
                        'Stok awal bahan wajib diisi.';
                } elseif ($stock < 0) {
                    $rowErrors[] =
                        'Stok awal tidak boleh negatif.';
                }

                if ($minimumStock === null) {
                    $rowErrors[] =
                        'Stok minimum bahan wajib diisi.';
                } elseif ($minimumStock < 0) {
                    $rowErrors[] =
                        'Stok minimum tidak boleh negatif.';
                }

                if (
                    $unit
                    && ! $unit->allows_decimal
                ) {
                    if (
                        $stock !== null
                        && abs(
                            $stock - round($stock)
                        ) > 0.000001
                    ) {
                        $rowErrors[] =
                            'Satuan ini tidak mengizinkan stok desimal.';
                    }

                    if (
                        $minimumStock !== null
                        && abs(
                            $minimumStock
                            - round($minimumStock)
                        ) > 0.000001
                    ) {
                        $rowErrors[] =
                            'Satuan ini tidak mengizinkan stok minimum desimal.';
                    }
                }
            } else {
                $stock = 1;
                $minimumStock = 0;
            }

            $isBorrowable =
                $this->parseBoolean(
                    $input['dapat_dipinjam'],
                    $type === 'tool'
                );

            if ($isBorrowable === null) {
                $rowErrors[] =
                    'Dapat dipinjam harus diisi ya atau tidak.';
            }

            if ($type === 'material') {
                $isBorrowable = false;
            }

            $isActive = $this->parseBoolean(
                $input['aktif'],
                true
            );

            if ($isActive === null) {
                $rowErrors[] =
                    'Status aktif harus diisi ya atau tidak.';
            }

            foreach ($rowErrors as $message) {
                $errors[] = [
                    'row' => $excelRow,
                    'message' => $message,
                ];
            }

            if ($rowErrors !== []) {
                continue;
            }

            $parsedRows[] = [
                'row' => $excelRow,
                'type' => $type,
                'quantity' => $quantity,
                'serial_numbers' =>
                    $serialNumbers,

                'workshop_model' => $workshop,
                'category_model' => $category,

                'data' => [
                    'type' => $type,
                    'name' => $name,

                    'item_category_id' =>
                        $category->id,

                    'unit_id' => $unit->id,

                    'workshop_id' =>
                        $workshop->id,

                    'storage_location_id' =>
                        $location?->id,

                    'brand' => $this->nullableString(
                        $input['merek']
                    ),

                    'model' => $this->nullableString(
                        $input['model']
                    ),

                    'specification' => null,

                    'received_date' =>
                        $receivedDate,

                    'acquisition_source' =>
                        $this->nullableString(
                            $input['sumber_perolehan']
                        ),

                    'fund_source' =>
                        $this->nullableString(
                            $input['sumber_dana']
                        ),

                    'unit_price' => $unitPrice,
                    'condition' => $condition,

                    'status' => $type === 'tool'
                        ? $this->statusFromCondition(
                            $condition
                        )
                        : (
                            $stock > 0
                                ? 'available'
                                : 'out_of_stock'
                        ),

                    'stock' => $stock,

                    'minimum_stock' =>
                        $minimumStock,

                    'is_borrowable' =>
                        $isBorrowable,

                    'description' =>
                        $this->nullableString(
                            $input['keterangan']
                        ),

                    'is_active' => $isActive,
                ],
            ];
        }

        /*
         * Nomor seri ganda di dalam file.
         */
        foreach (
            $serialRows
            as $serialNumber => $rowNumbers
        ) {
            if (count($rowNumbers) <= 1) {
                continue;
            }

            foreach (
                array_unique($rowNumbers)
                as $rowNumber
            ) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' =>
                        "Nomor seri {$serialNumber} muncul lebih dari satu kali dalam file.",
                ];
            }
        }

        /*
         * Nomor seri yang sudah ada di database.
         */
        if ($serialRows !== []) {
            $existingSerialNumbers = Item::query()
                ->whereIn(
                    'serial_number',
                    array_keys($serialRows)
                )
                ->pluck('serial_number');

            foreach (
                $existingSerialNumbers
                as $serialNumber
            ) {
                foreach (
                    $serialRows[
                        strtoupper($serialNumber)
                    ] ?? []
                    as $rowNumber
                ) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' =>
                            "Nomor seri {$serialNumber} sudah terdaftar.",
                    ];
                }
            }
        }

        if ($parsedRows === [] && $errors === []) {
            return back()->with(
                'error',
                'Tidak ditemukan baris data yang dapat diimport.'
            );
        }

        if ($errors !== []) {
            usort(
                $errors,
                fn (array $first, array $second): int =>
                    $first['row']
                    <=> $second['row']
            );

            return back()
                ->with(
                    'error',
                    'Import dibatalkan karena terdapat data yang tidak valid.'
                )
                ->with(
                    'import_errors',
                    array_slice(
                        $errors,
                        0,
                        200
                    )
                );
        }

        $createdCount = 0;

        DB::transaction(
            function () use (
                $parsedRows,
                $codeService,
                &$createdCount
            ): void {
                foreach (
                    $parsedRows
                    as $parsedRow
                ) {
                    $data = $parsedRow['data'];

                    /** @var Workshop $workshop */
                    $workshop =
                        $parsedRow['workshop_model'];

                    /** @var ItemCategory $category */
                    $category =
                        $parsedRow['category_model'];

                    if (
                        $parsedRow['type']
                        === 'tool'
                    ) {
                        for (
                            $index = 0;
                            $index
                                < $parsedRow['quantity'];
                            $index++
                        ) {
                            Item::query()->create([
                                ...$data,

                                'code' =>
                                    $codeService->generate(
                                        'tool',
                                        $workshop,
                                        $category
                                    ),

                                'serial_number' =>
                                    $parsedRow[
                                        'serial_numbers'
                                    ][$index] ?? null,
                            ]);

                            $createdCount++;
                        }

                        continue;
                    }

                    Item::query()->create([
                        ...$data,

                        'code' =>
                            $codeService->generate(
                                'material',
                                $workshop,
                                $category
                            ),

                        'serial_number' => null,
                    ]);

                    $createdCount++;
                }
            },
            attempts: 3
        );

        return redirect()
            ->route('items.index')
            ->with(
                'success',
                "{$createdCount} barang berhasil diimport."
            );
    }

    private function normalizeHeader(
        mixed $value
    ): string {
        $header = strtolower(
            trim((string) $value)
        );

        $header = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            $header
        );

        return trim(
            (string) $header,
            '_'
        );
    }

    private function isEmptyRow(
        array $row
    ): bool {
        foreach ($row as $value) {
            if (! $this->isBlank($value)) {
                return false;
            }
        }

        return true;
    }

    private function isBlank(
        mixed $value
    ): bool {
        return $value === null
            || trim((string) $value) === '';
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }

    private function parseSerialNumbers(
        mixed $value
    ): array {
        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        return collect(
            preg_split(
                '/[|\r\n]+/',
                $value
            ) ?: []
        )
            ->map(
                fn (string $serial): string =>
                    strtoupper(trim($serial))
            )
            ->filter()
            ->values()
            ->all();
    }

    private function parseNumber(
        mixed $value
    ): ?float {
        if ($this->isBlank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(
            ' ',
            '',
            trim((string) $value)
        );

        /*
         * Mendukung format Indonesia:
         * 1.500,50 menjadi 1500.50 (koma = desimal, titik = ribuan)
         */
        if (str_contains($normalized, ',')) {
            $normalized = str_replace(
                '.',
                '',
                $normalized
            );

            $normalized = str_replace(
                ',',
                '.',
                $normalized
            );
        } elseif (substr_count($normalized, '.') > 1) {
            /*
             * Tanpa koma, tetapi ada lebih dari satu titik:
             * 3.800.000.000.000 -> 3800000000000 (semua titik adalah
             * pemisah ribuan, bukan desimal). `(float)` langsung akan
             * memotong di titik kedua, jadi titik ribuan harus dihapus
             * sebelum dikonversi.
             */
            $normalized = str_replace(
                '.',
                '',
                $normalized
            );
        }

        return is_numeric($normalized)
            ? (float) $normalized
            : null;
    }

    private function parseBoolean(
        mixed $value,
        bool $default
    ): ?bool {
        if ($this->isBlank($value)) {
            return $default;
        }

        return match (
            strtolower(trim((string) $value))
        ) {
            '1',
            'ya',
            'yes',
            'true',
            'aktif' => true,

            '0',
            'tidak',
            'no',
            'false',
            'nonaktif' => false,

            default => null,
        };
    }

    private function parseDate(
        mixed $value
    ): ?string {
        if ($this->isBlank($value)) {
            return null;
        }

        try {
            if (
                is_numeric($value)
                && (float) $value > 0
            ) {
                return ExcelDate::
                    excelToDateTimeObject(
                        (float) $value
                    )
                    ->format('Y-m-d');
            }

            $dateValue = trim(
                (string) $value
            );

            foreach (
                [
                    'Y-m-d',
                    'd/m/Y',
                    'd-m-Y',
                ]
                as $format
            ) {
                try {
                    return Carbon::createFromFormat(
                        $format,
                        $dateValue
                    )->format('Y-m-d');
                } catch (Throwable) {
                    //
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
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