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
                'created_at' => now()
            ],
            [
                'value' => 'Usage',
                'name' => 'Secondary',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Usage',
                'name' => 'Whatsap',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Telephone',
                'name' => 'Rumah',
                'isDeleted' => 0,
                'created_at' => now()
            ],

            [
                'value' => 'Telephone',
                'name' => 'Whatshap',
                'isDeleted' => 0,
                'created_at' => now()
            ],

            [
                'value' => 'Telephone',
                'name' => 'Rumah',
                'isDeleted' => 0,
                'created_at' => now()
            ],

            [
                'value' => 'Messenger',
                'name' => 'Gmail',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Messenger',
                'name' => 'Yahoo',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Messenger',
                'name' => 'GooglePlus',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Messenger',
                'name' => 'Bing',
                'isDeleted' => 0,
                'created_at' => now()
            ],
        ];

        DB::table('data_static')->insert($data); 

        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'office@gmail.com',
            'password' => bcrypt("123"),
            'role' => '1',
            'isDeleted' => '0',
            'created_at' =>now(),
            'updated_at' =>now(),
        ]);


        
        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'wahyudidanny23@gmail.com',
            'password' => bcrypt("123"),
            'role' => '1',
            'isDeleted' => '0',
            'created_at' =>now(),
            'updated_at' =>now(),
        ]);


        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'admin@gmail.com',
            'password' => bcrypt("123"),
            'role' => '1',
            'isDeleted' => '0',
            'created_at' =>now(),
            'updated_at' =>now(),
        ]);


        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'doctor@gmail.com',
            'password' => bcrypt("123"),
            'role' => '1',
            'isDeleted' => '0',
            'created_at' =>now(),
            'updated_at' =>now(),
        ]);

        DB::table('users')->insert([
            'name' => 'DW',
            'email' => 'staff@gmail.com',
            'password' => bcrypt("123"),
            'role' => '1',
            'isDeleted' => '0',
            'created_at' =>now(),
            'updated_at' =>now(),
        ]);


    }
}

