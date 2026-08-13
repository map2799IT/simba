<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_assets', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('item_id')
                ->constrained('items')
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table
                ->string('asset_number', 100)
                ->unique();

            $table
                ->string('barcode_value', 150)
                ->unique();

            $table
                ->string('serial_number', 150)
                ->nullable()
                ->unique();

            $table
                ->foreignId('workshop_id')
                ->constrained('workshops')
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table
                ->foreignId('storage_location_id')
                ->nullable()
                ->constrained('storage_locations')
                ->restrictOnUpdate()
                ->nullOnDelete();

            $table
                ->string('condition', 30)
                ->default('good');

            $table
                ->string('status', 30)
                ->default('available');

            $table->date('received_date')->nullable();

            $table
                ->decimal('unit_price', 15, 2)
                ->nullable();

            $table->text('notes')->nullable();

            $table
                ->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'item_id',
                'status',
                'is_active',
            ]);

            $table->index([
                'workshop_id',
                'storage_location_id',
            ]);

            $table->index([
                'condition',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_assets');
    }
};
