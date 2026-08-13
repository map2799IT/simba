<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockReceiptRequest;
use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\Workshop;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockImportController extends Controller
{
    private function role(Request $r): string
    {
        return (string) $r->user()?->role;
    }

    private function authorizeAccess(Request $r): void
    {
        abort_unless(in_array((string) $r->user()?->role, ['admin', 'toolman', 'kepala_bengkel'], true), 403);
    }

    public function create(Request $r): View
    {
        $this->authorizeAccess($r);
        return view('stock-transactions.import', [
            'isAdmin' => $this->role($r) === 'admin',
            'isKepalaBengkel' => $this->role($r) === 'kepala_bengkel',
            'workshops' => Workshop::query()->withoutGlobalScopes()->where('is_active', true)->orderBy('code')->get(['id','code','name']),
        ]);
    }

    public function template(Request $r): StreamedResponse
    {
        $this->authorizeAccess($r);
        $isAdmin = $this->role($r) === 'admin';
        $headers = $isAdmin
            ? ['nomor_dokumen','tanggal','bengkel','kode_barang','nama_barang_penerimaan','jumlah','satuan','merek','model','spesifikasi','harga_unit','sumber_perolehan','sumber_dana','kondisi','lokasi']
            : ['nomor_dokumen','tanggal','kode_barang','nama_barang_penerimaan','jumlah','satuan','merek','model','spesifikasi','harga_unit','sumber_perolehan','sumber_dana','kondisi','lokasi'];
        $ex = $isAdmin
            ? ['BM-2026-001',date('Y-m-d'),'TKJ','ALT-2026-0001','Monitor Dell 24 inch',2,'unit','Dell','SE2419H','24 inch',1500000,'Dana BOS','BOS','good','Ruang Toolman']
            : ['BM-2026-001',date('Y-m-d'),'ALT-2026-0001','Monitor Dell 24 inch',2,'unit','Dell','SE2419H','24 inch',1500000,'Dana BOS','BOS','good','Ruang Toolman'];
        $sheet = new Spreadsheet();
        $s = $sheet->getActiveSheet();
        $s->setTitle('TEMPLATE IMPORT');
        $s->fromArray($headers, null, 'A1');
        $s->fromArray($ex, null, 'A2');
        $s->getStyle('A1:O1')->getFont()->setBold(true);
        $guide = $sheet->createSheet();
        $guide->setTitle('PANDUAN');
        $guide->setCellValue('A1', 'PANDUAN IMPORT BARANG MASUK');
        $guide->setCellValue('A3', 'Baris 2 contoh. Mulai isi baris 3. Foto TIDAK di Excel: setelah import buka Edit Data lalu upload foto.');
        $guide->setCellValue('A4', 'Kode/nama barang harus ada di master. Toolman: bengkel dari akun.');
        $guide->setCellValue('A6', 'Gunakan sheet REFERENSI untuk mengisi kode_barang, satuan, dan lokasi yang valid.');

        // Sheet 3: REFERENSI (master barang + lokasi per jurusan)
        $ref = $sheet->createSheet();
        $ref->setTitle('REFERENSI');

        $scopedWid = $isAdmin ? null : $r->user()?->workshop_id;

        // Bagian 1: Master Barang
        $ref->setCellValue('A1', 'MASTER BARANG (untuk kolom kode_barang / nama_barang_penerimaan)');
        $ref->getStyle('A1:E1')->getFont()->setBold(true);
        $ref->fromArray(['kode_barang','nama_barang','jenis','kategori','satuan'], null, 'A2');
        $ref->getStyle('A2:E2')->getFont()->setBold(true);
        $rowIdx = 3;
        foreach (\App\Models\Item::query()->withoutGlobalScopes()->where('is_active', true)->with(['category','unit'])->orderBy('code')->get(['id','code','name','type','item_category_id','unit_id']) as $item) {
            $ref->fromArray([
                $item->code,
                $item->name,
                $item->type === 'tool' ? 'Alat' : 'Bahan',
                $item->category?->name ?? '-',
                $item->unit?->name ?? '-',
            ], null, 'A'.$rowIdx);
            $rowIdx++;
        }

        $locStart = $rowIdx + 1;
        // Bagian 2: Lokasi per jurusan
        $ref->setCellValue('A'.$locStart, 'LOKASI PENYIMPANAN (untuk kolom lokasi)');
        $ref->getStyle('A'.$locStart.':C'.$locStart)->getFont()->setBold(true);
        $locStart++;
        $ref->fromArray(['bengkel','nama_lokasi','kode_lokasi'], null, 'A'.$locStart);
        $ref->getStyle('A'.$locStart.':C'.$locStart)->getFont()->setBold(true);
        $locStart++;
        $refLocations = \App\Models\StorageLocation::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when($scopedWid, fn ($q) => $q->where('workshop_id', $scopedWid))
            ->with(['workshop', 'parent'])
            ->orderBy('workshop_id')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get(['id', 'workshop_id', 'parent_id', 'name', 'code']);
        foreach ($refLocations as $loc) {
            $label = $loc->parent !== null
                ? '[Turunan] ' . $loc->parent->name . ' ('.$loc->parent->code.') > ' . $loc->name
                : '[Induk] ' . $loc->name;
            $ref->fromArray([
                $loc->workshop?->code ?? '-',
                $label,
                $loc->code,
            ], null, 'A'.$locStart);
            $locStart++;
        }

        $writer = new Xlsx($sheet);
        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'template-import-barang-masuk.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->authorizeAccess($r);
        $isAdmin = $this->role($r) === 'admin';
        $r->validate(['file' => ['required', 'file', 'max:10240', 'mimes:xlsx,csv']]);
        $file = $r->file('file');
        $rows = $this->readRows($file->getRealPath());
        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi data. Mulai isi dari baris 3.']);
        }
        $user = $r->user();
        $wid = $isAdmin ? $r->input('workshop_id') : $user?->workshop_id;
        $ws = Workshop::query()->withoutGlobalScopes()->find($wid);
        if ($ws === null) {
            throw ValidationException::withMessages(['workshop_id' => 'Jurusan wajib dipilih.']);
        }
        $defaultLoc = StorageLocation::query()->withoutGlobalScopes()->where('workshop_id', $ws->id)->where('is_active', true)->orderBy('id')->value('id');
        $docs = $this->groupByDocument($rows, (int) $ws->id);
        $errors = [];
        $created = 0;
        $details = 0;
        $existingRefs = \App\Models\ItemStockMovement::query()
            ->withoutGlobalScopes()
            ->where('workshop_id', $ws->id)
            ->whereNotNull('reference_number')
            ->whereIn('reference_number', array_column($docs, 'document_number'))
            ->pluck('reference_number')
            ->all();

        foreach ($docs as $doc) {
            try {
                if (in_array($doc['document_number'], $existingRefs, true)) {
                    $errors[] = 'Dokumen ' . ($doc['document_number'] ?? '-') . ': sudah diimport sebelumnya, dilewati.';
                    continue;
                }
                $payload = $this->buildPayload($doc, (int) $ws->id, $defaultLoc, (string) $ws->code);
                $this->submitViaManualFlow($payload, $user, $r);
                $created++;
                $details += count($doc['items']);
            } catch (\Throwable $e) {
                $errors[] = 'Dokumen ' . ($doc['document_number'] ?? '-') . ': ' . $e->getMessage();
            }
        }
        if ($created > 0) {
            $msg = "{$details} detail Barang Masuk dari {$created} transaksi berhasil diimport. Foto dapat dilengkapi lewat Edit Data.";
            if (! empty($errors)) {
                return redirect()->route('stock-receipts.index')->with('warning', $msg . ' ' . count($errors) . ' gagal.');
            }
            return redirect()->route('stock-receipts.index')->with('success', $msg);
        }
        throw ValidationException::withMessages(['file' => implode(' | ', array_slice($errors, 0, 10)) ?: 'Tidak ada data valid.']);
    }

    private function groupByDocument(array $rows, int $wid): array
    {
        $docs = [];
        foreach ($rows as $row) {
            $doc = strtoupper(trim((string) ($row['nomor_dokumen'] ?? '')));
            if ($doc === '' || $doc === 'BM-2026-001') {
                $doc = 'BM-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
            }
            $key = $wid . '|' . $doc;
            if (! isset($docs[$key])) {
                $docs[$key] = ['document_number' => $doc, 'receipt_date' => $row['tanggal'] ?? date('Y-m-d'), 'source' => $row['sumber_perolehan'] ?? null, 'fund_source' => $row['sumber_dana'] ?? null, 'items' => []];
            }
            $docs[$key]['items'][] = $row;
        }
        return array_values($docs);
    }

    private function buildPayload(array $doc, int $wid, ?int $defaultLoc, string $wcode): array
    {
        $items = [];
        foreach ($doc['items'] as $row) {
            $itemId = $this->resolveItemId($row);
            if ($itemId === null) {
                throw new \RuntimeException('Master barang tidak ditemukan: ' . ($row['kode_barang'] ?? $row['nama_barang_penerimaan'] ?? '-'));
            }
            $rawQty = trim((string) ($row['jumlah'] ?? ''));
            $rawPrice = trim((string) ($row['harga_unit'] ?? ''));
            $qty = (float) str_replace(',', '.', $rawQty);
            if ($qty <= 0) {
                throw new \RuntimeException('Jumlah harus > 0.');
            }
            $unitPrice = $rawPrice !== ''
                ? (string) str_replace(',', '.', $rawPrice)
                : null;
            $loc = $this->resolveLocation($row, $wid) ?? $defaultLoc;
            if ($loc === null) {
                throw new \RuntimeException("Tidak ada lokasi untuk jurusan $wcode.");
            }

            $condition = strtolower(trim((string) ($row['kondisi'] ?? 'good')));
            $condition = match ($condition) {
                'baik' => 'good',
                'rusak ringan' => 'minor_damage',
                'rusak berat' => 'major_damage',
                'dalam perawatan' => 'maintenance',
                'tidak layak pakai' => 'unfit',
                default => $condition,
            };

            if (! array_key_exists($condition, \App\Models\Item::conditionOptions())) {
                $condition = 'good';
            }

            $items[] = [
                'item_id' => $itemId,
                'quantity' => $qty,
                'storage_location_id' => $loc,
                'brand' => $row['merek'] ?? null,
                'model' => $row['model'] ?? null,
                'specification' => $row['spesifikasi'] ?? null,
                'unit_price' => $unitPrice,
                'condition' => $condition,
                'notes' => null,
            ];
        }
        return [
            'workshop_id' => $wid,
            'receipt_date' => $doc['receipt_date'] ?? date('Y-m-d'),
            'document_number' => $doc['document_number'],
            'source' => $doc['source'] ?? null,
            'fund_source' => $doc['fund_source'] ?? null,
            'notes' => null,
            'items' => $items,
        ];
    }

    private function submitViaManualFlow(array $payload, $user, Request $r): void
    {
        $synth = Request::create('/', 'POST', $payload);
        $synth->setUserResolver(fn () => $user);
        if ($r->session()) {
            $synth->setLaravelSession($r->session());
        }
        $fr = StoreStockReceiptRequest::createFrom($synth);
        $fr->setContainer(app());
        $fr->setRedirector(app('redirect'));

        // Jalankan validasi + prepareForValidation secara eksplisit
        // agar ->validated() terisi (sama seperti alur action injection manual).
        $fr->validateResolved();

        app(StockReceiptController::class)->store(
            $fr,
            app(\App\Services\BulkItemAssetService::class),
            app(\App\Services\StockReceiptCodeService::class)
        );
    }

    private function readRows(string $path): array
    {
        try {
            $io = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $rows = $io->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'File tidak terbaca: ' . $e->getMessage()]);
        }
        if (empty($rows)) {
            return [];
        }
        $first = array_values($rows[0] ?? []);
        if (isset($first[0]) && is_string($first[0]) && str_starts_with($first[0], "\xEF\xBB\xBF")) {
            $first[0] = substr($first[0], 3);
        }
        $header = array_map('strtolower', array_map('trim', $first));
        $map = array_flip($header);
        $data = [];
        foreach (array_slice($rows, 2) as $idx => $row) {
            $line = [];
            foreach ($map as $head => $col) {
                $line[$head] = trim((string) ($row[$col] ?? ''));
            }
            if (implode('', $line) === '') {
                continue;
            }
            $data[] = $line;
        }
        return $data;
    }

    private function resolveItemId(array $row): ?int
    {
        $code = trim((string) ($row['kode_barang'] ?? ''));
        $name = trim((string) ($row['nama_barang_penerimaan'] ?? ''));
        $q = Item::query()->withoutGlobalScopes()->where('is_active', true);
        if ($code !== '' && ($it = (clone $q)->where('code', $code)->first())) {
            return (int) $it->id;
        }
        if ($name !== '' && ($it = (clone $q)->where('name', $name)->first())) {
            return (int) $it->id;
        }
        return null;
    }

    private function resolveLocation(array $row, int $wid): ?int
    {
        $name = trim((string) ($row['lokasi'] ?? ''));

        if ($name === '') {
            return null;
        }

        /*
         * Bersihkan dekorator dari sheet REFERENSI:
         * "[Induk] Nama" -> Nama
         * "[Turunan] Induk (KODE) > Nama" -> Nama turunan
         */
        $clean = preg_replace('/^\[.*?\]\s*/', '', $name);
        $clean = preg_replace('/^.*>\s*/', '', $clean);
        $clean = trim((string) $clean);

        $q = StorageLocation::query()->withoutGlobalScopes()->where('workshop_id', $wid)->where('is_active', true);

        foreach (array_unique([$name, $clean]) as $candidate) {
            if ($candidate === '') {
                continue;
            }
            if ($loc = (clone $q)->where('name', $candidate)->first()) {
                return (int) $loc->id;
            }
            if ($loc = (clone $q)->where('code', $candidate)->first()) {
                return (int) $loc->id;
            }
        }

        throw new \RuntimeException("Lokasi tidak ditemukan: {$name}");
    }

    public function reference(Request $r): View
    {
        $this->authorizeAccess($r);
        $isAdmin = $this->role($r) === 'admin';
        $wid = $isAdmin ? ($r->input('workshop_id') ?: null) : $r->user()?->workshop_id;
        $scoped = ! $isAdmin;

        /*
         * Master barang adalah katalog umum (tidak per-jurusan):
         * kode_barang/name harus cocok master global saat import.
         */
        $items = \App\Models\Item::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->with(['category', 'unit'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'item_category_id', 'unit_id']);

        $workshops = \App\Models\Workshop::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when($scoped, fn ($q) => $q->whereKey($wid))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $locations = \App\Models\StorageLocation::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when($wid, fn ($q) => $q->where('workshop_id', $wid))
            ->with('parent')
            ->orderBy('workshop_id')
            ->orderBy('name')
            ->get(['id', 'workshop_id', 'parent_id', 'code', 'name']);

        $locationTree = [];
        foreach ($locations as $loc) {
            $workshopId = (int) $loc->workshop_id;
            $locationTree[$workshopId]['locations'][$loc->id] = [
                'name' => $loc->name,
                'code' => $loc->code,
            ];
            $parentId = $loc->parent_id;
            if ($loc->parent !== null) {
                $key = (int) $parentId;
                $locationTree[$workshopId]['roots'][$key][] = (int) $loc->id;
            } else {
                $locationTree[$workshopId]['roots'][0][] = (int) $loc->id;
            }
        }

        return view('stock-transactions.reference', [
            'items' => $items,
            'workshops' => $workshops,
            'locationTree' => $locationTree,
            'isAdmin' => $isAdmin,
            'selectedWorkshopId' => $wid,
        ]);
    }
}
