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
            'email' => 'yolo@gmail.com',
            'password' => bcrypt("111111"),
            'role' => 'admin',
            'created_at' =>'2022-08-30',
            'updated_at' =>'2022-08-30',
        ]);

    }
}

