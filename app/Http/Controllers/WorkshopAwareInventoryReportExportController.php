<?php

namespace App\Http\Controllers;

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

    public function excel(
        Request $request
    ): StreamedResponse {
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
