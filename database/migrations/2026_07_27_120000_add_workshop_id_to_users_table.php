<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('users')
            || Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return;
        }

        Schema::table(
            'users',
            function (
                Blueprint $table
            ): void {
                $table
                    ->foreignId(
                        'workshop_id'
                    )
                    ->nullable()
                    ->after('role')
                    ->constrained(
                        'workshops'
                    )
                    ->nullOnDelete();

                $table->index([
                    'role',
                    'workshop_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            return;
        }

        Schema::table(
            'users',
            function (
                Blueprint $table
            ): void {
                $table->dropForeign([
                    'workshop_id',
                ]);

                $table->dropIndex([
                    'role',
                    'workshop_id',
                ]);

                $table->dropColumn(
                    'workshop_id'
                );
            }
        );
    }
};
