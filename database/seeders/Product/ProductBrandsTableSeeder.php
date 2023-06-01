<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductBrandsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productBrands')->delete();
        
        \DB::table('productBrands')->insert(array (
            0 => 
            array (
                'id' => 1,
                'brandName' => 'brand 1',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:13',
                'updated_at' => '2023-01-14 22:55:13',
            ),
            1 => 
            array (
                'id' => 2,
                'brandName' => 'brand 2',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:15',
                'updated_at' => '2023-01-14 22:55:15',
            ),
            2 => 
            array (
                'id' => 3,
                'brandName' => 'brand 3',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:15',
                'updated_at' => '2023-01-14 22:55:15',
            ),
            3 => 
            array (
                'id' => 4,
                'brandName' => 'brand 4',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:17',
                'updated_at' => '2023-01-14 22:55:17',
            ),
            4 => 
            array (
                'id' => 5,
                'brandName' => 'brand 5',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:18',
                'updated_at' => '2023-01-14 22:55:18',
            ),
            5 => 
            array (
                'id' => 6,
                'brandName' => 'brand 6',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:19',
                'updated_at' => '2023-01-14 22:55:19',
            ),
            6 => 
            array (
                'id' => 7,
                'brandName' => 'ACIS',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:05:43',
                'updated_at' => '2023-01-19 01:05:43',
            ),
            7 => 
            array (
                'id' => 8,
                'brandName' => 'PT EVTI',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:05:51',
                'updated_at' => '2023-01-19 01:05:51',
            ),
            8 => 
            array (
                'id' => 9,
                'brandName' => 'PT Berkat Jaya',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:06:03',
                'updated_at' => '2023-01-19 01:06:03',
            ),
            9 => 
            array (
                'id' => 10,
                'brandName' => 'Royal Canin Indonesia',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:06:10',
                'updated_at' => '2023-01-19 01:06:10',
            ),
            10 => 
            array (
                'id' => 11,
                'brandName' => 'Nutrious',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:06:16',
                'updated_at' => '2023-01-19 01:06:16',
            ),
        ));
        
        
    }
}