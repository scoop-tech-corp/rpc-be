<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('facility')->insert([ "facilityCode" => 'XYZ123',
                                        "facilityName"=> "Kandang Maxi",
                                        "locationName"=> "RPC Permata Hijau Pekanbaru",
                                        "capacity"=> 1,
                                        "status"=> 1,
                                        "introduction"=> "Kandang maxi Extra bed for you love pet",
                                        "description"=> "Ukuran 8M Cocok untuk tipe anjing besar, seperti golden retriever",
                                        "isDeleted"=> 0,
                                        "created_at" => now(),
                                    ]);

        $facility_unit = [[
                                "facilityCode" => "XYZ123",
                                "unitName" => "Unit Testing 1",
                                "status"=> 1,
                                "notes"=> "Unit Testing 1.1",
                                "isDeleted"=> 0,
                                "created_at" => now(),
                            ],
                            [
                                "facilityCode" => "XYZ123",
                                "unitName" => "Unit Testing 1",
                                "status"=> 1,
                                "notes"=> "Unit Testing 1.2",
                                "isDeleted"=> 0,
                                "created_at" =>  now(),
                            ],
                            [
                                "facilityCode" => "XYZ123",
                                "unitName" => "Unit Testing 2",
                                "status"=> 1,
                                "notes"=> "Unit Testing 2.1",
                                "isDeleted"=> 0,
                                "created_at" =>  now(),
                            ],
                            [
                                'facilityCode' => 'XYZ123',
                                'unitName' => 'Unit Testing 4',
                                "status"=> 1,
                                "notes"=> "Unit Testing 4",
                                "isDeleted"=> 0,
                                "created_at" => now(),
                            ]
                        ];

        DB::table('facility_unit')->insert($facility_unit); 

        $faker = Faker::create('en_US');

        for ($i = 1; $i <= 60; $i++) {

            $facilityCode = $faker->isbn10;
            $facilityUnitLoop = $faker->numberBetween($min = 1, $max = 5);

            DB::table('facility')->insert(['facilityCode' => $facilityCode,
                                           'facilityName' => $faker->colorName,
                                           'locationName' => $faker->company,
                                           'capacity' => $faker->numberBetween($min = 0, $max = 1),
                                           'status' => $faker->numberBetween($min = 0, $max = 1),
                                           'introduction' => $faker->text,
                                           'description' => $faker->text,
                                           'isDeleted' => 0,
                                           'created_at' => now(), ]);
          
            for ($j = 1; $j <= $facilityUnitLoop; $j++) {

             DB::table('facility_unit')->insert(['facilityCode' => $facilityCode,
                                                 'unitName' => $faker->company,
                                                 'status' => $faker->numberBetween($min = 0, $max = 1),
                                                 'notes' => $faker->text,
                                                 'isDeleted' => 0,
                                                 'created_at' => now(), ]);

            }
            
        }

    }
}
