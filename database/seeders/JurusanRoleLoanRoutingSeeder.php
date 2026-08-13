<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class JurusanRoleLoanRoutingSeeder
    extends Seeder
{
    public function run(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable(
                'workshops'
            )
            || ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            $this->command?->error(
                'Tabel users/workshops atau users.workshop_id belum tersedia.'
            );

            return;
        }

        $workshops = DB::table(
            'workshops'
        )
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
            ]);

        foreach ($workshops as $workshop) {
            $code = strtolower(
                (string) $workshop->code
            );

            $this->ensureRoleUser(
                role: 'kepala_bengkel',
                workshopId: (int) $workshop->id,
                preferredEmail:
                    $workshop->code === 'TKJ'
                        ? 'kepala@simba.local'
                        : "kabeng.{$code}@simba.local",
                preferredUsername:
                    "kabeng_{$code}",
                preferredName:
                    "Kepala Bengkel {$workshop->code}"
            );

            $this->ensureRoleUser(
                role: 'toolman',
                workshopId: (int) $workshop->id,
                preferredEmail:
                    $workshop->code === 'TKR'
                        ? 'toolman@simba.local'
                        : "toolman.{$code}@simba.local",
                preferredUsername:
                    "toolman_{$code}",
                preferredName:
                    "Toolman {$workshop->code}"
            );

            $this->ensureRoleUser(
                role: 'siswa',
                workshopId: (int) $workshop->id,
                preferredEmail:
                    $workshop->code === 'TKJ'
                        ? 'siswa@simba.local'
                        : "siswa.{$code}@simba.local",
                preferredUsername:
                    "siswa_{$code}",
                preferredName:
                    "Siswa {$workshop->code}"
            );
        }

        /*
         * Guru tidak dikunci ke satu jurusan.
         */
        DB::table('users')
            ->where('role', 'guru')
            ->update([
                'workshop_id' => null,
                'updated_at' =>
                    Schema::hasColumn(
                        'users',
                        'updated_at'
                    )
                        ? now()
                        : DB::raw(
                            'updated_at'
                        ),
            ]);

        $this->backfillLoans();

        $this->command?->newLine();
        $this->command?->info(
            'Assignment jurusan dan routing toolman selesai.'
        );

        $this->command?->line(
            'Password seluruh akun baru: Password123!'
        );
    }

    private function ensureRoleUser(
        string $role,
        int $workshopId,
        string $preferredEmail,
        string $preferredUsername,
        string $preferredName
    ): void {
        $existing = DB::table('users')
            ->where('role', $role)
            ->where(
                'workshop_id',
                $workshopId
            )
            ->orderBy('id')
            ->first();

        if ($existing !== null) {
            DB::table('users')
                ->where(
                    'id',
                    $existing->id
                )
                ->update(
                    $this->filter([
                        'workshop_id' =>
                            $workshopId,
                        'is_active' => true,
                        'updated_at' => now(),
                    ])
                );

            $this->command?->line(
                "Gunakan {$existing->email} sebagai {$role} workshop {$workshopId}."
            );

            return;
        }

        $preferredExisting = DB::table('users')
            ->where(
                'email',
                $preferredEmail
            )
            ->first();

        $values = $this->filter([
            'name' => $preferredName,
            'username' =>
                $preferredUsername,
            'email' => $preferredEmail,
            'role' => $role,
            'workshop_id' =>
                $workshopId,
            'email_verified_at' =>
                now(),
            'password' =>
                Hash::make(
                    'Password123!'
                ),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($preferredExisting === null) {
            DB::table('users')
                ->insert($values);
        } else {
            unset(
                $values['created_at']
            );

            DB::table('users')
                ->where(
                    'id',
                    $preferredExisting->id
                )
                ->update($values);
        }

        $this->command?->line(
            "{$preferredEmail} -> {$role} workshop {$workshopId}"
        );
    }

    private function backfillLoans(): void
    {
        if (
            ! Schema::hasTable('loans')
            || ! Schema::hasTable(
                'loan_items'
            )
            || ! Schema::hasColumn(
                'loans',
                'workshop_id'
            )
        ) {
            return;
        }

        $loans = DB::table('loans')
            ->pluck('id');

        foreach ($loans as $loanId) {
            $workshopId =
                DB::table('loan_items')
                    ->join(
                        'items',
                        'items.id',
                        '=',
                        'loan_items.item_id'
                    )
                    ->where(
                        'loan_items.loan_id',
                        $loanId
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
                ->where('id', $loanId)
                ->update(
                    $this->filterFor(
                        'loans',
                        [
                            'workshop_id' =>
                                $workshopId,

                            'assigned_toolman_id' =>
                                $toolmanId,

                            'updated_at' => now(),
                        ]
                    )
                );
        }
    }

    private function filter(
        array $values
    ): array {
        return $this->filterFor(
            'users',
            $values
        );
    }

    private function filterFor(
        string $table,
        array $values
    ): array {
        $columns =
            Schema::getColumnListing(
                $table
            );

        return array_intersect_key(
            $values,
            array_flip($columns)
        );
    }
}
