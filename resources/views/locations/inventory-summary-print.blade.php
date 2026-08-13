<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Inventaris {{ $location->code }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin:0; background:#e9eef5; color:#172033; font-family:DejaVu Sans,Arial,sans-serif; font-size:9px; }
        .toolbar { display:flex; justify-content:space-between; gap:8px; align-items:center; flex-wrap:wrap; padding:10px 16px; background:#fff; border-bottom:1px solid #dbe3ee; }
        .toolbar-group { display:flex; gap:8px; flex-wrap:wrap; }
        .button { display:inline-block; border:1px solid #94a3b8; border-radius:6px; padding:7px 11px; color:#172033; background:#fff; text-decoration:none; font-weight:700; cursor:pointer; }
        .primary { color:#fff; background:#1769ff; border-color:#1769ff; }
        .danger { color:#dc2626; border-color:#dc2626; }
        .wrap { padding:20px; }
        .paper { max-width:1120px; margin:0 auto; padding:20px 22px; background:#fff; border:1px solid #d8e0eb; box-shadow:0 10px 35px rgba(15,23,42,.12); }
        .header { display:table; width:100%; border-bottom:2px solid #1769ff; padding-bottom:10px; }
        .header > div { display:table-cell; vertical-align:middle; }
        .right-head { text-align:right; }
        .brand { margin:0; font-size:17px; font-weight:900; }
        .title { font-size:16px; font-weight:900; }
        .small { color:#64748b; font-size:7px; }
        .code { color:#1769ff; font-size:7px; font-weight:800; }
        .scope { margin-top:11px; padding:7px 9px; background:#f8fafc; border:1px solid #dbe3ee; border-radius:6px; }
        .cards { width:100%; margin-top:11px; border-spacing:6px 0; table-layout:fixed; }
        .cards td { width:20%; border:1px solid #dbe3ee; border-radius:6px; padding:7px 9px; }
        .card-label { color:#64748b; font-size:6.5px; font-weight:800; text-transform:uppercase; }
        .card-value { margin-top:2px; font-size:13px; font-weight:900; }
        .section { margin-top:14px; }
        .section-title { margin:0 0 5px; padding-bottom:4px; border-bottom:1px solid #cfd8e6; font-size:10px; font-weight:900; }
        table.data { width:100%; border-collapse:collapse; table-layout:fixed; }
        table.data thead { display:table-header-group; }
        table.data tr { page-break-inside:avoid; }
        table.data th { padding:5px; border:1px solid #cfd8e6; background:#edf3fb; color:#344054; font-size:6.8px; text-align:left; text-transform:uppercase; }
        table.data td { padding:5px; border:1px solid #d9e1ec; font-size:7.4px; vertical-align:top; }
        .center { text-align:center; }
        .right { text-align:right; }
        .strong { font-weight:800; }
        .mono { font-family:DejaVu Sans Mono,monospace; }
        .empty { padding:15px; border:1px dashed #cbd5e1; color:#64748b; text-align:center; }
        .signatures { width:100%; margin-top:18px; border-collapse:collapse; table-layout:fixed; page-break-inside:avoid; }
        .signatures td { width:33.333%; text-align:center; vertical-align:top; }
        .space { height:42px; }
        .name { display:inline-block; min-width:150px; padding-top:3px; border-top:1px solid #172033; font-weight:800; }
        @media print { body{background:#fff}.toolbar{display:none}.wrap{padding:0}.paper{max-width:none;border:0;box-shadow:none;padding:0} }
    </style>
</head>
<body>
    @php
        $formatStock = static function (mixed $value): string {
            $formatted = number_format((float) $value, 3, ',', '.');
            return rtrim(rtrim($formatted, '0'), ',');
        };

        $documentCode =
            'SIMBA/RINGKASAN-INVENTARIS/'.
            ($location->workshop?->code ?: 'UMUM').'/' .
            $generatedAt->format('Ymd').'/' .
            $location->code;
    @endphp

    @unless ($pdfMode)
        <div class="toolbar">
            <strong>Ringkasan Inventaris: {{ $location->code }}</strong>
            <div class="toolbar-group">
                <a href="{{ route('locations.inventory.menu') }}" class="button">Menu Cetak</a>
                <a href="{{ route('locations.inventory.complete', [
                    'storageLocation' => $location->getRouteKey(),
                    'include_children' => $includeChildren ? 1 : 0,
                ]) }}" class="button">Buka Detail</a>
                <button type="button" class="button primary" onclick="window.print()">Print</button>
                <a href="{{ route('locations.inventory.summary.pdf', [
                    'storageLocation' => $location->getRouteKey(),
                    'include_children' => $includeChildren ? 1 : 0,
                ]) }}" class="button danger">Download PDF</a>
            </div>
        </div>
    @endunless

    <div class="wrap">
        <main class="paper">
            <header class="header">
                <div>
                    <h1 class="brand">SIMBA</h1>
                    <div class="small">Sistem Inventaris dan Peminjaman Bengkel</div>
                </div>
                <div class="right-head">
                    <div class="code">{{ $documentCode }}</div>
                    <div class="title">Ringkasan Inventaris Lokasi</div>
                    <div class="small">Tanpa rincian nomor inventaris dan unit alat</div>
                </div>
            </header>

            <div class="scope">
                <strong>Jenis:</strong>
                {{ $location->parent_id === null ? 'Lokasi Induk' : 'Lokasi Turunan' }}
                &nbsp; | &nbsp;
                <strong>Ruang lingkup:</strong>
                {{ $location->code }} - {{ $location->name }}
                {{ $includeChildren ? 'beserta seluruh turunannya' : '(lokasi ini saja)' }}
                &nbsp; | &nbsp;
                <strong>Dibuat:</strong> {{ $generatedAt->format('d-m-Y H:i') }}
                &nbsp; | &nbsp;
                <strong>Petugas:</strong> {{ $printedBy }}
            </div>

            <table class="cards">
                <tr>
                    <td><div class="card-label">Jenis Alat</div><div class="card-value">{{ $summary['tool_types'] }}</div></td>
                    <td><div class="card-label">Unit Alat</div><div class="card-value">{{ $summary['tool_units'] }}</div></td>
                    <td><div class="card-label">Jenis Bahan</div><div class="card-value">{{ $summary['material_types'] }}</div></td>
                    <td><div class="card-label">Stok Bahan</div><div class="card-value">{{ $formatStock($summary['material_stock']) }}</div></td>
                    <td><div class="card-label">Lokasi Dicakup</div><div class="card-value">{{ $summary['location_count'] }}</div></td>
                </tr>
            </table>

            <section class="section">
                <h2 class="section-title">Ringkasan Alat</h2>
                @if ($toolSummaries->isEmpty())
                    <div class="empty">Tidak ada alat aktif pada cakupan ini.</div>
                @else
                    <table class="data">
                        <thead><tr><th>Kode</th><th>Nama Alat</th><th>Kategori</th><th class="center">Jumlah Unit</th><th>Lokasi</th></tr></thead>
                        <tbody>
                            @foreach ($toolSummaries as $row)
                                <tr>
                                    <td class="mono">{{ $row['code'] }}</td>
                                    <td class="strong">{{ $row['name'] }}</td>
                                    <td>{{ $row['category'] }}</td>
                                    <td class="center strong">{{ $row['unit_count'] }} {{ $row['unit_name'] }}</td>
                                    <td>{{ implode(', ', $row['locations']) ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="section">
                <h2 class="section-title">Ringkasan Bahan</h2>
                @if ($materialSummaries->isEmpty())
                    <div class="empty">Tidak ada bahan aktif pada cakupan ini.</div>
                @else
                    <table class="data">
                        <thead><tr><th>Kode</th><th>Nama Bahan</th><th>Kategori</th><th class="right">Stok</th><th>Lokasi</th></tr></thead>
                        <tbody>
                            @foreach ($materialSummaries as $row)
                                <tr>
                                    <td class="mono">{{ $row['code'] }}</td>
                                    <td class="strong">{{ $row['name'] }}</td>
                                    <td>{{ $row['category'] }}</td>
                                    <td class="right strong">{{ $formatStock($row['stock']) }} {{ $row['unit_name'] }}</td>
                                    <td>{{ $row['location'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <table class="signatures">
                <tr>
                    <td>Dicetak oleh<div class="space"></div><div class="name">{{ $printedBy }}</div><div class="small">{{ $printedUsername }}</div></td>
                    <td>Diperiksa oleh, Toolman<div class="space"></div><div class="name">{{ $toolmanName }}</div><div class="small">{{ $toolmanUsername }}</div></td>
                    <td>Mengetahui, Kepala Bengkel<div class="space"></div><div class="name">{{ $headName }}</div><div class="small">{{ $headUsername }}</div></td>
                </tr>
            </table>
        </main>
    </div>
</body>
</html>
