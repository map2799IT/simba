<?php

namespace App\Http\Controllers;

use App\Models\ItemStockMovement;
use App\Services\InventoryPlacementReportService;
use Illuminate\Http\Request;
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

            $reportTitle = $tab === 'barang_masuk'
                ? 'Laporan Barang Masuk'
                : 'Laporan Barang Keluar';

            $view = $tab === 'barang_masuk'
                ? 'reports.stock-receipts-pdf'
                : 'reports.stock-issues-pdf';

            $filename = ($tab === 'barang_masuk' ? 'laporan-barang-masuk-' : 'laporan-barang-keluar-')
                . now()->format('Ymd-His') . '.pdf';

            $data = [
                'reportTitle' => $reportTitle,
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
        ];

        $filename =
            'laporan-inventaris-'.
            now()->format('Ymd-His').
            '.pdf';

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

    private function movementExportQuery(Request $request, string $type): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim((string) $request->input('search'));

        return ItemStockMovement::query()
            ->with(['item.category', 'item.unit', 'item.workshop', 'storageLocation', 'user'])
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

        return [
            'total_transactions' => (clone $base)->count(),
            'total_quantity'     => (float) (clone $base)->sum('quantity'),
            'unique_items'       => (clone $base)->distinct('item_id')->count('item_id'),
        ];
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

            $prefix = $tab === 'barang_masuk' ? 'laporan-barang-masuk' : 'laporan-barang-keluar';

            $filename = $prefix . '-' . now()->format('Ymd-His') . '.csv';

            return response()->streamDownload(
                function () use ($request, $type): void {
                    $rows = $this->movementExportQuery($request, $type)->get();

                    $output = fopen('php://output', 'wb');
                    if ($output === false) {
                        return;
                    }

                    fwrite($output, "\xEF\xBB\xBF");

                    $headers = $type === ItemStockMovement::TYPE_INCOMING
                        ? ['Tanggal Masuk', 'Kode Penerimaan', 'Kode Barang', 'Nama Barang', 'Kategori', 'Bengkel', 'Merek', 'Model', 'Jumlah Masuk', 'Satuan', 'Kondisi', 'Sumber Dana', 'Harga Satuan', 'Total Nilai', 'Referensi', 'Sumber', 'Lokasi Simpan', 'Petugas', 'Keterangan']
                        : ['Tanggal Masuk', 'Kode Barang', 'Nama Barang', 'Kategori', 'Bengkel', 'Merek', 'Model', 'Jumlah Keluar', 'Satuan', 'Kondisi', 'Tujuan', 'Keperluan', 'Referensi', 'Lokasi Simpan', 'Petugas', 'Keterangan'];

                    fputcsv($output, $headers, ';');

                    foreach ($rows as $m) {
                        $qty = (float) $m->quantity;
                        $unitPrice = (float) ($m->unit_price ?? 0);

                        if ($type === ItemStockMovement::TYPE_INCOMING) {
                            $row = [
                                $m->transaction_date?->format('Y-m-d'),
                                $m->receipt_code ?? $m->reference_number,
                                $m->item?->code,
                                $m->item?->name,
                                $m->item?->category?->name,
                                $m->item?->workshop?->code,
                                $m->brand ?? $m->item?->brand,
                                $m->model ?? $m->item?->model,
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
                        } else {
                            $row = [
                                $m->transaction_date?->format('Y-m-d'),
                                $m->item?->code,
                                $m->item?->name,
                                $m->item?->category?->name,
                                $m->item?->workshop?->code,
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

                        fputcsv($output, $row, ';');
                    }

                    fclose($output);
                },
                $filename,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        $filename =
            'laporan-inventaris-'.
            now()->format('Ymd-His').
            '.csv';

        return response()->streamDownload(
            function () use ($request): void {
                $service = app(
                    InventoryPlacementReportService::class
                );

                $rows =
                    $service->all($request);

                $output =
                    fopen(
                        'php://output',
                        'wb'
                    );

                if ($output === false) {
                    return;
                }

                fwrite(
                    $output,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $output,
                    [
                        'Kode',
                        'Nama Barang',
                        'Jenis',
                        'Kategori',
                        'Jurusan',
                        'Lokasi',
                        'Merek',
                        'Model',
                        'Kondisi',
                        'Status',
                        'Stok',
                        'Satuan',
                        'Harga Satuan',
                        'Nilai Inventaris',
                    ],
                    ';'
                );

                foreach ($rows as $row) {
                    fputcsv(
                        $output,
                        [
                            $row->code,
                            $row->name,
                            $row->type === 'tool'
                                ? 'Alat'
                                : 'Bahan',
                            $row->category_name
                                ?? '-',
                            $row->report_workshop_code
                                ?? '-',
                            $row->report_location_name
                                ?? '-',
                            $row->report_brand
                                ?? '-',
                            $row->report_model
                                ?? '-',
                            $this->conditionLabel(
                                $row->report_condition
                            ),
                            $this->statusLabel(
                                $row->report_status
                            ),
                            $row->report_stock,
                            $row->unit_symbol
                                ?: (
                                    $row->unit_name
                                    ?? ''
                                ),
                            $row->report_unit_price,
                            $row->report_inventory_value,
                        ],
                        ';'
                    );
                }

                fclose($output);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
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
