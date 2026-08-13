<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exception_class', 255)->nullable()->index();
            $table->text('message');
            $table->string('url', 1000)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('route_name', 255)->nullable()->index();
            $table->text('stack_trace')->nullable();
            $table->json('request_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->boolean('is_resolved')->default(false)->index();
            $table->text('resolution_note')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent();

            $table->index(['user_id', 'is_resolved']);
            $table->index(['created_at', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_error_logs');
    }
};
