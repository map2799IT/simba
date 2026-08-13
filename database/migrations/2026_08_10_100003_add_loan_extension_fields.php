<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->timestamp('extended_due_at')->nullable()->after('due_at');
            $table->foreignId('extended_by')->nullable()->after('extended_due_at')->constrained('users')->nullOnDelete();
            $table->text('extension_reason')->nullable()->after('extended_by');
            $table->timestamp('extended_at')->nullable()->after('extension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('extended_by');
            $table->dropColumn(['extended_due_at', 'extended_by', 'extension_reason', 'extended_at']);
        });
    }
};