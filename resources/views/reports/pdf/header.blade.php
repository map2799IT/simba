{{-- Reusable PDF Header/Kop Surat
     Props: $reportTitle, $workshopName (optional), $periodFrom, $periodTo (optional)
--}}
@php
    $school = config('school');
@endphp
<div class="header">
    <div class="logo-left">
        <img src="{{ public_path('branding/simba-mark.png') }}" alt="Logo" style="height:55px; width:auto;" onerror="this.style.display='none'">
    </div>
    <div class="kop-center">
        <div style="font-size:8px; font-weight:900; text-transform:uppercase;">{{ $school['province'] }}</div>
        <div style="font-size:14px; font-weight:900; text-transform:uppercase;">{{ $school['institution'] }}</div>
        <div style="font-size:7px; margin-top:2px;">{{ $school['address'] }}</div>
        <div style="font-size:7px;">Telepon {{ $school['phone'] }}, Kode Pos {{ $school['postal_code'] }}</div>
        <div style="font-size:7px;">Email: {{ $school['email'] }} | Website: {{ $school['website'] }}</div>
    </div>
    <div class="logo-right">
        <img src="{{ public_path('branding/simba-logo.png') }}" alt="SIMBA" style="height:45px; width:auto;" onerror="this.style.display='none'">
    </div>
</div>
<div class="divider-double"></div>

<div class="doc-title">{{ strtoupper($reportTitle ?? 'LAPORAN') }}</div>
@if (!empty($workshopName))
    <div class="doc-subtitle">{{ strtoupper($workshopName) }}</div>
@endif

<table class="doc-meta">
    <tr>
        <td style="width:120px;">Jurusan</td>
        <td>: {{ $workshopName ?? 'Semua Jurusan' }}</td>
        @if (!empty($periodFrom) || !empty($periodTo))
            <td style="width:100px;">Periode</td>
            <td>: {{ $periodFrom ?? '-' }} s.d. {{ $periodTo ?? '-' }}</td>
        @endif
    </tr>
    <tr>
        <td>Tanggal Cetak</td>
        <td>: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB</td>
        <td>Dicetak Oleh</td>
        <td>: {{ auth()->user()?->name ?? '-' }}</td>
    </tr>
</table>
