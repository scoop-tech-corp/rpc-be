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

        DB::table('data_static')->insert($data); 

        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'office@gmail.com',
            'password' => bcrypt("123"),
            'role' => 'office',
            'created_at' =>'2022-08-30',
            'updated_at' =>'2022-08-30',
        ]);


        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'admin@gmail.com',
            'password' => bcrypt("123"),
            'role' => 'admin',
            'created_at' =>'2022-08-30',
            'updated_at' =>'2022-08-30',
        ]);


        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'doctor@gmail.com',
            'password' => bcrypt("123"),
            'role' => 'doctor',
            'created_at' =>'2022-08-30',
            'updated_at' =>'2022-08-30',
        ]);

        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'staff@gmail.com',
            'password' => bcrypt("123"),
            'role' => 'staff',
            'created_at' =>'2022-08-30',
            'updated_at' =>'2022-08-30',
        ]);




        $userRole = [
            [
                'roleName' => 'admin',
                'isActive' =>1,
                'created_at' => now()
            ],
            [
                'roleName' => 'doctor',
                'isActive' =>1,
                'created_at' => now()
            ],
            [
                'roleName' => 'office',
                'isActive' =>1,
                'created_at' => now()
            ],
            [
                'roleName' => 'staff',
                'isActive' =>1,
                'created_at' => now()
            ],
        ];
        DB::table('users_role')->insert($userRole); 

    }
}

