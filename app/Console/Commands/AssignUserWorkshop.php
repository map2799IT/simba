<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignUserWorkshop extends Command
{
    protected $signature =
        'user:assign-workshop
        {email : Email pengguna}
        {workshop=ALL : Kode jurusan, atau ALL untuk guru/admin}';

    protected $description =
        'Menetapkan satu jurusan untuk kepala bengkel, toolman, siswa; guru/admin menggunakan ALL.';

    public function handle(): int
    {
        if (
            ! Schema::hasColumn(
                'users',
                'workshop_id'
            )
        ) {
            $this->error(
                'Kolom users.workshop_id belum tersedia.'
            );

            return self::FAILURE;
        }

        $email = strtolower(
            trim(
                (string)
                $this->argument('email')
            )
        );

        $user = DB::table('users')
            ->where('email', $email)
            ->first();

        if ($user === null) {
            $this->error(
                "Pengguna {$email} tidak ditemukan."
            );

            return self::FAILURE;
        }

        $role = (string) $user->role;

        if (
            in_array(
                $role,
                [
                    'guru',
                    'admin',
                ],
                true
            )
        ) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'workshop_id' => null,
                    'updated_at' => now(),
                ]);

            $this->info(
                "{$email} memiliki akses seluruh jurusan."
            );

            return self::SUCCESS;
        }

        $code = strtoupper(
            trim(
                (string)
                $this->argument(
                    'workshop'
                )
            )
        );

        if ($code === 'ALL') {
            $this->error(
                'Kepala bengkel, toolman, dan siswa wajib memiliki satu jurusan.'
            );

            return self::FAILURE;
        }

        $workshop = DB::table(
            'workshops'
        )
            ->where('code', $code)
            ->first();

        if ($workshop === null) {
            $this->error(
                "Jurusan {$code} tidak ditemukan."
            );

            return self::FAILURE;
        }

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'workshop_id' =>
                    $workshop->id,

                'updated_at' => now(),
            ]);

        $this->info(
            "{$email} ditetapkan ke {$code}."
        );

        return self::SUCCESS;
    }
}
