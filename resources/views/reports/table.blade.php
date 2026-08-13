@extends('layouts.app')
@section('title', $reportTitle)
@section('content')
    <x-page-header title="{{ $reportTitle }}" description="Laporan operasional SIMBA sesuai ruang lingkup akses akun." :breadcrumb="['Laporan', $reportTitle]">
        <div class="flex flex-wrap gap-2">
            @if (request()->routeIs('reports.stock'))
                <x-button href="{{ route('reports.stock.excel', request()->query()) }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Excel</x-button>
                <x-button href="{{ route('reports.stock.pdf', request()->query()) }}" variant="soft-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</x-button>
            @elseif (request()->routeIs('reports.loans'))
                <x-button href="{{ route('reports.loans.excel', request()->query()) }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Excel</x-button>
                <x-button href="{{ route('reports.loans.pdf', request()->query()) }}" variant="soft-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</x-button>
            @elseif (request()->routeIs('reports.damages'))
                <x-button href="{{ route('reports.damages.excel', request()->query()) }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Excel</x-button>
                <x-button href="{{ route('reports.damages.pdf', request()->query()) }}" variant="soft-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</x-button>
            @elseif (request()->routeIs('reports.stock-movements'))
                <x-button href="{{ route('reports.stock-movements.excel', request()->query()) }}" variant="soft-success"><i class="bi bi-file-earmark-excel"></i> Excel</x-button>
                <x-button href="{{ route('reports.stock-movements.pdf', request()->query()) }}" variant="soft-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</x-button>
            @endif
        </div>
    </x-page-header>
    <section class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ url()->current() }}" class="p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="bi bi-search"></i></span>
                    <input name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cari laporan...">
                </div>
                <x-button type="submit" variant="primary"><i class="bi bi-funnel"></i> Filter</x-button>
                <x-button href="{{ url()->current() }}" variant="secondary">Reset</x-button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">{{ $reportTitle }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ method_exists($rows, 'total') ? $rows->total() : $rows->count() }} data ditemukan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                    @if ($reportType === 'low_stock')
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Barang</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kode</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Jenis</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Jurusan</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Lokasi</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Stok</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Minimum</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Kurang</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    @elseif ($reportType === 'stock_movements')
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tanggal</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kode</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nama Barang</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Bengkel</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Jenis</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Referensi</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Sumber/Tujuan</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Sebelum</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Perubahan</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Sesudah</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Satuan</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Petugas</th>
                    @elseif ($reportType === 'loans')
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kode</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Peminjam</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tgl Pengajuan</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Batas Kembali</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Jml Alat</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Alat</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Keperluan</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tgl Kembali</th>
                    @else
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kode</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nama Alat</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Bengkel</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Waktu Laporan</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tingkat</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Pelapor</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Petugas</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Deskripsi</th>
                    @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)                    @if ($reportType === 'low_stock')
                        @php $st=(float)($row->report_stock ?? $row->stock ?? 0); $min=(float)($row->minimum_stock ?? 0); $kur=max(0,$min-$st); $stL=$st<=0?'Habis':($st<$min?'Kritis':($st<=$min?'Rendah':'Aman')); $stV=$st<=0?'danger':($st<=$min?'warning':'success'); @endphp
                        <tr class="hover:bg-slate-50"><td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row->name ?? '-' }}</td><td class="px-4 py-3 font-mono text-sm text-slate-600">{{ $row->code ?? '-' }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $row->type === 'tool' ? 'Alat' : 'Bahan' }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $row->report_workshop_code ?? $row->workshop_code ?? '-' }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $row->report_location_name ?? '-' }}</td><td class="px-4 py-3 text-right text-sm font-bold text-slate-900">{{ number_format($st,3,',','.') }}</td><td class="px-4 py-3 text-right text-sm text-slate-600">{{ number_format($min,3,',','.') }}</td><td class="px-4 py-3 text-right text-sm font-bold text-red-600">{{ number_format($kur,3,',','.') }}</td><td class="px-4 py-3"><x-badge variant="{{ $stV }}" dot>{{ $stL }}</x-badge></td></tr>
                    @elseif ($reportType === 'stock_movements')
                        @php $mvL=['incoming'=>'Masuk','outgoing'=>'Keluar','adjustment_in'=>'Penyesuaian Masuk','adjustment_out'=>'Penyesuaian Keluar','loan'=>'Peminjaman','return'=>'Pengembalian','initial'=>'Saldo Awal']; $mvT=$row->type??'-'; $mvLabel=$mvL[$mvT]??$mvT; $mvD=is_object($row)&&property_exists($row,'transaction_date')?($row->transaction_date?->format('d-m-Y H:i')??'-'):($row->transaction_date??'-'); $mvI=is_object($row)&&property_exists($row,'item')?($row->item?->name??'-'):($row->item_name??'-'); $mvC=is_object($row)&&property_exists($row,'item')?($row->item?->code??'-'):($row->item_code??'-'); $mvW=is_object($row)&&property_exists($row,'item')?($row->item?->workshop?->code??'-'):($row->report_workshop_code??'-'); $mvU=is_object($row)&&property_exists($row,'user')?($row->user?->name??'Sistem'):($row->user_name??'Sistem'); $mvQ=is_object($row)&&property_exists($row,'item')?($row->item?->unit?->code??''):($row->unit_code??''); $mvB=(float)($row->stock_before??0); $mvA=(float)($row->stock_after??0); $mvDf=$mvA-$mvB; @endphp
                        <tr class="hover:bg-slate-50"><td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $mvD }}</td><td class="px-4 py-3 font-mono text-sm text-slate-600">{{ $mvC }}</td><td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $mvI }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $mvW }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $mvLabel }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $row->reference_number ?? '-' }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $row->source ?? $row->destination ?? '-' }}</td><td class="px-4 py-3 text-right text-sm text-slate-600">{{ number_format($mvB,3,',','.') }}</td><td class="px-4 py-3 text-right text-sm font-bold {{ $mvDf>=0?'text-emerald-600':'text-red-600' }}">{{ $mvDf>=0?'+':'' }}{{ number_format($mvDf,3,',','.') }}</td><td class="px-4 py-3 text-right text-sm font-bold text-slate-900">{{ number_format($mvA,3,',','.') }}</td><td class="px-4 py-3 text-right text-sm text-slate-600">{{ $mvQ }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $mvU }}</td></tr>
                    @elseif ($reportType === 'loans')
                        @php $ls=['pending'=>'Menunggu','approved'=>'Disetujui','borrowed'=>'Sedang Dipinjam','partially_returned'=>'Sebagian Kembali','returned'=>'Dikembalikan','completed'=>'Selesai','rejected'=>'Ditolak','cancelled'=>'Dibatalkan']; $lSt=$ls[$row->status??'']??ucfirst(str_replace('_',' ',$row->status??'-')); $lReg=$row->request_date??null; $lDue=$row->due_at??null; $lRet=$row->returned_at??null; $lRegF=$lReg?date('d M Y', strtotime($lReg)):'-'; $lDueF=$lDue?date('d M Y, H:i', strtotime($lDue)):'-'; $lRetF=$lRet?date('d M Y, H:i', strtotime($lRet)):'Belum dikembalikan'; $lItems=$row->items??collect(); $lAlat=$lItems->take(2)->map(fn($li)=>$li->item?->code??'-')->join(', '); $lMore=max(0,$lItems->count()-2); $lVar=in_array($row->status??'',['returned','completed'])?'success':(in_array($row->status??'',['rejected','cancelled'])?'danger':'warning'); @endphp
                        <tr class="hover:bg-slate-50"><td class="px-4 py-3 font-mono text-sm font-semibold text-slate-900">{{ $row->code ?? '-' }}</td><td class="px-4 py-3 text-sm text-slate-700">{{ $row->borrower?->name ?? '-' }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $lRegF }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $lDueF }}</td><td class="px-4 py-3"><x-badge variant="{{ $lVar }}">{{ $lSt }}</x-badge></td><td class="px-4 py-3 text-right text-sm font-bold text-slate-900">{{ $lItems->count() }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $lAlat }}@if($lMore>0) <span class="text-xs text-slate-400">+{{$lMore}} lainnya</span>@endif</td><td class="px-4 py-3 text-sm text-slate-600">{{ $row->purpose ?? '-' }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $lRetF }}</td></tr>
                    @else
                        @php $dSev=['minor'=>'Rusak Ringan','moderate'=>'Sedang','major'=>'Rusak Berat','critical'=>'Kritis']; $dSevL=$dSev[$row->severity??'']??ucfirst(str_replace('_',' ',$row->severity??'-')); $dSt=method_exists($row,'statusLabel')?$row->statusLabel():ucfirst(str_replace('_',' ',$row->status??'-')); $dItem=$row->item?->name??'-'; $dCode=$row->item?->code??'-'; $dW=$row->item?->workshop?->code??'-'; $dT=is_object($row->reported_at)?$row->reported_at->format('d-m-Y H:i'):($row->reported_at??'-'); $dR=$row->reporter?->name??'Sistem'; $dH=$row->handler?->name??'-'; @endphp
                        <tr class="hover:bg-slate-50"><td class="px-4 py-3 text-sm text-slate-600">{{ $row->code ?? '-' }}</td><td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $dItem }}<div class="font-mono text-xs text-slate-500">{{ $dCode }}</div></td><td class="px-4 py-3 text-sm text-slate-600">{{ $dW }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $dT }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $dSevL }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $dSt }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $dR }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ $dH }}</td><td class="px-4 py-3 text-sm text-slate-600">{{ mb_substr($row->description ?? '-', 0, 60) }}</td></tr>
                    @endif
                    @empty
                        <tr><td colspan="9" class="px-5 py-10"><x-empty-state icon="bi-bar-chart-line" title="Belum ada data laporan" description="Tidak ada data yang sesuai dengan filter." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($rows, 'hasPages') && $rows->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </section>
@endsection