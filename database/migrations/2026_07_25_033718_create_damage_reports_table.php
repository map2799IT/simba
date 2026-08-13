<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'damage_reports',
            function (Blueprint $table): void {
                $table->id();

                $table->string('code', 40)
                    ->nullable()
                    ->unique();

                $table->foreignId('item_id')
                    ->constrained('items')
                    ->restrictOnDelete();

                $table->foreignId('loan_item_id')
                    ->nullable()
                    ->constrained('loan_items')
                    ->nullOnDelete();

                $table->foreignId('reported_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('handled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('completed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('status', 30)
                    ->default('reported');

                $table->string('severity', 30);

                $table->dateTime('reported_at');

                $table->dateTime('started_at')
                    ->nullable();

                $table->dateTime('completed_at')
                    ->nullable();

                $table->string(
                    'condition_before',
                    30
                );

                $table->string(
                    'condition_after',
                    30
                )->nullable();

                $table->text('description');

                $table->text('diagnosis')
                    ->nullable();

                $table->text('action_taken')
                    ->nullable();

                $table->string('vendor', 150)
                    ->nullable();

                $table->decimal(
                    'repair_cost',
                    15,
                    2
                )->nullable();

                $table->text('notes')
                    ->nullable();

                $table->text('resolution_notes')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'item_id',
                    'status',
                ]);

                $table->index([
                    'status',
                    'reported_at',
                ]);

                $table->index([
                    'severity',
                    'reported_at',
                ]);

                $table->index('loan_item_id');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'damage_reports'
        );
    }
};