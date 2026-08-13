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