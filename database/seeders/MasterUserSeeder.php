<?php

namespace Database\Seeders;

use Database\Seeders\User\userSeeder;
use Database\Seeders\UserRole\userRoleSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            userRoleSeeder::class,
            userSeeder::class
        ]);
    }
}
