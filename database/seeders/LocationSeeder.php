<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $faker = Faker::create('en_US');

        for ($i = 1; $i <= 1; $i++) {

            $codeLocation = $faker->bankAccountNumber;

            DB::table('location')->insert([
                'codeLocation' => $codeLocation,
                "locationName" => $faker->company,
                "isBranch" => $faker->numberBetween($min = 0, $max = 1),
                "status" => $faker->numberBetween($min = 0, $max = 1),
                "description" => $faker->text,
                "image" => "",
                "imageTitle" => $faker->image,
                "isDeleted" => $faker->numberBetween($min = 0, $max = 1),
                "created_at" => now(),
            ]);

          
            $locationDetailLoop = $faker->numberBetween($min = 1, $max = 3);


          // 11-94 provinsi
            for ($i = 1; $i <= $locationDetailLoop; $i++) {
                    
                DB::table('location_detail_address')->insert([
                    'codeLocation' => $codeLocation,
                    "addressName"=> "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
                    "additionalInfo"=> "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
                    "provinceName"=> "Kemanggisan",
                    "cityName"=> "Jakarta Barat",
                    "postalCode"=> "11480",
                    "country"=> "Indonesia",
                    "isPrimary"=> 1,
                    "isDeleted" => 0,
                    'created_at' => now(),
                ]);

            }

        }

    }
}
