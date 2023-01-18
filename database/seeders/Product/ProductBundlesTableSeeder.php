<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductBundlesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productBundles')->delete();
        
        \DB::table('productBundles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'steril kucing betina',
                'locationId' => 2,
                'categoryId' => 1,
                'remark' => 'coba',
                'status' => 1,
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