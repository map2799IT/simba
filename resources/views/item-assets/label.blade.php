<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Label QR {{ $asset->asset_number }}
    </title>

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #edf2f7;
            color: #172033;
            font-family:
                DejaVu Sans,
                Arial,
                sans-serif;
            margin: 0;
        }

        .toolbar {
            background: #ffffff;
            border-bottom: 1px solid #dbe3ee;
            padding: 10px 14px;
        }

        .button {
            background: #ffffff;
            border: 1px solid #94a3b8;
            border-radius: 7px;
            color: #1e293b;
            cursor: pointer;
            display: inline-block;
            font: inherit;
            font-size: 11px;
            font-weight: 800;
            margin-right: 6px;
            padding: 7px 11px;
            text-decoration: none;
        }

        .button--primary {
            background: #1769ff;
            border-color: #1769ff;
            color: #ffffff;
        }

        .paper {
            background: #ffffff;
            border: 1px solid #dbe3ee;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .12);
            margin: 18px auto;
            max-width: 760px;
            min-height: 1000px;
            padding: 28px;
        }

        .header {
            border-bottom: 2px solid #1769ff;
            display: table;
            padding-bottom: 10px;
            width: 100%;
        }

        .header > div {
            display: table-cell;
            vertical-align: middle;
        }

        .header-right {
            text-align: right;
        }

        .brand {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
        }

        .subtitle {
            color: #64748b;
            font-size: 9px;
        }

        .document-number {
            color: #1769ff;
            font-size: 8px;
            font-weight: 900;
        }

        .label-card {
            border: 2px solid #172033;
            border-radius: 12px;
            margin: 35px auto 25px;
            max-width: 390px;
            padding: 25px;
            text-align: center;
        }

        .label-card__brand {
            color: #1769ff;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .qr {
            height: 210px;
            margin: 18px auto;
            width: 210px;
        }

        .qr svg,
        .qr img {
            display: block;
            height: 100%;
            margin: auto;
            width: 100%;
        }

        .item-name {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
        }

        .asset-number {
            font-family:
                DejaVu Sans Mono,
                Consolas,
                monospace;
            font-size: 13px;
            font-weight: 900;
            margin-top: 8px;
        }

        .meta {
            color: #475569;
            font-size: 10px;
            line-height: 1.6;
            margin-top: 12px;
        }

        .signature-table {
            border-collapse: collapse;
            margin-top: 40px;
            table-layout: fixed;
            width: 100%;
        }

        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 33.333%;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            border-top: 1px solid #334155;
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin: 0 auto;
            max-width: 180px;
            padding-top: 4px;
        }

        .signature-role {
            color: #64748b;
            font-size: 9px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .paper {
                border: 0;
                box-shadow: none;
                margin: 0;
                max-width: none;
                min-height: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    @php
        $generatedAt = now();

        $officialDocument =
            app(
                \App\Services\OfficialDocumentService::class
            )->make(
                (int) $asset->workshop_id,
                auth()->user(),
                'QR-TUNGGAL',
                $asset->asset_number,
                $generatedAt
            );
    @endphp

    <div class="toolbar">
        <button
            type="button"
            class="button button--primary"
            onclick="window.print()"
        >
            Print Label Resmi
        </button>

        <button
            type="button"
            class="button"
            onclick="window.close()"
        >
            Tutup
        </button>
    </div>

    <main class="paper">
        <header class="header">
            <div>
                <div class="brand">
                    SIMBA
                </div>

                <div class="subtitle">
                    Sistem Inventaris dan Peminjaman Bengkel
                </div>
            </div>

            <div class="header-right">
                <div class="document-number">
                    {{ $officialDocument['number'] }}
                </div>

                <div class="subtitle">
                    Label QR Unit Alat
                </div>
            </div>
        </header>

        <section class="label-card">
            <div class="label-card__brand">
                Inventaris Resmi SIMBA
            </div>

            <div class="qr">
                @if ($qrDataUri ?? null)
                    <img
                        src="{{ $qrDataUri }}"
                        alt="QR {{ $asset->asset_number }}"
                    >
                @elseif ($qrSvg ?? null)
                    {!! $qrSvg !!}
                @else
                    QR tidak tersedia
                @endif
            </div>

            <div class="item-name">
                {{ $asset->item?->name }}
            </div>

            <div class="asset-number">
                {{ $asset->asset_number }}
            </div>

            <div class="meta">
                Serial/Internal:
                {{ $asset->serial_number ?: '-' }}
                <br>

                Jurusan:
                {{ $asset->workshop?->code }}
                — {{ $asset->workshop?->name }}
                <br>

                Lokasi:
                {{ $asset->storageLocation?->name
                    ?? '-' }}
                <br>

                Kondisi:
                {{ $asset->conditionLabel() }}
                · Status:
                {{ $asset->statusLabel() }}
            </div>
        </section>

        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-role">
                        Dicetak oleh
                    </div>

                    <div class="signature-space"></div>

                    <span class="signature-name">
                        {{ $officialDocument[
                            'printedBy'
                        ]['name'] }}
                    </span>
                </td>

                <td>
                    <div class="signature-role">
                        Diperiksa Toolman
                    </div>

                    <div class="signature-space"></div>

                    <span class="signature-name">
                        {{ $officialDocument[
                            'toolman'
                        ]['name'] }}
                    </span>
                </td>

                <td>
                    <div class="signature-role">
                        Mengetahui Kepala Bengkel
                    </div>

                    <div class="signature-space"></div>

                    <span class="signature-name">
                        {{ $officialDocument[
                            'head'
                        ]['name'] }}
                    </span>
                </td>
            </tr>
        </table>
    </main>
</body>
</html>
