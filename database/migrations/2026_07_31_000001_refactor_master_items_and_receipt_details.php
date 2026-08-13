<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('items')
            && Schema::hasColumn('items', 'workshop_id')
        ) {
            DB::statement(
                'ALTER TABLE `items` MODIFY `workshop_id` BIGINT UNSIGNED NULL'
            );
        }

        if (Schema::hasTable('item_stock_movements')) {
            Schema::table(
                'item_stock_movements',
                function (Blueprint $table): void {
                    if (! Schema::hasColumn('item_stock_movements', 'receipt_code')) {
                        $table->string('receipt_code', 100)
                            ->nullable()
                            ->unique()
                            ->after('id');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'workshop_id')) {
                        $table->unsignedBigInteger('workshop_id')
                            ->nullable()
                            ->index()
                            ->after('user_id');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'storage_location_id')) {
                        $table->unsignedBigInteger('storage_location_id')
                            ->nullable()
                            ->index()
                            ->after('workshop_id');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'brand')) {
                        $table->string('brand', 100)
                            ->nullable()
                            ->after('source');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'model')) {
                        $table->string('model', 100)
                            ->nullable()
                            ->after('brand');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'specification')) {
                        $table->text('specification')
                            ->nullable()
                            ->after('model');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'fund_source')) {
                        $table->string('fund_source', 150)
                            ->nullable()
                            ->after('specification');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'unit_price')) {
                        $table->decimal('unit_price', 15, 2)
                            ->nullable()
                            ->after('fund_source');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'condition')) {
                        $table->string('condition', 30)
                            ->nullable()
                            ->after('unit_price');
                    }

                    if (! Schema::hasColumn('item_stock_movements', 'photo_path')) {
                        $table->string('photo_path')
                            ->nullable()
                            ->after('condition');
                    }
                }
            );
        }

        if (Schema::hasTable('item_assets')) {
            Schema::table(
                'item_assets',
                function (Blueprint $table): void {
                    if (! Schema::hasColumn('item_assets', 'receipt_code')) {
                        $table->string('receipt_code', 100)
                            ->nullable()
                            ->index()
                            ->after('barcode_value');
                    }

                    if (! Schema::hasColumn('item_assets', 'brand')) {
                        $table->string('brand', 100)
                            ->nullable()
                            ->after('serial_number');
                    }

                    if (! Schema::hasColumn('item_assets', 'model')) {
                        $table->string('model', 100)
                            ->nullable()
                            ->after('brand');
                    }

                    if (! Schema::hasColumn('item_assets', 'specification')) {
                        $table->text('specification')
                            ->nullable()
                            ->after('model');
                    }

                    if (! Schema::hasColumn('item_assets', 'acquisition_source')) {
                        $table->string('acquisition_source', 150)
                            ->nullable()
                            ->after('specification');
                    }

                    if (! Schema::hasColumn('item_assets', 'fund_source')) {
                        $table->string('fund_source', 150)
                            ->nullable()
                            ->after('acquisition_source');
                    }

                    if (! Schema::hasColumn('item_assets', 'photo_path')) {
                        $table->string('photo_path')
                            ->nullable()
                            ->after('unit_price');
                    }
                }
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item_assets')) {
            foreach ([
                'receipt_code',
                'brand',
                'model',
                'specification',
                'acquisition_source',
                'fund_source',
                'photo_path',
            ] as $column) {
                if (Schema::hasColumn('item_assets', $column)) {
                    Schema::table('item_assets', fn (Blueprint $table) =>
                        $table->dropColumn($column)
                    );
                }
            }
        }

        if (Schema::hasTable('item_stock_movements')) {
            foreach ([
                'receipt_code',
                'workshop_id',
                'storage_location_id',
                'brand',
                'model',
                'specification',
                'fund_source',
                'unit_price',
                'condition',
                'photo_path',
            ] as $column) {
                if (Schema::hasColumn('item_stock_movements', $column)) {
                    Schema::table('item_stock_movements', fn (Blueprint $table) =>
                        $table->dropColumn($column)
                    );
                }
            }
        }
    }
};
