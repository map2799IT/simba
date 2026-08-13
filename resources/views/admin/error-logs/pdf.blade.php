<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Audit Sistem</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111827; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .sub { color: #4b5563; margin-bottom: 10px; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 4px; vertical-align: top; }
        th { background: #e5e7eb; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>AUDIT SISTEM</h1>
    <div class="sub">Dicetak {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }} · {{ $logs->count() }} record</div>
    <table>
        <thead>
            <tr>
                <th>Waktu</th><th>User</th><th>Role</th><th>Status</th><th>Method</th>
                <th>URL</th><th>Route</th><th>IP</th><th>Pesan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d-m-Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'Guest' }}</td>
                    <td>{{ $log->user?->role ?? '-' }}</td>
                    <td class="right">{{ $log->http_status }}</td>
                    <td>{{ $log->method }}</td>
                    <td>{{ $log->url }}</td>
                    <td>{{ $log->route_name ?? '-' }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($log->message, 120) }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
