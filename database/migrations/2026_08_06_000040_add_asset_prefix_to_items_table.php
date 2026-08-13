<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('items')
            || Schema::hasColumn(
                'items',
                'asset_prefix'
            )
        ) {
            return;
        }

        Schema::table(
            'items',
            function (
                Blueprint $table
            ): void {
                $table
                    ->string(
                        'asset_prefix',
                        24
                    )
                    ->nullable()
                    ->after('code')
                    ->index();
            }
        );
    }

    public function down(): void
    {
        if (
            Schema::hasTable('items')
            && Schema::hasColumn(
                'items',
                'asset_prefix'
            )
        ) {
            Schema::table(
                'items',
                function (
                    Blueprint $table
                ): void {
                    $table->dropColumn(
                        'asset_prefix'
                    );
                }
            );
        }
    }
};
