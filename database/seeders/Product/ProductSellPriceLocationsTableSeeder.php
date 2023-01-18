<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductSellPriceLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productSellPriceLocations')->delete();
        
        \DB::table('productSellPriceLocations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productSellId' => 16,
                'locationId' => 1,
                'price' => '25000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            1 => 
            array (
                'id' => 2,
                'productSellId' => 16,
                'locationId' => 1,
                'price' => '14500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            2 => 
            array (
                'id' => 3,
                'productSellId' => 17,
                'locationId' => 1,
                'price' => '25000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            3 => 
            array (
                'id' => 4,
                'productSellId' => 17,
                'locationId' => 1,
                'price' => '14500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            4 => 
            array (
                'id' => 5,
                'productSellId' => 18,
                'locationId' => 1,
                'price' => '25000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
            5 => 
            array (
                'id' => 6,
                'productSellId' => 18,
                'locationId' => 1,
                'price' => '14500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:47',
                'updated_at' => '2023-01-19 00:55:47',
            ),
        ));
        
        
    }
}