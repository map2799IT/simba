<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loans')) {
            $addWorkshop = ! Schema::hasColumn('loans', 'workshop_id');
            $addToolman = ! Schema::hasColumn('loans', 'assigned_toolman_id');

            if ($addWorkshop || $addToolman) {
                Schema::table('loans', function (Blueprint $table) use ($addWorkshop, $addToolman): void {
                    if ($addWorkshop) {
                        $table->foreignId('workshop_id')->nullable()
                            ->after('borrower_id')->constrained('workshops')->nullOnDelete();
                    }

                    if ($addToolman) {
                        $table->foreignId('assigned_toolman_id')->nullable()
                            ->after('workshop_id')->constrained('users')->nullOnDelete();
                    }
                });
            }
        }

        if (! Schema::hasTable('loan_items')) {
            return;
        }

        if (! $this->indexExists('loan_items', 'loan_items_loan_id_index')) {
            Schema::table('loan_items', function (Blueprint $table): void {
                $table->index('loan_id', 'loan_items_loan_id_index');
            });
        }

        if ($this->indexExists('loan_items', 'loan_items_loan_id_item_id_unique')) {
            Schema::table('loan_items', function (Blueprint $table): void {
                $table->dropUnique('loan_items_loan_id_item_id_unique');
            });
        }

        $add = [
            'item_asset_id' => ! Schema::hasColumn('loan_items', 'item_asset_id'),
            'workshop_id' => ! Schema::hasColumn('loan_items', 'workshop_id'),
            'quantity' => ! Schema::hasColumn('loan_items', 'quantity'),
            'is_consumable' => ! Schema::hasColumn('loan_items', 'is_consumable'),
            'issued_at' => ! Schema::hasColumn('loan_items', 'issued_at'),
            'stock_movement_id' => ! Schema::hasColumn('loan_items', 'stock_movement_id'),
            'returned_quantity' => ! Schema::hasColumn('loan_items', 'returned_quantity'),
            'return_condition' => ! Schema::hasColumn('loan_items', 'return_condition'),
            'return_status' => ! Schema::hasColumn('loan_items', 'return_status'),
        ];

        if (in_array(true, $add, true)) {
            Schema::table('loan_items', function (Blueprint $table) use ($add): void {
                if ($add['item_asset_id']) {
                    $table->foreignId('item_asset_id')->nullable()
                        ->after('item_id')->constrained('item_assets')
                        ->restrictOnUpdate()->restrictOnDelete();
                }

                if ($add['workshop_id']) {
                    $table->foreignId('workshop_id')->nullable()
                        ->after('item_asset_id')->constrained('workshops')->nullOnDelete();
                }

                if ($add['quantity']) {
                    $table->decimal('quantity', 14, 3)->default(1)->after('workshop_id');
                }

                if ($add['is_consumable']) {
                    $table->boolean('is_consumable')->default(false)->after('quantity');
                }

                if ($add['issued_at']) {
                    $table->dateTime('issued_at')->nullable()->after('condition_out');
                }

                if ($add['stock_movement_id']) {
                    $table->foreignId('stock_movement_id')->nullable()
                        ->after('issued_at')->constrained('item_stock_movements')->nullOnDelete();
                }

                if ($add['returned_quantity']) {
                    $table->decimal('returned_quantity', 14, 3)->default(0);
                }

                if ($add['return_condition']) {
                    $table->string('return_condition', 50)->nullable();
                }

                if ($add['return_status']) {
                    $table->string('return_status', 30)->nullable();
                }
            });
        }

        if (! $this->indexExists('loan_items', 'loan_items_loan_id_item_id_index')) {
            Schema::table('loan_items', function (Blueprint $table): void {
                $table->index(['loan_id', 'item_id'], 'loan_items_loan_id_item_id_index');
            });
        }

        if (! $this->indexExists('loan_items', 'loan_items_loan_id_asset_id_unique')) {
            Schema::table('loan_items', function (Blueprint $table): void {
                $table->unique(['loan_id', 'item_asset_id'], 'loan_items_loan_id_asset_id_unique');
            });
        }

        if (! $this->indexExists('loan_items', 'loan_items_workshop_return_index')) {
            Schema::table('loan_items', function (Blueprint $table): void {
                $table->index(['workshop_id', 'returned_at'], 'loan_items_workshop_return_index');
            });
        }

        DB::table('loan_items')->whereNull('quantity')->update(['quantity' => 1]);

        if (Schema::hasTable('items')) {
            DB::table('loan_items')
                ->join('items', 'items.id', '=', 'loan_items.item_id')
                ->where('items.type', 'material')
                ->update(['loan_items.is_consumable' => true]);
        }

        if (Schema::hasColumn('loan_items', 'workshop_id')) {
            DB::statement('
                UPDATE loan_items li
                INNER JOIN loans l ON l.id = li.loan_id
                SET li.workshop_id = l.workshop_id
                WHERE li.workshop_id IS NULL AND l.workshop_id IS NOT NULL
            ');

            if (Schema::hasColumn('loan_items', 'item_asset_id') && Schema::hasTable('item_assets')) {
                DB::statement('
                    UPDATE loan_items li
                    INNER JOIN item_assets ia ON ia.id = li.item_asset_id
                    SET li.workshop_id = ia.workshop_id
                    WHERE li.workshop_id IS NULL
                ');
            }
        }
    }

    public function down(): void
    {
        // Data transaksi tidak dihapus saat rollback demi menjaga riwayat.
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
