<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Barang Masuk {{ $movement->receipt_code ?: $movement->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #111827; font-size: 12px; margin: 0; padding: 24px; }
        h1 { font-size: 18px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px; }
        .muted { color: #6b7280; font-size: 11px; }
        .title { color: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 7px 10px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 11px; }
        .kv { margin-bottom: 8px; }
        .kv .lbl { color: #6b7280; font-size: 11px; }
        .kv .val { font-weight: 600; }
        .footer { margin-top: 28px; display: flex; justify-content: space-between; gap: 40px; }
        .sign { flex: 1; text-align: center; font-size: 11px; }
        .sign .space { height: 72px; }
        .muted-rule { border-top: 1px solid #d1d5db; margin: 20px 0 8px; }
        .badge { display:inline-block; padding:2px 8px; border:1px solid #2563eb; color:#2563eb; border-radius:4px; font-size:10px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">BUKTI BARANG MASUK</h1>
            <div class="muted">Sistem Inventaris Bengkel (SIMBA)</div>
        </div>
        <div style="text-align:right">
            <div class="muted">Kode Barang Masuk</div>
            <div style="font-size:14px; font-weight:700">{{ $movement->receipt_code ?: '-' }}</div>
        </div>
    </div>

    <div class="kv"><span class="lbl">Nomor Dokumen:</span> <span class="val">{{ $movement->reference_number ?: '-' }}</span></div>
    <div class="kv"><span class="lbl">Tanggal:</span> <span class="val">{{ $movement->transaction_date?->format('d-m-Y') ?? '-' }}</span></div>
    <div class="kv"><span class="lbl">Bengkel / Jurusan:</span> <span class="val">{{ $movement->workshop?->code }} — {{ $movement->workshop?->name }}</span></div>

    <div class="kv"><span class="lbl">Barang:</span> <span class="val">{{ $movement->item?->name ?? '-' }} ({{ $movement->item?->code ?? '-' }})</span></div>
    <div class="kv"><span class="lbl">Jumlah:</span> <span class="val">{{ \App\Support\QuantityFormatter::format($movement->quantity, $movement->item?->unit) }} {{ $movement->item?->unit?->name }}</span></div>
    <div class="kv"><span class="lbl">Merek / Model:</span> <span class="val">{{ collect([$movement->brand, $movement->model])->filter()->implode(' / ') ?: '-' }}</span></div>
    <div class="kv"><span class="lbl">Sumber / Dana:</span> <span class="val">{{ collect([$movement->source, $movement->fund_source])->filter()->implode(' · ') ?: '-' }}</span></div>
    <div class="kv"><span class="lbl">Lokasi:</span> <span class="val">{{ $movement->storageLocation?->code }} — {{ $movement->storageLocation?->name }}</span></div>
    <div class="kv"><span class="lbl">Kondisi:</span> <span class="val">{{ $movement->item?->conditionLabel() ?? ($movement->condition ?? '-') }}</span></div>
    <div class="kv"><span class="lbl">Dicatat oleh:</span> <span class="val">{{ $movement->user?->name ?? $movement->user?->username ?? '-' }}</span></div>

    @if ($movement->description)
        <div class="kv"><span class="lbl">Catatan:</span> <span class="val">{{ $movement->description }}</span></div>
    @endif

    <div class="muted-rule"></div>

    <div class="footer">
        <div class="sign">
            <div>Diterima Oleh,</div>
            <div class="space"></div>
            <div style="border-top:1px solid #111827; margin:0 20px; padding-top:4px">( .................................... )</div>
        </div>
        <div class="sign">
            <div>Mengetahui, {{ $movement->workshop?->name }}</div>
            <div class="space"></div>
            <div style="border-top:1px solid #111827; margin:0 20px; padding-top:4px">( .................................... )</div>
        </div>
    </div>
</body>
</html>