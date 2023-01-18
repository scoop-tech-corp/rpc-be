<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductClinicCustomerGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productClinicCustomerGroups')->delete();
        
        \DB::table('productClinicCustomerGroups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productClinicId' => 22,
                'customerGroupId' => 1,
                'price' => '10000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            1 => 
            array (
                'id' => 2,
                'productClinicId' => 22,
                'customerGroupId' => 1,
                'price' => '4500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            2 => 
            array (
                'id' => 3,
                'productClinicId' => 23,
                'customerGroupId' => 1,
                'price' => '10000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            3 => 
            array (
                'id' => 4,
                'productClinicId' => 23,
                'customerGroupId' => 1,
                'price' => '4500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            4 => 
            array (
                'id' => 5,
                'productClinicId' => 24,
                'customerGroupId' => 1,
                'price' => '10000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
            5 => 
            array (
                'id' => 6,
                'productClinicId' => 24,
                'customerGroupId' => 1,
                'price' => '4500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:58:42',
                'updated_at' => '2023-01-19 00:58:42',
            ),
        ));
        
        
    }
}