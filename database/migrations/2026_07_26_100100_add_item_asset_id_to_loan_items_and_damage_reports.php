<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateLoanItems();
        $this->updateDamageReports();
    }

    public function down(): void
    {
        $this->rollbackDamageReports();
        $this->rollbackLoanItems();
    }

    private function updateLoanItems(): void
    {
        if (! Schema::hasTable('loan_items')) {
            return;
        }

        /*
         * Foreign key loan_items.loan_id sebelumnya memakai index
         * gabungan loan_id + item_id.
         *
         * MySQL tidak mengizinkan index tersebut dihapus sebelum
         * tersedia index pengganti yang diawali kolom loan_id.
         */
        if (
            ! $this->indexExists(
                'loan_items',
                'loan_items_loan_id_index'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->index(
                        'loan_id',
                        'loan_items_loan_id_index'
                    );
                }
            );
        }

        /*
         * Hapus unique lama setelah foreign key loan_id sudah mempunyai
         * index pengganti.
         */
        if (
            $this->indexExists(
                'loan_items',
                'loan_items_loan_id_item_id_unique'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'loan_items_loan_id_item_id_unique'
                    );
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'loan_items',
                'item_asset_id'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table
                        ->foreignId('item_asset_id')
                        ->nullable()
                        ->after('item_id')
                        ->constrained('item_assets')
                        ->restrictOnUpdate()
                        ->restrictOnDelete();
                }
            );
        }

        if (
            ! $this->indexExists(
                'loan_items',
                'loan_items_loan_id_item_id_index'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->index(
                        [
                            'loan_id',
                            'item_id',
                        ],
                        'loan_items_loan_id_item_id_index'
                    );
                }
            );
        }

        if (
            ! $this->indexExists(
                'loan_items',
                'loan_items_loan_id_asset_id_unique'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    /*
                     * Satu unit fisik tidak boleh dicatat dua kali
                     * pada transaksi peminjaman yang sama.
                     *
                     * Nilai NULL tetap diperbolehkan untuk data lama
                     * yang belum memiliki unit alat.
                     */
                    $table->unique(
                        [
                            'loan_id',
                            'item_asset_id',
                        ],
                        'loan_items_loan_id_asset_id_unique'
                    );
                }
            );
        }
    }

    private function updateDamageReports(): void
    {
        if (! Schema::hasTable('damage_reports')) {
            return;
        }

        if (
            ! Schema::hasColumn(
                'damage_reports',
                'item_asset_id'
            )
        ) {
            Schema::table(
                'damage_reports',
                function (Blueprint $table): void {
                    $table
                        ->foreignId('item_asset_id')
                        ->nullable()
                        ->after('item_id')
                        ->constrained('item_assets')
                        ->restrictOnUpdate()
                        ->nullOnDelete();
                }
            );
        }

        if (
            ! $this->indexExists(
                'damage_reports',
                'damage_reports_asset_status_index'
            )
        ) {
            Schema::table(
                'damage_reports',
                function (Blueprint $table): void {
                    $table->index(
                        [
                            'item_asset_id',
                            'status',
                        ],
                        'damage_reports_asset_status_index'
                    );
                }
            );
        }
    }

    private function rollbackDamageReports(): void
    {
        if (
            ! Schema::hasTable('damage_reports')
            || ! Schema::hasColumn(
                'damage_reports',
                'item_asset_id'
            )
        ) {
            return;
        }

        if (
            $this->indexExists(
                'damage_reports',
                'damage_reports_asset_status_index'
            )
        ) {
            Schema::table(
                'damage_reports',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'damage_reports_asset_status_index'
                    );
                }
            );
        }

        Schema::table(
            'damage_reports',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'item_asset_id'
                );
            }
        );
    }

    private function rollbackLoanItems(): void
    {
        if (! Schema::hasTable('loan_items')) {
            return;
        }

        if (
            $this->indexExists(
                'loan_items',
                'loan_items_loan_id_asset_id_unique'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'loan_items_loan_id_asset_id_unique'
                    );
                }
            );
        }

        if (
            $this->indexExists(
                'loan_items',
                'loan_items_loan_id_item_id_index'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'loan_items_loan_id_item_id_index'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'loan_items',
                'item_asset_id'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->dropConstrainedForeignId(
                        'item_asset_id'
                    );
                }
            );
        }

        /*
         * Pulihkan unique lama terlebih dahulu agar foreign key loan_id
         * tetap memiliki index sebelum index tunggal dihapus.
         */
        if (
            ! $this->indexExists(
                'loan_items',
                'loan_items_loan_id_item_id_unique'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->unique(
                        [
                            'loan_id',
                            'item_id',
                        ],
                        'loan_items_loan_id_item_id_unique'
                    );
                }
            );
        }

        if (
            $this->indexExists(
                'loan_items',
                'loan_items_loan_id_index'
            )
        ) {
            Schema::table(
                'loan_items',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'loan_items_loan_id_index'
                    );
                }
            );
        }
    }

    private function indexExists(
        string $table,
        string $index
    ): bool {
        return DB::table(
            'information_schema.statistics'
        )
            ->whereRaw(
                'table_schema = DATABASE()'
            )
            ->where(
                'table_name',
                $table
            )
            ->where(
                'index_name',
                $index
            )
            ->exists();
    }
};
