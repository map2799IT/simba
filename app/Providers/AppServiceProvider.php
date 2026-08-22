<?php

namespace App\Providers;

use App\Models\DamageReport;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStockMovement;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\StorageLocation;
use App\Models\Unit;
use App\Models\Workshop;
use App\Observers\AuditObserver;
use App\Observers\ItemObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * Hapus file hot Vite jika dev server tidak dapat dijangkau dari
         * browser. Jika hot file mengarah ke IPv6 loopback ([::1]) tetapi
         * APP_URL menggunakan IP jaringan, browser tidak bisa mengakses
         * asset Vite sehingga CSS/JS tidak tampil.
         */
        $hotFile = public_path('hot');
        if (file_exists($hotFile)) {
            $devUrl  = trim(file_get_contents($hotFile));
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            $devHost    = parse_url($devUrl, PHP_URL_HOST);
            $isLoopback = in_array($devHost, ['localhost', '127.0.0.1', '[::1]'], true)
                || $devHost === '::1';

            $appIsNetwork = $appHost && ! in_array($appHost, ['localhost', '127.0.0.1', '[::1]'], true)
                && filter_var($appHost, FILTER_VALIDATE_IP) !== false;

            if ($isLoopback && $appIsNetwork) {
                @unlink($hotFile);
            }
        }

        /*
         * Observer saldo awal barang.
         */
        Item::observe(ItemObserver::class);

        /*
         * Observer audit aktivitas.
         */
        $auditedModels = [
            Workshop::class,
            StorageLocation::class,
            ItemCategory::class,
            Unit::class,
            Item::class,
            ItemStockMovement::class,
            Loan::class,
            LoanItem::class,
            DamageReport::class,
        ];

        foreach ($auditedModels as $modelClass) {
            /** @var class-string<Model> $modelClass */
            $modelClass::observe(
                AuditObserver::class
            );
        }
    }
}