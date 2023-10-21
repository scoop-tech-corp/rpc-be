<?php

namespace Database\Seeders\Service;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class frequencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['userId' => '1', 'name' => 'once per day'],
            ['userId' => '1', 'name' => 'twice per day'],
            ['userId' => '1', 'name' => 'thrice per day'],
            ['userId' => '1', 'name' => 'four times per day'],
            ['userId' => '1', 'name' => 'every other day'],
            ['userId' => '1', 'name' => 'every 3 days'],
            ['userId' => '1', 'name' => 'once a week'],
            ['userId' => '1', 'name' => 'once every 2 weeks'],
            ['userId' => '1', 'name' => 'once every 4 weeks'],
            ['userId' => '1', 'name' => 'every 2 hours'],
            ['userId' => '1', 'name' => 'every 4 hours'],
            ['userId' => '1', 'name' => 'every 8 hours'],
            ['userId' => '1', 'name' => 'every 12 hours']
        ];
        DB::table('servicesFrequency')->insert($data);
    }
}
