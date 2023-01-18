<?php

namespace Database\Seeders\Customer;

use Illuminate\Database\Seeder;

class CustomerGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customerGroups')->delete();
        
        \DB::table('customerGroups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'customerGroup' => 'Pecinta',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:03:22',
                'updated_at' => '2023-01-19 01:03:22',
            ),
            1 => 
            array (
                'id' => 2,
                'customerGroup' => 'AML',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:03:25',
                'updated_at' => '2023-01-19 01:03:25',
            ),
            2 => 
            array (
                'id' => 3,
                'customerGroup' => 'Klinik Sehat',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:03:32',
                'updated_at' => '2023-01-19 01:03:32',
            ),
            3 => 
            array (
                'id' => 4,
                'customerGroup' => 'Klinik Bugar',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:03:38',
                'updated_at' => '2023-01-19 01:03:38',
            ),
        ));
        
        
    }
}