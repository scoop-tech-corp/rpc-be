<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductClinicPriceLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productClinicPriceLocations')->delete();
        
        \DB::table('productClinicPriceLocations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productClinicId' => 25,
                'locationId' => 1,
                'price' => '25000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            1 => 
            array (
                'id' => 2,
                'productClinicId' => 25,
                'locationId' => 1,
                'price' => '14500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            2 => 
            array (
                'id' => 3,
                'productClinicId' => 26,
                'locationId' => 1,
                'price' => '25000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            3 => 
            array (
                'id' => 4,
                'productClinicId' => 26,
                'locationId' => 1,
                'price' => '14500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            4 => 
            array (
                'id' => 5,
                'productClinicId' => 27,
                'locationId' => 1,
                'price' => '25000.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
            5 => 
            array (
                'id' => 6,
                'productClinicId' => 27,
                'locationId' => 1,
                'price' => '14500.00',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 00:59:00',
                'updated_at' => '2023-01-19 00:59:00',
            ),
        ));
        
        
    }
}