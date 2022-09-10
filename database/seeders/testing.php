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
                'value' => 'Pemakaian',
                'name' => 'Utama',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Pemakaian',
                'name' => 'Secondary',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Pemakaian',
                'name' => 'Whatsap',
                'isDeleted' => 0,
            ],
            [
                'value' => 'Telepon',
                'name' => 'Rumah',
                'isDeleted' => 0,
            ],

            [
                'value' => 'Telepon',
                'name' => 'Whatshap',
                'isDeleted' => 0,
            ],

            [
                'value' => 'Telepon',
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

        DB::table('location')->insert([
            'codeLocation' => 'abc123',
            "locationName" => "RPC Permata Hijau Pekanbaru",
            "isBranch"=> 0,
            "status"=> 1,
            "introduction"=>"RPC Permata Hijau Pekanbaru, the best pet shop in the pekanbaru",
            "description"=>"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum fuga, alias placeat necessitatibus dolorem ea autem tempore omnis asperiores nostrum, excepturi a unde mollitia blanditiis iusto. Dolorum tempora enim atque.",
            "image"=>"D:\\ImageFolder\\ExamplePath\\ImageRPCPermataHijau.jpg",
            "imageTitle"=>"ImageRPCPermataHijau.jpg",
            "isDeleted"=>0,
            'created_at' =>'2022-08-30',
        ]);

        $operational_days = [
            [
                "codeLocation" => "abc123",
                "days_name"=> "Monday",
                "from_time"=> "10:00PM",
                "to_time"=> "10:00PM",
                "all_day"=> 1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "days_name"=> "Tuesday",
                "from_time"=> "12:00PM",
                "to_time"=> "13:00PM",
                "all_day"=>1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "days_name"=> "Wednesday",
                "from_time"=> "10:00PM",
                "to_time"=> "10:00PM",
                "all_day"=> 1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "days_name"=> "Thursday",
                "from_time"=> "10:00PM",
                "to_time"=> "10:00PM",
                "all_day"=> 1,
                'created_at' =>'2022-08-30',
            ],
            [
                "codeLocation" => "abc123",
                "days_name"=> "Friday",
                "from_time"=> "10:00PM",
                "to_time"=> "10:00PM",
                "all_day"=> 1,
                'created_at' =>'2022-08-30',
            ],

        ];

        DB::table('location_operational')->insert($operational_days); // Query Builder approach

        DB::table('location_alamat_detail')->insert([
            'codeLocation' => 'abc123',
            "alamatJalan"=> "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
            "infoTambahan"=> "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
            "kotaID"=> "Jakarta Barat",
            "provinsiID"=> "Kemanggisan",
            "kodePos"=> "11480",
            "negara"=> "Indonesia",
            "parkir"=> "Yes",
            "pemakaian"=> "Indekos",
            "isDeleted" => 0,
            'created_at' => '2022-08-30',
        ]);

        $messenger = [
            [
                'codeLocation' => 'abc123',
                "pemakaian" => "Utama",
                "namaMessenger" => "(021) 3851185",
                "tipe" => "Fax",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],
            [
                'codeLocation' => 'abc123',
                "pemakaian" => "Utama",
                "namaMessenger" => "(021) 012345678",
                "tipe" => "Office",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],

        ];
        DB::table('location_messenger')->insert($messenger); 


        $email = [
            [
                'codeLocation' => 'abc123',
                "pemakaian"=> "Utama",
                "namaPengguna"=> "wahyudidanny23@gmail.com",
                "tipe"=> "Personal",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],
            [
                'codeLocation' => 'abc123',
                "pemakaian"=> "Secondary",
                "namaPengguna"=> "wahyudidanny25@gmail.com",
                "tipe"=> "Personal",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],

        ];
        DB::table('location_email')->insert($email); 


        $telepon = [
            [
                'codeLocation' => 'abc123',
                "pemakaian"=> "Utama",
                "nomorTelepon"=> "087888821648",
                "tipe"=> "Telepon Selular",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],
            [
                'codeLocation' => 'abc123',
                "pemakaian"=> "Secondary",
                "nomorTelepon"=> "085265779499",
                "tipe"=> "Whatshapp",
                "isDeleted" => 0,
                'created_at' => '2022-08-30',
            ],

        ];
        DB::table('location_telepon')->insert($telepon); 

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
                'unitName' => 'Unit Testing 1',
                "fasilitasName"=> "Kandang Maxi",
                "status"=> 1,
                "notes"=> "Unit Testing 1.1",
                "isdDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],
            [
                'unitName' => 'Unit Testing 2',
                "fasilitasName"=> "Kandang Maxi",
                "status"=> 1,
                "notes"=> "Unit Testing 1.2",
                "isdDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],
            [
                'unitName' => 'Unit Testing 3',
                "fasilitasName"=> "Kandang Maxi",
                "status"=> 1,
                "notes"=> "Unit Testing 3",
                "isdDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],
            [
                'unitName' => 'Unit Testing 4',
                "fasilitasName"=> "Kandang Maxi",
                "status"=> 1,
                "notes"=> "Unit Testing 4",
                "isdDeleted"=> 0,
                "created_at" => '2022-09-13',
            ],

        ];
        DB::table('fasilitas_unit')->insert($fasilitas_unit); 


    }
}
