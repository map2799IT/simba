<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStockMovement;
use App\Models\Loan;
use App\Models\Workshop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'inventory' => 'Inventaris Barang',
        'low_stock' => 'Stok Rendah',
        'stock_movements' => 'Mutasi Stok',
        'stock_receipts' => 'Barang Masuk',
        'loans' => 'Peminjaman dan Pengembalian',
        'damages' => 'Kerusakan dan Perbaikan',
    ];

    public function index(Request $request): View
    {
        $reportType = $this->resolveReportType($request);

        $rows = $this->buildQuery(
            $request,
            $reportType
        )
            ->paginate(25)
            ->withQueryString();

        return view('reports.index', [
            'reportType' => $reportType,

            'reportTitle' =>
                self::REPORT_TYPES[$reportType],

            'reportTypes' =>
                self::REPORT_TYPES,

            'rows' => $rows,

            'summary' =>
                $this->summary(),

            'workshops' =>
                Workshop::query()
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->get(),

            'categories' =>
                ItemCategory::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),

            'itemTypes' =>
                Item::typeOptions(),

            'itemStatuses' =>
                Item::statusOptions(),

            'movementTypes' =>
                ItemStockMovement::typeOptions(),

            'loanStatuses' =>
                Loan::filterStatusOptions(),

            'damageStatuses' =>
                DamageReport::statusOptions(),

            'damageSeverities' =>
                DamageReport::severityOptions(),
        ]);
    }

    public function stock(Request $request): View
    {
        $request->merge(['report' => 'low_stock']);

        return $this->renderNamedReport($request, 'low_stock');
    }

    public function loans(Request $request): View
    {
        $request->merge(['report' => 'loans']);

        return $this->renderNamedReport($request, 'loans');
    }

    public function damages(Request $request): View
    {
        $request->merge(['report' => 'damages']);

        return $this->renderNamedReport($request, 'damages');
    }

    public function stockMovements(Request $request): View
    {
        $request->merge(['report' => 'stock_movements']);

        return $this->renderNamedReport($request, 'stock_movements');
    }

    public function stockReceipts(Request $request): View
    {
        $workshops = Workshop::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $rows = $this->stockReceiptsQuery($request)
            ->paginate(25)
            ->withQueryString();

        $summary = $this->stockReceiptsSummary($request);

        return view('reports.stock-receipts', [
            'reportTitle' => 'Laporan Barang Masuk',
            'rows'        => $rows,
            'workshops'   => $workshops,
            'categories'  => $categories,
            'summary'     => $summary,
        ]);
    }

    public function stockReceiptsPdf(Request $request): Response
    {
        $rows = $this->stockReceiptsQuery($request)->get();

        $pdf = Pdf::loadView('reports.stock-receipts-pdf', [
            'reportTitle' => 'Laporan Barang Masuk',
            'rows'        => $rows,
            'filters'     => $request->query(),
            'summary'     => $this->stockReceiptsSummary($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            sprintf('laporan-barang-masuk-%s.pdf', now()->format('Ymd-His'))
        );
    }

    public function stockReceiptsExcel(Request $request): StreamedResponse
    {
        $rows = $this->stockReceiptsQuery($request)->get();

        $headers = [
            'Tanggal Masuk',
            'Kode Penerimaan',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Bengkel',
            'Merek',
            'Model',
            'Spesifikasi',
            'Jumlah Masuk',
            'Satuan',
            'Kondisi',
            'Sumber Dana',
            'Harga Satuan',
            'Total Nilai',
            'Referensi',
            'Sumber',
            'Lokasi Simpan',
            'Petugas',
            'Keterangan',
        ];

        $excelRows = $rows->map(function (ItemStockMovement $m): array {
            $qty        = (float) $m->quantity;
            $unitPrice  = (float) ($m->unit_price ?? 0);

            return [
                $m->transaction_date?->format('Y-m-d'),
                $m->receipt_code,
                $m->item?->code,
                $m->item?->name,
                $m->item?->category?->name,
                $m->item?->workshop?->code,
                $m->brand ?? $m->item?->brand,
                $m->model ?? $m->item?->model,
                $m->specification,
                $qty,
                $m->item?->unit?->code,
                $m->condition,
                $m->fund_source,
                $unitPrice ?: null,
                $unitPrice ? round($qty * $unitPrice, 2) : null,
                $m->reference_number,
                $m->source,
                $m->storageLocation?->name,
                $m->user?->name ?? 'Sistem',
                $m->description,
            ];
        })->all();

        $reportTitle = 'Laporan Barang Masuk';

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(Str::limit($reportTitle, 31, ''));
        $sheet->fromArray($headers, null, 'A1');

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAF7');

        $rowNumber = 2;
        foreach ($excelRows as $excelRow) {
            $sheet->fromArray($excelRow, null, "A{$rowNumber}");
            if (isset($excelRow[0]) && $excelRow[0] !== null) {
                $sheet->setCellValueExplicit(
                    "A{$rowNumber}",
                    (string) $excelRow[0],
                    DataType::TYPE_STRING
                );
            }
            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(
                Coordinate::stringFromColumnIndex($i)
            )->setAutoSize(true);
        }

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            sprintf('laporan-barang-masuk-%s.xlsx', now()->format('Ymd-His')),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function stockPdf(Request $request): Response
    {
        $request->merge(['report' => 'low_stock']);

        return $this->pdf($request);
    }

    public function stockExcel(Request $request): StreamedResponse
    {
        $request->merge(['report' => 'low_stock']);

        return $this->excel($request);
    }

    public function loansPdf(Request $request): Response
    {
        $request->merge(['report' => 'loans']);

        return $this->pdf($request);
    }

    public function loansExcel(Request $request): StreamedResponse
    {
        $request->merge(['report' => 'loans']);

        return $this->excel($request);
    }

    public function damagesPdf(Request $request): Response
    {
        $request->merge(['report' => 'damages']);

        return $this->pdf($request);
    }

    public function damagesExcel(Request $request): StreamedResponse
    {
        $request->merge(['report' => 'damages']);

        return $this->excel($request);
    }

    public function stockMovementsPdf(Request $request): Response
    {
        $request->merge(['report' => 'stock_movements']);

        return $this->pdf($request);
    }

    public function stockMovementsExcel(Request $request): StreamedResponse
    {
        $request->merge(['report' => 'stock_movements']);

        return $this->excel($request);
    }

    private function renderNamedReport(Request $request, string $reportType): View
    {
        $request->merge(['report' => $reportType]);

        return view('reports.table', [
            'reportType' => $reportType,
            'reportTitle' => self::REPORT_TYPES[$reportType],
            'rows' => $this->buildQuery($request, $reportType)
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function excel(
        Request $request
    ): StreamedResponse {
        $reportType = $this->resolveReportType(
            $request
        );

        $reportTitle =
            self::REPORT_TYPES[$reportType];

        $rows = $this->buildQuery(
            $request,
            $reportType
        )->get();

        [
            'headers' => $headers,
            'rows' => $excelRows,
        ] = $this->excelDefinition(
            $reportType,
            $rows
        );

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            Str::limit(
                $reportTitle,
                31,
                ''
            )
        );

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        $lastColumn =
            Coordinate::stringFromColumnIndex(
                count($headers)
            );

        $sheet->getStyle(
            "A1:{$lastColumn}1"
        )
            ->getFont()
            ->setBold(true);

        $sheet->getStyle(
            "A1:{$lastColumn}1"
        )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet->getStyle(
            "A1:{$lastColumn}1"
        )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFD9EAF7');

        $rowNumber = 2;

        foreach ($excelRows as $excelRow) {
            $sheet->fromArray(
                $excelRow,
                null,
                "A{$rowNumber}"
            );

            /*
             * Kolom pertama umumnya merupakan kode.
             * Disimpan sebagai teks agar tidak diubah Excel.
             */
            if (
                isset($excelRow[0])
                && $excelRow[0] !== null
            ) {
                $sheet->setCellValueExplicit(
                    "A{$rowNumber}",
                    (string) $excelRow[0],
                    DataType::TYPE_STRING
                );
            }

            $rowNumber++;
        }

        $lastRow = max(
            2,
            $rowNumber - 1
        );

        $sheet->freezePane('A2');

        $sheet->setAutoFilter(
            "A1:{$lastColumn}{$lastRow}"
        );

        for (
            $columnIndex = 1;
            $columnIndex <= count($headers);
            $columnIndex++
        ) {
            $column =
                Coordinate::stringFromColumnIndex(
                    $columnIndex
                );

            $sheet->getColumnDimension(
                $column
            )->setAutoSize(true);
        }

        $filename = sprintf(
            'laporan-%s-%s.xlsx',
            Str::slug($reportTitle),
            now()->format('Ymd-His')
        );

        return response()->streamDownload(
            function () use (
                $spreadsheet
            ): void {
                $writer = new Xlsx(
                    $spreadsheet
                );

                $writer->save(
                    'php://output'
                );

                $spreadsheet
                    ->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function pdf(
        Request $request
    ): Response {
        $reportType = $this->resolveReportType(
            $request
        );

        $reportTitle =
            self::REPORT_TYPES[$reportType];

        $rows = $this->buildQuery(
            $request,
            $reportType
        )->get();

        $pdf = Pdf::loadView(
            'reports.pdf',
            [
                'reportType' =>
                    $reportType,

                'reportTitle' =>
                    $reportTitle,

                'rows' => $rows,

                'filters' =>
                    $request->query(),

                'summary' =>
                    $this->summary(),
            ]
        )->setPaper(
            'a4',
            'landscape'
        );

        return $pdf->download(
            sprintf(
                'laporan-%s-%s.pdf',
                Str::slug($reportTitle),
                now()->format('Ymd-His')
            )
        );
    }

    private function resolveReportType(
        Request $request
    ): string {
        $reportType = (string)
            $request->input(
                'report',
                'inventory'
            );

        return array_key_exists(
            $reportType,
            self::REPORT_TYPES
        )
            ? $reportType
            : 'inventory';
    }

    private function buildQuery(
        Request $request,
        string $reportType
    ): Builder {
        return match ($reportType) {
            'low_stock' =>
                $this->lowStockQuery(
                    $request
                ),

            'stock_movements' =>
                $this->stockMovementQuery(
                    $request
                ),

            'loans' =>
                $this->loanQuery(
                    $request
                ),

            'damages' =>
                $this->damageQuery(
                    $request
                ),

            default =>
                $this->inventoryQuery(
                    $request
                ),
        };
    }

    private function stockReceiptsQuery(Request $request): Builder
    {
        $search = trim((string) $request->input('search'));

        return ItemStockMovement::query()
            ->with([
                'item.category',
                'item.unit',
                'item.workshop',
                'storageLocation',
                'user',
            ])
            ->where('type', ItemStockMovement::TYPE_INCOMING)
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $sub) use ($search): void {
                        $sub->where('receipt_code', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('fund_source', 'like', "%{$search}%")
                            ->orWhereHas('item', function (Builder $itemQuery) use ($search): void {
                                $itemQuery
                                    ->where('code', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('item_id'),
                fn (Builder $q): Builder => $q->where('item_id', $request->input('item_id'))
            )
            ->when(
                $request->filled('item_category_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('item_category_id', $request->input('item_category_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('year'),
                fn (Builder $q): Builder => $q->whereYear('transaction_date', $request->integer('year'))
            )
            ->when(
                $request->filled('sort'),
                function (Builder $q) use ($request): void {
                    $dir = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';
                    match ($request->input('sort')) {
                        'item_name'        => $q->orderBy(
                            \App\Models\Item::select('name')
                                ->whereColumn('items.id', 'item_stock_movements.item_id')
                                ->limit(1),
                            $dir
                        ),
                        'transaction_date' => $q->orderBy('transaction_date', $dir),
                        'quantity'         => $q->orderBy('quantity', $dir),
                        default            => null,
                    };
                },
                fn (Builder $q): Builder => $q->orderBy(
                    \App\Models\Item::select('name')
                        ->whereColumn('items.id', 'item_stock_movements.item_id')
                        ->limit(1)
                )->orderByDesc('transaction_date')
            );
    }

    private function stockReceiptsSummary(Request $request): array
    {
        $base = ItemStockMovement::query()
            ->where('type', ItemStockMovement::TYPE_INCOMING)
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('year'),
                fn (Builder $q): Builder => $q->whereYear('transaction_date', $request->integer('year'))
            );

        return [
            'total_transactions' => (clone $base)->count(),
            'total_quantity'     => (float) (clone $base)->sum('quantity'),
            'total_value'        => (float) (clone $base)->selectRaw('SUM(quantity * COALESCE(unit_price, 0)) as v')->value('v'),
            'unique_items'       => (clone $base)->distinct('item_id')->count('item_id'),
        ];
    }

    private function stockIssuesQuery(Request $request): Builder
    {
        $search = trim((string) $request->input('search'));

        return ItemStockMovement::query()
            ->with([
                'item.category',
                'item.unit',
                'item.workshop',
                'storageLocation',
                'user',
            ])
            ->where('type', ItemStockMovement::TYPE_OUTGOING)
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $sub) use ($search): void {
                        $sub->where('reference_number', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhere('purpose', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('item', function (Builder $itemQuery) use ($search): void {
                                $itemQuery
                                    ->where('code', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('item_category_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('item_category_id', $request->input('item_category_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('sort'),
                function (Builder $q) use ($request): void {
                    $dir = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
                    match ($request->input('sort')) {
                        'item_name' => $q->orderBy(
                            \App\Models\Item::select('name')
                                ->whereColumn('items.id', 'item_stock_movements.item_id')
                                ->limit(1),
                            $dir
                        ),
                        'quantity'  => $q->orderBy('quantity', $dir),
                        default     => $q->orderBy('transaction_date', $dir)->orderByDesc('id'),
                    };
                },
                fn (Builder $q): Builder => $q->orderByDesc('transaction_date')->orderByDesc('id')
            );
    }

    private function stockIssuesSummary(Request $request): array
    {
        $base = ItemStockMovement::query()
            ->where('type', ItemStockMovement::TYPE_OUTGOING)
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            );

        return [
            'total_transactions' => (clone $base)->count(),
            'total_quantity'     => (float) (clone $base)->sum('quantity'),
            'unique_items'       => (clone $base)->distinct('item_id')->count('item_id'),
        ];
    }

    private function inventoryQuery(
        Request $request
    ): Builder {
        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        return Item::query()
            ->with([
                'category',
                'unit',
                'workshop',
                'location.parent.parent.parent',
            ])
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
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
                                    'brand',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'model',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'serial_number',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('type'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'type',
                        $request->input(
                            'type'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'workshop_id'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'workshop_id',
                        $request->input(
                            'workshop_id'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'item_category_id'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'item_category_id',
                        $request->input(
                            'item_category_id'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'item_status'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'status',
                        $request->input(
                            'item_status'
                        )
                    )
            )
            ->when(
                $request->filled('date_from'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'received_date',
                        '>=',
                        $request->input(
                            'date_from'
                        )
                    )
            )
            ->when(
                $request->filled('date_to'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'received_date',
                        '<=',
                        $request->input(
                            'date_to'
                        )
                    )
            )
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('code');
    }

    private function lowStockQuery(
        Request $request
    ): Builder {
        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        return Item::query()
            ->with([
                'category',
                'unit',
                'workshop',
                'location.parent.parent.parent',
            ])
            ->where(
                'type',
                'material'
            )
            ->where(
                'is_active',
                true
            )
            ->whereColumn(
                'stock',
                '<=',
                'minimum_stock'
            )
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
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
                                    'brand',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled(
                    'workshop_id'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'workshop_id',
                        $request->input(
                            'workshop_id'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'item_category_id'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'item_category_id',
                        $request->input(
                            'item_category_id'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'item_status'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'status',
                        $request->input(
                            'item_status'
                        )
                    )
            )
            ->orderByRaw(
                'stock ASC'
            )
            ->orderBy('name')
            ->orderBy('code');
    }

    private function stockMovementQuery(
        Request $request
    ): Builder {
        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        return ItemStockMovement::query()
            ->with([
                'item.category',
                'item.unit',
                'item.workshop',
                'item.location.parent.parent.parent',
                'user',
            ])
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $subquery
                        ) use ($search): void {
                            $subquery
                                ->where(
                                    'reference_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'source',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'destination',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'purpose',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled(
                    'movement_type'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'type',
                        $request->input(
                            'movement_type'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'workshop_id'
                ),
                function (
                    Builder $query
                ) use ($request): void {
                    $query->whereHas(
                        'item',
                        fn (
                            Builder $itemQuery
                        ): Builder =>
                            $itemQuery->where(
                                'workshop_id',
                                $request->input(
                                    'workshop_id'
                                )
                            )
                    );
                }
            )
            ->when(
                $request->filled('date_from'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'transaction_date',
                        '>=',
                        $request->input(
                            'date_from'
                        )
                    )
            )
            ->when(
                $request->filled('date_to'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'transaction_date',
                        '<=',
                        $request->input(
                            'date_to'
                        )
                    )
            )
            ->orderByDesc(
                'transaction_date'
            )
            ->orderByDesc('id');
    }

    private function loanQuery(
        Request $request
    ): Builder {
        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        return Loan::query()
            ->with([
                'borrower',
                'approver',
                'items.item.category',
                'items.item.workshop',
            ])
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
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
                                    'purpose',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'borrower',
                                    fn (
                                        Builder $userQuery
                                    ): Builder =>
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'items.item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled(
                    'loan_status'
                ),
                function (
                    Builder $query
                ) use ($request): void {
                    $status = (string)
                        $request->input(
                            'loan_status'
                        );

                    if ($status === 'overdue') {
                        $query
                            ->whereIn(
                                'status',
                                [
                                    Loan::STATUS_BORROWED,
                                    Loan::STATUS_PARTIAL,
                                ]
                            )
                            ->where(
                                'due_at',
                                '<',
                                now()
                            );

                        return;
                    }

                    $query->where(
                        'status',
                        $status
                    );
                }
            )
            ->when(
                $request->filled(
                    'workshop_id'
                ),
                function (
                    Builder $query
                ) use ($request): void {
                    $query->whereHas(
                        'items.item',
                        fn (
                            Builder $itemQuery
                        ): Builder =>
                            $itemQuery->where(
                                'workshop_id',
                                $request->input(
                                    'workshop_id'
                                )
                            )
                    );
                }
            )
            ->when(
                $request->filled('date_from'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'request_date',
                        '>=',
                        $request->input(
                            'date_from'
                        )
                    )
            )
            ->when(
                $request->filled('date_to'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'request_date',
                        '<=',
                        $request->input(
                            'date_to'
                        )
                    )
            )
            ->orderByDesc(
                'request_date'
            )
            ->orderByDesc('id');
    }

    private function damageQuery(
        Request $request
    ): Builder {
        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        return DamageReport::query()
            ->with([
                'item.category',
                'item.workshop',
                'item.location.parent.parent.parent',
                'reporter',
                'handler',
                'completer',
            ])
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
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
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'diagnosis',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled(
                    'damage_status'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'status',
                        $request->input(
                            'damage_status'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'severity'
                ),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'severity',
                        $request->input(
                            'severity'
                        )
                    )
            )
            ->when(
                $request->filled(
                    'workshop_id'
                ),
                function (
                    Builder $query
                ) use ($request): void {
                    $query->whereHas(
                        'item',
                        fn (
                            Builder $itemQuery
                        ): Builder =>
                            $itemQuery->where(
                                'workshop_id',
                                $request->input(
                                    'workshop_id'
                                )
                            )
                    );
                }
            )
            ->when(
                $request->filled('date_from'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'reported_at',
                        '>=',
                        $request->input(
                            'date_from'
                        )
                    )
            )
            ->when(
                $request->filled('date_to'),
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'reported_at',
                        '<=',
                        $request->input(
                            'date_to'
                        )
                    )
            )
            ->orderByDesc(
                'reported_at'
            )
            ->orderByDesc('id');
    }

    private function summary(): array
    {
        $assetValue = Item::query()
            ->where('is_active', true)
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'tool'
                                THEN COALESCE(unit_price, 0)
                            ELSE
                                COALESCE(unit_price, 0)
                                * COALESCE(stock, 0)
                        END
                    ),
                    0
                ) AS total_value
            ")
            ->value('total_value');

        return [
            'tools' =>
                Item::query()
                    ->where('type', 'tool')
                    ->where('is_active', true)
                    ->count(),

            'materials' =>
                Item::query()
                    ->where('type', 'material')
                    ->where('is_active', true)
                    ->count(),

            'low_stock' =>
                Item::query()
                    ->where('type', 'material')
                    ->where('is_active', true)
                    ->whereColumn(
                        'stock',
                        '<=',
                        'minimum_stock'
                    )
                    ->count(),

            'open_loans' =>
                Loan::query()
                    ->whereIn(
                        'status',
                        [
                            Loan::STATUS_BORROWED,
                            Loan::STATUS_PARTIAL,
                        ]
                    )
                    ->count(),

            'open_damages' =>
                DamageReport::query()
                    ->whereIn(
                        'status',
                        DamageReport::openStatuses()
                    )
                    ->count(),

            'asset_value' =>
                (float) $assetValue,
        ];
    }

    private function excelDefinition(
        string $reportType,
        Collection $rows
    ): array {
        return match ($reportType) {
            'stock_movements' => [
                'headers' => [
                    'Tanggal',
                    'Kode Barang',
                    'Nama Barang',
                    'Bengkel',
                    'Jenis Transaksi',
                    'Referensi',
                    'Sumber / Tujuan',
                    'Stok Sebelum',
                    'Perubahan',
                    'Stok Sesudah',
                    'Satuan',
                    'Petugas',
                    'Keterangan',
                ],

                'rows' => $rows->map(
                    function (
                        ItemStockMovement $movement
                    ): array {
                        return [
                            $movement
                                ->transaction_date
                                ->format('Y-m-d'),

                            $movement->item?->code,

                            $movement->item?->name,

                            $movement
                                ->item
                                ?->workshop
                                ?->code,

                            $movement->typeLabel(),

                            $movement
                                ->reference_number,

                            $movement->source
                                ?: $movement->destination,

                            (float)
                                $movement->stock_before,

                            $movement->difference(),

                            (float)
                                $movement->stock_after,

                            $movement
                                ->item
                                ?->unit
                                ?->code,

                            $movement->user?->name
                                ?? 'Sistem',

                            $movement->description,
                        ];
                    }
                )->all(),
            ],

            'loans' => [
                'headers' => [
                    'Kode Peminjaman',
                    'Peminjam',
                    'Tanggal Pengajuan',
                    'Batas Kembali',
                    'Status',
                    'Jumlah Alat',
                    'Alat',
                    'Keperluan',
                    'Disetujui Oleh',
                    'Tanggal Kembali',
                ],

                'rows' => $rows->map(
                    function (
                        Loan $loan
                    ): array {
                        return [
                            $loan->code,

                            $loan->borrower?->name,

                            $loan
                                ->request_date
                                ?->format('Y-m-d'),

                            $loan
                                ->due_at
                                ?->format(
                                    'Y-m-d H:i'
                                ),

                            $loan->statusLabel(),

                            $loan->items->count(),

                            $loan->items
                                ->map(
                                    fn (
                                        $loanItem
                                    ): ?string =>
                                        $loanItem
                                            ->item
                                            ?->code
                                )
                                ->filter()
                                ->implode(', '),

                            $loan->purpose,

                            $loan
                                ->approver
                                ?->name,

                            $loan
                                ->returned_at
                                ?->format(
                                    'Y-m-d H:i'
                                ),
                        ];
                    }
                )->all(),
            ],

            'damages' => [
                'headers' => [
                    'Kode Laporan',
                    'Kode Alat',
                    'Nama Alat',
                    'Bengkel',
                    'Waktu Laporan',
                    'Tingkat Kerusakan',
                    'Status',
                    'Pelapor',
                    'Petugas',
                    'Deskripsi',
                    'Diagnosis',
                    'Tindakan',
                    'Vendor',
                    'Biaya Perbaikan',
                    'Selesai',
                ],

                'rows' => $rows->map(
                    function (
                        DamageReport $report
                    ): array {
                        return [
                            $report->code,

                            $report->item?->code,

                            $report->item?->name,

                            $report
                                ->item
                                ?->workshop
                                ?->code,

                            $report
                                ->reported_at
                                ?->format(
                                    'Y-m-d H:i'
                                ),

                            $report
                                ->severityLabel(),

                            $report
                                ->statusLabel(),

                            $report
                                ->reporter
                                ?->name
                                ?? 'Sistem',

                            $report
                                ->handler
                                ?->name,

                            $report->description,

                            $report->diagnosis,

                            $report
                                ->action_taken,

                            $report->vendor,

                            $report
                                ->repair_cost !== null
                                    ? (float)
                                        $report
                                            ->repair_cost
                                    : null,

                            $report
                                ->completed_at
                                ?->format(
                                    'Y-m-d H:i'
                                ),
                        ];
                    }
                )->all(),
            ],

            default => [
                'headers' => [
                    'Kode',
                    'Nama Barang',
                    'Jenis',
                    'Kategori',
                    'Satuan',
                    'Bengkel',
                    'Lokasi',
                    'Merek',
                    'Model',
                    'Nomor Seri',
                    'Kondisi',
                    'Status',
                    'Stok',
                    'Stok Minimum',
                    'Harga Satuan',
                    'Nilai Barang',
                    'Tanggal Masuk',
                    'Aktif',
                ],

                'rows' => $rows->map(
                    function (
                        Item $item
                    ): array {
                        $value = $item->isTool()
                            ? (float)
                                ($item->unit_price
                                    ?? 0)
                            : (
                                (float)
                                    ($item->unit_price
                                        ?? 0)
                                * (float)
                                    $item->stock
                            );

                        return [
                            $item->code,
                            $item->name,
                            $item->typeLabel(),

                            $item
                                ->category
                                ?->name,

                            $item
                                ->unit
                                ?->code,

                            $item
                                ->workshop
                                ?->code,

                            $item
                                ->location
                                ?->full_path,

                            $item->brand,
                            $item->model,

                            $item
                                ->serial_number,

                            $item
                                ->conditionLabel(),

                            $item
                                ->statusLabel(),

                            (float) $item->stock,

                            (float)
                                $item->minimum_stock,

                            $item
                                ->unit_price !== null
                                    ? (float)
                                        $item->unit_price
                                    : null,

                            $value,

                            $item
                                ->received_date
                                ?->format('Y-m-d'),

                            $item->is_active
                                ? 'Ya'
                                : 'Tidak',
                        ];
                    }
                )->all(),
            ],
        };
    }
}
