<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductBundleDetailsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productBundleDetails')->delete();
        
        \DB::table('productBundleDetails')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productBundleId' => 1,
                'productId' => 1,
                'quantity' => 2,
                'total' => '50000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:10:05',
                'updated_at' => '2023-01-19 01:10:05',
            ),
            1 => 
            array (
                'id' => 2,
                'productBundleId' => 1,
                'productId' => 2,
                'quantity' => 4,
                'total' => '40000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:10:05',
                'updated_at' => '2023-01-19 01:10:05',
            ),
            2 => 
            array (
                'id' => 3,
                'productBundleId' => 1,
                'productId' => 3,
                'quantity' => 5,
                'total' => '100000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:10:05',
                'updated_at' => '2023-01-19 01:10:05',
            ),
        ));
        
        
    }
}