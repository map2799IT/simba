@php
    $document = $officialDocument;
@endphp

<table
    style="
        border-collapse:collapse;
        table-layout:fixed;
        width:100%;
    "
>
    <tr>
        @foreach (
            [
                $document['printedBy'],
                $document['toolman'],
                $document['head'],
            ]
            as $person
        )
            <td
                style="
                    border:0;
                    padding:0 8px;
                    text-align:center;
                    vertical-align:top;
                    width:33.333%;
                "
            >
                <div
                    style="
                        color:#475569;
                        font-size:7px;
                    "
                >
                    {{ $person['position'] }}
                </div>

                <div style="height:42px;"></div>

                <span
                    style="
                        border-top:1px solid #334155;
                        display:block;
                        font-size:7px;
                        font-weight:bold;
                        margin:0 auto;
                        max-width:180px;
                        padding-top:3px;
                    "
                >
                    {{ $person['name'] }}
                </span>

                @if ($person['identifier'])
                    <div
                        style="
                            color:#64748b;
                            font-size:6px;
                            margin-top:2px;
                        "
                    >
                        {{ $person['identifier'] }}
                    </div>
                @endif
            </td>
        @endforeach
    </tr>
</table>
