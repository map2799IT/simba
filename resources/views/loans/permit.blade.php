<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Serah Terima Alat</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            line-height: 1.5;
        }

        .kop {
            border-bottom: 2px solid #1769ff;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        h2.title {
            font-size: 13px;
            text-align: center;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 4px 0 16px;
            letter-spacing: 0.5px;
        }

        .meta-row {
            margin-bottom: 3px;
        }

        .meta-label {
            font-weight: bold;
        }

        table.identity {
            border-collapse: collapse;
            width: 100%;
            margin: 8px 0 14px;
        }

        table.identity td {
            border: 0;
            padding: 2px 0;
            vertical-align: top;
        }

        table.identity td.label {
            width: 30%;
            font-weight: bold;
        }

        table.identity td.sep {
            width: 1%;
            padding-right: 6px;
        }

        table.data {
            border-collapse: collapse;
            width: 100%;
            margin: 12px 0;
        }

        table.data th,
        table.data td {
            border: 1px solid #d9e1ec;
            padding: 5px 6px;
            vertical-align: top;
            text-align: left;
        }

        table.data th {
            background: #edf3fb;
            font-size: 8px;
            text-transform: uppercase;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        .statement {
            margin: 12px 0;
            text-align: justify;
        }

        .signature {
            margin-top: 34px;
            page-break-inside: avoid;
        }

        table.data {
            page-break-inside: auto;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        .title {
            page-break-after: avoid;
        }
    </style>
</head>

<body>
    @php
        $generatedAt = $generatedAt ?? now();

        $workshopId = $loan->workshop_id
            ?? $loan->borrower?->workshop_id
            ?? null;

        $officialDocument = app(\App\Services\OfficialDocumentService::class)->make(
            $workshopId ? (int) $workshopId : null,
            auth()->user(),
            'SURAT-SERAH-TERIMA-ALAT',
            $workshopId ? 'JURUSAN' : 'GLOBAL',
            $generatedAt
        );

        $approver = $loan->approver ?? $loan->borrower;

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $formatIdDate = static function (?string $value) use ($monthNames): string {
            $date = $value ? \Carbon\Carbon::parse($value) : null;
            if ($date === null) {
                return '-';
            }
            return $date->format('j') . ' ' . ($monthNames[(int) $date->format('n')] ?? $date->format('F')) . ' ' . $date->format('Y');
        };
    @endphp

    @include('prints.official-letterhead', [
        'officialDocument' => $officialDocument,
        'reportTitle' => 'Surat Serah Terima Alat',
        'periodLabel' => $loan->code,
    ])

    <h2 class="title">Surat Serah Terima Alat</h2>

    <table class="identity">
        <tr>
            <td class="label">Nomor Surat</td>
            <td class="sep">:</td>
            <td>{{ $officialDocument['number'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Peminjaman</td>
            <td class="sep">:</td>
            <td>{{ $loan->code }}</td>
        </tr>
        <tr>
            <td class="label">Nama Peminjam</td>
            <td class="sep">:</td>
            <td>{{ $loan->borrower?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Peran</td>
            <td class="sep">:</td>
            <td>{{ $loan->borrower?->roleLabel() ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jurusan</td>
            <td class="sep">:</td>
            <td>{{ $loan->workshop?->code ?? $loan->borrower?->workshop?->code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Batas Pengembalian</td>
            <td class="sep">:</td>
            <td>{{ $loan->due_at ? $formatIdDate($loan->due_at) . ' pukul ' . $loan->due_at->format('H:i') . ' WIB' : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Keperluan</td>
            <td class="sep">:</td>
            <td>{{ $loan->purpose ?? '-' }}</td>
        </tr>
    </table>

    <p class="statement">
        Yang bertanda tangan di bawah ini, kami pihak yang bertanggung jawab atas
        pengelolaan inventaris bengkel, dengan ini menyatakan bahwa alat-alat yang
        tercantum dalam tabel berikut telah diserahkan kepada peminjam pada tanggal
        {{ $formatIdDate($loan->borrowed_at ?? $loan->approved_at) }} dalam kondisi
        baik dan layak digunakan, guna dipergunakan sesuai dengan keperluan sebagaimana
        yang telah diuraikan di atas.
    </p>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center" style="width:6%;">No</th>
                <th style="width:20%;">Kode Alat</th>
                <th style="width:32%;">Nama Alat</th>
                <th class="text-center" style="width:16%;">Nomor Unit / QR</th>
                <th class="text-center" style="width:10%;">Jumlah</th>
                <th class="text-center" style="width:16%;">Kondisi Keluar</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @forelse ($loan->items as $index => $li)
                @php
                    $qtyRaw = (float) ($li->quantity ?? 1);
                    $totalQty += $qtyRaw;
                    $qtyText = $qtyRaw === floor($qtyRaw)
                        ? number_format($qtyRaw, 0, ',', '.')
                        : rtrim(rtrim(number_format($qtyRaw, 3, ',', '.'), '0'), ',');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $li->item?->code ?? '-' }}</td>
                    <td>{{ $li->item?->name ?? '-' }}</td>
                    <td class="text-center">{{ $li->itemAsset?->asset_number ?? '-' }}</td>
                    <td class="text-center">{{ $qtyText }}</td>
                    <td class="text-center">{{ $li->condition_out ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada alat dalam peminjaman ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($loan->items->count() > 1)
        <tfoot>
            <tr class="text-bold">
                <td class="text-right" colspan="4">Total Jumlah</td>
                <td class="text-center">
                    {{ number_format($totalQty, 0, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <p class="statement">
        Peminjam bertanggung jawab sepenuhnya atas keutuhan, kebersihan, dan keamanan
        alat-alat yang dipinjam selama masa peminjaman, serta berkewajiban untuk
        mengembalikannya tepat waktu dalam kondisi baik dan layak sebagaimana saat
        diserahkan. Segala kerusakan, kehilangan, atau penyalahgunaan yang timbul
        selama masa peminjaman merupakan tanggung jawab peminjam dan akan diproses
        sesuai dengan ketentuan yang berlaku.
    </p>

    <p class="statement">
        Demikian surat serah terima ini dibuat dengan sebenar-benarnya dan dipergunakan
        sebagaimana mestinya. Atas perhatian dan kerja samanya, diucapkan terima kasih.
    </p>

    <div class="signature">
        @include('prints.official-signatures', [
            'officialDocument' => $officialDocument,
            'signatureMode' => 'general',
            'signaturePeople' => [
                [
                    'position' => 'Peminjam',
                    'name' => $loan->borrower?->name ?? '-',
                    'identifier' => $loan->borrower?->nomor_identitas ?? null,
                ],
                [
                    'position' => 'Penyerah / Toolman',
                    'name' => $approver?->name ?? '-',
                    'identifier' => $approver?->nomor_identitas ?? null,
                ],
                [
                    'position' => $officialDocument['head']['position'] ?? 'Kepala Bengkel',
                    'name' => $officialDocument['head']['name'] ?? '-',
                    'identifier' => $officialDocument['head']['identifier'] ?? null,
                ],
            ],
        ])
    </div>
</body>
</html>
