<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn(
                'users',
                'role'
            )
        ) {
            return;
        }

        $driver =
            DB::connection()
                ->getDriverName();

        if (
            ! in_array(
                $driver,
                [
                    'mysql',
                    'mariadb',
                ],
                true
            )
        ) {
            return;
        }

        $column =
            DB::selectOne(
                "SHOW COLUMNS FROM `users` WHERE `Field` = 'role'"
            );

        $type =
            (string)
            (
                $column->Type
                ?? $column->type
                ?? ''
            );

        if (
            ! str_starts_with(
                strtolower($type),
                'enum('
            )
            || str_contains(
                $type,
                "'wakil_sarpras'"
            )
        ) {
            return;
        }

        preg_match_all(
            "/'((?:[^'\\\\]|\\\\.)*)'/",
            $type,
            $matches
        );

        $values =
            array_map(
                static fn (
                    string $value
                ): string =>
                    stripcslashes($value),
                $matches[1]
                ?? []
            );

        $values[] =
            'wakil_sarpras';

        $values =
            array_values(
                array_unique(
                    $values
                )
            );

        $enum =
            implode(
                ',',
                array_map(
                    static fn (
                        string $value
                    ): string =>
                        "'".
                        str_replace(
                            "'",
                            "''",
                            $value
                        ).
                        "'",
                    $values
                )
            );

        $nullable =
            strtoupper(
                (string)
                (
                    $column->Null
                    ?? $column->null
                    ?? 'NO'
                )
            ) === 'YES'
                ? 'NULL'
                : 'NOT NULL';

        $default =
            $column->Default
            ?? $column->default
            ?? null;

        $defaultSql =
            $default === null
                ? ''
                : " DEFAULT '".
                    str_replace(
                        "'",
                        "''",
                        (string) $default
                    ).
                    "'";

        DB::statement(
            "ALTER TABLE `users` ".
            "MODIFY `role` ENUM({$enum}) ".
            "{$nullable}{$defaultSql}"
        );
    }

    public function down(): void
    {
        /*
         * Tidak menghapus nilai ENUM agar akun wakil_sarpras
         * yang sudah dibuat tidak menjadi data invalid.
         */
    }
};
