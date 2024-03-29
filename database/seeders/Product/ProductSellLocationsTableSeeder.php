<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductSellLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productSellLocations')->delete();
        
        \DB::table('productSellLocations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productSellId' => 1,
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
                'created_at' => '2023-01-15 14:20:50',
                'updated_at' => '2023-01-15 14:20:50',
            ),
            1 => 
            array (
                'id' => 2,
                'productSellId' => 2,
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
                'created_at' => '2023-01-15 14:20:50',
                'updated_at' => '2023-01-15 14:20:50',
            ),
            2 => 
            array (
                'id' => 3,
                'productSellId' => 3,
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
                'created_at' => '2023-01-15 14:20:50',
                'updated_at' => '2023-01-15 14:20:50',
            ),
            3 => 
            array (
                'id' => 4,
                'productSellId' => 4,
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
                'created_at' => '2023-01-15 14:20:52',
                'updated_at' => '2023-01-15 14:20:52',
            ),
            4 => 
            array (
                'id' => 5,
                'productSellId' => 5,
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
                'created_at' => '2023-01-15 14:20:52',
                'updated_at' => '2023-01-15 14:20:52',
            ),
            5 => 
            array (
                'id' => 6,
                'productSellId' => 6,
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
                'created_at' => '2023-01-15 14:20:52',
                'updated_at' => '2023-01-15 14:20:52',
            ),
            6 => 
            array (
                'id' => 7,
                'productSellId' => 7,
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
                'created_at' => '2023-01-15 14:20:55',
                'updated_at' => '2023-01-15 14:20:55',
            ),
            7 => 
            array (
                'id' => 8,
                'productSellId' => 8,
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
                'created_at' => '2023-01-15 14:20:55',
                'updated_at' => '2023-01-15 14:20:55',
            ),
            8 => 
            array (
                'id' => 9,
                'productSellId' => 9,
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
                'created_at' => '2023-01-15 14:20:55',
                'updated_at' => '2023-01-15 14:20:55',
            ),
            9 => 
            array (
                'id' => 10,
                'productSellId' => 10,
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
                'created_at' => '2023-01-19 00:54:58',
                'updated_at' => '2023-01-19 00:54:58',
            ),
            10 => 
            array (
                'id' => 11,
                'productSellId' => 11,
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
                'created_at' => '2023-01-19 00:54:58',
                'updated_at' => '2023-01-19 00:54:58',
            ),
            11 => 
            array (
                'id' => 12,
                'productSellId' => 12,
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
                'created_at' => '2023-01-19 00:54:58',
                'updated_at' => '2023-01-19 00:54:58',
            ),
            12 => 
            array (
                'id' => 13,
                'productSellId' => 13,
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
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            13 => 
            array (
                'id' => 14,
                'productSellId' => 14,
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
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            14 => 
            array (
                'id' => 15,
                'productSellId' => 15,
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
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            15 => 
            array (
                'id' => 16,
                'productSellId' => 16,
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
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            16 => 
            array (
                'id' => 17,
                'productSellId' => 17,
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
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            17 => 
            array (
                'id' => 18,
                'productSellId' => 18,
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
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            18 => 
            array (
                'id' => 19,
                'productSellId' => 19,
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
                'created_at' => '2023-01-19 00:56:17',
                'updated_at' => '2023-01-19 00:56:17',
            ),
            19 => 
            array (
                'id' => 20,
                'productSellId' => 20,
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
                'created_at' => '2023-01-19 00:56:17',
                'updated_at' => '2023-01-19 00:56:17',
            ),
            20 => 
            array (
                'id' => 21,
                'productSellId' => 21,
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
                'created_at' => '2023-01-19 00:56:17',
                'updated_at' => '2023-01-19 00:56:17',
            ),
        ));
        
        
    }
}