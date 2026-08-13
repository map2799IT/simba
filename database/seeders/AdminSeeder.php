<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@simba.local'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Administrator SIMBA',
                'username' => 'admin',
                'nomor_identitas' => 'ADMIN-001',
                'phone' => null,
                'password' => Hash::make('Admin123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}