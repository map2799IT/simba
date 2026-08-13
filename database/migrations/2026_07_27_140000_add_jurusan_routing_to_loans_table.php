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

        Schema::table(
            'loans',
            function (
                Blueprint $table
            ): void {
                if (
                    ! Schema::hasColumn(
                        'loans',
                        'workshop_id'
                    )
                ) {
                    $table
                        ->foreignId(
                            'workshop_id'
                        )
                        ->nullable()
                        ->after('borrower_id')
                        ->constrained(
                            'workshops'
                        )
                        ->nullOnDelete();
                }

                if (
                    ! Schema::hasColumn(
                        'loans',
                        'assigned_toolman_id'
                    )
                ) {
                    $table
                        ->foreignId(
                            'assigned_toolman_id'
                        )
                        ->nullable()
                        ->after('workshop_id')
                        ->constrained(
                            'users'
                        )
                        ->nullOnDelete();
                }
            }
        );

        $this->backfill();
    }

    public function down(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        Schema::table(
            'loans',
            function (
                Blueprint $table
            ): void {
                if (
                    Schema::hasColumn(
                        'loans',
                        'assigned_toolman_id'
                    )
                ) {
                    $table->dropConstrainedForeignId(
                        'assigned_toolman_id'
                    );
                }

                if (
                    Schema::hasColumn(
                        'loans',
                        'workshop_id'
                    )
                ) {
                    $table->dropConstrainedForeignId(
                        'workshop_id'
                    );
                }
            }
        );
    }

    private function backfill(): void
    {
        if (
            ! Schema::hasTable(
                'loan_items'
            )
            || ! Schema::hasTable(
                'items'
            )
        ) {
            return;
        }

        $loans = DB::table('loans')
            ->select([
                'id',
                'workshop_id',
            ])
            ->get();

        foreach ($loans as $loan) {
            $workshopId =
                $loan->workshop_id
                ?? DB::table(
                    'loan_items'
                )
                    ->join(
                        'items',
                        'items.id',
                        '=',
                        'loan_items.item_id'
                    )
                    ->where(
                        'loan_items.loan_id',
                        $loan->id
                    )
                    ->value(
                        'items.workshop_id'
                    );

            if ($workshopId === null) {
                continue;
            }

            $toolmanQuery =
                DB::table('users')
                    ->where(
                        'role',
                        'toolman'
                    )
                    ->where(
                        'workshop_id',
                        $workshopId
                    );

            if (
                Schema::hasColumn(
                    'users',
                    'is_active'
                )
            ) {
                $toolmanQuery->where(
                    'is_active',
                    true
                );
            }

            $toolmanId =
                $toolmanQuery
                    ->orderBy('id')
                    ->value('id');

            DB::table('loans')
                ->where(
                    'id',
                    $loan->id
                )
                ->update([
                    'workshop_id' =>
                        $workshopId,

                    'assigned_toolman_id' =>
                        $toolmanId,

                    'updated_at' =>
                        Schema::hasColumn(
                            'loans',
                            'updated_at'
                        )
                            ? now()
                            : DB::raw(
                                'updated_at'
                            ),
                ]);
        }
    }
};
