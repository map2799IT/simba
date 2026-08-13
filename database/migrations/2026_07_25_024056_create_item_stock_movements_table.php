<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'item_stock_movements',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('item_id')
                    ->constrained('items')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('type', 30);

                $table->decimal(
                    'quantity',
                    14,
                    3
                )->default(0);

                $table->decimal(
                    'stock_before',
                    14,
                    3
                )->default(0);

                $table->decimal(
                    'stock_after',
                    14,
                    3
                )->default(0);

                $table->date('transaction_date');

                $table->string(
                    'reference_number',
                    100
                )->nullable();

                $table->string(
                    'source',
                    150
                )->nullable();

                $table->text(
                    'description'
                )->nullable();

                $table->timestamps();

                $table->index([
                    'item_id',
                    'transaction_date',
                ]);

                $table->index([
                    'type',
                    'transaction_date',
                ]);

                $table->index('reference_number');
            }
        );

        /*
         * Membuat saldo awal untuk barang yang sudah ada sebelum
         * modul riwayat stok dipasang.
         */
        DB::table('items')
            ->orderBy('id')
            ->chunkById(
                500,
                function ($items): void {
                    $now = now();
                    $rows = [];

                    foreach ($items as $item) {
                        $stockAfter = $item->type === 'tool'
                            ? 1
                            : (float) $item->stock;

                        $transactionDate =
                            $item->received_date
                            ?: substr(
                                (string) (
                                    $item->created_at
                                    ?: $now
                                ),
                                0,
                                10
                            );

                        $rows[] = [
                            'item_id' => $item->id,
                            'user_id' => null,
                            'type' => 'initial',
                            'quantity' => $stockAfter,
                            'stock_before' => 0,
                            'stock_after' => $stockAfter,
                            'transaction_date' =>
                                $transactionDate,
                            'reference_number' => null,
                            'source' =>
                                $item->acquisition_source,
                            'description' =>
                                'Saldo awal saat modul riwayat stok diaktifkan.',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table(
                            'item_stock_movements'
                        )->insert($rows);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'item_stock_movements'
        );
    }
};