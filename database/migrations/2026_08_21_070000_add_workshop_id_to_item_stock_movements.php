<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('item_stock_movements', 'workshop_id')) {
            Schema::table('item_stock_movements', function (Blueprint $table): void {
                $table->foreignId('workshop_id')
                    ->nullable()
                    ->after('item_id')
                    ->constrained('workshops')
                    ->nullOnDelete();
                
                $table->index(['workshop_id', 'transaction_date']);
            });
            
            // Populate workshop_id from items table
            \Illuminate\Support\Facades\DB::statement("
                UPDATE item_stock_movements ism
                INNER JOIN items i ON i.id = ism.item_id
                SET ism.workshop_id = i.workshop_id
                WHERE ism.workshop_id IS NULL
                  AND i.workshop_id IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('item_stock_movements', 'workshop_id')) {
            Schema::table('item_stock_movements', function (Blueprint $table): void {
                $table->dropForeign(['workshop_id']);
                $table->dropColumn('workshop_id');
            });
        }
    }
};
