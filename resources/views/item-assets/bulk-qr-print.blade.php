<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <title>QR {{ $item->name }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 7mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 0;
        }

        .toolbar {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
            padding: 8px;
        }

        .toolbar a,
        .toolbar button {
            background: white;
            border: 1px solid #374151;
            border-radius: 4px;
            color: #111827;
            cursor: pointer;
            display: inline-block;
            padding: 7px 10px;
            text-decoration: none;
        }

        .toolbar .primary {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        .notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            margin-bottom: 10px;
            padding: 8px;
        }

        .header {
            border-bottom: 1px solid #2563eb;
            margin-bottom: 4mm;
            padding-bottom: 2mm;
        }

        .header h1 {
            font-size: 15px;
            margin: 0 0 3px;
        }

        .header .meta {
            color: #4b5563;
            font-size: 8px;
        }

        .sheet {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .sheet td {
            border: 1px dashed #6b7280;
            height: 86mm;
            overflow: hidden;
            padding: 3mm;
            text-align: center;
            vertical-align: middle;
            width: 33.333%;
        }

        .sheet td.empty-cell {
            border-color: transparent;
        }

        .qr {
            height: 40mm;
            margin: 0 auto 2mm;
            width: 40mm;
        }

        .qr img {
            display: block;
            height: 40mm;
            margin: 0 auto;
            width: 40mm;
        }

        .qr-error {
            border: 1px solid #ef4444;
            color: #b91c1c;
            font-size: 8px;
            height: 40mm;
            padding-top: 16mm;
        }

        .name {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 1.5mm;
        }

        .asset,
        .serial {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8.5px;
            font-weight: bold;
        }

        .serial {
            font-weight: normal;
            margin-top: 1mm;
        }

        .label-meta {
            color: #4b5563;
            font-size: 7.5px;
            line-height: 1.35;
            margin-top: 1.5mm;
        }

        .page-break {
            page-break-after: always;
        }

        .empty {
            border: 1px solid #d1d5db;
            padding: 30px;
            text-align: center;
        }

        @media print {
            .toolbar,
            .notice {
                display: none;
            }
        }
    </style>
</head>
<body>
    @unless ($pdfMode ?? false)
        <div class="toolbar">
            <strong>
                Pratinjau QR Resmi
            </strong>

            <button
                type="button"
                class="primary"
                onclick="window.print()"
            >
                Print {{ $assets->count() }} QR
            </button>

            <a
                href="{{ route(
                    'item-assets.qr-bulk.download',
                    array_filter([
                        'item' => $item->id,
                        'workshop_id' => $selectedWorkshopId,
                    ])
                ) }}"
            >
                Download PDF
            </a>

            <button
                type="button"
                onclick="window.close()"
            >
                Tutup
            </button>
        </div>
    @endunless

    @if ($pdfFallback ?? false)
        <div class="notice">
            DomPDF belum tersedia. Gunakan Print lalu pilih Save as PDF.
        </div>
    @endif

    @if ($labels->isEmpty())
        <div class="empty">
            Tidak ada unit alat aktif pada jurusan yang dipilih.
        </div>
    @else
        @foreach ($labels->chunk(9) as $pageIndex => $pageLabels)
            <div class="header">
                <h1>
                    QR Unit Alat — {{ $item->name }}
                </h1>

                <div class="meta">
                    Jurusan:
                    @if ($printWorkshop)
                        {{ $printWorkshop->code }}
                        — {{ $printWorkshop->name }}
                    @else
                        Beberapa jurusan
                    @endif

                    | Lokasi: {{ $locationSummary }}
                    | {{ $assets->count() }} unit
                    | Halaman {{ $pageIndex + 1 }}
                    dari {{ $labels->chunk(9)->count() }}
                    | Dicetak {{ $generatedAt->format('d-m-Y H:i') }}
                </div>
            </div>

            <table class="sheet">
                <tbody>
                    @foreach ($pageLabels->chunk(3) as $row)
                        <tr>
                            @foreach ($row as $label)
                                @php
                                    $asset = $label['asset'];

                                    $qrSource =
                                        $label['qrPngDataUri']
                                        ?? (
                                            ($pdfMode ?? false)
                                                ? null
                                                : $label['qrSvgDataUri']
                                        );
                                @endphp

                                <td>
                                    <div class="qr">
                                        @if ($qrSource)
                                            <img
                                                src="{{ $qrSource }}"
                                                alt="QR {{ $asset->asset_number }}"
                                            >
                                        @else
                                            <div class="qr-error">
                                                QR gagal dibuat
                                            </div>
                                        @endif
                                    </div>

                                    <div class="name">
                                        {{ $item->name }}
                                    </div>

                                    <div class="asset">
                                        {{ $asset->asset_number }}
                                    </div>

                                    <div class="serial">
                                        Nomor seri:
                                        {{ $asset->serial_number
                                            ?: 'BELUM DIISI' }}
                                    </div>

                                    <div class="label-meta">
                                        Jurusan:
                                        {{ $asset->workshop?->code ?? '-' }}
                                        <br>

                                        Lokasi:
                                        {{ $asset->storageLocation?->name
                                            ?? 'Belum ditentukan' }}
                                        <br>

                                        Kondisi:
                                        {{ $asset->conditionLabel() }}
                                        | Status:
                                        {{ $asset->statusLabel() }}
                                    </div>
                                </td>
                            @endforeach

                            @for ($empty = $row->count(); $empty < 3; $empty++)
                                <td class="empty-cell"></td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if (! $loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endif
</body>
</html>
