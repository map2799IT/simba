<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Inventaris {{ $location->code }}
    </title>

    <style>
        @page {
            margin: 18px 22px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h1,
        h2,
        p {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            margin-top: 4px;
            color: #4b5563;
        }

        .information {
            width: 100%;
            margin-bottom: 12px;
        }

        .information td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .summary td {
            border: 1px solid #9ca3af;
            padding: 7px;
            text-align: center;
        }

        .summary-label {
            color: #4b5563;
            font-size: 8px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 2px;
        }

        .inventory {
            width: 100%;
            border-collapse: collapse;
        }

        .inventory th,
        .inventory td {
            border: 1px solid #6b7280;
            padding: 5px;
            vertical-align: top;
        }

        .inventory th {
            background: #e5e7eb;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .small {
            font-size: 8px;
            color: #4b5563;
        }

        .footer {
            margin-top: 12px;
            font-size: 8px;
            color: #4b5563;
        }

        .page-number::after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">
            DAFTAR INVENTARIS LOKASI PENYIMPANAN
        </div>

        <div class="subtitle">
            Sistem Informasi Manajemen Bengkel dan Alat
        </div>
    </div>

    <table class="information">
        <tr>
            <td style="width: 16%;">
                Kode lokasi
            </td>

            <td style="width: 34%;">
                : <strong>{{ $location->code }}</strong>
            </td>

            <td style="width: 16%;">
                Bengkel
            </td>

            <td style="width: 34%;">
                :
                {{ $location->workshop?->name ?? '-' }}
            </td>
        </tr>

        <tr>
            <td>
                Nama lokasi
            </td>

            <td>
                : {{ $location->name }}
            </td>

            <td>
                Jenis lokasi
            </td>

            <td>
                : {{ $location->typeLabel() }}
            </td>
        </tr>

        <tr>
            <td>
                Jalur lokasi
            </td>

            <td colspan="3">
                : {{ $location->fullPath() }}
            </td>
        </tr>

        <tr>
            <td>
                Dicetak
            </td>

            <td colspan="3">
                :
                {{ $generatedAt->format('d-m-Y H:i:s') }}
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">
                    Jenis Barang
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $summary['total_rows'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Total Stok
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $summary['total_stock'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Alat
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $summary['tools'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Bahan
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $summary['materials'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Stok Minimum
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $summary['low_stock'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>
        </tr>
    </table>

    <table class="inventory">
        <thead>
            <tr>
                <th style="width: 3%;">
                    No.
                </th>

                <th style="width: 10%;">
                    Kode
                </th>

                <th style="width: 18%;">
                    Nama Barang
                </th>

                <th style="width: 10%;">
                    Kategori
                </th>

                <th style="width: 10%;">
                    Merek/Model
                </th>

                <th style="width: 12%;">
                    Lokasi
                </th>

                <th style="width: 7%;">
                    Kondisi
                </th>

                <th style="width: 7%;">
                    Status
                </th>

                <th style="width: 6%;">
                    Stok
                </th>

                <th style="width: 7%;">
                    Satuan
                </th>

                <th style="width: 10%;">
                    Nomor Seri
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td class="text-center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $item->code }}
                    </td>

                    <td>
                        <strong>
                            {{ $item->name }}
                        </strong>

                        <div class="small">
                            {{ $item->type === 'material'
                                ? 'Bahan'
                                : 'Alat' }}
                        </div>
                    </td>

                    <td>
                        {{ $item->category_name ?? '-' }}
                    </td>

                    <td>
                        {{ $item->brand ?? '-' }}

                        @if (! empty($item->model))
                            <div class="small">
                                {{ $item->model }}
                            </div>
                        @endif
                    </td>

                    <td>
                        {{ $item->location_code ?? '-' }}

                        <div class="small">
                            {{ $item->location_name ?? '-' }}
                        </div>
                    </td>

                    <td class="text-center">
                        {{ ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $item->condition ?? '-'
                            )
                        ) }}
                    </td>

                    <td class="text-center">
                        {{ ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $item->status ?? '-'
                            )
                        ) }}
                    </td>

                    <td class="text-right">
                        {{ number_format(
                            (float) ($item->stock ?? 0),
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="text-center">
                        {{ $item->unit_name ?? '-' }}
                    </td>

                    <td>
                        {{ $item->serial_number ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="11"
                        class="text-center"
                        style="padding: 20px;"
                    >
                        Tidak ada barang pada lokasi ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dibuat secara otomatis oleh SIMBA.

        <span style="float: right;">
            Halaman <span class="page-number"></span>
        </span>
    </div>
</body>
</html>