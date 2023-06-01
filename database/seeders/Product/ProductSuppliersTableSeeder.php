<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductSuppliersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productSuppliers')->delete();
        
        \DB::table('productSuppliers')->insert(array (
            0 => 
            array (
                'id' => 1,
                'supplierName' => 'supp 1',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:25',
                'updated_at' => '2023-01-14 22:55:25',
            ),
            1 => 
            array (
                'id' => 2,
                'supplierName' => 'supp 2',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:26',
                'updated_at' => '2023-01-14 22:55:26',
            ),
            2 => 
            array (
                'id' => 3,
                'supplierName' => 'supp 3',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:27',
                'updated_at' => '2023-01-14 22:55:27',
            ),
            3 => 
            array (
                'id' => 4,
                'supplierName' => 'supp 4',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:28',
                'updated_at' => '2023-01-14 22:55:28',
            ),
            4 => 
            array (
                'id' => 5,
                'supplierName' => 'supp 5',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-14 22:55:29',
                'updated_at' => '2023-01-14 22:55:29',
            ),
        ));
        
        
    }
}