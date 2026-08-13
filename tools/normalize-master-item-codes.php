<?php

declare(strict_types=1);

use App\Models\Item;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

if (PHP_SAPI !== 'cli') {
    exit("Terminal only.\n");
}

echo "NORMALISASI KODE MASTER TANPA TAHUN\n";
echo "===================================\n\n";
echo "Format baru: ALT-0001 dan BHN-0001.\n";
echo "Nomor inventaris unit alat tidak diubah.\n\n";
echo "Ketik: UBAH KODE MASTER\n";
echo "> ";

if (trim((string) fgets(STDIN)) !== 'UBAH KODE MASTER') {
    echo "Dibatalkan.\n";
    exit(1);
}

$items = Item::query()
    ->withoutGlobalScopes()
    ->orderBy('type')
    ->orderBy('id')
    ->get([
        'id',
        'type',
        'code',
    ]);

$backupName = 'master-code-backup-'.
    now()->format('Ymd-His').
    '.csv';

$csv = "id,type,old_code,new_code\n";
$newCodes = [];

$counters = [
    'tool' => 0,
    'material' => 0,
];

foreach ($items as $item) {
    if (! array_key_exists($item->type, $counters)) {
        continue;
    }

    $counters[$item->type]++;

    $prefix = $item->type === 'tool'
        ? 'ALT'
        : 'BHN';

    $newCode = $prefix.'-'.str_pad(
        (string) $counters[$item->type],
        4,
        '0',
        STR_PAD_LEFT
    );

    $newCodes[$item->id] = $newCode;

    $csv .= implode(',', [
        $item->id,
        $item->type,
        '"'.str_replace(
            '"',
            '""',
            (string) $item->code
        ).'"',
        $newCode,
    ])."\n";
}

Storage::disk('local')->put(
    $backupName,
    $csv
);

DB::transaction(
    function () use ($items, $newCodes): void {
        foreach ($items as $item) {
            if (! isset($newCodes[$item->id])) {
                continue;
            }

            DB::table('items')
                ->where('id', $item->id)
                ->update([
                    'code' =>
                        'TMP-MASTER-'.
                        $item->id.
                        '-'.
                        uniqid(),
                ]);
        }

        foreach ($items as $item) {
            if (! isset($newCodes[$item->id])) {
                continue;
            }

            DB::table('items')
                ->where('id', $item->id)
                ->update([
                    'code' => $newCodes[$item->id],
                ]);
        }
    },
    attempts: 3
);

echo "\nKODE MASTER BERHASIL DINORMALISASI.\n";
echo "Backup mapping: storage/app/{$backupName}\n";
