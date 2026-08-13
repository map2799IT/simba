<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        if (
            ! Schema::hasColumn(
                'loans',
                'scheduled_at'
            )
        ) {
            Schema::table(
                'loans',
                function (
                    Blueprint $table
                ): void {
                    $table
                        ->dateTime(
                            'scheduled_at'
                        )
                        ->nullable()
                        ->after(
                            'request_date'
                        )
                        ->index();
                }
            );
        }

        /*
         * Data lama tidak mempunyai jam rencana.
         * Gunakan borrowed_at bila sudah diserahterimakan,
         * selain itu gunakan tanggal pengajuan pukul 00:00.
         */
        DB::statement(
            '
            UPDATE loans
            SET scheduled_at =
                COALESCE(
                    borrowed_at,
                    TIMESTAMP(request_date, "00:00:00")
                )
            WHERE scheduled_at IS NULL
            '
        );
    }

    public function down(): void
    {
        if (
            Schema::hasTable('loans')
            && Schema::hasColumn(
                'loans',
                'scheduled_at'
            )
        ) {
            Schema::table(
                'loans',
                function (
                    Blueprint $table
                ): void {
                    $table->dropColumn(
                        'scheduled_at'
                    );
                }
            );
        }
    }
};
