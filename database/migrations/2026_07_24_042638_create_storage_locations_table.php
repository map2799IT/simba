<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->restrictOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('storage_locations')
                ->restrictOnDelete();

            $table->string('code', 40);
            $table->string('name', 100);

            $table->string('type', 20);

            $table->text('description')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'workshop_id',
                'code',
            ]);

            $table->index([
                'workshop_id',
                'type',
            ]);

            $table->index([
                'parent_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_locations');
    }
};