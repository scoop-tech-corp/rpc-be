<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class UsagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('usages')->delete();
        
        \DB::table('usages')->insert(array (
            0 => 
            array (
                'id' => 1,
                'usage' => 'usage 1',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:11:55',
                'updated_at' => '2023-01-15 23:11:55',
            ),
            1 => 
            array (
                'id' => 2,
                'usage' => 'usage 2',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:11:56',
                'updated_at' => '2023-01-15 23:11:56',
            ),
            2 => 
            array (
                'id' => 3,
                'usage' => 'usage 3',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:11:57',
                'updated_at' => '2023-01-15 23:11:57',
            ),
            3 => 
            array (
                'id' => 4,
                'usage' => 'usage 4',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:11:59',
                'updated_at' => '2023-01-15 23:11:59',
            ),
            4 => 
            array (
                'id' => 5,
                'usage' => 'usage 5',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:12:00',
                'updated_at' => '2023-01-15 23:12:00',
            ),
        ));
        
        
    }
}