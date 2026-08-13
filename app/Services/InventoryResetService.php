<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class InventoryResetService
{
    /**
     * Tabel diurutkan dari transaksi paling bergantung
     * sampai master barang.
     *
     * @return array<int, string>
     */
    public function inventoryTables(
        bool $clearAudit = false
    ): array {
        $tables = [
            'loan_return_items',
            'loan_returns',
            'loan_item_assets',
            'loan_items',
            'loan_approvals',
            'loan_histories',
            'loans',

            'damage_report_items',
            'damage_actions',
            'damage_reports',

            'maintenance_logs',
            'maintenance_items',
            'maintenances',

            'repair_logs',
            'repair_items',
            'repairs',

            'stock_receipt_items',
            'stock_receipts',
            'stock_issue_items',
            'stock_issues',

            'item_stock_adjustment_items',
            'item_stock_adjustments',

            'item_asset_status_histories',
            'item_asset_movements',
            'item_asset_histories',

            'item_stock_movements',
            'stock_movements',

            'item_assets',
            'items',
            'item_code_sequences',
        ];

        if ($clearAudit) {
            $tables[] = 'audit_logs';
        }

        return $tables;
    }

    /**
     * Tabel master yang harus tetap tersedia.
     *
     * @return array<int, string>
     */
    public function preservedTables(): array
    {
        return [
            'roles',
            'users',
            'workshops',
            'students',
            'storage_locations',
            'item_categories',
            'units',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function counts(
        array $tables
    ): array {
        $counts = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $counts[$table] =
                DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * @return array{
     *     cleared: array<string, int>,
     *     preserved_before: array<string, int>,
     *     preserved_after: array<string, int>
     * }
     */
    public function clear(
        bool $clearAudit = false
    ): array {
        $tables = array_values(
            array_filter(
                $this->inventoryTables(
                    $clearAudit
                ),
                static fn (
                    string $table
                ): bool =>
                    Schema::hasTable($table)
            )
        );

        $preservedBefore =
            $this->counts(
                $this->preservedTables()
            );

        $clearedBefore =
            $this->counts($tables);

        $connection =
            DB::connection();

        $driver =
            $connection->getDriverName();

        try {
            $this->disableForeignKeys(
                $driver
            );

            foreach ($tables as $table) {
                $this->truncateTable(
                    $table,
                    $driver
                );
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Gagal mereset tabel inventaris: '.
                $exception->getMessage(),
                previous: $exception
            );
        } finally {
            $this->enableForeignKeys(
                $driver
            );
        }

        return [
            'cleared' =>
                $clearedBefore,

            'preserved_before' =>
                $preservedBefore,

            'preserved_after' =>
                $this->counts(
                    $this->preservedTables()
                ),
        ];
    }

    private function truncateTable(
        string $table,
        string $driver
    ): void {
        $wrapped =
            DB::connection()
                ->getQueryGrammar()
                ->wrapTable($table);

        if ($driver === 'pgsql') {
            DB::statement(
                "TRUNCATE TABLE {$wrapped} RESTART IDENTITY CASCADE"
            );

            return;
        }

        if ($driver === 'mysql') {
            DB::statement(
                "TRUNCATE TABLE {$wrapped}"
            );

            return;
        }

        DB::table($table)->delete();

        if ($driver === 'sqlite') {
            DB::table('sqlite_sequence')
                ->where(
                    'name',
                    $table
                )
                ->delete();

            return;
        }

        if ($driver === 'sqlsrv') {
            try {
                DB::statement(
                    "DBCC CHECKIDENT ({$wrapped}, RESEED, 0)"
                );
            } catch (Throwable) {
                /*
                 * Tidak semua tabel SQL Server mempunyai identity.
                 */
            }
        }
    }

    private function disableForeignKeys(
        string $driver
    ): void {
        match ($driver) {
            'mysql' =>
                DB::statement(
                    'SET FOREIGN_KEY_CHECKS=0'
                ),

            'sqlite' =>
                DB::statement(
                    'PRAGMA foreign_keys=OFF'
                ),

            default => null,
        };
    }

    private function enableForeignKeys(
        string $driver
    ): void {
        match ($driver) {
            'mysql' =>
                DB::statement(
                    'SET FOREIGN_KEY_CHECKS=1'
                ),

            'sqlite' =>
                DB::statement(
                    'PRAGMA foreign_keys=ON'
                ),

            default => null,
        };
    }
}
