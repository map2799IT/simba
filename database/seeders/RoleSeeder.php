<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Mengelola sistem dan seluruh pengguna.',
            ],
            [
                'name' => 'kepala_bengkel',
                'label' => 'Kepala Bengkel',
                'description' => 'Mengawasi inventaris dan memberikan persetujuan.',
            ],
            [
                'name' => 'toolman',
                'label' => 'Toolman',
                'description' => 'Mengelola barang dan transaksi bengkel.',
            ],
            [
                'name' => 'guru',
                'label' => 'Guru',
                'description' => 'Mengajukan peminjaman dan mengawasi siswa.',
            ],
            [
                'name' => 'siswa',
                'label' => 'Siswa',
                'description' => 'Mengajukan peminjaman alat dan permintaan bahan.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}