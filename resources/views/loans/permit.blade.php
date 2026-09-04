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
    @endphp

    @include('prints.official-letterhead', [
        'officialDocument' => $officialDocument,
        'reportTitle' => 'Surat Serah Terima Alat',
        'periodLabel' => $loan->code,
    ])

    <h2 class="title">Surat Serah Terima Alat</h2>

    <div class="meta-row">
        <span class="meta-label">Nomor Peminjaman:</span>
        {{ $loan->code }}
    </div>
    <div class="meta-row">
        <span class="meta-label">Nama Peminjam:</span>
        {{ $loan->borrower?->name ?? '-' }}
    </div>
    <div class="meta-row">
        <span class="meta-label">Peran:</span>
        {{ $loan->borrower?->roleLabel() ?? '-' }}
    </div>
    <div class="meta-row">
        <span class="meta-label">Jurusan:</span>
        {{ $loan->workshop?->code ?? $loan->borrower?->workshop?->code ?? '-' }}
    </div>
    <div class="meta-row">
        <span class="meta-label">Batas Pengembalian:</span>
        {{ optional($loan->due_at)->format('d-m-Y H:i') ?? '-' }}
    </div>
    <div class="meta-row">
        <span class="meta-label">Keperluan:</span>
        {{ $loan->purpose ?? '-' }}
    </div>

    <p class="statement">
        Dengan ini dinyatakan bahwa alat-alat berikut telah diserahkan kepada peminjam
        pada tanggal {{ optional($loan->borrowed_at ?? $loan->approved_at)->format('d-m-Y') ?? '-' }}
        dalam kondisi baik, untuk dipergunakan sesuai keperluan yang tertera di atas.
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
        Peminjam bertanggung jawab penuh atas keutuhan, kebersihan, dan keamanan alat
        yang dipinjam, dan wajib mengembalikannya tepat waktu dalam kondisi baik.
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
