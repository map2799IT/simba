@php
    $document = $officialDocument ?? null;
    $schoolName = 'SMK Negeri 4 Palembang';
    $schoolTagline = 'SMK Hebat, Siap Kerja, Santun, Mandiri, Kreatif';
    $schoolAddress = 'Jl. Sersan Sani No. 1019, Talang Aman, Kec. Kemuning, Kota Palembang, Sumatera Selatan';
    $schoolContact = 'Telp. 0711-810364 · Email: smkn4plbng@gmail.com · www.smkn4palembang.sch.id';
    $leftLogoPath = public_path('images/kop/logo-sumsel.jpg');
    $rightLogoPath = public_path('images/kop/logo-smkn4-palembang.png');
    $leftLogoUri = file_exists($leftLogoPath)
        ? 'file://' . str_replace('\\', '/', $leftLogoPath)
        : null;
    $rightLogoUri = file_exists($rightLogoPath)
        ? 'file://' . str_replace('\\', '/', $rightLogoPath)
        : null;
@endphp

<table style="width:100%; border-collapse:collapse; table-layout:fixed;">
    <tr>
        <td style="width:14%; vertical-align:middle; text-align:center;">
            @if ($leftLogoUri)
                <img src="{{ $leftLogoUri }}" alt="Logo Sumsel" style="height:76px; width:auto;">
            @elseif ($rightLogoUri)
                <img src="{{ $rightLogoUri }}" alt="Logo SMK Negeri 4 Palembang" style="height:76px; width:auto;">
            @endif
        </td>
        <td style="width:72%; vertical-align:middle; text-align:center; line-height:1.35;">
            <div style="font-size:16px; font-weight:bold; text-transform:uppercase; letter-spacing:0.6px; color:#0f172a;">{{ $schoolName }}</div>
            <div style="font-size:9.5px; font-weight:bold; color:#1d4ed8; text-transform:uppercase; letter-spacing:0.4px; margin-top:1px;">{{ $schoolTagline }}</div>
            <div style="font-size:8px; color:#334155; margin-top:3px;">{{ $schoolAddress }}</div>
            <div style="font-size:7px; color:#64748b; margin-top:1px;">{{ $schoolContact }}</div>
        </td>
        <td style="width:14%; vertical-align:middle; text-align:center;">
            @if ($rightLogoUri)
                <img src="{{ $rightLogoUri }}" alt="Logo SMK Negeri 4 Palembang" style="height:76px; width:auto;">
            @endif
        </td>
    </tr>
</table>

<div style="border-bottom:2.5px solid #1769ff; margin-top:7px;"></div>
<div style="border-bottom:1px solid #1769ff; margin-top:2px; margin-bottom:8px;"></div>

@if (!empty($document['number']) || !empty($reportTitle) || !empty($periodLabel))
    <table style="width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:6px;">
        <tr>
            <td style="vertical-align:bottom; text-align:left; font-size:7px; color:#64748b;">
                @if (!empty($document['number']))
                    <div style="font-weight:bold; text-transform:uppercase; color:#1769ff;">No: {{ $document['number'] }}</div>
                @endif
                @if (!empty($periodLabel))
                    <div style="margin-top:1px;">{{ $periodLabel }}</div>
                @endif
            </td>
            <td style="vertical-align:bottom; text-align:right; font-size:12px; font-weight:bold; color:#0f172a;">
                @if (!empty($reportTitle))
                    <div>{{ $reportTitle }}</div>
                @endif
            </td>
        </tr>
    </table>
@endif
