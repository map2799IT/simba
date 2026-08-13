<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WorkshopRoleAccountSeeder
    extends Seeder
{
    private string $now;

    public function run(): void
    {
        $this->now =
            now()->toDateTimeString();

        foreach (
            [
                'users',
                'workshops',
            ]
            as $table
        ) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Tabel {$table} belum tersedia."
                );
            }
        }

        $password = (string) config(
            'simba_seed.default_password',
            'Password123!'
        );

        $workshops = DB::table(
            'workshops'
        )
            ->orderBy('code')
            ->get();

        foreach ($workshops as $workshop) {
            $code = strtoupper(
                (string) $workshop->code
            );

            $slug = strtolower(
                preg_replace(
                    '/[^a-z0-9]+/i',
                    '_',
                    $code
                )
            );

            $headId = $this->upsertUser(
                [
                    'name' =>
                        'Kepala Bengkel '.$code,

                    'username' =>
                        'kabeng_'.$slug,

                    'email' =>
                        'kabeng.'.
                        $slug.
                        '@simba.local',

                    'role' =>
                        'kepala_bengkel',

                    'workshop_id' =>
                        (int) $workshop->id,

                    'password' =>
                        $password,

                    'is_active' =>
                        (bool)
                        $workshop->is_active,
                ]
            );

            $this->upsertUser(
                [
                    'name' =>
                        'Toolman '.$code,

                    'username' =>
                        'toolman_'.$slug,

                    'email' =>
                        'toolman.'.
                        $slug.
                        '@simba.local',

                    'role' =>
                        'toolman',

                    'workshop_id' =>
                        (int) $workshop->id,

                    'password' =>
                        $password,

                    'is_active' =>
                        (bool)
                        $workshop->is_active,
                ]
            );

            $this->assignHead(
                (int) $workshop->id,
                $headId
            );

            $this->ensureRootLocation(
                $workshop
            );

            $this->command?->line(
                sprintf(
                    '%-8s kabeng.%-12s toolman.%-12s password: %s',
                    $code,
                    $slug.'@simba.local',
                    $slug.'@simba.local',
                    $password
                )
            );
        }
    }

    private function upsertUser(
        array $account
    ): int {
        $roleId =
            $this->roleId(
                $account['role']
            );

        $values = $this->filterColumns(
            'users',
            [
                'name' =>
                    $account['name'],

                'username' =>
                    $account['username'],

                'email' =>
                    $account['email'],

                'role' =>
                    $account['role'],

                'role_id' =>
                    $roleId,

                'workshop_id' =>
                    $account['workshop_id'],

                'password' =>
                    Hash::make(
                        $account['password']
                    ),

                'email_verified_at' =>
                    $this->now,

                'is_active' =>
                    $account['is_active'],

                'updated_at' =>
                    $this->now,
            ]
        );

        $existingId =
            DB::table('users')
                ->where(
                    'email',
                    $account['email']
                )
                ->value('id');

        if ($existingId !== null) {
            DB::table('users')
                ->where(
                    'id',
                    $existingId
                )
                ->update($values);

            return (int) $existingId;
        }

        $values =
            $this->filterColumns(
                'users',
                array_merge(
                    $values,
                    [
                        'created_at' =>
                            $this->now,
                    ]
                )
            );

        return (int)
            DB::table('users')
                ->insertGetId($values);
    }

    private function roleId(
        string $role
    ): ?int {
        if (! Schema::hasTable('roles')) {
            return null;
        }

        $columns =
            Schema::getColumnListing(
                'roles'
            );

        foreach (
            [
                'name',
                'slug',
                'key',
                'code',
            ]
            as $column
        ) {
            if (
                ! in_array(
                    $column,
                    $columns,
                    true
                )
            ) {
                continue;
            }

            $value =
                $column === 'code'
                    ? strtoupper($role)
                    : $role;

            $id = DB::table('roles')
                ->where(
                    $column,
                    $value
                )
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    private function assignHead(
        int $workshopId,
        int $headId
    ): void {
        $values = $this->filterColumns(
            'workshops',
            [
                'manager_id' =>
                    $headId,

                'head_id' =>
                    $headId,

                'head_user_id' =>
                    $headId,

                'responsible_user_id' =>
                    $headId,

                'updated_at' =>
                    $this->now,
            ]
        );

        if ($values === []) {
            return;
        }

        DB::table('workshops')
            ->where(
                'id',
                $workshopId
            )
            ->update($values);
    }

    private function ensureRootLocation(
        object $workshop
    ): void {
        if (
            ! Schema::hasTable(
                'storage_locations'
            )
        ) {
            return;
        }

        $exists =
            DB::table(
                'storage_locations'
            )
                ->where(
                    'workshop_id',
                    $workshop->id
                )
                ->exists();

        if ($exists) {
            return;
        }

        $code =
            strtoupper(
                (string)
                $workshop->code
            ).
            '-R01';

        $values = $this->filterColumns(
            'storage_locations',
            [
                'workshop_id' =>
                    (int)
                    $workshop->id,

                'parent_id' =>
                    null,

                'code' =>
                    $code,

                'name' =>
                    'Ruang Utama '.
                    strtoupper(
                        (string)
                        $workshop->code
                    ),

                'type' =>
                    'room',

                'description' =>
                    'Lokasi utama dibuat otomatis oleh seeder.',

                'is_active' =>
                    (bool)
                    $workshop->is_active,

                'created_at' =>
                    $this->now,

                'updated_at' =>
                    $this->now,
            ]
        );

        DB::table(
            'storage_locations'
        )->insert($values);
    }

    private function filterColumns(
        string $table,
        array $values
    ): array {
        $columns = array_flip(
            Schema::getColumnListing(
                $table
            )
        );

        return array_intersect_key(
            $values,
            $columns
        );
    }
}
