<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';

$app->make(Kernel::class)
    ->bootstrap();

$failed = false;

echo "SIMBA STOCK RECEIPT PHOTO VIEW CHECK\n";
echo "====================================\n\n";

$checks = [
    'Controller foto tersedia' =>
        class_exists(
            \App\Http\Controllers\StockReceiptPhotoController::class
        ),

    'Route foto aktif' =>
        Route::has(
            'stock-receipts.photo.active'
        ),

    'Route foto usulan' =>
        Route::has(
            'stock-receipts.photo.proposed'
        ),

    'View index' =>
        View::exists(
            'stock-receipts.index'
        ),

    'View detail' =>
        View::exists(
            'stock-receipts.show'
        ),

    'View edit' =>
        View::exists(
            'stock-receipts.edit'
        ),

    'View approval' =>
        View::exists(
            'stock-receipts.approvals'
        ),

    'Partial foto' =>
        View::exists(
            'stock-receipts._photo-card'
        ),
];

foreach ($checks as $label => $valid) {
    echo str_pad($label, 42).
        ': '.
        ($valid ? 'OK' : 'GAGAL').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$routeChecks = [
    'stock-receipts.photo.active' =>
        'StockReceiptPhotoController@active',

    'stock-receipts.photo.proposed' =>
        'StockReceiptPhotoController@proposed',
];

echo "\nROUTE ACTION\n";
echo "------------\n";

foreach (
    $routeChecks
    as $name => $expected
) {
    $route =
        Route::getRoutes()
            ->getByName($name);

    $action =
        $route?->getActionName();

    $valid =
        is_string($action)
        && str_contains(
            $action,
            $expected
        );

    echo str_pad($name, 42).
        ': '.
        ($valid ? 'OK ' : 'GAGAL ').
        ($action ?? '-').
        PHP_EOL;

    if (! $valid) {
        $failed = true;
    }
}

$disk =
    Storage::disk('public');

$activeRows =
    DB::table('item_stock_movements')
        ->where('type', 'incoming')
        ->whereNotNull('photo_path')
        ->where('photo_path', '!=', '')
        ->get([
            'id',
            'receipt_code',
            'photo_path',
        ]);

$activeExisting =
    $activeRows->filter(
        fn (object $row): bool =>
            $disk->exists(
                $row->photo_path
            )
    );

$activeMissing =
    $activeRows->reject(
        fn (object $row): bool =>
            $disk->exists(
                $row->photo_path
            )
    );

$proposedRows =
    DB::table(
        'stock_receipt_change_requests'
    )
        ->where(
            'status',
            'pending'
        )
        ->get([
            'id',
            'requested_payload',
        ])
        ->filter(
            function (
                object $row
            ): bool {
                $payload =
                    json_decode(
                        (string)
                        $row->requested_payload,
                        true
                    );

                return is_array($payload)
                    && ! empty(
                        $payload[
                            'replace_photo'
                        ]
                    )
                    && ! empty(
                        $payload[
                            'photo_path'
                        ]
                    );
            }
        );

$proposedExisting =
    $proposedRows->filter(
        function (
            object $row
        ) use (
            $disk
        ): bool {
            $payload =
                json_decode(
                    (string)
                    $row->requested_payload,
                    true
                );

            return $disk->exists(
                $payload[
                    'photo_path'
                ]
            );
        }
    );

echo "\nFILE FOTO\n";
echo "---------\n";
echo str_pad('Barang Masuk dengan path foto', 42).
    ': '.
    $activeRows->count().
    PHP_EOL;

echo str_pad('File foto aktif ditemukan', 42).
    ': '.
    $activeExisting->count().
    PHP_EOL;

echo str_pad('File foto aktif hilang', 42).
    ': '.
    $activeMissing->count().
    PHP_EOL;

echo str_pad('Foto usulan pending ditemukan', 42).
    ': '.
    $proposedExisting->count().
    PHP_EOL;

if ($activeMissing->isNotEmpty()) {
    echo "\nPATH FOTO HILANG\n";
    echo "----------------\n";

    foreach ($activeMissing as $row) {
        echo '#'.
            $row->id.
            ' '.
            (
                $row->receipt_code
                ?: '-'
            ).
            ' => '.
            $row->photo_path.
            PHP_EOL;
    }

    $failed = true;
}

echo "\nHAK AKSES\n";
echo "---------\n";
echo "Admin           : seluruh foto\n";
echo "Toolman         : foto jurusannya\n";
echo "Kepala Bengkel  : foto jurusannya\n";
echo "Pengunjung      : ditolak oleh middleware auth\n";

echo "\n".
    (
        $failed
            ? 'STOCK RECEIPT PHOTO VIEW BELUM VALID.'
            : 'STOCK RECEIPT PHOTO VIEW SUDAH VALID.'
    ).
    PHP_EOL;

exit($failed ? 1 : 0);
