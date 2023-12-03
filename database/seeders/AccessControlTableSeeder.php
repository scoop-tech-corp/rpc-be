<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccessControlTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('accessControl')->delete();
        
        \DB::table('accessControl')->insert(array (
            0 => 
            array (
                'id' => 1,
                'menuListId' => 1,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            1 => 
            array (
                'id' => 2,
                'menuListId' => 1,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            2 => 
            array (
                'id' => 3,
                'menuListId' => 1,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            3 => 
            array (
                'id' => 4,
                'menuListId' => 1,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            4 => 
            array (
                'id' => 5,
                'menuListId' => 1,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            5 => 
            array (
                'id' => 6,
                'menuListId' => 1,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            6 => 
            array (
                'id' => 7,
                'menuListId' => 1,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            7 => 
            array (
                'id' => 8,
                'menuListId' => 2,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            8 => 
            array (
                'id' => 9,
                'menuListId' => 2,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            9 => 
            array (
                'id' => 10,
                'menuListId' => 2,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            10 => 
            array (
                'id' => 11,
                'menuListId' => 2,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            11 => 
            array (
                'id' => 12,
                'menuListId' => 2,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            12 => 
            array (
                'id' => 13,
                'menuListId' => 2,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            13 => 
            array (
                'id' => 14,
                'menuListId' => 2,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            14 => 
            array (
                'id' => 15,
                'menuListId' => 3,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            15 => 
            array (
                'id' => 16,
                'menuListId' => 3,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            16 => 
            array (
                'id' => 17,
                'menuListId' => 3,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            17 => 
            array (
                'id' => 18,
                'menuListId' => 3,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            18 => 
            array (
                'id' => 19,
                'menuListId' => 3,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            19 => 
            array (
                'id' => 20,
                'menuListId' => 3,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            20 => 
            array (
                'id' => 21,
                'menuListId' => 3,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            21 => 
            array (
                'id' => 22,
                'menuListId' => 4,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            22 => 
            array (
                'id' => 23,
                'menuListId' => 4,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            23 => 
            array (
                'id' => 24,
                'menuListId' => 4,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            24 => 
            array (
                'id' => 25,
                'menuListId' => 4,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            25 => 
            array (
                'id' => 26,
                'menuListId' => 4,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            26 => 
            array (
                'id' => 27,
                'menuListId' => 4,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            27 => 
            array (
                'id' => 28,
                'menuListId' => 4,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            28 => 
            array (
                'id' => 29,
                'menuListId' => 5,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            29 => 
            array (
                'id' => 30,
                'menuListId' => 5,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            30 => 
            array (
                'id' => 31,
                'menuListId' => 5,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            31 => 
            array (
                'id' => 32,
                'menuListId' => 5,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            32 => 
            array (
                'id' => 33,
                'menuListId' => 5,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            33 => 
            array (
                'id' => 34,
                'menuListId' => 5,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            34 => 
            array (
                'id' => 35,
                'menuListId' => 5,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            35 => 
            array (
                'id' => 36,
                'menuListId' => 6,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            36 => 
            array (
                'id' => 37,
                'menuListId' => 6,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            37 => 
            array (
                'id' => 38,
                'menuListId' => 6,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            38 => 
            array (
                'id' => 39,
                'menuListId' => 6,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            39 => 
            array (
                'id' => 40,
                'menuListId' => 6,
                'roleId' => 5,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            40 => 
            array (
                'id' => 41,
                'menuListId' => 6,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            41 => 
            array (
                'id' => 42,
                'menuListId' => 6,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            42 => 
            array (
                'id' => 43,
                'menuListId' => 7,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            43 => 
            array (
                'id' => 44,
                'menuListId' => 7,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            44 => 
            array (
                'id' => 45,
                'menuListId' => 7,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            45 => 
            array (
                'id' => 46,
                'menuListId' => 7,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            46 => 
            array (
                'id' => 47,
                'menuListId' => 7,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            47 => 
            array (
                'id' => 48,
                'menuListId' => 7,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            48 => 
            array (
                'id' => 49,
                'menuListId' => 7,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            49 => 
            array (
                'id' => 50,
                'menuListId' => 8,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            50 => 
            array (
                'id' => 51,
                'menuListId' => 8,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            51 => 
            array (
                'id' => 52,
                'menuListId' => 8,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            52 => 
            array (
                'id' => 53,
                'menuListId' => 8,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            53 => 
            array (
                'id' => 54,
                'menuListId' => 8,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            54 => 
            array (
                'id' => 55,
                'menuListId' => 8,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            55 => 
            array (
                'id' => 56,
                'menuListId' => 8,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            56 => 
            array (
                'id' => 57,
                'menuListId' => 9,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            57 => 
            array (
                'id' => 58,
                'menuListId' => 9,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            58 => 
            array (
                'id' => 59,
                'menuListId' => 9,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            59 => 
            array (
                'id' => 60,
                'menuListId' => 9,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            60 => 
            array (
                'id' => 61,
                'menuListId' => 9,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            61 => 
            array (
                'id' => 62,
                'menuListId' => 9,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            62 => 
            array (
                'id' => 63,
                'menuListId' => 9,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            63 => 
            array (
                'id' => 64,
                'menuListId' => 10,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            64 => 
            array (
                'id' => 65,
                'menuListId' => 10,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            65 => 
            array (
                'id' => 66,
                'menuListId' => 10,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            66 => 
            array (
                'id' => 67,
                'menuListId' => 10,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            67 => 
            array (
                'id' => 68,
                'menuListId' => 10,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            68 => 
            array (
                'id' => 69,
                'menuListId' => 10,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            69 => 
            array (
                'id' => 70,
                'menuListId' => 10,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            70 => 
            array (
                'id' => 71,
                'menuListId' => 11,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            71 => 
            array (
                'id' => 72,
                'menuListId' => 11,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            72 => 
            array (
                'id' => 73,
                'menuListId' => 11,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            73 => 
            array (
                'id' => 74,
                'menuListId' => 11,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            74 => 
            array (
                'id' => 75,
                'menuListId' => 11,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            75 => 
            array (
                'id' => 76,
                'menuListId' => 11,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            76 => 
            array (
                'id' => 77,
                'menuListId' => 11,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            77 => 
            array (
                'id' => 78,
                'menuListId' => 12,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            78 => 
            array (
                'id' => 79,
                'menuListId' => 12,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            79 => 
            array (
                'id' => 80,
                'menuListId' => 12,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            80 => 
            array (
                'id' => 81,
                'menuListId' => 12,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            81 => 
            array (
                'id' => 82,
                'menuListId' => 12,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            82 => 
            array (
                'id' => 83,
                'menuListId' => 12,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            83 => 
            array (
                'id' => 84,
                'menuListId' => 12,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            84 => 
            array (
                'id' => 85,
                'menuListId' => 13,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            85 => 
            array (
                'id' => 86,
                'menuListId' => 13,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            86 => 
            array (
                'id' => 87,
                'menuListId' => 13,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            87 => 
            array (
                'id' => 88,
                'menuListId' => 13,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            88 => 
            array (
                'id' => 89,
                'menuListId' => 13,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            89 => 
            array (
                'id' => 90,
                'menuListId' => 13,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            90 => 
            array (
                'id' => 91,
                'menuListId' => 13,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            91 => 
            array (
                'id' => 92,
                'menuListId' => 14,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            92 => 
            array (
                'id' => 93,
                'menuListId' => 14,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            93 => 
            array (
                'id' => 94,
                'menuListId' => 14,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            94 => 
            array (
                'id' => 95,
                'menuListId' => 14,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            95 => 
            array (
                'id' => 96,
                'menuListId' => 14,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            96 => 
            array (
                'id' => 97,
                'menuListId' => 14,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            97 => 
            array (
                'id' => 98,
                'menuListId' => 14,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            98 => 
            array (
                'id' => 99,
                'menuListId' => 15,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            99 => 
            array (
                'id' => 100,
                'menuListId' => 15,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            100 => 
            array (
                'id' => 101,
                'menuListId' => 15,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            101 => 
            array (
                'id' => 102,
                'menuListId' => 15,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            102 => 
            array (
                'id' => 103,
                'menuListId' => 15,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            103 => 
            array (
                'id' => 104,
                'menuListId' => 15,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            104 => 
            array (
                'id' => 105,
                'menuListId' => 15,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            105 => 
            array (
                'id' => 106,
                'menuListId' => 16,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            106 => 
            array (
                'id' => 107,
                'menuListId' => 16,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            107 => 
            array (
                'id' => 108,
                'menuListId' => 16,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            108 => 
            array (
                'id' => 109,
                'menuListId' => 16,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            109 => 
            array (
                'id' => 110,
                'menuListId' => 16,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            110 => 
            array (
                'id' => 111,
                'menuListId' => 16,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            111 => 
            array (
                'id' => 112,
                'menuListId' => 16,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            112 => 
            array (
                'id' => 113,
                'menuListId' => 17,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            113 => 
            array (
                'id' => 114,
                'menuListId' => 17,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            114 => 
            array (
                'id' => 115,
                'menuListId' => 17,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            115 => 
            array (
                'id' => 116,
                'menuListId' => 17,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            116 => 
            array (
                'id' => 117,
                'menuListId' => 17,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            117 => 
            array (
                'id' => 118,
                'menuListId' => 17,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            118 => 
            array (
                'id' => 119,
                'menuListId' => 17,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            119 => 
            array (
                'id' => 120,
                'menuListId' => 18,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            120 => 
            array (
                'id' => 121,
                'menuListId' => 18,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            121 => 
            array (
                'id' => 122,
                'menuListId' => 18,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            122 => 
            array (
                'id' => 123,
                'menuListId' => 18,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            123 => 
            array (
                'id' => 124,
                'menuListId' => 18,
                'roleId' => 5,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            124 => 
            array (
                'id' => 125,
                'menuListId' => 18,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            125 => 
            array (
                'id' => 126,
                'menuListId' => 18,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            126 => 
            array (
                'id' => 127,
                'menuListId' => 19,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            127 => 
            array (
                'id' => 128,
                'menuListId' => 19,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            128 => 
            array (
                'id' => 129,
                'menuListId' => 19,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            129 => 
            array (
                'id' => 130,
                'menuListId' => 19,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            130 => 
            array (
                'id' => 131,
                'menuListId' => 19,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            131 => 
            array (
                'id' => 132,
                'menuListId' => 19,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            132 => 
            array (
                'id' => 133,
                'menuListId' => 19,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            133 => 
            array (
                'id' => 134,
                'menuListId' => 20,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            134 => 
            array (
                'id' => 135,
                'menuListId' => 20,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            135 => 
            array (
                'id' => 136,
                'menuListId' => 20,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            136 => 
            array (
                'id' => 137,
                'menuListId' => 20,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            137 => 
            array (
                'id' => 138,
                'menuListId' => 20,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            138 => 
            array (
                'id' => 139,
                'menuListId' => 20,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            139 => 
            array (
                'id' => 140,
                'menuListId' => 20,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            140 => 
            array (
                'id' => 141,
                'menuListId' => 21,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            141 => 
            array (
                'id' => 142,
                'menuListId' => 21,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            142 => 
            array (
                'id' => 143,
                'menuListId' => 21,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            143 => 
            array (
                'id' => 144,
                'menuListId' => 21,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            144 => 
            array (
                'id' => 145,
                'menuListId' => 21,
                'roleId' => 5,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            145 => 
            array (
                'id' => 146,
                'menuListId' => 21,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            146 => 
            array (
                'id' => 147,
                'menuListId' => 21,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            147 => 
            array (
                'id' => 148,
                'menuListId' => 22,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            148 => 
            array (
                'id' => 149,
                'menuListId' => 22,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            149 => 
            array (
                'id' => 150,
                'menuListId' => 22,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            150 => 
            array (
                'id' => 151,
                'menuListId' => 22,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            151 => 
            array (
                'id' => 152,
                'menuListId' => 22,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            152 => 
            array (
                'id' => 153,
                'menuListId' => 22,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            153 => 
            array (
                'id' => 154,
                'menuListId' => 22,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            154 => 
            array (
                'id' => 155,
                'menuListId' => 23,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            155 => 
            array (
                'id' => 156,
                'menuListId' => 23,
                'roleId' => 2,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            156 => 
            array (
                'id' => 157,
                'menuListId' => 23,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            157 => 
            array (
                'id' => 158,
                'menuListId' => 23,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            158 => 
            array (
                'id' => 159,
                'menuListId' => 23,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            159 => 
            array (
                'id' => 160,
                'menuListId' => 23,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            160 => 
            array (
                'id' => 161,
                'menuListId' => 23,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            161 => 
            array (
                'id' => 162,
                'menuListId' => 24,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            162 => 
            array (
                'id' => 163,
                'menuListId' => 24,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            163 => 
            array (
                'id' => 164,
                'menuListId' => 24,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            164 => 
            array (
                'id' => 165,
                'menuListId' => 24,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            165 => 
            array (
                'id' => 166,
                'menuListId' => 24,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            166 => 
            array (
                'id' => 167,
                'menuListId' => 24,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            167 => 
            array (
                'id' => 168,
                'menuListId' => 24,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            168 => 
            array (
                'id' => 169,
                'menuListId' => 25,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            169 => 
            array (
                'id' => 170,
                'menuListId' => 25,
                'roleId' => 2,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            170 => 
            array (
                'id' => 171,
                'menuListId' => 25,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            171 => 
            array (
                'id' => 172,
                'menuListId' => 25,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            172 => 
            array (
                'id' => 173,
                'menuListId' => 25,
                'roleId' => 5,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            173 => 
            array (
                'id' => 174,
                'menuListId' => 25,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            174 => 
            array (
                'id' => 175,
                'menuListId' => 25,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            175 => 
            array (
                'id' => 176,
                'menuListId' => 26,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            176 => 
            array (
                'id' => 177,
                'menuListId' => 26,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            177 => 
            array (
                'id' => 178,
                'menuListId' => 26,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            178 => 
            array (
                'id' => 179,
                'menuListId' => 26,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            179 => 
            array (
                'id' => 180,
                'menuListId' => 26,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            180 => 
            array (
                'id' => 181,
                'menuListId' => 26,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            181 => 
            array (
                'id' => 182,
                'menuListId' => 26,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            182 => 
            array (
                'id' => 183,
                'menuListId' => 27,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            183 => 
            array (
                'id' => 184,
                'menuListId' => 27,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            184 => 
            array (
                'id' => 185,
                'menuListId' => 27,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            185 => 
            array (
                'id' => 186,
                'menuListId' => 27,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            186 => 
            array (
                'id' => 187,
                'menuListId' => 27,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            187 => 
            array (
                'id' => 188,
                'menuListId' => 27,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            188 => 
            array (
                'id' => 189,
                'menuListId' => 27,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            189 => 
            array (
                'id' => 190,
                'menuListId' => 28,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            190 => 
            array (
                'id' => 191,
                'menuListId' => 28,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            191 => 
            array (
                'id' => 192,
                'menuListId' => 28,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            192 => 
            array (
                'id' => 193,
                'menuListId' => 28,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            193 => 
            array (
                'id' => 194,
                'menuListId' => 28,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            194 => 
            array (
                'id' => 195,
                'menuListId' => 28,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            195 => 
            array (
                'id' => 196,
                'menuListId' => 28,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            196 => 
            array (
                'id' => 197,
                'menuListId' => 29,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            197 => 
            array (
                'id' => 198,
                'menuListId' => 29,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            198 => 
            array (
                'id' => 199,
                'menuListId' => 29,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            199 => 
            array (
                'id' => 200,
                'menuListId' => 29,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            200 => 
            array (
                'id' => 201,
                'menuListId' => 29,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            201 => 
            array (
                'id' => 202,
                'menuListId' => 29,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            202 => 
            array (
                'id' => 203,
                'menuListId' => 29,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            203 => 
            array (
                'id' => 204,
                'menuListId' => 30,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            204 => 
            array (
                'id' => 205,
                'menuListId' => 30,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            205 => 
            array (
                'id' => 206,
                'menuListId' => 30,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            206 => 
            array (
                'id' => 207,
                'menuListId' => 30,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            207 => 
            array (
                'id' => 208,
                'menuListId' => 30,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            208 => 
            array (
                'id' => 209,
                'menuListId' => 30,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            209 => 
            array (
                'id' => 210,
                'menuListId' => 30,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            210 => 
            array (
                'id' => 211,
                'menuListId' => 31,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            211 => 
            array (
                'id' => 212,
                'menuListId' => 31,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            212 => 
            array (
                'id' => 213,
                'menuListId' => 31,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            213 => 
            array (
                'id' => 214,
                'menuListId' => 31,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            214 => 
            array (
                'id' => 215,
                'menuListId' => 31,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            215 => 
            array (
                'id' => 216,
                'menuListId' => 31,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            216 => 
            array (
                'id' => 217,
                'menuListId' => 31,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            217 => 
            array (
                'id' => 218,
                'menuListId' => 32,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            218 => 
            array (
                'id' => 219,
                'menuListId' => 32,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            219 => 
            array (
                'id' => 220,
                'menuListId' => 32,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            220 => 
            array (
                'id' => 221,
                'menuListId' => 32,
                'roleId' => 4,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            221 => 
            array (
                'id' => 222,
                'menuListId' => 32,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            222 => 
            array (
                'id' => 223,
                'menuListId' => 32,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            223 => 
            array (
                'id' => 224,
                'menuListId' => 32,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            224 => 
            array (
                'id' => 225,
                'menuListId' => 33,
                'roleId' => 1,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            225 => 
            array (
                'id' => 226,
                'menuListId' => 33,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            226 => 
            array (
                'id' => 227,
                'menuListId' => 33,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            227 => 
            array (
                'id' => 228,
                'menuListId' => 33,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            228 => 
            array (
                'id' => 229,
                'menuListId' => 33,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            229 => 
            array (
                'id' => 230,
                'menuListId' => 33,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            230 => 
            array (
                'id' => 231,
                'menuListId' => 33,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            231 => 
            array (
                'id' => 232,
                'menuListId' => 34,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            232 => 
            array (
                'id' => 233,
                'menuListId' => 34,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            233 => 
            array (
                'id' => 234,
                'menuListId' => 34,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            234 => 
            array (
                'id' => 235,
                'menuListId' => 34,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            235 => 
            array (
                'id' => 236,
                'menuListId' => 34,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            236 => 
            array (
                'id' => 237,
                'menuListId' => 34,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            237 => 
            array (
                'id' => 238,
                'menuListId' => 34,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            238 => 
            array (
                'id' => 239,
                'menuListId' => 35,
                'roleId' => 1,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            239 => 
            array (
                'id' => 240,
                'menuListId' => 35,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            240 => 
            array (
                'id' => 241,
                'menuListId' => 35,
                'roleId' => 3,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            241 => 
            array (
                'id' => 242,
                'menuListId' => 35,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            242 => 
            array (
                'id' => 243,
                'menuListId' => 35,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            243 => 
            array (
                'id' => 244,
                'menuListId' => 35,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            244 => 
            array (
                'id' => 245,
                'menuListId' => 35,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            245 => 
            array (
                'id' => 246,
                'menuListId' => 36,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            246 => 
            array (
                'id' => 247,
                'menuListId' => 36,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            247 => 
            array (
                'id' => 248,
                'menuListId' => 36,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            248 => 
            array (
                'id' => 249,
                'menuListId' => 36,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            249 => 
            array (
                'id' => 250,
                'menuListId' => 36,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            250 => 
            array (
                'id' => 251,
                'menuListId' => 36,
                'roleId' => 6,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            251 => 
            array (
                'id' => 252,
                'menuListId' => 36,
                'roleId' => 7,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            252 => 
            array (
                'id' => 253,
                'menuListId' => 37,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            253 => 
            array (
                'id' => 254,
                'menuListId' => 37,
                'roleId' => 2,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            254 => 
            array (
                'id' => 255,
                'menuListId' => 37,
                'roleId' => 3,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            255 => 
            array (
                'id' => 256,
                'menuListId' => 37,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            256 => 
            array (
                'id' => 257,
                'menuListId' => 37,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            257 => 
            array (
                'id' => 258,
                'menuListId' => 37,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            258 => 
            array (
                'id' => 259,
                'menuListId' => 37,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            259 => 
            array (
                'id' => 260,
                'menuListId' => 38,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            260 => 
            array (
                'id' => 261,
                'menuListId' => 38,
                'roleId' => 2,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            261 => 
            array (
                'id' => 262,
                'menuListId' => 38,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            262 => 
            array (
                'id' => 263,
                'menuListId' => 38,
                'roleId' => 4,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            263 => 
            array (
                'id' => 264,
                'menuListId' => 38,
                'roleId' => 5,
                'accessTypeId' => 2,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            264 => 
            array (
                'id' => 265,
                'menuListId' => 38,
                'roleId' => 6,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            265 => 
            array (
                'id' => 266,
                'menuListId' => 38,
                'roleId' => 7,
                'accessTypeId' => 4,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 12:12:35',
                'updated_at' => '2023-08-20 12:12:35',
            ),
            266 => 
            array (
                'id' => 267,
                'menuListId' => 41,
                'roleId' => 1,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            267 => 
            array (
                'id' => 268,
                'menuListId' => 41,
                'roleId' => 2,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            268 => 
            array (
                'id' => 269,
                'menuListId' => 41,
                'roleId' => 3,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            269 => 
            array (
                'id' => 270,
                'menuListId' => 41,
                'roleId' => 4,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            270 => 
            array (
                'id' => 271,
                'menuListId' => 41,
                'roleId' => 5,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            271 => 
            array (
                'id' => 272,
                'menuListId' => 41,
                'roleId' => 6,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            272 => 
            array (
                'id' => 273,
                'menuListId' => 41,
                'roleId' => 7,
                'accessTypeId' => 3,
                'isDeleted' => 0,
                'created_at' => '2023-08-20 13:20:04',
                'updated_at' => '2023-08-20 13:20:04',
            ),
            273 => 
            array (
                'id' => 275,
                'menuListId' => 42,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            274 => 
            array (
                'id' => 276,
                'menuListId' => 42,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            275 => 
            array (
                'id' => 277,
                'menuListId' => 42,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            276 => 
            array (
                'id' => 278,
                'menuListId' => 42,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            277 => 
            array (
                'id' => 279,
                'menuListId' => 42,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            278 => 
            array (
                'id' => 280,
                'menuListId' => 42,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            279 => 
            array (
                'id' => 281,
                'menuListId' => 42,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            280 => 
            array (
                'id' => 282,
                'menuListId' => 43,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            281 => 
            array (
                'id' => 283,
                'menuListId' => 43,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            282 => 
            array (
                'id' => 284,
                'menuListId' => 43,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            283 => 
            array (
                'id' => 285,
                'menuListId' => 43,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            284 => 
            array (
                'id' => 286,
                'menuListId' => 43,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            285 => 
            array (
                'id' => 287,
                'menuListId' => 43,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            286 => 
            array (
                'id' => 288,
                'menuListId' => 43,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            287 => 
            array (
                'id' => 289,
                'menuListId' => 44,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            288 => 
            array (
                'id' => 290,
                'menuListId' => 44,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            289 => 
            array (
                'id' => 291,
                'menuListId' => 44,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            290 => 
            array (
                'id' => 292,
                'menuListId' => 44,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            291 => 
            array (
                'id' => 293,
                'menuListId' => 44,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            292 => 
            array (
                'id' => 294,
                'menuListId' => 44,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            293 => 
            array (
                'id' => 295,
                'menuListId' => 44,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            294 => 
            array (
                'id' => 296,
                'menuListId' => 45,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            295 => 
            array (
                'id' => 297,
                'menuListId' => 45,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            296 => 
            array (
                'id' => 298,
                'menuListId' => 45,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            297 => 
            array (
                'id' => 299,
                'menuListId' => 45,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            298 => 
            array (
                'id' => 300,
                'menuListId' => 45,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            299 => 
            array (
                'id' => 301,
                'menuListId' => 45,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            300 => 
            array (
                'id' => 302,
                'menuListId' => 45,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            301 => 
            array (
                'id' => 303,
                'menuListId' => 46,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            302 => 
            array (
                'id' => 304,
                'menuListId' => 46,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            303 => 
            array (
                'id' => 305,
                'menuListId' => 46,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            304 => 
            array (
                'id' => 306,
                'menuListId' => 46,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            305 => 
            array (
                'id' => 307,
                'menuListId' => 46,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            306 => 
            array (
                'id' => 308,
                'menuListId' => 46,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            307 => 
            array (
                'id' => 309,
                'menuListId' => 46,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            308 => 
            array (
                'id' => 310,
                'menuListId' => 47,
                'roleId' => 1,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            309 => 
            array (
                'id' => 311,
                'menuListId' => 47,
                'roleId' => 2,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            310 => 
            array (
                'id' => 312,
                'menuListId' => 47,
                'roleId' => 3,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            311 => 
            array (
                'id' => 313,
                'menuListId' => 47,
                'roleId' => 4,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            312 => 
            array (
                'id' => 314,
                'menuListId' => 47,
                'roleId' => 5,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            313 => 
            array (
                'id' => 315,
                'menuListId' => 47,
                'roleId' => 6,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            314 => 
            array (
                'id' => 316,
                'menuListId' => 47,
                'roleId' => 7,
                'accessTypeId' => 1,
                'isDeleted' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}