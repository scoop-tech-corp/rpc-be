<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductBundleLogsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productBundleLogs')->delete();
        
        \DB::table('productBundleLogs')->insert(array (
            0 => 
            array (
                'id' => 1,
                'productBundleId' => 1,
                'event' => 'Created',
                'details' => 'A draft Product Bundle has been created.',
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