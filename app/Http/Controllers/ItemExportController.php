<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemExportController extends Controller
{
    public function excel(
        Request $request
    ): StreamedResponse {
        $items = $this->filteredQuery($request)
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Barang');

        $headers = [
            'Kode',
            'Nama Barang',
            'Jenis',
            'Kategori',
            'Satuan',
            'Bengkel',
            'Lokasi',
            'Merek / Model',
            'Nomor Seri',
            'Tanggal Masuk',
            'Sumber Perolehan',
            'Sumber Dana',
            'Harga Satuan',
            'Kondisi',
            'Status',
            'Stok',
            'Stok Minimum',
            'Dapat Dipinjam',
            'Aktif',
            'Keterangan',
        ];

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        $rowNumber = 2;

        foreach ($items as $item) {
            $sheet->fromArray([
                $item->code,
                $item->name,
                $item->typeLabel(),
                $item->category->name,
                $item->unit->code,
                $item->workshop->code,
                $item->location?->full_path
                    ?? 'Belum ditentukan',

                trim(
                    ($item->brand ?? '')
                    .' '
                    .($item->model ?? '')
                ),

                $item->serial_number,

                $item->received_date?->format(
                    'Y-m-d'
                ),

                $item->acquisition_source,
                $item->fund_source,

                $item->unit_price !== null
                    ? (float) $item->unit_price
                    : null,

                $item->conditionLabel(),
                $item->statusLabel(),

                (float) $item->stock,

                (float) $item->minimum_stock,

                $item->is_borrowable
                    ? 'Ya'
                    : 'Tidak',

                $item->is_active
                    ? 'Ya'
                    : 'Tidak',

                $item->description,
            ], null, "A{$rowNumber}");

            /*
             * Menjaga kode dan nomor seri sebagai teks.
             */
            $sheet->setCellValueExplicit(
                "A{$rowNumber}",
                $item->code,
                DataType::TYPE_STRING
            );

            $sheet->setCellValueExplicit(
                "I{$rowNumber}",
                $item->serial_number ?? '',
                DataType::TYPE_STRING
            );

            $rowNumber++;
        }

        $lastRow = max(
            2,
            $rowNumber - 1
        );

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

        $sheet->getStyle(
            "M2:M{$lastRow}"
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            "P2:Q{$lastRow}"
        )->getNumberFormat()
            ->setFormatCode('#,##0.000');

        $sheet->freezePane('A2');
        $sheet->setAutoFilter(
            "A1:T{$lastRow}"
        );

        foreach (range('A', 'T') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }

        $filename = 'data-barang-simba-'
            .now()->format('Ymd-His')
            .'.xlsx';

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

    public function pdf(
        Request $request
    ): Response {
        $items = $this->filteredQuery($request)
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        $pdf = Pdf::loadView(
            'items.pdf',
            [
                'items' => $items,
                'filters' => $request->query(),
            ]
        )->setPaper(
            'a4',
            'landscape'
        );

        return $pdf->download(
            'data-barang-simba-'
            .now()->format('Ymd-His')
            .'.pdf'
        );
    }

    private function filteredQuery(
        Request $request
    ): Builder {
        $search = trim(
            (string) $request->input('search')
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
                fn (Builder $query): Builder =>
                    $query->where(
                        'type',
                        $request->input('type')
                    )
            )
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $query): Builder =>
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
                fn (Builder $query): Builder =>
                    $query->where(
                        'item_category_id',
                        $request->input(
                            'item_category_id'
                        )
                    )
            )
            ->when(
                $request->filled('status'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'status',
                        $request->input('status')
                    )
            );
    }
}