<?php

namespace Database\Seeders\Menu;

use Illuminate\Database\Seeder;

class MenuGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('menuGroups')->delete();

        \DB::table('menuGroups')->insert(array (
            0 =>
            array (
                'id' => 1,
                'groupName' => 'menu-group-1',
                'orderData' => 1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 =>
            array (
                'id' => 2,
                'groupName' => 'group-customer',
                'orderData' => 2,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 =>
            array (
                'id' => 3,
                'groupName' => 'group-staff',
                'orderData' => 3,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            3 =>
            array (
                'id' => 4,
                'groupName' => 'group-promotion',
                'orderData' => 4,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            4 =>
            array (
                'id' => 5,
                'groupName' => 'group-service',
                'orderData' => 5,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            5 =>
            array (
                'id' => 6,
                'groupName' => 'group-product',
                'orderData' => 6,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            6 =>
            array (
                'id' => 7,
                'groupName' => 'group-location',
                'orderData' => 7,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            7 =>
            array (
                'id' => 8,
                'groupName' => 'group-finance',
                'orderData' => 8,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            8 =>
            array (
                'id' => 9,
                'groupName' => 'menu-group-2',
                'orderData' => 9,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));


    }
}
