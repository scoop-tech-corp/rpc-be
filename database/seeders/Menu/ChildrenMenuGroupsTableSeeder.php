<?php

namespace Database\Seeders\Menu;

use Illuminate\Database\Seeder;

class ChildrenMenuGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('childrenMenuGroups')->delete();

        \DB::table('childrenMenuGroups')->insert(array (
            0 =>
            array (
                'id' => 1,
                'groupId' => 1,
                'identify' => 'dashboard-menu',
                'title' => 'dashboard',
                'type' => 'item',
                'url' => '/dashboard',
                'icon' => 'DashboardIcon',
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
                'groupId' => 1,
                'identify' => 'calendar-menu',
                'title' => 'calendar',
                'type' => 'item',
                'url' => '/calendar',
                'icon' => 'CalendarOutlined',
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
                'groupId' => 1,
                'identify' => 'message-menu',
                'title' => 'message',
                'type' => 'item',
                'url' => '/message',
                'icon' => 'MessageOutlined',
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
                'groupId' => 2,
                'identify' => 'customer',
                'title' => 'customer',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'SmileOutlined',
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
                'groupId' => 3,
                'identify' => 'staff',
                'title' => 'staff',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'TeamOutlined',
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
                'groupId' => 4,
                'identify' => 'promotion',
                'title' => 'promotion',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'PercentageOutlined',
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
                'groupId' => 5,
                'identify' => 'service',
                'title' => 'service',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'SolutionOutlined',
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
                'groupId' => 6,
                'identify' => 'product',
                'title' => 'product',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'Inventory2Icon',
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
                'groupId' => 7,
                'identify' => 'location',
                'title' => 'location',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'LocationOn',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            9 =>
            array (
                'id' => 10,
                'groupId' => 8,
                'identify' => 'finance',
                'title' => 'finance',
                'type' => 'collapse',
                'url' => '',
                'icon' => 'DollarCircleOutlined',
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            10 =>
            array (
                'id' => 11,
                'groupId' => 9,
                'identify' => 'report-menu',
                'title' => 'report',
                'type' => 'item',
                'url' => '',
                'icon' => 'FileOutlined',
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
