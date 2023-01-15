<?php

namespace Database\Seeders;

use Database\Seeders\Facility\FacilitySeeder;
use Database\Seeders\Location\LocationSeeder;
use Database\Seeders\Region\regionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            FacilitySeeder::class,
            LocationSeeder::class,
            regionSeeder::class
        ]);
    }
}
