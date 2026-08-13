<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_issue_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 100)->nullable()->index();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->date('transaction_date');
            $table->string('destination', 150)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['workshop_id', 'status']);
            $table->index('status');
        });

        Schema::create('stock_issue_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_issue_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->json('asset_ids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_issue_request_items');
        Schema::dropIfExists('stock_issue_requests');
    }
};
