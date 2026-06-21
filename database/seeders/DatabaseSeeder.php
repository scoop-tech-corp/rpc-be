<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\Service\frequencySeeder;
use Database\Seeders\Service\PetHotelServiceSeeder;
use Database\Seeders\Service\PetHotelTreatmentPlanSeeder;
use Database\Seeders\Cage\CageSeeder;
use Database\Seeders\QueueMenuSeeder;
use Database\Seeders\Promotion\PromotionDiscountSeeder;
use Database\Seeders\Customer\CustomerTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(MasterLocationSeeder::class);
        $this->call(frequencySeeder::class);
        $this->call(CageSeeder::class);
        $this->call(PetHotelServiceSeeder::class);
        $this->call(PetHotelTreatmentPlanSeeder::class);
        $this->call(QueueMenuSeeder::class);
        $this->call(PromotionDiscountSeeder::class);
        $this->call(CustomerTableSeeder::class);
    }
}
