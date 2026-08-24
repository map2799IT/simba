<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <title>Laporan Inventaris{{ !empty($periodLabel) ? ' ' . $periodLabel : '' }}</title>

    <style>
        @page {
            margin: 18px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
        }

        h1 {
            font-size: 17px;
            margin: 0 0 4px;
        }

        .meta {
            color: #4b5563;
            margin-bottom: 12px;
        }

        .fallback {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            margin-bottom: 10px;
            padding: 7px;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 4px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-transform: uppercase;
        }

        .right {
            text-align: right;
        }

        .code {
            font-family: DejaVu Sans Mono, monospace;
            font-weight: bold;
        }

        .total {
            font-size: 10px;
            font-weight: bold;
            margin-top: 9px;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $money = static fn (mixed $value): string =>
            'Rp '.
            number_format(
                (float) $value,
                0,
                ',',
                '.'
            );

        $quantity = static function (
            mixed $value,
            mixed $allowsDecimal
        ): string {
            if (
                class_exists(
                    \App\Support\QuantityFormatter::class
                )
            ) {
                return \App\Support\QuantityFormatter::format(
                    $value,
                    (bool) $allowsDecimal
                );
            }

            return (bool) $allowsDecimal
                ? rtrim(
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
                )
                : number_format(
                    (float) $value,
                    0,
                    ',',
                    '.'
                );
        };

        $condition = static fn (
            mixed $value
        ): string => match ($value) {
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
    @endphp

    @if ($pdfFallback ?? false)
        <div class="fallback">
            DomPDF belum tersedia. Gunakan Print
            browser lalu pilih Save as PDF.
        </div>
    @endif

    <h1>Laporan Inventaris{{ !empty($periodLabel) ? ' ' . $periodLabel : '' }}</h1>

    <div class="meta">
        Ruang lingkup:
        {{ $scopeLabel ?? 'Semua jurusan' }}
        <br>

        Jurusan dan lokasi bersumber dari unit fisik
        serta transaksi Barang Masuk.
        @if (!empty($filters['year']) || !empty($filters['date_from']) || !empty($filters['date_to']))
            <br>
            Filter:
            @if (!empty($filters['year']))
                Tahun {{ $filters['year'] }}
            @endif
            @if (!empty($filters['date_from']))
                dari {{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d-m-Y') }}
            @endif
            @if (!empty($filters['date_to']))
                s/d {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d-m-Y') }}
            @endif
        @endif
        <br>

        Dicetak:
        {{ $generatedAt->format('d-m-Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Barang</th>
                <th>Kategori</th>
                <th>Jurusan</th>
                <th>Lokasi</th>
                <th>Kondisi</th>
                <th class="right">Stok</th>
                <th class="right">Harga</th>
                <th class="right">Nilai</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td class="code">
                        {{ $item->code }}
                    </td>

                    <td>
                        {{ $item->name }}
                        <br>

                        <small>
                            {{ $item->report_brand ?: '-' }}

                            @if (
                                $item->report_model
                                && $item->report_model !== '-'
                            )
                                /
                                {{ $item->report_model }}
                            @endif
                        </small>
                    </td>

                    <td>
                        {{ $item->category_name ?? '-' }}
                    </td>

                    <td>
                        {{ $item->report_workshop_code }}
                    </td>

                    <td>
                        {{ $item->report_location_name }}
                    </td>

                    <td>
                        {{ $condition(
                            $item->report_condition
                        ) }}
                    </td>

                    <td class="right">
                        {{ $quantity(
                            $item->report_stock,
                            $item->allows_decimal
                        ) }}

                        {{ $item->unit_symbol
                            ?: $item->unit_name }}
                    </td>

                    <td class="right">
                        {{ $money(
                            $item->report_unit_price
                        ) }}
                    </td>

                    <td class="right">
                        {{ $money(
                            $item->report_inventory_value
                        ) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="9"
                        style="text-align:center;"
                    >
                        Tidak ada data inventaris.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">
        Total nilai:
        {{ $money($totalValue) }}
    </div>
</body>
</html>
