<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductSellCustomerGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productSellCustomerGroups')->delete();
        
        \DB::table('productSellCustomerGroups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productSellId' => 13,
                'customerGroupId' => 1,
                'price' => '10000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            1 => 
            array (
                'id' => 2,
                'productSellId' => 13,
                'customerGroupId' => 1,
                'price' => '4500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            2 => 
            array (
                'id' => 3,
                'productSellId' => 14,
                'customerGroupId' => 1,
                'price' => '10000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            3 => 
            array (
                'id' => 4,
                'productSellId' => 14,
                'customerGroupId' => 1,
                'price' => '4500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            4 => 
            array (
                'id' => 5,
                'productSellId' => 15,
                'customerGroupId' => 1,
                'price' => '10000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
            5 => 
            array (
                'id' => 6,
                'productSellId' => 15,
                'customerGroupId' => 1,
                'price' => '4500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:55:27',
                'updated_at' => '2023-01-19 00:55:27',
            ),
        ));
        
        
    }
}