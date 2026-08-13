{{-- Reusable PDF Signature Section
     Props: $workshopId (optional), $workshopName (optional)
--}}
@php
    $school = config('school');
    $principal = $school['signatories']['principal'];
    $wakaSarpras = $school['signatories']['waka_sarpras'];

    // Cari Kabeng dari DB berdasarkan workshop_id
    $kabeng = null;
    if (!empty($workshopId)) {
        $kabengUser = \App\Models\User::query()
            ->withoutGlobalScopes()
            ->where('role', 'kepala_bengkel')
            ->where('workshop_id', $workshopId)
            ->where('is_active', true)
            ->first();

        if ($kabengUser) {
            $kabeng = [
                'name' => $kabengUser->name,
                'nip'  => $kabengUser->nip ?? '-',
            ];
        }
    }

    // Fallback ke config jika tidak ada di DB
    if (!$kabeng && !empty($workshopName)) {
        $kabeng = $school['workshop_heads'][strtoupper($workshopName)] ?? null;
    }

    $printDate = \Carbon\Carbon::now()->translatedFormat('d F Y');
    $printCity = 'Palembang';
@endphp

<div class="signature">
    <div style="text-align:right; margin-bottom:8px;">{{ $printCity }}, {{ $printDate }}</div>
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-title">Mengetahui,</div>
                <div class="signature-role">Kepala Sekolah</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $principal['name'] }}</div>
                <div class="signature-nip">NIP. {{ $principal['nip'] }}</div>
            </td>
            <td class="signature-cell">
                <div class="signature-title">Mengetahui,</div>
                <div class="signature-role">Waka Bid. Sarana & Prasarana</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $wakaSarpras['name'] }}</div>
                <div class="signature-nip">NIP. {{ $wakaSarpras['nip'] }}</div>
            </td>
            <td class="signature-cell">
                <div class="signature-title">Hormat Kami,</div>
                <div class="signature-role">Kepala Bengkel{{ $workshopName ? ' '.$workshopName : '' }}</div>
                <div class="signature-space"></div>
                @if ($kabeng)
                    <div class="signature-name">{{ $kabeng['name'] }}</div>
                    <div class="signature-nip">NIP. {{ $kabeng['nip'] }}</div>
                @else
                    <div class="signature-name">..................................</div>
                    <div class="signature-nip">NIP. .........................</div>
                @endif
            </td>
        </tr>
    </table>
</div>
