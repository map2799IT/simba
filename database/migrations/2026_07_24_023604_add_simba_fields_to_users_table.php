<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->after('id')
                ->constrained('roles')
                ->nullOnDelete();

            $table->string('username', 50)
                ->nullable()
                ->unique()
                ->after('name');

            $table->string('nomor_identitas', 50)
                ->nullable()
                ->unique()
                ->after('username');

            $table->string('phone', 20)
                ->nullable()
                ->after('email');

            $table->boolean('is_active')
                ->default(true)
                ->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);

            $table->dropColumn([
                'role_id',
                'username',
                'nomor_identitas',
                'phone',
                'is_active',
            ]);
        });
    }
};