<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>{{ $reportTitle }}</title>

    <style>
        @page {
            margin: 18px 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 8px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 17px;
        }

        .subtitle {
            margin-bottom: 12px;
            color: #4b5563;
        }

        .summary {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .summary td {
            width: 16.66%;
            padding: 6px;
            border: 1px solid #d1d5db;
        }

        .summary-label {
            color: #6b7280;
            font-size: 7px;
        }

        .summary-value {
            font-size: 10px;
            font-weight: bold;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        .data th,
        .data td {
            padding: 4px;
            vertical-align: top;
            border: 1px solid #9ca3af;
        }

        .data th {
            background: #e5e7eb;
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .small {
            color: #4b5563;
            font-size: 7px;
        }
    </style>
</head>

<body>
    @php
        $fmtQty = static function (mixed $value, mixed $unitOrAllowsDecimal = false): string {
            return \App\Support\QuantityFormatter::format($value, $unitOrAllowsDecimal);
        };
    @endphp
    <h1>
        {{ $reportTitle }}
    </h1>

    <div class="subtitle">
        Sistem Inventaris dan Peminjaman Bengkel
        · Dicetak {{ now()->format('d-m-Y H:i') }}
        · Jumlah data {{ $rows->count() }}
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">
                    Alat Aktif
                </div>

                <div class="summary-value">
                    {{ $summary['tools'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Jenis Bahan
                </div>

                <div class="summary-value">
                    {{ $summary['materials'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Stok Rendah
                </div>

                <div class="summary-value">
                    {{ $summary['low_stock'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Sedang Dipinjam
                </div>

                <div class="summary-value">
                    {{ $summary['open_loans'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Kerusakan Aktif
                </div>

                <div class="summary-value">
                    {{ $summary['open_damages'] }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Nilai Inventaris
                </div>

                <div class="summary-value">
                    Rp
                    {{ number_format(
                        $summary['asset_value'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>
        </tr>
    </table>

    @if (
        in_array(
            $reportType,
            ['inventory', 'low_stock'],
            true
        )
    )
        <table class="data">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Barang</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Bengkel/Lokasi</th>
                    <th>Kondisi</th>
                    <th>Stok</th>
                    <th>Minimum</th>
                    <th>Status</th>
                    <th>Nilai</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $item)
                    @php
                        $conditionLabels = ['good'=>'Baik','minor_damage'=>'Rusak Ringan','major_damage'=>'Rusak Berat','mixed'=>'Beragam'];
                        $statusLabels = ['available'=>'Tersedia','out_of_stock'=>'Habis'];
                        $typeLabels = ['tool'=>'Alat','material'=>'Bahan'];
                        $condLabel = $conditionLabels[$item->report_condition ?? ''] ?? ucfirst(str_replace('_',' ',$item->report_condition ?? '-'));
                        $statLabel = $statusLabels[$item->report_status ?? ''] ?? ucfirst(str_replace('_',' ',$item->report_status ?? '-'));
                        $typeLabel = $typeLabels[$item->type ?? ''] ?? ucfirst($item->type ?? '-');
                        $itemValue = (float)($item->report_inventory_value ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $item->code }}</td>
                        <td>
                            <strong>{{ $item->name }}</strong>
                            @if (!empty($item->report_brand) && $item->report_brand !== '-')
                                <div class="small">{{ $item->report_brand }} {{ $item->report_model ?? '' }}</div>
                            @endif
                        </td>
                        <td class="center">{{ $typeLabel }}</td>
                        <td>{{ $item->category_name ?? '-' }}</td>
                        <td>
                            {{ $item->report_workshop_code ?? '-' }}
                            <div class="small">{{ $item->report_location_name ?? '-' }}</div>
                        </td>
                        <td>{{ $condLabel }}</td>
                        <td class="right">{{ $fmtQty($item->report_stock ?? 0, $item) }} {{ $item->unit_symbol ?: ($item->unit_name ?? '') }}</td>
                        <td class="right">{{ $fmtQty($item->minimum_stock ?? 0) }}</td>
                        <td>{{ $statLabel }}</td>
                        <td class="right">Rp {{ number_format($itemValue,0,',','.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center">
                            Tidak ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'stock_movements')
        <table class="data">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Transaksi</th>
                    <th>Referensi</th>
                    <th>Sumber/Tujuan</th>
                    <th>Sebelum</th>
                    <th>Perubahan</th>
                    <th>Sesudah</th>
                    <th>Petugas</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $movement)
                    @php
                        $typeLabelsMovement = ['incoming'=>'Masuk','outgoing'=>'Keluar','adjustment_in'=>'Penyesuaian Masuk','adjustment_out'=>'Penyesuaian Keluar','loan'=>'Peminjaman','return'=>'Pengembalian','initial'=>'Saldo Awal'];
                        $mvType = $typeLabelsMovement[$movement->type ?? ''] ?? ucfirst(str_replace('_',' ',$movement->type ?? '-'));
                        $diff = (float)($movement->stock_after ?? 0) - (float)($movement->stock_before ?? 0);
                        $txDate = $movement->transaction_date instanceof \Carbon\Carbon
                            ? $movement->transaction_date->format('d-m-Y H:i')
                            : (\Carbon\Carbon::parse($movement->transaction_date ?? $movement->created_at)->format('d-m-Y H:i'));
                    @endphp
                    <tr>
                        <td>{{ $txDate }}</td>
                        <td>
                            <strong>{{ $movement->item?->name ?? ($movement->item_name ?? '-') }}</strong>
                            <div class="small">{{ $movement->item?->code ?? ($movement->item_code ?? '') }}</div>
                        </td>
                        <td>{{ $mvType }}</td>
                        <td>{{ $movement->reference_number ?? '-' }}</td>
                        <td>{{ $movement->source ?? $movement->destination ?? '-' }}</td>
                        <td class="right">{{ $fmtQty($movement->stock_before ?? 0) }}</td>
                        <td class="right">{{ $fmtQty($diff) }}</td>
                        <td class="right">{{ $fmtQty($movement->stock_after ?? 0) }}</td>
                        <td>{{ $movement->user?->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center">
                            Tidak ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'loans')
        <table class="data">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Peminjam</th>
                    <th>Pengajuan</th>
                    <th>Batas Kembali</th>
                    <th>Jumlah Alat</th>
                    <th>Alat</th>
                    <th>Keperluan</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $loan)
                    @php
                        $loanStatusLabels = ['pending'=>'Menunggu','requested'=>'Menunggu','approved'=>'Disetujui','borrowed'=>'Sedang Dipinjam','partially_returned'=>'Sebagian Kembali','returned'=>'Dikembalikan','completed'=>'Selesai','rejected'=>'Ditolak','cancelled'=>'Dibatalkan'];
                        $loanStatus = $loanStatusLabels[$loan->status ?? ''] ?? ucfirst(str_replace('_',' ',$loan->status ?? '-'));
                        $borrowedAt = $loan->borrowed_at ?? $loan->scheduled_at;
                        $borrowedFmt = $borrowedAt ? \Carbon\Carbon::parse($borrowedAt)->format('d-m-Y H:i') : ($loan->request_date ? \Carbon\Carbon::parse($loan->request_date)->format('d-m-Y') : '-');
                    @endphp
                    <tr>
                        <td>{{ $loan->code }}</td>
                        <td>{{ $loan->borrower?->name ?? '-' }}</td>
                        <td>{{ $borrowedFmt }}</td>
                        <td>{{ $loan->due_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="center">{{ $loan->items?->count() ?? 0 }}</td>
                        <td>{{ $loan->items?->map(fn($li) => $li->item?->code)->filter()->implode(', ') ?? '-' }}</td>
                        <td>{{ $loan->purpose }}</td>
                        <td>{{ $loanStatus }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center">
                            Tidak ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Alat</th>
                    <th>Waktu</th>
                    <th>Tingkat</th>
                    <th>Status</th>
                    <th>Pelapor</th>
                    <th>Petugas</th>
                    <th>Diagnosis/Tindakan</th>
                    <th>Vendor</th>
                    <th>Biaya</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $report)
                    <tr>
                        <td>{{ $report->code }}</td>

                        <td>
                            <strong>
                                {{ $report->item?->name }}
                            </strong>

                            <div class="small">
                                {{ $report->item?->code }}
                            </div>
                        </td>

                        <td>
                            {{ $report
                                ->reported_at
                                ?->format('d-m-Y H:i') }}
                        </td>

                        <td>
                            {{ $report->severityLabel() }}
                        </td>

                        <td>
                            {{ $report->statusLabel() }}
                        </td>

                        <td>
                            {{ $report->reporter?->name
                                ?? 'Sistem' }}
                        </td>

                        <td>
                            {{ $report->handler?->name
                                ?? '-' }}
                        </td>

                        <td>
                            {{ $report->diagnosis
                                ?: $report->description }}

                            @if ($report->action_taken)
                                <div class="small">
                                    {{ $report->action_taken }}
                                </div>
                            @endif
                        </td>

                        <td>
                            {{ $report->vendor ?: '-' }}
                        </td>

                        <td class="right">
                            @if (
                                $report->repair_cost !== null
                            )
                                Rp
                                {{ number_format(
                                    (float)
                                    $report->repair_cost,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center">
                            Tidak ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>