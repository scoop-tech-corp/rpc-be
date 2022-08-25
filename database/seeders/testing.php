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

       // DB::table('data_static')->insert(

        $data = [
            [
                'value' => 'Pemakaian',
                'name' => 'Utama',
                'isDeleted' => 0
            ],
            [
                'value' => 'Pemakaian',
                'name' => 'Secondary',
                'isDeleted' => 0
            ],
            [
                'value' => 'Pemakaian',
                'name' => 'Whatsap',
                'isDeleted' => 0
            ],
            [
                'value' => 'Telepon',
                'name' => 'Rumah',
                'isDeleted' => 0
            ],

            [
                'value' => 'Telepon',
                'name' => 'Whatshap',
                'isDeleted' => 0
            ],

            [
                'value' => 'Telepon',
                'name' => 'Rumah',
                'isDeleted' => 0
            ],

            [
                'value' => 'Messenger',
                'name' => 'Gmail',
                'isDeleted' => 0
            ],
            [
                'value' => 'Messenger',
                'name' => 'Yahoo',
                'isDeleted' => 0
            ],
            [
                'value' => 'Messenger',
                'name' => 'GooglePlus',
                'isDeleted' => 0
            ],
            [
                'value' => 'Messenger',
                'name' => 'Bing',
                'isDeleted' => 0
            ],
        ];
       
        DB::table('data_static')->insert($data); // Query Builder approach


        // DB::table('locations')->insert([
        //     'locationName' => 'RPC Kelapa Gading',
        //     'isBranch' => 1,
        //     'status' => 1,
        //     'introduction' => 'just some introduction',
        //     'description' => 'some description',
        //     'image' =>'test.png',
        //     'imageTitle' =>'image RPC Kelapa Gading',
        //     'created_at' =>'2022-08-08',

        // ]);

        // DB::table('location_operational_hours_details')->insert([
        //     'codeLocation' => 1,
        //     'days_name' => 'Monday',
        //     'from_time' => '12:00 AM',
        //     'to_time' => '12:00 PM',
        //     'all_day' => 1,
        //     'created_at' =>'2022-08-08',
        // ]);

        // DB::table('location_operational_hours_details')->insert([
        //     'codeLocation' => 1,
        //     'days_name' => 'Tuesday',
        //     'from_time' => '12:00 AM',
        //     'to_time' => '12:00 PM',
        //     'all_day' => 1,
        //     'created_at' =>'2022-08-08',
        // ]);

        // DB::table('location_operational_hours_details')->insert([
        //     'codeLocation' => 1,
        //     'days_name' => 'Wednesday',
        //     'from_time' => '12:00 AM',
        //     'to_time' => '12:00 PM',
        //     'all_day' => 1,
        //     'created_at' =>'2022-08-08',
        // ]);

        // DB::table('locations_alamats_details')->insert([
        //     'codeLocation' => 1,
        //     'alamatJalan' => 'Jalan U 27 B Palmerah Barat 11480',
        //     'infoTambahan' => 'Patokan Nasi Goreng Kuning Arema',
        //     'kotaID' => '0001',
        //     'provinsiID' => '0002',
        //     'kodePos' =>'11480',
        //     'negara' =>'Indonesia',
        //     'parkir' =>1, // 1 true
        //     'pemakaian' =>'Tempat Tinggal',
        //     'created_at' =>'2022-08-08',
        // ]);

        // DB::table('locations_alamats_details')->insert([
        //     'codeLocation' => 1,
        //     'alamatJalan' => 'Jalan ITC Permata Hijau No 6',
        //     'infoTambahan' => 'Patokan Nasi Goreng Kuning Arema',
        //     'kotaID' => '0002',
        //     'provinsiID' => '0003',
        //     'kodePos' =>'11111',
        //     'negara' =>'Indonesia',
        //     'parkir' =>1, // 1 ada
        //     'pemakaian' =>'Tempat Tinggal',
        //     'created_at' =>'2022-08-08',
        // ]);

        // DB::table('location_operational_hours_details')->insert([
        //     'codeLocation' => 1,
        //     'days_name' => 'Tuesday',
        //     'from_time' => '12:00 AM',
        //     'to_time' => '12:00 PM',
        //     'all_day' => 1,
        //     'created_at' =>'2022-08-08',
        // ]);

        // DB::table('location_operational_hours_details')->insert([
        //     'codeLocation' => 1,
        //     'days_name' => 'Wednesday',
        //     'from_time' => '12:00 AM',
        //     'to_time' => '1:00 PM',
        //     'all_day' => 0,
        //     'created_at' =>'2022-08-08',
        // ]);

    }
}
