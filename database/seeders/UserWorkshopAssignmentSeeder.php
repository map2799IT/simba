<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserWorkshopAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            JurusanRoleLoanRoutingSeeder::class,
        ]);
    }
}
