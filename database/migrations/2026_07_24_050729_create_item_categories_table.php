<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name', 100);

            /*
             * tool     = khusus alat
             * material = khusus bahan
             * both     = dapat dipakai alat dan bahan
             */
            $table->string('applies_to', 20)
                ->default('both');

            $table->text('description')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'applies_to',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};