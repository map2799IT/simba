<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 40)
                ->nullable()
                ->unique();

            $table->foreignId('borrower_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 30)
                ->default('pending');

            $table->date('request_date');

            $table->dateTime('due_at');

            $table->dateTime('approved_at')
                ->nullable();

            $table->dateTime('borrowed_at')
                ->nullable();

            $table->dateTime('rejected_at')
                ->nullable();

            $table->dateTime('returned_at')
                ->nullable();

            $table->string('purpose', 255);

            $table->text('notes')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'borrower_id',
                'status',
            ]);

            $table->index([
                'status',
                'due_at',
            ]);

            $table->index('request_date');
        });

        Schema::create('loan_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('loan_id')
                ->constrained('loans')
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();

            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('condition_out', 30);

            $table->string('condition_in', 30)
                ->nullable();

            $table->dateTime('returned_at')
                ->nullable();

            $table->text('return_notes')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'loan_id',
                'item_id',
            ]);

            $table->index([
                'item_id',
                'returned_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_items');
        Schema::dropIfExists('loans');
    }
};