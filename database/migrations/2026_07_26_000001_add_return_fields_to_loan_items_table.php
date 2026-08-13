<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_items')) {
            return;
        }

        Schema::table('loan_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('loan_items', 'returned_quantity')) {
                $table
                    ->decimal('returned_quantity', 12, 3)
                    ->default(0);
            }

            if (! Schema::hasColumn('loan_items', 'return_condition')) {
                $table
                    ->string('return_condition', 50)
                    ->nullable();
            }

            if (! Schema::hasColumn('loan_items', 'return_notes')) {
                $table
                    ->text('return_notes')
                    ->nullable();
            }

            if (! Schema::hasColumn('loan_items', 'return_status')) {
                $table
                    ->string('return_status', 30)
                    ->nullable();
            }

            if (! Schema::hasColumn('loan_items', 'returned_at')) {
                $table
                    ->timestamp('returned_at')
                    ->nullable();
            }

            if (! Schema::hasColumn('loan_items', 'returned_by')) {
                $table
                    ->unsignedBigInteger('returned_by')
                    ->nullable();

                $table->index('returned_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('loan_items')) {
            return;
        }

        Schema::table('loan_items', function (Blueprint $table): void {
            $columns = [
                'returned_quantity',
                'return_condition',
                'return_notes',
                'return_status',
                'returned_at',
                'returned_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('loan_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
