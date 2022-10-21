<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
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

        DB::table('location')
         ->insert([ 'codeLocation' => 'abc123',
                    "locationName" => "RPC Permata Hijau Pekanbaru",
                    "status"=> 1,
                    "description"=>"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum fuga, alias placeat necessitatibus dolorem ea autem tempore omnis asperiores nostrum, excepturi a unde mollitia blanditiis iusto. Dolorum tempora enim atque.",
                    "isDeleted"=>0,
                    'created_at' =>now(),
                ]);
        
        
        DB::table('location_detail_address')
         ->insert([ 'codeLocation' => 'abc123',
                    "addressName"=> "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
                    "additionalInfo"=> "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
                    "provinceCode"=> 12,
                    "cityCode"=> 1102,
                    "postalCode"=> 9999,
                    "country"=> "Indonesia",
                    "isPrimary"=> 1,
                    "isDeleted" => 0,
                    'created_at' =>now(),
                 ]);

        $operationalHour = [
                [
                    "codeLocation" => "abc123",
                    "dayName"=> "Monday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => "abc123",
                    "dayName"=> "Tuesday",
                    "fromTime"=> "12:00PM",
                    "toTime"=> "13:00PM",
                    "allDay"=>1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => "abc123",
                    "dayName"=> "Wednesday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => "abc123",
                    "dayName"=> "Thursday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => "abc123",
                    "dayName"=> "Friday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],

             ];
        DB::table('location_operational')->insert($operationalHour); 
        
        
        $messenger = 
        [
            [
                'codeLocation' => 'abc123',
                "messengerNumber" => "(021) 3851185",
                "type" => "Fax",
                "usage" => "Utama",
                "isDeleted" => 0,
                'created_at' => now(),
            ],
            [
                'codeLocation' => 'abc123',
                "messengerNumber" => "(021) 012345678",
                "type" => "Office",
                "usage" => "Utama",
                "isDeleted" => 0,
                'created_at' => now(),
            ],

        ];
        DB::table('location_messenger')->insert($messenger); 

                
        $email = 
        [
            [
                'codeLocation' => 'abc123',
                "username"=> "wahyudidanny23@gmail.com",
                "usage"=> "Utama",
                "isDeleted" => 0,
                'created_at' => now(),
            ],
            [
                'codeLocation' => 'abc123',
                "username"=> "wahyudidanny25@gmail.com",
                "usage"=> "Secondary",
                "isDeleted" => 0,
                'created_at' =>now(),
            ],

        ];
        DB::table('location_email')->insert($email); 

       
        $telepon = [
                    [
                        'codeLocation' => 'abc123',
                        "phoneNumber"=> "087888821648",
                        "type"=> "Telepon Selular",
                        "usage"=> "Utama",
                        "isDeleted" => 0,
                        'created_at' => now(),
                    ],
                    [
                        'codeLocation' => 'abc123',
                        "phoneNumber"=> "085265779499",
                        "type"=> "Whatshapp",
                        "usage"=> "Secondary",
                        "isDeleted" => 0,
                        'created_at' => now(),
                    ],
                 ];

        DB::table('location_telephone')->insert($telepon); 

       //----------------------------------**********------------------------------------------

        $faker = Faker::create('en_US');

        for ($i = 1; $i <= 60; $i++) {

        $codeLocation = $faker->isbn10;
        $imageName = $faker->company.' '.".jpg";
      
        DB::table('location')
        ->insert([ 'codeLocation' => $codeLocation,
                    "locationName" =>  $faker->company,
                    "status"=> $faker->numberBetween($min = 0, $max = 1),
                    "description"=> $faker->text,
                    "isDeleted"=>0,
                    'created_at'=>now(),
                ]);
               
            $locationAddressLoop = $faker->numberBetween($min = 1, $max = 2);   
            $isPrimaryAddress = 1;
                
            for ($j = 1; $j <= $locationAddressLoop; $j++) {

                DB::table('location_detail_address')
                ->insert(['codeLocation' => $codeLocation,
                            "addressName"=> $faker->address,
                            "additionalInfo"=> $faker->text,
                            "provinceCode" =>  11,
                            "cityCode"=> 1101,
                            "postalCode"=> 99999,
                            "country"=> "Indonesia",
                            "isPrimary"=> $isPrimaryAddress,
                            "isDeleted" => 0,
                            'created_at'=>now(),
                        ]);
                
                if ($locationAddressLoop > 1)
                    $isPrimaryAddress = 0;

            }  

        $operationalHourFaker = [
                [
                    "codeLocation" => $codeLocation,
                    "dayName"=> "Monday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => $codeLocation,
                    "dayName"=> "Tuesday",
                    "fromTime"=> "12:00PM",
                    "toTime"=> "13:00PM",
                    "allDay"=>1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => $codeLocation,
                    "dayName"=> "Wednesday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => $codeLocation,
                    "dayName"=> "Thursday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],
                [
                    "codeLocation" => $codeLocation,
                    "dayName"=> "Friday",
                    "fromTime"=> "10:00PM",
                    "toTime"=> "10:00PM",
                    "allDay"=> 1,
                    'created_at' =>now(),
                ],

             ];
             
        DB::table('location_operational')->insert($operationalHourFaker); 

        $locationMessengerLoop = $faker->numberBetween($min = 1, $max = 3);
        $isPrimaryMessenger= "Utama";  

        for ($x= 1; $x <= $locationMessengerLoop; $x++) {

            DB::table('location_messenger')
            ->insert(['codeLocation' => $codeLocation,
                        "messengerNumber"=> $faker->phoneNumber,
                        "type"=> $faker->randomElement(['Office', 'Fax']),
                        "usage" =>  $isPrimaryMessenger,
                        "isDeleted" => 0,
                        'created_at'=>now(),
                    ]);
            
            if ($locationMessengerLoop > 1)
                $isPrimaryMessenger = "Secondary";

        }


        $locationEmailLoop = $faker->numberBetween($min = 1, $max = 3);
        $isPrimaryEmail= "Utama";  

        for ($c= 1; $c <= $locationEmailLoop; $c++) {

            DB::table('location_email')
            ->insert(['codeLocation' => $codeLocation,
                        "username"=> $faker->email,
                        "usage" =>  $isPrimaryEmail,
                        "isDeleted" => 0,
                        'created_at'=>now(),
                    ]);
            
            if ($locationEmailLoop > 1)
                $isPrimaryEmail = "Secondary";

        }

        $locationTelephoneLoop = $faker->numberBetween($min = 1, $max = 3);
        $isPrimaryTelephone= "Utama";  

        for ($a= 1; $a <= $locationTelephoneLoop; $a++) {

            DB::table('location_telephone')
            ->insert(['codeLocation' => $codeLocation,
                        "phoneNumber"=> $faker->phoneNumber,
                        "type"=> $faker->randomElement(['Telepon Selular', 'Whatshapp']),
                        "usage" =>  $isPrimaryTelephone,
                        "isDeleted" => 0,
                        'created_at'=>now(),
                    ]);
            
            if ($locationTelephoneLoop > 1)
                $isPrimaryTelephone = "Secondary";

        }
     }     

    }
}
