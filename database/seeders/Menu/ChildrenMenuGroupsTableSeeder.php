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
                'orderData' => 1,
                'menuName' => 'Dashboard',
                'identify' => 'dashboard-menu',
                'title' => 'dashboard',
                'type' => 'item',
                'icon' => 'DashboardIcon',
                'isActive' => 1,
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
                'orderData' => 2,
                'menuName' => 'Calendar',
                'identify' => 'calendar-menu',
                'title' => 'calendar',
                'type' => 'item',
                'icon' => 'CalendarOutlined',
                'isActive' => 1,
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
                'orderData' => 3,
                'menuName' => 'Message',
                'identify' => 'message-menu',
                'title' => 'message',
                'type' => 'item',
                'icon' => 'MessageOutlined',
                'isActive' => 1,
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
                'orderData' => 4,
                'menuName' => 'Customer',
                'identify' => 'customer',
                'title' => 'customer',
                'type' => 'collapse',
                'icon' => 'SmileOutlined',
                'isActive' => 1,
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
                'orderData' => 5,
                'menuName' => 'Staff',
                'identify' => 'staff',
                'title' => 'staff',
                'type' => 'collapse',
                'icon' => 'TeamOutlined',
                'isActive' => 1,
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
                'orderData' => 6,
                'menuName' => 'Promotion',
                'identify' => 'promotion',
                'title' => 'promotion',
                'type' => 'collapse',
                'icon' => 'PercentageOutlined',
                'isActive' => 1,
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
                'orderData' => 7,
                'menuName' => 'Service',
                'identify' => 'service',
                'title' => 'service',
                'type' => 'collapse',
                'icon' => 'SolutionOutlined',
                'isActive' => 1,
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
                'orderData' => 8,
                'menuName' => 'Product',
                'identify' => 'product',
                'title' => 'product',
                'type' => 'collapse',
                'icon' => 'Inventory2Icon',
                'isActive' => 1,
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
                'orderData' => 9,
                'menuName' => 'Location',
                'identify' => 'location',
                'title' => 'location',
                'type' => 'collapse',
                'icon' => 'LocationOn',
                'isActive' => 1,
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
                'orderData' => 10,
                'menuName' => 'Finance',
                'identify' => 'finance',
                'title' => 'finance',
                'type' => 'collapse',
                'icon' => 'DollarCircleOutlined',
                'isActive' => 1,
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
                'orderData' => 11,
                'menuName' => 'Report',
                'identify' => 'report-menu',
                'title' => 'report',
                'type' => 'item',
                'icon' => 'FileOutlined',
                'isActive' => 1,
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
