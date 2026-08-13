<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_item_replacement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_asset_id')->nullable()->constrained('item_assets')->nullOnDelete();
            $table->foreignId('new_asset_id')->nullable()->constrained('item_assets')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->string('damage_description', 1000)->nullable();
            $table->string('replacement_asset_code', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_item_replacement_requests');
    }
};
