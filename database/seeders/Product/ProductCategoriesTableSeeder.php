<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productCategories')->delete();
        
        \DB::table('productCategories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'categoryName' => 'bius 5',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:01:11',
                'updated_at' => '2023-01-19 01:01:11',
            ),
            1 => 
            array (
                'id' => 2,
                'categoryName' => 'bius 1',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:01:12',
                'updated_at' => '2023-01-19 01:01:12',
            ),
            2 => 
            array (
                'id' => 3,
                'categoryName' => 'obat kering',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:01:16',
                'updated_at' => '2023-01-19 01:01:16',
            ),
            3 => 
            array (
                'id' => 4,
                'categoryName' => 'obat moinum',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:01:21',
                'updated_at' => '2023-01-19 01:01:21',
            ),
            4 => 
            array (
                'id' => 5,
                'categoryName' => 'obat minum',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:01:23',
                'updated_at' => '2023-01-19 01:01:23',
            ),
            5 => 
            array (
                'id' => 6,
                'categoryName' => 'barang tajam',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:16',
                'updated_at' => '2023-01-19 01:04:16',
            ),
            6 => 
            array (
                'id' => 7,
                'categoryName' => 'barang tumpul',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:19',
                'updated_at' => '2023-01-19 01:04:19',
            ),
            7 => 
            array (
                'id' => 8,
                'categoryName' => 'barang kecil',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:22',
                'updated_at' => '2023-01-19 01:04:22',
            ),
            8 => 
            array (
                'id' => 9,
                'categoryName' => 'barang sedang',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:30',
                'updated_at' => '2023-01-19 01:04:30',
            ),
            9 => 
            array (
                'id' => 10,
                'categoryName' => 'barang besar',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:35',
                'updated_at' => '2023-01-19 01:04:35',
            ),
            10 => 
            array (
                'id' => 11,
                'categoryName' => 'untuk customer',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:40',
                'updated_at' => '2023-01-19 01:04:40',
            ),
            11 => 
            array (
                'id' => 12,
                'categoryName' => 'dapat di daur ulang',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:04:54',
                'updated_at' => '2023-01-19 01:04:54',
            ),
            12 => 
            array (
                'id' => 13,
                'categoryName' => 'mudah terbakar',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:05:02',
                'updated_at' => '2023-01-19 01:05:02',
            ),
            13 => 
            array (
                'id' => 14,
                'categoryName' => 'dapat berasap',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:05:11',
                'updated_at' => '2023-01-19 01:05:11',
            ),
            14 => 
            array (
                'id' => 15,
                'categoryName' => 'arsenik',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:05:18',
                'updated_at' => '2023-01-19 01:05:18',
            ),
        ));
        
        
    }
}