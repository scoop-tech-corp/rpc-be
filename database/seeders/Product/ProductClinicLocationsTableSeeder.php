<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductClinicLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productClinicLocations')->delete();
        
        \DB::table('productClinicLocations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productClinicId' => 1,
                'locationId' => 7,
                'inStock' => 10,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:44',
                'updated_at' => '2023-01-14 22:55:44',
            ),
            1 => 
            array (
                'id' => 2,
                'productClinicId' => 2,
                'locationId' => 8,
                'inStock' => 10,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:44',
                'updated_at' => '2023-01-14 22:55:44',
            ),
            2 => 
            array (
                'id' => 3,
                'productClinicId' => 3,
                'locationId' => 1,
                'inStock' => 10,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:44',
                'updated_at' => '2023-01-14 22:55:44',
            ),
            3 => 
            array (
                'id' => 4,
                'productClinicId' => 4,
                'locationId' => 7,
                'inStock' => 10,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:54',
                'updated_at' => '2023-01-14 22:55:54',
            ),
            4 => 
            array (
                'id' => 5,
                'productClinicId' => 5,
                'locationId' => 8,
                'inStock' => 10,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:54',
                'updated_at' => '2023-01-14 22:55:54',
            ),
            5 => 
            array (
                'id' => 6,
                'productClinicId' => 6,
                'locationId' => 1,
                'inStock' => 10,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:54',
                'updated_at' => '2023-01-14 22:55:54',
            ),
            6 => 
            array (
                'id' => 7,
                'productClinicId' => 7,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:57:01',
                'updated_at' => '2023-01-14 22:57:01',
            ),
            7 => 
            array (
                'id' => 8,
                'productClinicId' => 8,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:57:01',
                'updated_at' => '2023-01-14 22:57:01',
            ),
            8 => 
            array (
                'id' => 9,
                'productClinicId' => 9,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:57:01',
                'updated_at' => '2023-01-14 22:57:01',
            ),
            9 => 
            array (
                'id' => 10,
                'productClinicId' => 10,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:38',
                'updated_at' => '2023-01-15 14:20:38',
            ),
            10 => 
            array (
                'id' => 11,
                'productClinicId' => 11,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:38',
                'updated_at' => '2023-01-15 14:20:38',
            ),
            11 => 
            array (
                'id' => 12,
                'productClinicId' => 12,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:38',
                'updated_at' => '2023-01-15 14:20:38',
            ),
            12 => 
            array (
                'id' => 13,
                'productClinicId' => 13,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:41',
                'updated_at' => '2023-01-15 14:20:41',
            ),
            13 => 
            array (
                'id' => 14,
                'productClinicId' => 14,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:41',
                'updated_at' => '2023-01-15 14:20:41',
            ),
            14 => 
            array (
                'id' => 15,
                'productClinicId' => 15,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:41',
                'updated_at' => '2023-01-15 14:20:41',
            ),
            15 => 
            array (
                'id' => 16,
                'productClinicId' => 16,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:46',
                'updated_at' => '2023-01-15 14:20:46',
            ),
            16 => 
            array (
                'id' => 17,
                'productClinicId' => 17,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:46',
                'updated_at' => '2023-01-15 14:20:46',
            ),
            17 => 
            array (
                'id' => 18,
                'productClinicId' => 18,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 14:20:46',
                'updated_at' => '2023-01-15 14:20:46',
            ),
            18 => 
            array (
                'id' => 19,
                'productClinicId' => 19,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:07',
                'updated_at' => '2023-01-19 00:58:07',
            ),
            19 => 
            array (
                'id' => 20,
                'productClinicId' => 20,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:07',
                'updated_at' => '2023-01-19 00:58:07',
            ),
            20 => 
            array (
                'id' => 21,
                'productClinicId' => 21,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:07',
                'updated_at' => '2023-01-19 00:58:07',
            ),
            21 => 
            array (
                'id' => 22,
                'productClinicId' => 22,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            22 => 
            array (
                'id' => 23,
                'productClinicId' => 23,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            23 => 
            array (
                'id' => 24,
                'productClinicId' => 24,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            24 => 
            array (
                'id' => 25,
                'productClinicId' => 25,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            25 => 
            array (
                'id' => 26,
                'productClinicId' => 26,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            26 => 
            array (
                'id' => 27,
                'productClinicId' => 27,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            27 => 
            array (
                'id' => 28,
                'productClinicId' => 28,
                'locationId' => 7,
                'inStock' => 3,
                'lowStock' => 5,
                'reStockLimit' => 10,
                'diffStock' => -2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:12',
                'updated_at' => '2023-01-19 00:59:12',
            ),
            28 => 
            array (
                'id' => 29,
                'productClinicId' => 29,
                'locationId' => 8,
                'inStock' => 2,
                'lowStock' => 5,
                'reStockLimit' => 20,
                'diffStock' => -3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:12',
                'updated_at' => '2023-01-19 00:59:12',
            ),
            29 => 
            array (
                'id' => 30,
                'productClinicId' => 30,
                'locationId' => 1,
                'inStock' => 4,
                'lowStock' => 5,
                'reStockLimit' => 8,
                'diffStock' => -1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:12',
                'updated_at' => '2023-01-19 00:59:12',
            ),
        ));
        
        
    }
}