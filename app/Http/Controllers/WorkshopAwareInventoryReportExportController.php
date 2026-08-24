<?php

namespace App\Http\Controllers;

use App\Models\ItemStockMovement;
use App\Services\InventoryPlacementReportService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkshopAwareInventoryReportExportController extends Controller
{
    public function pdf(Request $request): mixed
    {
        $service = app(
            InventoryPlacementReportService::class
        );

        // Tangani tab barang masuk / barang keluar
        $tab = in_array($request->input('tab'), ['barang_masuk', 'barang_keluar'], true)
            ? $request->input('tab')
            : 'inventaris';

        if ($tab !== 'inventaris') {
            $type = $tab === 'barang_masuk'
                ? ItemStockMovement::TYPE_INCOMING
                : ItemStockMovement::TYPE_OUTGOING;

            $rows = $this->movementExportQuery($request, $type)->get();

            $baseTitle = $tab === 'barang_masuk'
                ? 'Laporan Barang Masuk'
                : 'Laporan Barang Keluar';

            $periodLabel = $this->reportPeriodLabel($request);

            $reportTitle = $baseTitle . $periodLabel;

            $view = $tab === 'barang_masuk'
                ? 'reports.stock-receipts-pdf'
                : 'reports.stock-issues-pdf';

            $slug = ($tab === 'barang_masuk' ? 'laporan-barang-masuk' : 'laporan-barang-keluar')
                . ($periodLabel !== ''
                    ? '-' . preg_replace('/[^0-9A-Za-z-]+/', '', str_replace(' ', '-', strtolower($periodLabel)))
                    : '');

            $filename = $slug . '-' . now()->format('Ymd-His') . '.pdf';

            $data = [
                'reportTitle' => $reportTitle,
                'periodLabel' => trim($periodLabel),
                'rows'        => $rows,
                'filters'     => $request->query(),
                'summary'     => $this->movementSummary($request, $type),
            ];

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                return \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data)
                    ->setPaper('a4', 'landscape')
                    ->download($filename);
            }

            return response()->view($view, array_merge($data, ['pdfFallback' => true]));
        }

        $items =
            $service->all($request);

        $summary =
            $service->summary($request);

        $selectedWorkshopId =
            $service->effectiveWorkshopId(
                $request
            );

        $scopeLabel =
            'Semua jurusan';

        if ($selectedWorkshopId !== null) {
            $workshop =
                \Illuminate\Support\Facades\DB::table(
                    'workshops'
                )
                    ->where(
                        'id',
                        $selectedWorkshopId
                    )
                    ->first();

            $scopeLabel =
                $workshop !== null
                    ? $workshop->code.
                        ' - '.
                        $workshop->name
                    : 'Jurusan tidak ditemukan';
        }

        $data = [
            'items' => $items,
            'totalValue' =>
                $summary['total_value'],
            'generatedAt' => now(),
            'scopeLabel' => $scopeLabel,
            'filters' => $request->query(),
            'periodLabel' => trim($this->reportPeriodLabel($request)),
        ];

        $periodLabel = $this->reportPeriodLabel($request);

        $filename =
            'laporan-inventaris'
            . ($periodLabel !== ''
                ? '-' . preg_replace('/[^0-9A-Za-z-]+/', '', str_replace(' ', '-', strtolower($periodLabel)))
                : '')
            . '-' . now()->format('Ymd-His') . '.pdf';

        if (
            class_exists(
                \Barryvdh\DomPDF\Facade\Pdf::class
            )
        ) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'reports.inventory-pdf',
                $data
            )
                ->setPaper(
                    'a4',
                    'landscape'
                )
                ->download($filename);
        }

        return response()->view(
            'reports.inventory-pdf',
            array_merge(
                $data,
                [
                    'pdfFallback' => true,
                ]
            )
        );
    }

    private function reportPeriodLabel(Request $request): string
    {
        $filters = $request->query();

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = ! empty($filters['date_from'])
                ? \Illuminate\Support\Carbon::parse($filters['date_from'])
                : null;
            $to = ! empty($filters['date_to'])
                ? \Illuminate\Support\Carbon::parse($filters['date_to'])
                : null;

            $label = ($from ? $from->format('d-m-Y') : '...')
                . ' s/d '
                . ($to ? $to->format('d-m-Y') : '...');

            return ' (Periode ' . $label . ')';
        }

        if (! empty($filters['year'])) {
            return ' ' . (int) $filters['year'];
        }

        return '';
    }

    private function workshopCode(mixed $movement): ?string
    {
        $code = $movement->item?->workshop?->code;
        if ($code === null || $code === '') {
            $code = $movement->item?->itemAssets?->first()?->workshop?->code;
        }
        return $code ?: null;
    }

    private function movementExportQuery(Request $request, string $type): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim((string) $request->input('search'));

        return ItemStockMovement::query()
            ->with(['item.category', 'item.unit', 'item.workshop', 'item.itemAssets.workshop', 'storageLocation', 'user'])
            ->where('type', $type)
            ->when(
                $search !== '',
                function ($q) use ($search): void {
                    $q->where(function ($sub) use ($search): void {
                        $sub->where('reference_number', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            ->orWhere('purpose', 'like', "%{$search}%")
                            ->orWhereHas('item', function ($iq) use ($search): void {
                                $iq->where('code', 'like', "%{$search}%")
                                   ->orWhere('name', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                $request->filled('workshop_id'),
                fn ($q) => $q->whereHas(
                    'item',
                    fn ($iq) => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('item_category_id'),
                fn ($q) => $q->whereHas(
                    'item',
                    fn ($iq) => $iq->where('item_category_id', $request->input('item_category_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn ($q) => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('year'),
                fn ($q) => $q->whereYear('transaction_date', $request->integer('year'))
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    private function movementSummary(Request $request, string $type): array
    {
        $base = ItemStockMovement::query()
            ->where('type', $type)
            ->when(
                $request->filled('workshop_id'),
                fn ($q) => $q->whereHas(
                    'item',
                    fn ($iq) => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn ($q) => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('year'),
                fn ($q) => $q->whereYear('transaction_date', $request->integer('year'))
            );

        $result = [
            'total_transactions' => (clone $base)->count(),
            'total_quantity'     => (float) (clone $base)->sum('quantity'),
            'unique_items'       => (clone $base)->distinct('item_id')->count('item_id'),
        ];

        if ($type === ItemStockMovement::TYPE_INCOMING) {
            $result['total_value'] = (float) (clone $base)
                ->selectRaw('SUM(quantity * COALESCE(unit_price, 0)) as v')
                ->value('v');
        }

        return $result;
    }

    public function excel(
        Request $request
    ): StreamedResponse {
        $tab = in_array($request->input('tab'), ['barang_masuk', 'barang_keluar'], true)
            ? $request->input('tab')
            : 'inventaris';

        if ($tab !== 'inventaris') {
            $type = $tab === 'barang_masuk'
                ? ItemStockMovement::TYPE_INCOMING
                : ItemStockMovement::TYPE_OUTGOING;

            return $this->movementExcel($request, $type);
        }

        return $this->inventoryExcel($request);
    }

    /**
     * Export laporan inventaris ke XLSX asli.
     *
     * Sel angka ditulis sebagai nilai numerik (bukan teks) sehingga
     * Excel membaca 3800 sebagai 3800, bukan string "3.800.000.000.000".
     */
    private function inventoryExcel(Request $request): StreamedResponse
    {
        $service = app(InventoryPlacementReportService::class);
        $rows    = $service->all($request);

        $headers = [
            'Kode', 'Nama Barang', 'Jenis', 'Kategori', 'Jurusan', 'Lokasi',
            'Merek', 'Model', 'Kondisi', 'Status', 'Stok', 'Satuan',
            'Harga Satuan', 'Nilai Inventaris',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris');
        $sheet->fromArray($headers, null, 'A1');

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAF7');

        $rowNumber = 2;
        foreach ($rows as $row) {
            $stock  = (float) $row->report_stock;
            $price  = (float) $row->report_unit_price;
            $value  = $stock * $price;

            $sheet->fromArray([
                $row->code,
                $row->name,
                $row->type === 'tool' ? 'Alat' : 'Bahan',
                $row->category_name ?? '-',
                $row->report_workshop_code ?? '-',
                $row->report_location_name ?? '-',
                $row->report_brand ?? '-',
                $row->report_model ?? '-',
                $this->conditionLabel($row->report_condition),
                $this->statusLabel($row->report_status),
                $stock,
                $row->unit_symbol ?: ($row->unit_name ?? ''),
                $price,
                $value,
            ], null, "A{$rowNumber}");

            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

        // Kolom angka rata kanan dengan format ribuan Indonesia.
        $sheet->getStyle("K2:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("M2:M{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("N2:N{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(
                Coordinate::stringFromColumnIndex($i)
            )->setAutoSize(true);
        }

        $periodLabel = $this->reportPeriodLabel($request);
        $filename    = 'laporan-inventaris'
            . ($periodLabel !== ''
                ? '-' . preg_replace('/[^0-9A-Za-z-]+/', '', str_replace(' ', '-', strtolower($periodLabel)))
                : '')
            . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Export laporan Barang Masuk / Barang Keluar ke XLSX asli.
     */
    private function movementExcel(Request $request, string $type): StreamedResponse
    {
        $rows = $this->movementExportQuery($request, $type)->get();

        $isIncoming = $type === ItemStockMovement::TYPE_INCOMING;

        $headers = $isIncoming
            ? ['Tanggal Masuk', 'Kode Penerimaan', 'Kode Barang', 'Nama Barang', 'Kategori', 'Bengkel', 'Merek', 'Model', 'Jumlah Masuk', 'Satuan', 'Kondisi', 'Sumber Dana', 'Harga Satuan', 'Total Nilai', 'Referensi', 'Sumber', 'Lokasi Simpan', 'Petugas', 'Keterangan']
            : ['Tanggal Masuk', 'Kode Barang', 'Nama Barang', 'Kategori', 'Bengkel', 'Merek', 'Model', 'Jumlah Keluar', 'Satuan', 'Kondisi', 'Tujuan', 'Keperluan', 'Referensi', 'Lokasi Simpan', 'Petugas', 'Keterangan'];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($isIncoming ? 'Barang Masuk' : 'Barang Keluar');
        $sheet->fromArray($headers, null, 'A1');

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAF7');

        $rowNumber = 2;
        foreach ($rows as $m) {
            $qty       = (float) $m->quantity;
            $unitPrice = (float) ($m->unit_price ?? 0);
            $total     = $unitPrice ? $qty * $unitPrice : null;

            $row = [
                $m->transaction_date?->format('Y-m-d'),
                $m->receipt_code ?? $m->reference_number,
                $m->item?->code,
                $m->item?->name,
                $m->item?->category?->name,
                $this->workshopCode($m),
                $m->brand ?? $m->item?->brand,
                $m->model ?? $m->item?->model,
                $qty,
                $m->item?->unit?->code,
                $m->condition,
                $m->fund_source,
                $unitPrice ?: null,
                $total,
                $m->reference_number,
                $m->source,
                $m->storageLocation?->name,
                $m->user?->name ?? 'Sistem',
                $m->description,
            ];

            // Barang Keluar: susun kolom berbeda.
            if (! $isIncoming) {
                $row = [
                    $m->transaction_date?->format('Y-m-d'),
                    $m->item?->code,
                    $m->item?->name,
                    $m->item?->category?->name,
                    $this->workshopCode($m),
                    $m->brand ?? $m->item?->brand,
                    $m->model ?? $m->item?->model,
                    $qty,
                    $m->item?->unit?->code,
                    $m->condition,
                    $m->destination,
                    $m->purpose,
                    $m->reference_number,
                    $m->storageLocation?->name,
                    $m->user?->name ?? 'Sistem',
                    $m->description,
                ];
            }

            $sheet->fromArray($row, null, "A{$rowNumber}");
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

        $periodLabel = $this->reportPeriodLabel($request);
        $prefix      = $isIncoming ? 'laporan-barang-masuk' : 'laporan-barang-keluar';
        $filename    = $prefix
            . ($periodLabel !== ''
                ? '-' . preg_replace('/[^0-9A-Za-z-]+/', '', str_replace(' ', '-', strtolower($periodLabel)))
                : '')
            . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function conditionLabel(
        ?string $value
    ): string {
        return match ($value) {
            'good' => 'Baik',
            'minor_damage' => 'Rusak Ringan',
            'major_damage' => 'Rusak Berat',
            'mixed' => 'Beragam',
            default => ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $value
                )
            ),
        };
    }

    private function statusLabel(
        ?string $value
    ): string {
        return match ($value) {
            'available' => 'Tersedia',
            'out_of_stock' => 'Stok Habis',
            default => ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $value
                )
            ),
        };
    }
}
