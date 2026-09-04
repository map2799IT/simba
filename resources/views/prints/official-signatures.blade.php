@php
    $document = $officialDocument;
    $signatureMode = $signatureMode ?? 'general'; // general | location
    $signaturePeople = $signaturePeople ?? [
        $document['printedBy'],
        $document['toolman'],
        $document['head'],
    ];

    $signatureDate = $signatureDate ?? $document['generatedAt'] ?? now();

    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $signatureLocation = $signatureLocation ?? 'Palembang';
    $formattedSignatureDate = $signatureDate instanceof \DateTimeInterface
        ? $signatureDate->format('j') . ' ' . ($monthNames[(int) $signatureDate->format('n')] ?? $signatureDate->format('F')) . ' ' . $signatureDate->format('Y')
        : (string) $signatureDate;
@endphp

<div style="text-align:right; font-size:8px; color:#172033; margin-bottom:24px;">
    {{ $signatureLocation }}, {{ $formattedSignatureDate }}
</div>

<table style="border-collapse:collapse; table-layout:fixed; width:100%;">
    <tr>
        @foreach ($signaturePeople as $person)
            @php
                $colWidth = count($signaturePeople) === 2 ? '50%' : (count($signaturePeople) === 3 ? '33.333%' : '25%');
            @endphp
            <td style="border:0; padding:0 8px; text-align:center; vertical-align:top; width:{{ $colWidth }};">
                <div style="color:#475569; font-size:7px; font-weight:bold;">{{ $person['position'] }}</div>
                <div style="height:42px;"></div>
                <span style="border-top:1px solid #334155; display:block; font-size:7px; font-weight:bold; margin:0 auto; max-width:180px; padding-top:3px;">
                    {{ $person['name'] }}
                </span>
                @if ($person['identifier'])
                    <div style="color:#64748b; font-size:6px; margin-top:2px;">{{ $person['identifier'] }}</div>
                @endif
            </td>
        @endforeach
    </tr>
</table>
