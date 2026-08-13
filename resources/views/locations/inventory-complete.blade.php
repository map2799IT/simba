<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Inventaris Lengkap {{ $location->code }}
    </title>

    <style>
        @page {
            size: A4 landscape;
            margin: 9mm 9mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #e9eef5;
            color: #172033;
            font-family:
                DejaVu Sans,
                Arial,
                sans-serif;
            font-size: 9px;
            line-height: 1.4;
            margin: 0;
        }

        .toolbar {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #dbe3ee;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            padding: 10px 16px;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .toolbar__group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .button {
            background: #ffffff;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            color: #172033;
            cursor: pointer;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 7px 11px;
            text-decoration: none;
        }

        .button--primary {
            background: #1769ff;
            border-color: #1769ff;
            color: #ffffff;
        }

        .button--danger {
            border-color: #dc2626;
            color: #dc2626;
        }

        .paper-wrap {
            padding: 20px;
        }

        .paper {
            background: #ffffff;
            border: 1px solid #d8e0eb;
            box-shadow: 0 10px 35px
                rgba(15, 23, 42, .12);
            margin: 0 auto;
            max-width: 1120px;
            min-height: 700px;
            padding: 20px 22px;
        }

        .header {
            border-bottom: 2px solid #1769ff;
            display: table;
            padding-bottom: 10px;
            table-layout: fixed;
            width: 100%;
        }

        .header__left,
        .header__right {
            display: table-cell;
            vertical-align: middle;
        }

        .header__left {
            width: 55%;
        }

        .header__right {
            text-align: right;
            width: 45%;
        }

        .brand {
            font-size: 17px;
            font-weight: 900;
            margin: 0;
        }

        .brand-subtitle,
        .document-subtitle {
            color: #64748b;
            font-size: 7px;
        }

        .document-code {
            color: #1769ff;
            font-size: 7px;
            font-weight: 800;
        }

        .document-title {
            font-size: 16px;
            font-weight: 900;
            margin: 2px 0;
        }

        .scope {
            background: #f8fafc;
            border: 1px solid #dbe3ee;
            border-radius: 6px;
            margin-top: 11px;
            padding: 7px 9px;
        }

        .cards {
            border-collapse: separate;
            border-spacing: 6px 0;
            margin: 11px -6px 0;
            table-layout: fixed;
            width: calc(100% + 12px);
        }

        .cards td {
            border: 1px solid #dbe3ee;
            border-radius: 6px;
            padding: 7px 9px;
            width: 20%;
        }

        .card-label {
            color: #64748b;
            font-size: 6.5px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .card-value {
            font-size: 13px;
            font-weight: 900;
            margin-top: 2px;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            border-bottom: 1px solid #cfd8e6;
            font-size: 10px;
            font-weight: 900;
            margin: 0 0 5px;
            padding-bottom: 4px;
        }

        table.data {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        table.data thead {
            display: table-header-group;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        table.data th {
            background: #edf3fb;
            border: 1px solid #cfd8e6;
            color: #344054;
            font-size: 6.2px;
            font-weight: 900;
            padding: 4px;
            text-align: left;
            text-transform: uppercase;
        }

        table.data td {
            border: 1px solid #d9e1ec;
            font-size: 6.8px;
            padding: 4px;
            vertical-align: top;
        }

        table.data tbody tr:nth-child(even) td {
            background: #fbfdff;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .strong {
            font-weight: 800;
        }

        .muted {
            color: #64748b;
            font-size: 6px;
        }

        .mono {
            font-family:
                DejaVu Sans Mono,
                monospace;
        }

        .empty {
            border: 1px dashed #cbd5e1;
            color: #64748b;
            padding: 15px;
            text-align: center;
        }

        .signatures {
            border-collapse: collapse;
            margin-top: 18px;
            page-break-inside: avoid;
            table-layout: fixed;
            width: 100%;
        }

        .signatures td {
            text-align: center;
            vertical-align: top;
            width: 33.333%;
        }

        .signature-space {
            height: 42px;
        }

        .signature-name {
            border-top: 1px solid #172033;
            display: inline-block;
            font-weight: 800;
            min-width: 150px;
            padding-top: 3px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .paper-wrap {
                padding: 0;
            }

            .paper {
                border: 0;
                box-shadow: none;
                max-width: none;
                min-height: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    @php
        $formatStock = static function (
            mixed $value
        ): string {
            $formatted =
                number_format(
                    (float) $value,
                    3,
                    ',',
                    '.'
                );

            return rtrim(
                rtrim(
                    $formatted,
                    '0'
                ),
                ','
            );
        };

        $formatCurrency = static fn (
            mixed $value
        ): string =>
            'Rp '.
            number_format(
                (float) $value,
                0,
                ',',
                '.'
            );

        $documentCode =
            'SIMBA/LAPORAN-INVENTARIS/'.
            (
                $location
                    ->workshop
                    ?->code
                ?: 'UMUM'
            ).
            '/'.
            $generatedAt
                ->format('Ymd').
            '/'.
            $location->code;
    @endphp

    @unless ($pdfMode)
        <div class="toolbar">
            <div>
                <strong>
                    Inventaris Lengkap:
                    {{ $location->code }}
                </strong>
            </div>

            <div class="toolbar__group">
                <a
                    href="{{ route(
                        'locations.inventory.summary',
                        [
                            'storageLocation' =>
                                $location
                                    ->getRouteKey(),

                            'include_children' =>
                                $includeChildren
                                    ? 1
                                    : 0,
                        ]
                    ) }}"
                    class="button"
                >
                    Ringkasan Isi
                </a>

                <a
                    href="{{ route(
                        'locations.inventory.complete',
                        [
                            'storageLocation' =>
                                $location
                                    ->getRouteKey(),

                            'include_children' =>
                                $includeChildren
                                    ? 0
                                    : 1,
                        ]
                    ) }}"
                    class="button"
                >
                    @if ($includeChildren)
                        Lokasi Ini Saja
                    @else
                        Sertakan Turunan
                    @endif
                </a>

                <button
                    type="button"
                    class="button button--primary"
                    onclick="window.print()"
                >
                    Print
                </button>

                <a
                    href="{{ route(
                        'locations.inventory.complete.pdf',
                        [
                            'storageLocation' =>
                                $location
                                    ->getRouteKey(),

                            'include_children' =>
                                $includeChildren
                                    ? 1
                                    : 0,
                        ]
                    ) }}"
                    class="button button--danger"
                >
                    Download PDF
                </a>
            </div>
        </div>
    @endunless

    <div class="paper-wrap">
        <main class="paper">
            <header class="header">
                <div class="header__left">
                    <h1 class="brand">
                        SIMBA
                    </h1>

                    <div class="brand-subtitle">
                        Sistem Inventaris dan
                        Peminjaman Bengkel
                    </div>
                </div>

                <div class="header__right">
                    <div class="document-code">
                        {{ $documentCode }}
                    </div>

                    <div class="document-title">
                        Laporan Inventaris Lengkap
                    </div>

                    <div class="document-subtitle">
                        Data master dan rincian unit alat
                    </div>
                </div>
            </header>

            <div class="scope">
                <strong>Ruang lingkup:</strong>
                Lokasi {{ $location->code }}
                - {{ $location->name }}

                @if ($includeChildren)
                    dan seluruh lokasi turunannya
                @else
                    (isi langsung)
                @endif

                &nbsp; | &nbsp;

                <strong>Dibuat:</strong>
                {{ $generatedAt
                    ->format('d-m-Y H:i') }}

                &nbsp; | &nbsp;

                <strong>Petugas:</strong>
                {{ $printedBy }}
            </div>

            <table class="cards">
                <tr>
                    <td>
                        <div class="card-label">
                            Total Master
                        </div>

                        <div class="card-value">
                            {{ $summary[
                                'master_count'
                            ] }}
                        </div>
                    </td>

                    <td>
                        <div class="card-label">
                            Master Alat
                        </div>

                        <div class="card-value">
                            {{ $summary[
                                'tool_types'
                            ] }}
                        </div>
                    </td>

                    <td>
                        <div class="card-label">
                            Unit Alat
                        </div>

                        <div class="card-value">
                            {{ $summary[
                                'tool_units'
                            ] }}
                        </div>
                    </td>

                    <td>
                        <div class="card-label">
                            Master Bahan
                        </div>

                        <div class="card-value">
                            {{ $summary[
                                'material_types'
                            ] }}
                        </div>
                    </td>

                    <td>
                        <div class="card-label">
                            Total Nilai
                        </div>

                        <div class="card-value">
                            {{ $formatCurrency(
                                $summary[
                                    'total_value'
                                ]
                            ) }}
                        </div>
                    </td>
                </tr>
            </table>

            <section class="section">
                <h2 class="section-title">
                    Daftar Master Alat dan Bahan
                </h2>

                @if ($completeRows->isEmpty())
                    <div class="empty">
                        Tidak ada inventaris aktif
                        pada cakupan lokasi ini.
                    </div>
                @else
                    <table class="data">
                        <colgroup>
                            <col style="width:10%">
                            <col style="width:17%">
                            <col style="width:7%">
                            <col style="width:10%">
                            <col style="width:6%">
                            <col style="width:12%">
                            <col style="width:11%">
                            <col style="width:9%">
                            <col style="width:7%">
                            <col style="width:6%">
                            <col style="width:7%">
                            <col style="width:8%">
                        </colgroup>

                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th>Kategori</th>
                                <th>Jurusan</th>
                                <th>Lokasi</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th class="right">Stok</th>
                                <th>Satuan</th>
                                <th class="right">Harga</th>
                                <th class="right">Nilai</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $completeRows
                                as $row
                            )
                                <tr>
                                    <td class="mono">
                                        {{ $row['code'] }}
                                    </td>

                                    <td>
                                        <div class="strong">
                                            {{ $row['name'] }}
                                        </div>

                                        @if (
                                            $row[
                                                'brand_model'
                                            ] !== ''
                                        )
                                            <div class="muted">
                                                {{ $row[
                                                    'brand_model'
                                                ] }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $row['type'] }}
                                    </td>

                                    <td>
                                        {{ $row[
                                            'category'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $row[
                                            'workshop'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $row[
                                            'location'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $row[
                                            'condition'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $row[
                                            'status'
                                        ] }}
                                    </td>

                                    <td class="right strong">
                                        {{ $formatStock(
                                            $row['stock']
                                        ) }}
                                    </td>

                                    <td>
                                        {{ $row[
                                            'unit_name'
                                        ] }}
                                    </td>

                                    <td class="right">
                                        {{ $formatCurrency(
                                            $row[
                                                'unit_price'
                                            ]
                                        ) }}
                                    </td>

                                    <td class="right strong">
                                        {{ $formatCurrency(
                                            $row[
                                                'total_value'
                                            ]
                                        ) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="section">
                <h2 class="section-title">
                    Rincian Setiap Unit Alat
                </h2>

                @if ($assetDetails->isEmpty())
                    <div class="empty">
                        Tidak ada unit alat aktif
                        pada cakupan lokasi ini.
                    </div>
                @else
                    <table class="data">
                        <colgroup>
                            <col style="width:4%">
                            <col style="width:14%">
                            <col style="width:13%">
                            <col style="width:18%">
                            <col style="width:13%">
                            <col style="width:10%">
                            <col style="width:10%">
                            <col style="width:9%">
                            <col style="width:9%">
                        </colgroup>

                        <thead>
                            <tr>
                                <th class="center">No</th>
                                <th>Nomor Inventaris</th>
                                <th>Nomor Seri</th>
                                <th>Nama Alat</th>
                                <th>Lokasi</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th>Tgl Perolehan</th>
                                <th class="right">Harga Unit</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $assetDetails
                                as $index => $asset
                            )
                                <tr>
                                    <td class="center">
                                        {{ $index + 1 }}
                                    </td>

                                    <td class="mono">
                                        {{ $asset[
                                            'asset_number'
                                        ] }}
                                    </td>

                                    <td class="mono">
                                        {{ $asset[
                                            'serial_number'
                                        ] }}
                                    </td>

                                    <td>
                                        <div class="strong">
                                            {{ $asset[
                                                'item_name'
                                            ] }}
                                        </div>

                                        <div class="muted mono">
                                            {{ $asset[
                                                'item_code'
                                            ] }}
                                        </div>
                                    </td>

                                    <td>
                                        {{ $asset[
                                            'location'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $asset[
                                            'condition'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $asset[
                                            'status'
                                        ] }}
                                    </td>

                                    <td>
                                        @if (
                                            $asset[
                                                'received_date'
                                            ]
                                        )
                                            {{ \Illuminate\Support\Carbon::parse(
                                                $asset[
                                                    'received_date'
                                                ]
                                            )->format(
                                                'd-m-Y'
                                            ) }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="right">
                                        {{ $formatCurrency(
                                            $asset[
                                                'unit_price'
                                            ]
                                        ) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <table class="signatures">
                <tr>
                    <td>
                        Dicetak oleh

                        <div class="signature-space"></div>

                        <div class="signature-name">
                            {{ $printedBy }}
                        </div>

                        <div class="muted">
                            {{ $printedUsername }}
                        </div>
                    </td>

                    <td>
                        Diperiksa oleh, Toolman

                        <div class="signature-space"></div>

                        <div class="signature-name">
                            {{ $toolmanName }}
                        </div>

                        <div class="muted">
                            {{ $toolmanUsername }}
                        </div>
                    </td>

                    <td>
                        Mengetahui, Kepala Bengkel

                        <div class="signature-space"></div>

                        <div class="signature-name">
                            {{ $headName }}
                        </div>

                        <div class="muted">
                            {{ $headUsername }}
                        </div>
                    </td>
                </tr>
            </table>
        </main>
    </div>
</body>
</html>
