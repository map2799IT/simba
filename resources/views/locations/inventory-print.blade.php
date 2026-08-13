<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Inventaris Lokasi {{ $location->code }}
    </title>

    <style>
        @page {
            size: A4 landscape;
            margin: 9mm 10mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #e9eef5;
            color: #172033;
            font-family:
                DejaVu Sans,
                Arial,
                sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        .toolbar {
            align-items: center;
            background: rgba(255, 255, 255, .97);
            border-bottom: 1px solid #d7deea;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            padding: 10px 16px;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .toolbar-group {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .toolbar-title {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .button {
            background: #ffffff;
            border: 1px solid #94a3b8;
            border-radius: 7px;
            color: #1e293b;
            cursor: pointer;
            display: inline-block;
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            padding: 7px 11px;
            text-decoration: none;
        }

        .button--primary {
            background: #1769ff;
            border-color: #1769ff;
            color: #ffffff;
        }

        .button--danger {
            border-color: #ef4444;
            color: #dc2626;
        }

        .paper-wrap {
            padding: 20px;
        }

        .paper {
            background: #ffffff;
            border: 1px solid #d8e0eb;
            box-shadow: 0 14px 45px rgba(15, 23, 42, .13);
            margin: 0 auto;
            max-width: 1120px;
            min-height: 720px;
            padding: 22px 24px 24px;
        }

        .header {
            border-bottom: 2px solid #1769ff;
            display: table;
            padding-bottom: 10px;
            table-layout: fixed;
            width: 100%;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 55%;
        }

        .header-right {
            text-align: right;
            width: 45%;
        }

        .brand-table {
            border-collapse: collapse;
        }

        .brand-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .brand-mark {
            background: #1769ff;
            border-radius: 8px;
            color: #ffffff;
            font-size: 17px;
            font-weight: 900;
            height: 38px;
            line-height: 38px;
            margin-right: 9px;
            text-align: center;
            width: 38px;
        }

        .brand-name {
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .brand-subtitle {
            color: #64748b;
            font-size: 7px;
            margin-top: 1px;
        }

        .document-number {
            color: #1769ff;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .document-title {
            color: #0f172a;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.2;
            margin-top: 2px;
        }

        .document-subtitle {
            color: #64748b;
            font-size: 7px;
            margin-top: 2px;
        }

        .info-table {
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 11px;
            table-layout: fixed;
            width: 100%;
        }

        .info-table td {
            background: #f8fafc;
            border-bottom: 1px solid #dbe3ee;
            border-top: 1px solid #dbe3ee;
            padding: 7px 9px;
            vertical-align: top;
            width: 25%;
        }

        .info-table td:first-child {
            border-left: 1px solid #dbe3ee;
            border-radius: 7px 0 0 7px;
        }

        .info-table td:last-child {
            border-right: 1px solid #dbe3ee;
            border-radius: 0 7px 7px 0;
        }

        .info-label {
            color: #64748b;
            display: block;
            font-size: 6px;
            font-weight: 900;
            letter-spacing: .05em;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .info-value {
            color: #172033;
            display: block;
            font-size: 8px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .summary-table {
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 11px -7px 0;
            table-layout: fixed;
            width: calc(100% + 14px);
        }

        .summary-table td {
            border: 1px solid #dbe3ee;
            border-radius: 7px;
            padding: 8px 10px;
            vertical-align: middle;
            width: 25%;
        }

        .summary-label {
            color: #64748b;
            font-size: 6px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.1;
            margin-top: 2px;
        }

        .section {
            margin-top: 14px;
        }

        .section-heading {
            border-bottom: 1px solid #cfd8e6;
            color: #0f172a;
            font-size: 10px;
            font-weight: 900;
            margin-bottom: 6px;
            padding-bottom: 5px;
        }

        .data-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background: #edf3fb;
            border: 1px solid #cfd8e6;
            color: #344054;
            font-size: 6.5px;
            font-weight: 900;
            padding: 5px;
            text-align: left;
            text-transform: uppercase;
        }

        .data-table td {
            border: 1px solid #d9e1ec;
            color: #293548;
            font-size: 7px;
            padding: 5px;
            vertical-align: top;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #fbfdff;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .strong {
            color: #0f172a;
            font-weight: 800;
        }

        .muted {
            color: #64748b;
            font-size: 6px;
        }

        .mono {
            font-family:
                DejaVu Sans Mono,
                Consolas,
                monospace;
        }

        .status {
            border-radius: 999px;
            display: inline-block;
            font-size: 5.5px;
            font-weight: 900;
            padding: 3px 5px;
            text-transform: uppercase;
        }

        .status--good {
            background: #dcfce7;
            color: #166534;
        }

        .status--blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status--danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 7px;
            color: #64748b;
            padding: 14px;
            text-align: center;
        }

        .signature {
            border-top: 1px solid #dbe3ee;
            margin-top: 17px;
            padding-top: 10px;
        }

        .signature-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .signature-table td {
            border: 0;
            padding: 0 8px;
            text-align: center;
            vertical-align: top;
            width: 33.333%;
        }

        .signature-role {
            color: #475569;
            font-size: 6.5px;
        }

        .signature-space {
            height: 38px;
        }

        .signature-name {
            border-top: 1px solid #334155;
            color: #0f172a;
            display: block;
            font-size: 7px;
            font-weight: 900;
            margin: 0 auto;
            max-width: 180px;
            padding-top: 3px;
        }

        .signature-id {
            color: #64748b;
            font-size: 6px;
            margin-top: 2px;
        }

        .footer-note {
            color: #64748b;
            font-size: 6px;
            line-height: 1.5;
            margin-top: 8px;
        }

        .notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            color: #78350f;
            margin-bottom: 10px;
            padding: 8px 10px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar,
            .notice {
                display: none !important;
            }

            .paper-wrap {
                padding: 0;
            }

            .paper {
                border: 0;
                box-shadow: none;
                margin: 0;
                max-width: none;
                min-height: 0;
                padding: 0;
                width: 100%;
            }
        }

        @media screen and (max-width: 900px) {
            .paper-wrap {
                overflow-x: auto;
                padding: 8px;
            }

            .paper {
                min-width: 1000px;
            }

            .toolbar {
                position: static;
            }
        }
    </style>
</head>

<body>
    @php
        $formatStock = static function (
            mixed $value
        ): string {
            return rtrim(
                rtrim(
                    number_format(
                        (float) $value,
                        3,
                        ',',
                        '.'
                    ),
                    '0'
                ),
                ','
            );
        };

        $officialDocument =
            $officialDocument
            ?? app(
                \App\Services\OfficialDocumentService::class
            )->make(
                (int) $location->workshop_id,
                auth()->user(),
                'INVENTARIS-LOKASI',
                $location->code,
                $generatedAt
            );

        $totalEntries =
            (int) $summary['tool_units']
            +
            (int) $summary['material_types'];
    @endphp

    @unless ($pdfMode ?? false)
        <div class="toolbar">
            <div class="toolbar-group">
                <span class="toolbar-title">
                    Pratinjau Dokumen Inventaris Lokasi
                </span>

                <button
                    type="button"
                    class="button button--primary"
                    onclick="window.print()"
                >
                    Print Dokumen
                </button>

                <a
                    href="{{ route(
                        'locations.inventory.pdf',
                        array_filter([
                            'location' =>
                                $location->id,

                            'include_children' =>
                                $includeChildren
                                    ? 1
                                    : null,
                        ])
                    ) }}"
                    class="button button--danger"
                >
                    Download PDF
                </a>
            </div>

            <div class="toolbar-group">
                @if ($includeChildren)
                    <a
                        href="{{ route(
                            'locations.inventory.print',
                            [
                                'storageLocation' =>
                                    $location->id,
                            ]
                        ) }}"
                        class="button"
                    >
                        Lokasi Ini Saja
                    </a>
                @else
                    <a
                        href="{{ route(
                            'locations.inventory.print',
                            [
                                'storageLocation' =>
                                    $location->id,

                                'include_children' =>
                                    1,
                            ]
                        ) }}"
                        class="button"
                    >
                        Sertakan Turunan
                    </a>
                @endif
            </div>
        </div>
    @endunless

    <div class="paper-wrap">
        <main class="paper">
            @if ($pdfFallback ?? false)
                <div class="notice">
                    DomPDF belum tersedia. Gunakan
                    <strong>Print Dokumen</strong>, lalu pilih
                    <strong>Save as PDF</strong>.
                </div>
            @endif

            <header class="header">
                <div class="header-left">
                    <table class="brand-table">
                        <tr>
                            <td>
                                <div class="brand-mark">
                                    S
                                </div>
                            </td>

                            <td>
                                <div class="brand-name">
                                    {{ $officialDocument['systemName'] }}
                                </div>

                                <div class="brand-subtitle">
                                    {{ $officialDocument['systemSubtitle'] }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="header-right">
                    <div class="document-number">
                        {{ $officialDocument['number'] }}
                    </div>

                    <div class="document-title">
                        Daftar Inventaris Lokasi
                    </div>

                    <div class="document-subtitle">
                        Dokumen resmi kontrol isi lokasi
                    </div>
                </div>
            </header>

            <table class="info-table">
                <tr>
                    <td>
                        <span class="info-label">
                            Jurusan
                        </span>

                        <span class="info-value">
                            {{ $officialDocument['workshopLabel'] }}
                        </span>
                    </td>

                    <td>
                        <span class="info-label">
                            Lokasi
                        </span>

                        <span class="info-value">
                            {{ $location->breadcrumb() }}
                        </span>
                    </td>

                    <td>
                        <span class="info-label">
                            Cakupan
                        </span>

                        <span class="info-value">
                            {{ $includeChildren
                                ? 'Lokasi dan seluruh turunannya'
                                : 'Lokasi ini saja' }}
                        </span>
                    </td>

                    <td>
                        <span class="info-label">
                            Dicetak
                        </span>

                        <span class="info-value">
                            {{ $generatedAt->format(
                                'd-m-Y H:i'
                            ) }}
                        </span>
                    </td>
                </tr>
            </table>

            <table class="summary-table">
                <tr>
                    <td>
                        <div class="summary-label">
                            Jenis Alat
                        </div>

                        <div class="summary-value">
                            {{ $summary['tool_types'] }}
                        </div>
                    </td>

                    <td>
                        <div class="summary-label">
                            Unit Alat
                        </div>

                        <div class="summary-value">
                            {{ $summary['tool_units'] }}
                        </div>
                    </td>

                    <td>
                        <div class="summary-label">
                            Jenis Bahan
                        </div>

                        <div class="summary-value">
                            {{ $summary['material_types'] }}
                        </div>
                    </td>

                    <td>
                        <div class="summary-label">
                            Total Entri
                        </div>

                        <div class="summary-value">
                            {{ $totalEntries }}
                        </div>
                    </td>
                </tr>
            </table>

            <section class="section">
                <div class="section-heading">
                    Ringkasan Alat Berdasarkan Nama
                </div>

                @if ($toolGroups->isEmpty())
                    <div class="empty">
                        Belum ada unit alat aktif pada lokasi ini.
                    </div>
                @else
                    <table class="data-table">
                        <colgroup>
                            <col style="width:4%">
                            <col style="width:26%">
                            <col style="width:9%">
                            <col style="width:9%">
                            <col style="width:9%">
                            <col style="width:9%">
                            <col style="width:34%">
                        </colgroup>

                        <thead>
                            <tr>
                                <th class="center">No</th>
                                <th>Nama Alat</th>
                                <th class="center">Jumlah</th>
                                <th class="center">Tersedia</th>
                                <th class="center">Dipinjam</th>
                                <th class="center">Masalah</th>
                                <th>Nomor Inventaris</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $toolGroups->values()
                                as $index => $group
                            )
                                @php
                                    $first =
                                        $group->first();

                                    $available =
                                        $group->where(
                                            'status',
                                            'available'
                                        )->count();

                                    $borrowed =
                                        $group->whereIn(
                                            'status',
                                            [
                                                'borrowed',
                                                'reserved',
                                            ]
                                        )->count();

                                    $problem =
                                        $group->whereIn(
                                            'status',
                                            [
                                                'damaged',
                                                'under_repair',
                                                'lost',
                                            ]
                                        )->count();
                                @endphp

                                <tr>
                                    <td class="center">
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        <div class="strong">
                                            {{ $first->item?->name
                                                ?? '-' }}
                                        </div>

                                        <div class="muted mono">
                                            {{ $first->item?->code
                                                ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="center strong">
                                        {{ $group->count() }}
                                    </td>

                                    <td class="center">
                                        <span class="status status--good">
                                            {{ $available }}
                                        </span>
                                    </td>

                                    <td class="center">
                                        <span class="status status--blue">
                                            {{ $borrowed }}
                                        </span>
                                    </td>

                                    <td class="center">
                                        <span class="status {{
                                            $problem > 0
                                                ? 'status--danger'
                                                : 'status--good'
                                        }}">
                                            {{ $problem }}
                                        </span>
                                    </td>

                                    <td class="mono">
                                        {{ $group
                                            ->pluck(
                                                'asset_number'
                                            )
                                            ->filter()
                                            ->implode(', ') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="section">
                <div class="section-heading">
                    Detail Unit Alat
                    — {{ $assets->count() }} unit
                </div>

                @if ($assets->isEmpty())
                    <div class="empty">
                        Tidak ada detail unit alat pada lokasi ini.
                    </div>
                @else
                    <table class="data-table">
                        <colgroup>
                            <col style="width:4%">
                            <col style="width:24%">
                            <col style="width:18%">
                            <col style="width:16%">
                            <col style="width:17%">
                            <col style="width:10%">
                            <col style="width:11%">
                        </colgroup>

                        <thead>
                            <tr>
                                <th class="center">No</th>
                                <th>Nama Alat</th>
                                <th>Nomor Inventaris</th>
                                <th>Nomor Seri</th>
                                <th>Lokasi</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $assets
                                as $index => $asset
                            )
                                <tr>
                                    <td class="center">
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        <div class="strong">
                                            {{ $asset->item?->name
                                                ?? '-' }}
                                        </div>

                                        <div class="muted mono">
                                            {{ $asset->item?->code
                                                ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="mono">
                                        {{ $asset->asset_number }}
                                    </td>

                                    <td class="mono">
                                        {{ $asset->serial_number
                                            ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $asset
                                            ->storageLocation
                                            ?->name
                                            ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $asset->conditionLabel() }}
                                    </td>

                                    <td>
                                        {{ $asset->statusLabel() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="section">
                <div class="section-heading">
                    Daftar Bahan
                    — stok ditampilkan sesuai satuan
                </div>

                @if ($materials->isEmpty())
                    <div class="empty">
                        Tidak ada bahan aktif pada lokasi ini.
                    </div>
                @else
                    <table class="data-table">
                        <colgroup>
                            <col style="width:4%">
                            <col style="width:17%">
                            <col style="width:31%">
                            <col style="width:22%">
                            <col style="width:9%">
                            <col style="width:8%">
                            <col style="width:9%">
                        </colgroup>

                        <thead>
                            <tr>
                                <th class="center">No</th>
                                <th>Kode</th>
                                <th>Nama Bahan</th>
                                <th>Lokasi</th>
                                <th class="right">Stok</th>
                                <th>Satuan</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $materials
                                as $index => $item
                            )
                                <tr>
                                    <td class="center">
                                        {{ $index + 1 }}
                                    </td>

                                    <td class="mono">
                                        {{ $item->code }}
                                    </td>

                                    <td class="strong">
                                        {{ $item->name }}
                                    </td>

                                    <td>
                                        {{ $item->location?->name
                                            ?? '-' }}
                                    </td>

                                    <td class="right strong">
                                        {{ $formatStock(
                                            $item->stock
                                        ) }}
                                    </td>

                                    <td>
                                        {{ $item->unit?->name
                                            ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $item->statusLabel() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <div class="signature">
                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="signature-role">
                                Dicetak oleh,
                                {{ $officialDocument[
                                    'printedBy'
                                ]['position'] }}
                            </div>

                            <div class="signature-space"></div>

                            <span class="signature-name">
                                {{ $officialDocument[
                                    'printedBy'
                                ]['name'] }}
                            </span>

                            @if (
                                $officialDocument[
                                    'printedBy'
                                ]['identifier']
                            )
                                <div class="signature-id">
                                    {{ $officialDocument[
                                        'printedBy'
                                    ]['identifier'] }}
                                </div>
                            @endif
                        </td>

                        <td>
                            <div class="signature-role">
                                Diperiksa oleh,
                                Toolman
                            </div>

                            <div class="signature-space"></div>

                            <span class="signature-name">
                                {{ $officialDocument[
                                    'toolman'
                                ]['name'] }}
                            </span>

                            @if (
                                $officialDocument[
                                    'toolman'
                                ]['identifier']
                            )
                                <div class="signature-id">
                                    {{ $officialDocument[
                                        'toolman'
                                    ]['identifier'] }}
                                </div>
                            @endif
                        </td>

                        <td>
                            <div class="signature-role">
                                Mengetahui,
                                Kepala Bengkel
                            </div>

                            <div class="signature-space"></div>

                            <span class="signature-name">
                                {{ $officialDocument[
                                    'head'
                                ]['name'] }}
                            </span>

                            @if (
                                $officialDocument[
                                    'head'
                                ]['identifier']
                            )
                                <div class="signature-id">
                                    {{ $officialDocument[
                                        'head'
                                    ]['identifier'] }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="footer-note">
                Dokumen resmi SIMBA. Nomor inventaris mewakili
                satu unit alat fisik. Stok bahan tidak dijumlahkan
                lintas satuan yang berbeda.
            </div>
        </main>
    </div>
</body>
</html>
