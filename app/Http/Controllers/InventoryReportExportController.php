<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportExportController extends Controller
{
    /**
     * Export laporan inventaris ke PDF.
     */
    public function pdf(Request $request): Response
    {
        $items = $this->inventoryQuery($request)->get();

        $totalValue = $items->sum(
            fn (object $item): float =>
                (float) $item->inventory_value
        );

        $data = [
            'items' => $items,
            'totalValue' => $totalValue,
            'filters' => [
                'search' => trim(
                    (string) $request->input('search')
                ),
                'workshop_id' => $request->input(
                    'workshop_id'
                ),
                'item_category_id' => $request->input(
                    'item_category_id'
                ),
                'type' => $request->input('type'),
                'condition' => $request->input(
                    'condition'
                ),
                'status' => $request->input('status'),
            ],
            'generatedAt' => now(),
        ];

        $filename =
            'laporan-inventaris-'.
            now()->format('Ymd-His').
            '.pdf';

        /*
         * Pilihan pertama: wrapper Laravel DomPDF.
         */
        if (
            class_exists(
                \Barryvdh\DomPDF\Facade\Pdf::class
            )
        ) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'reports.inventory-pdf',
                $data
            )
                ->setPaper('a4', 'landscape')
                ->setOption(
                    'defaultFont',
                    'DejaVu Sans'
                );

            return $pdf->download($filename);
        }

        /*
         * Pilihan kedua: DomPDF tanpa wrapper Laravel.
         */
        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set(
                'defaultFont',
                'DejaVu Sans'
            );
            $options->set(
                'isRemoteEnabled',
                false
            );

            $dompdf = new \Dompdf\Dompdf(
                $options
            );

            $html = view(
                'reports.inventory-pdf',
                $data
            )->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper(
                'A4',
                'landscape'
            );
            $dompdf->render();

            return response(
                $dompdf->output(),
                200,
                [
                    'Content-Type' =>
                        'application/pdf',

                    'Content-Disposition' =>
                        'attachment; filename="'.
                        $filename.
                        '"',
                ]
            );
        }

        abort(
            500,
            'Generator PDF belum tersedia. Instal barryvdh/laravel-dompdf menggunakan PHP XAMPP 8.4.'
        );
    }

    /**
     * Export laporan inventaris ke XLSX asli.
     */
    public function excel(Request $request): StreamedResponse
    {
        $headers = [
            'Kode', 'Nama Barang', 'Jenis', 'Kategori', 'Bengkel', 'Lokasi',
            'Merek', 'Model', 'Kondisi', 'Status', 'Stok', 'Satuan',
            'Harga Satuan', 'Nilai Inventaris',
        ];

        $items = $this->inventoryQuery($request)
            ->orderBy('items.id')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris');
        $sheet->fromArray($headers, null, 'A1');

        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAF7');

        $rowNumber = 2;
        foreach ($items as $item) {
            $stock = (float) $item->stock;
            $price = (float) $item->unit_price;

            // Alat dinilai dari harga satuan; bahan = stok x harga.
            $value = $item->type === 'tool' ? $price : $stock * $price;

            $sheet->fromArray([
                $item->code,
                $item->name,
                $this->typeLabel($item->type),
                $item->category_name ?? '-',
                $item->workshop_code ?? '-',
                $item->location_name ?? '-',
                $item->brand ?? '-',
                $item->model ?? '-',
                $this->label($item->condition),
                $this->label($item->status),
                $stock,
                $item->unit_symbol ?: ($item->unit_name ?? ''),
                $price,
                $value,
            ], null, "A{$rowNumber}");

            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i)
            )->setAutoSize(true);
        }

        $filename =
            'laporan-inventaris-'.
            now()->format('Ymd-His').
            '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Query inventaris yang sama untuk PDF dan Excel.
     */
    private function inventoryQuery(
        Request $request
    ): Builder {
        /*
         * Database SIMBA tidak selalu memakai kolom `symbol`
         * pada tabel units. Deteksi kolom singkatan yang tersedia.
         */
        $unitSymbolColumn = $this->firstExistingColumn(
            'units',
            [
                'symbol',
                'abbreviation',
                'abbreviated_name',
                'short_name',
                'short_code',
                'code',
            ]
        );

        $query = DB::table('items')
            ->leftJoin(
                'item_categories',
                'item_categories.id',
                '=',
                'items.item_category_id'
            )
            ->leftJoin(
                'units',
                'units.id',
                '=',
                'items.unit_id'
            )
            ->leftJoin(
                'workshops',
                'workshops.id',
                '=',
                'items.workshop_id'
            )
            ->leftJoin(
                'storage_locations',
                'storage_locations.id',
                '=',
                'items.storage_location_id'
            )
            ->select([
                'items.id',
                'items.code',
                'items.name',
                'items.type',
                'items.brand',
                'items.model',
                'items.serial_number',
                'items.condition',
                'items.status',
                'items.stock',
                'items.minimum_stock',
                'items.unit_price',
                'items.is_active',

                'item_categories.name as category_name',

                'units.name as unit_name',

                'workshops.code as workshop_code',
                'workshops.name as workshop_name',

                'storage_locations.name as location_name',
            ]);

        if ($unitSymbolColumn !== null) {
            $query->addSelect(
                DB::raw(
                    "units.`{$unitSymbolColumn}` as unit_symbol"
                )
            );
        } else {
            $query->addSelect(
                DB::raw('NULL as unit_symbol')
            );
        }

        $query->selectRaw(
                "
                CASE
                    WHEN items.type = 'tool'
                        THEN COALESCE(
                            items.unit_price,
                            0
                        )
                    ELSE
                        COALESCE(
                            items.unit_price,
                            0
                        )
                        *
                        COALESCE(
                            items.stock,
                            0
                        )
                END AS inventory_value
                "
            );

        $search = trim(
            (string) $request->input('search')
        );

        $query->when(
            $search !== '',
            function (
                Builder $searchQuery
            ) use ($search): void {
                $searchQuery->where(
                    function (
                        Builder $nestedQuery
                    ) use ($search): void {
                        $nestedQuery
                            ->where(
                                'items.code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'items.name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'items.brand',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'items.model',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'items.serial_number',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            }
        );

        $query->when(
            $request->filled('workshop_id'),
            fn (Builder $filteredQuery): Builder =>
                $filteredQuery->where(
                    'items.workshop_id',
                    $request->integer(
                        'workshop_id'
                    )
                )
        );

        $query->when(
            $request->filled(
                'item_category_id'
            ),
            fn (Builder $filteredQuery): Builder =>
                $filteredQuery->where(
                    'items.item_category_id',
                    $request->integer(
                        'item_category_id'
                    )
                )
        );

        $query->when(
            $request->filled('type'),
            fn (Builder $filteredQuery): Builder =>
                $filteredQuery->where(
                    'items.type',
                    (string) $request->input(
                        'type'
                    )
                )
        );

        $query->when(
            $request->filled('condition'),
            fn (Builder $filteredQuery): Builder =>
                $filteredQuery->where(
                    'items.condition',
                    (string) $request->input(
                        'condition'
                    )
                )
        );

        $query->when(
            $request->filled('status'),
            fn (Builder $filteredQuery): Builder =>
                $filteredQuery->where(
                    'items.status',
                    (string) $request->input(
                        'status'
                    )
                )
        );

        return $query
            ->orderBy('items.type')
            ->orderBy('items.name')
            ->orderBy('items.code');
    }

    private function firstExistingColumn(
        string $table,
        array $candidates
    ): ?string {
        if (! Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function typeLabel(
        ?string $type
    ): string {
        return $type === 'material'
            ? 'Bahan'
            : 'Alat';
    }

    private function label(
        ?string $value
    ): string {
        if (
            $value === null
            || $value === ''
        ) {
            return '-';
        }

        return ucwords(
            str_replace(
                '_',
                ' ',
                $value
            )
        );
    }
}
