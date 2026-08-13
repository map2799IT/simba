<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'item_stock_movements',
            function (Blueprint $table): void {
                $table->string(
                    'destination',
                    150
                )
                    ->nullable()
                    ->after('source');

                $table->string(
                    'purpose',
                    255
                )
                    ->nullable()
                    ->after('destination');

                $table->index('destination');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'item_stock_movements',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'destination',
                ]);

                $table->dropColumn([
                    'destination',
                    'purpose',
                ]);
            }
        );
    }
};