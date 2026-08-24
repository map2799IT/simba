<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle ?? 'Laporan Barang Keluar' }}{{ !empty($filters['year']) ? ' ' . $filters['year'] : '' }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8px;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #1769ff;
            display: table;
            padding-bottom: 8px;
            table-layout: fixed;
            width: 100%;
        }

        .header > div { display: table-cell; vertical-align: middle; }
        .right { text-align: right; }
        .brand { font-size: 15px; font-weight: 900; }
        .title { font-size: 15px; font-weight: 900; }
        .number { color: #1769ff; font-size: 6px; font-weight: 900; }

        .meta {
            background: #f8fafc;
            border: 1px solid #dbe3ee;
            margin-top: 9px;
            padding: 7px;
        }

        .summary {
            display: table;
            margin-top: 8px;
            table-layout: fixed;
            width: 100%;
        }

        .summary-cell {
            border: 1px solid #dbe3ee;
            display: table-cell;
            padding: 5px 7px;
            text-align: center;
            width: 25%;
        }

        .summary-cell .label { color: #64748b; font-size: 6px; text-transform: uppercase; }
        .summary-cell .value { color: #0f172a; font-size: 10px; font-weight: 900; margin-top: 2px; }

        table.data {
            border-collapse: collapse;
            margin-top: 10px;
            width: 100%;
        }

        table.data th,
        table.data td {
            border: 1px solid #d9e1ec;
            padding: 3px 4px;
            vertical-align: top;
        }

        table.data th {
            background: #edf3fb;
            font-size: 6px;
            text-transform: uppercase;
        }

        .group-row td {
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .text-right { text-align: right; }
        .text-red { color: #b91c1c; font-weight: 900; }
        .text-muted { color: #64748b; }

        .signature {
            border-top: 1px solid #dbe3ee;
            margin-top: 14px;
            padding-top: 9px;
        }
    </style>
</head>

<body>
    @php
        $generatedAt = now();

        $workshopIds = collect($rows)
            ->map(fn ($r) => $r->workshop_id ?? $r->item?->workshop_id)
            ->filter()->unique()->values();

        $workshopId = $workshopIds->count() === 1
            ? (int) $workshopIds->first()
            : (in_array(auth()->user()?->role, ['kepala_bengkel', 'toolman'])
                ? (int) auth()->user()->workshop_id
                : null);

        $officialDocument = app(\App\Services\OfficialDocumentService::class)->make(
            $workshopId,
            auth()->user(),
            'LAPORAN-BARANG-KELUAR',
            $workshopId ? 'JURUSAN' : 'GLOBAL',
            $generatedAt
        );

        $condMap   = ['good' => 'Baik', 'damaged' => 'Rusak', 'needs_repair' => 'Perlu Perbaikan'];
        $lastItemName = null;
    @endphp

    <header class="header">
        <div>
            <div class="brand">SIMBA</div>
            <div>Sistem Inventaris dan Peminjaman Bengkel</div>
        </div>
        <div class="right">
            <div class="number">{{ $officialDocument['number'] }}</div>
            <div class="title">{{ $reportTitle ?? 'Laporan Barang Keluar' }}{{ !empty($filters['year']) ? ' ' . $filters['year'] : '' }}</div>
        </div>
    </header>

    <div class="meta">
        Ruang lingkup: {{ $officialDocument['workshopLabel'] }}
        · Dicetak: {{ $generatedAt->format('d-m-Y H:i') }}
        · Jumlah entri: {{ collect($rows)->count() }}
        @if (!empty($filters['year']))
            · Tahun: {{ $filters['year'] }}
        @endif
        @if (!empty($filters['date_from']) || !empty($filters['date_to']))
            · Periode:
            {{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') : '…' }}
            s/d
            {{ !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') : '…' }}
        @endif
    </div>

    @if (!empty($summary))
    <div class="summary">
        <div class="summary-cell">
            <div class="label">Total Transaksi</div>
            <div class="value">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Jenis Barang</div>
            <div class="value">{{ number_format($summary['unique_items'] ?? 0) }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Total Kuantitas</div>
            <div class="value">{{ number_format($summary['total_quantity'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Total Barang Keluar</div>
            <div class="value">{{ number_format($summary['total_quantity'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th>Tanggal Masuk</th>
                <th>Kode Pengeluaran</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Bengkel</th>
                <th>Merek / Model</th>
                <th class="text-right">Jml Keluar</th>
                <th>Satuan</th>
                <th>Kondisi</th>
                <th>Tujuan</th>
                <th>Keperluan</th>
                <th>Referensi</th>
                <th>Lokasi Simpan</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $itemName  = $row->item?->name ?? '-';
                    $newGroup  = $itemName !== $lastItemName;
                    $lastItemName = $itemName;
                    $qty       = (float) $row->quantity;
                    $brand     = $row->brand ?? $row->item?->brand;
                    $model     = $row->model ?? $row->item?->model;
                    $condLabel = $condMap[$row->condition ?? ''] ?? ($row->condition ?? '-');
                @endphp
                @if ($newGroup)
                    <tr class="group-row">
                        <td colspan="15">
                            {{ $itemName }}
                            @if ($row->item?->code) · {{ $row->item->code }} @endif
                            @if ($row->item?->category) · {{ $row->item->category->name }} @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <td>{{ $row->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                    <td>{{ $row->reference_number ?? '-' }}</td>
                    <td>{{ $row->item?->code ?? '-' }}</td>
                    <td>{{ $itemName }}</td>
                    <td>{{ $row->item?->category?->name ?? '-' }}</td>
                    <td>{{ $row->item?->workshop?->code ?? '-' }}</td>
                    <td>{{ implode(' / ', array_filter([$brand, $model])) ?: '-' }}</td>
                    <td class="text-right text-red">-{{ number_format($qty, 2, ',', '.') }}</td>
                    <td>{{ $row->item?->unit?->code ?? '-' }}</td>
                    <td>{{ $condLabel }}</td>
                    <td>{{ $row->destination ?? '-' }}</td>
                    <td>{{ $row->purpose ?? '-' }}</td>
                    <td>{{ $row->reference_number ?? '-' }}</td>
                    <td>{{ $row->storageLocation?->name ?? '-' }}</td>
                    <td>{{ $row->user?->name ?? 'Sistem' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center;padding:12px;color:#64748b;">
                        Tidak ada data barang keluar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature">
        @include('prints.official-signatures', ['officialDocument' => $officialDocument])
    </div>
</body>
</html>
