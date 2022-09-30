<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class testing extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $data = [
            [
                'value' => 'Usage',
                'name' => 'Utama',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Usage',
                'name' => 'Secondary',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Usage',
                'name' => 'Whatsap',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Telephone',
                'name' => 'Rumah',
                'isDeleted' => 0,
            ],

            [
                'value' => 'Telephone',
                'name' => 'Whatshap',
                'isDeleted' => 0,
            ],

            [
                'value' => 'Telephone',
                'name' => 'Rumah',
                'isDeleted' => 0,
            ],

            [
                'value' => 'Messenger',
                'name' => 'Gmail',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Messenger',
                'name' => 'Yahoo',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Messenger',
                'name' => 'GooglePlus',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Messenger',
                'name' => 'Bing',
                'isDeleted' => 0,
            ],
        ];

        DB::table('data_static')->insert($data); // Query Builder approach

        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'yolo@gmail.com',
            'password' => bcrypt("111111"),
            'created_at' =>'2022-08-30',
            'updated_at' =>'2022-08-30',
        ]);


        //location start
        DB::table('location')->insert([
            'codeLocation' => 'abc123',
            "locationName" => "RPC Permata Hijau Pekanbaru",
            "isBranch"=> 0,
            "status"=> 1,
            // "introduction"=>"RPC Permata Hijau Pekanbaru, the best pet shop in the pekanbaru",
            "description"=>"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum fuga, alias placeat necessitatibus dolorem ea autem tempore omnis asperiores nostrum, excepturi a unde mollitia blanditiis iusto. Dolorum tempora enim atque.",
            "image"=>"D:\\ImageFolder\\ExamplePath\\ImageRPCPermataHijau.jpg",
            "imageTitle"=>"ImageRPCPermataHijau.jpg",
            "isDeleted"=>0,
            'created_at' =>'2022-08-30',
        ]);

        $operational_days = [
            [
                "codeLocation" => "abc123",
                "dayName"=> "Monday",
                "fromTime"=> "10:00PM",
                "toTime"=> "10:00PM",
                "allDay"=> 1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "dayName"=> "Tuesday",
                "fromTime"=> "12:00PM",
                "toTime"=> "13:00PM",
                "allDay"=>1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "dayName"=> "Wednesday",
                "fromTime"=> "10:00PM",
                "toTime"=> "10:00PM",
                "allDay"=> 1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "dayName"=> "Thursday",
                "fromTime"=> "10:00PM",
                "toTime"=> "10:00PM",
                "allDay"=> 1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "dayName"=> "Friday",
                "fromTime"=> "10:00PM",
                "toTime"=> "10:00PM",
                "allDay"=> 1,
                'created_at' =>'2022-08-30',
            ],

        ];

        DB::table('location_operational')->insert($operational_days); // Query Builder approach

        DB::table('location_detail_address')->insert([
            'codeLocation' => 'abc123',
            "addressName"=> "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
            "additionalInfo"=> "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
            "cityName"=> "Jakarta Barat",
            "provinceName"=> "Kemanggisan",
            // "districtName"=> "palmerah",
            "postalCode"=> "11480",
            "country"=> "Indonesia",
            "isPrimary"=> 1,
            // "parking"=> 1,
            // "usage"=> "utama",
            "isDeleted" => 0,
            'created_at' => '2022-08-30',
        ]);


        
        $messenger = [
            [
                'codeLocation' => 'abc123',
                "messengerName" => "(021) 3851185",
                "type" => "Fax",
                "usage" => "Utama",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],
            [
                'codeLocation' => 'abc123',
                "namaMessenger" => "(021) 012345678",
                "type" => "Office",
                "usage" => "Utama",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],

        ];
        DB::table('location_messenger')->insert($messenger); 


        $email = [
            [
                'codeLocation' => 'abc123',
                "username"=> "wahyudidanny23@gmail.com",
                "type"=> "Personal",
                "usage"=> "Utama",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],
            [
                'codeLocation' => 'abc123',
                "username"=> "wahyudidanny25@gmail.com",
                "type"=> "Personal",
                "usage"=> "Secondary",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
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
                'created_at' => '2022-08-30',
            ],
            [
                'codeLocation' => 'abc123',
                "phoneNumber"=> "085265779499",
                "type"=> "Whatshapp",
                "usage"=> "Secondary",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],

        ];
        DB::table('location_telephone')->insert($telepon); 
        //location end

        


        DB::table('fasilitas')->insert([
            'codeFasilitas' => 'XYZ123',
            "fasilitasName"=> "Kandang Maxi",
            "locationName"=> "RPC Permata Hijau Pekanbaru",
            "capacity"=> 1,
            "status"=> 1,
            "introduction"=> "Kandang maxi Extra bed for you love pet",
            "description"=> "Ukuran 8M Cocok untuk tipe anjing besar, seperti golden retriever",
            "isDeleted"=> 0,
            "created_at" => '2022-08-30',
        ]);

        $fasilitas_unit = [
            [
                'codeFasilitas' => 'XYZ123',
                'unitName' => 'Unit Testing 1',
                "status"=> 1,
                "notes"=> "Unit Testing 1.1",
                "isDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],
            [
                'codeFasilitas' => 'XYZ123',
                'unitName' => 'Unit Testing 2',
                "status"=> 1,
                "notes"=> "Unit Testing 1.2",
                "isDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],
            [
                'codeFasilitas' => 'XYZ123',
                'unitName' => 'Unit Testing 3',
                "status"=> 1,
                "notes"=> "Unit Testing 3",
                "isDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],
            [
                'codeFasilitas' => 'XYZ123',
                'unitName' => 'Unit Testing 4',
                "status"=> 1,
                "notes"=> "Unit Testing 4",
                "isDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],

        ];
        DB::table('fasilitas_unit')->insert($fasilitas_unit); 


    }
}

