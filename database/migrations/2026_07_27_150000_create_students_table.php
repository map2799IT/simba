<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn', 20)->unique();
            $table->string('nis', 50)->nullable()->index();
            $table->string('name', 150);
            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->restrictOnDelete();
            $table->string('class_name', 100);
            $table->string('gender', 1);
            $table->date('birth_date');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index([
                'workshop_id',
                'class_name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
