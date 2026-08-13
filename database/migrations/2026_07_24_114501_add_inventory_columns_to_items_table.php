<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('type', 20);

            $table->string('code', 150)->unique();
            $table->string('name', 150);

            $table->foreignId('item_category_id')
                ->constrained('item_categories')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->restrictOnDelete();

            $table->foreignId('storage_location_id')
                ->nullable()
                ->constrained('storage_locations')
                ->nullOnDelete();

            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();

            $table->string('serial_number', 100)
                ->nullable()
                ->unique();

            $table->text('specification')->nullable();

            $table->date('received_date')->nullable();

            $table->string('acquisition_source', 100)
                ->nullable();

            $table->string('fund_source', 100)
                ->nullable();

            $table->decimal('unit_price', 15, 2)
                ->nullable();

            $table->string('condition', 30)
                ->default('good');

            $table->string('status', 30)
                ->default('available');

            $table->decimal('stock', 14, 3)
                ->default(0);

            $table->decimal('minimum_stock', 14, 3)
                ->default(0);

            $table->boolean('is_borrowable')
                ->default(false);

            $table->string('photo_path')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->index([
                'type',
                'is_active',
            ]);

            $table->index([
                'workshop_id',
                'storage_location_id',
            ]);

            $table->index([
                'item_category_id',
                'status',
            ]);

            $table->index('received_date');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign([
                'item_category_id',
            ]);

            $table->dropForeign([
                'unit_id',
            ]);

            $table->dropForeign([
                'workshop_id',
            ]);

            $table->dropForeign([
                'storage_location_id',
            ]);

            $table->dropUnique([
                'code',
            ]);

            $table->dropUnique([
                'serial_number',
            ]);

            $table->dropIndex([
                'type',
                'is_active',
            ]);

            $table->dropIndex([
                'workshop_id',
                'storage_location_id',
            ]);

            $table->dropIndex([
                'item_category_id',
                'status',
            ]);

            $table->dropIndex([
                'received_date',
            ]);

            $table->dropColumn([
                'type',
                'code',
                'name',
                'item_category_id',
                'unit_id',
                'workshop_id',
                'storage_location_id',
                'brand',
                'model',
                'serial_number',
                'specification',
                'received_date',
                'acquisition_source',
                'fund_source',
                'unit_price',
                'condition',
                'status',
                'stock',
                'minimum_stock',
                'is_borrowable',
                'photo_path',
                'description',
                'is_active',
            ]);
        });
    }
};