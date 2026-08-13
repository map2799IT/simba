<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'audit_logs',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('event', 30);

                $table->string(
                    'auditable_type',
                    191
                );

                $table->unsignedBigInteger(
                    'auditable_id'
                );

                $table->string(
                    'auditable_label',
                    255
                )->nullable();

                $table->string(
                    'route_name',
                    191
                )->nullable();

                $table->text('url')
                    ->nullable();

                $table->string(
                    'method',
                    10
                )->nullable();

                $table->string(
                    'ip_address',
                    45
                )->nullable();

                $table->string(
                    'user_agent',
                    500
                )->nullable();

                $table->json('old_values')
                    ->nullable();

                $table->json('new_values')
                    ->nullable();

                $table->timestamp('created_at')
                    ->useCurrent();

                $table->index([
                    'auditable_type',
                    'auditable_id',
                ]);

                $table->index([
                    'user_id',
                    'created_at',
                ]);

                $table->index([
                    'event',
                    'created_at',
                ]);

                $table->index('route_name');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};