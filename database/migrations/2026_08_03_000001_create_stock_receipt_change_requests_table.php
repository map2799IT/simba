<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_receipt_change_requests')) {
            return;
        }

        Schema::create('stock_receipt_change_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('item_stock_movement_id')
                ->constrained('item_stock_movements')
                ->cascadeOnDelete();

            $table->foreignId('requested_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->json('original_payload');
            $table->json('requested_payload');
            $table->text('request_note')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['item_stock_movement_id', 'status'],
                'stock_receipt_request_movement_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_change_requests');
    }
};
