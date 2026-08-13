<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: tambah kolom bukti gambar pada laporan kerusakan.
     * Data lama kompatibel (kolom nullable).
     */
    public function up(): void
    {
        if (Schema::hasTable('damage_reports') && ! Schema::hasColumn('damage_reports', 'evidence_image')) {
            Schema::table('damage_reports', function (Blueprint $table): void {
                $table->string('evidence_image', 500)->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('damage_reports') && Schema::hasColumn('damage_reports', 'evidence_image')) {
            Schema::table('damage_reports', function (Blueprint $table): void {
                $table->dropColumn('evidence_image');
            });
        }
    }
};
