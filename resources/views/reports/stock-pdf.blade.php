<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle ?? 'Laporan Stok' }}</title>

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

        .header > div {
            display: table-cell;
            vertical-align: middle;
        }

        .right {
            text-align: right;
        }

        .brand {
            font-size: 15px;
            font-weight: 900;
        }

        .title {
            font-size: 15px;
            font-weight: 900;
        }

        .number {
            color: #1769ff;
            font-size: 6px;
            font-weight: 900;
        }

        .meta {
            background: #f8fafc;
            border: 1px solid #dbe3ee;
            margin-top: 9px;
            padding: 7px;
        }

        table.data {
            border-collapse: collapse;
            margin-top: 10px;
            width: 100%;
        }

        table.data th,
        table.data td {
            border: 1px solid #d9e1ec;
            padding: 4px;
            vertical-align: top;
        }

        table.data th {
            background: #edf3fb;
            font-size: 6px;
            text-transform: uppercase;
        }

        .signature {
            border-top: 1px solid #dbe3ee;
            margin-top: 14px;
            padding-top: 9px;
        }
    </style>
</head>

<body>
    @php
        $rows =
            $rows
            ?? $items
            ?? $loans
            ?? $damageReports
            ?? $reports
            ?? $movements
            ?? collect();

        $generatedAt =
            $generatedAt
            ?? now();

        $workshopIds =
            collect($rows)
                ->map(
                    static fn ($row) =>
                        data_get(
                            $row,
                            'workshop_id'
                        )
                        ?? data_get(
                            $row,
                            'item.workshop_id'
                        )
                        ?? data_get(
                            $row,
                            'loan.workshop_id'
                        )
                )
                ->filter()
                ->unique()
                ->values();

        $workshopId =
            $workshopIds->count() === 1
                ? (int) $workshopIds->first()
                : (
                    auth()->user()?->role
                    === 'kepala_bengkel'
                    || auth()->user()?->role
                    === 'toolman'
                        ? (int)
                            auth()->user()
                                ->workshop_id
                        : null
                );

        $officialDocument =
            $officialDocument
            ?? app(
                \App\Services\OfficialDocumentService::class
            )->make(
                $workshopId,
                auth()->user(),
                'LAPORAN-STOK',
                $workshopId
                    ? 'JURUSAN'
                    : 'GLOBAL',
                $generatedAt
            );
    @endphp

    <header class="header">
        <div>
            <div class="brand">
                SIMBA
            </div>

            <div>
                Sistem Inventaris dan Peminjaman Bengkel
            </div>
        </div>

        <div class="right">
            <div class="number">
                {{ $officialDocument['number'] }}
            </div>

            <div class="title">
                {{ $documentTitle ?? 'Laporan Stok' }}
            </div>
        </div>
    </header>

    <div class="meta">
        Ruang lingkup:
        {{ $scopeLabel
            ?? $officialDocument['workshopLabel'] }}
        · Dicetak:
        {{ $generatedAt->format(
            'd-m-Y H:i'
        ) }}
        · Jumlah data:
        {{ collect($rows)->count() }}
    </div>

    @if (isset($headers) && isset($tableRows))
        <table class="data">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($tableRows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="{{ count($headers) }}"
                            style="text-align:center;"
                        >
                            Tidak ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <div
            style="
                border:1px dashed #cbd5e1;
                color:#64748b;
                margin-top:10px;
                padding:18px;
                text-align:center;
            "
        >
            Template resmi sudah aktif.
            Controller laporan perlu mengirim
            <code>$headers</code> dan
            <code>$tableRows</code>.
        </div>
    @endif

    <div class="signature">
        @include(
            'prints.official-signatures',
            [
                'officialDocument' =>
                    $officialDocument,
            ]
        )
    </div>
</body>
</html>
