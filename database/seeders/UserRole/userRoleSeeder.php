<?php

namespace Database\Seeders\UserRole;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class userRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('data_static')->truncate();
        DB::table('dataStaticStaff')->truncate();
        DB::table('dataStaticCustomer')->truncate();
        DB::table('usersRoles')->truncate();
        DB::table('accessType')->truncate();
        DB::table('menuList')->truncate();
         DB::table('menuMaster')->truncate();
        DB::table('accessLimit')->truncate();
        DB::table('accessControl')->truncate();




        $data = [
            ['value' => 'Usage', 'name' => 'Utama', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Usage', 'name' => 'Secondary', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Telephone', 'name' => 'Rumah', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Telephone', 'name' => 'Whatshapp', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Messenger', 'name' => 'Gmail', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Messenger', 'name' => 'Yahoo', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Messenger', 'name' => 'GooglePlus', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Messenger', 'name' => 'Bing', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
        ];



        $userRole = [
            ['roleName' => 'Administrator', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
            ['roleName' => 'Manager', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
            ['roleName' => 'Staff', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
            ['roleName' => 'Customer', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
            ['roleName' => 'Intership', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
            ['roleName' => 'Office', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
            ['roleName' => 'Doctor', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),], // ADD BY DANNY WAHYUDI 13-03-2023
        ];


        $accessType = [
            ['accessType' => 'Read', 'created_at' => now(), 'updated_at' => now(),],
            ['accessType' => 'Write', 'created_at' => now(), 'updated_at' => now(),],
            ['accessType' => 'None', 'created_at' => now(), 'updated_at' => now(),],
            ['accessType' => 'Full', 'created_at' => now(), 'updated_at' => now(),],
        ];


        // $menulist = [
        //     ['menuName' => 'Location', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Facility', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Product', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Staff', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Services', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Customer', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Promo', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Kalender', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
        //     ['menuName' => 'Messenger', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],

        // ];


        $menulist = [
            ['masterId' => '1',   'menuName' => 'Location', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '1', 'menuName' => 'Facility', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '2', 'menuName' => 'Product', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '3', 'menuName' => 'Staff', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '', 'menuName' => 'Services', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '4', 'menuName' => 'Customer', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '', 'menuName' => 'Promo', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '', 'menuName' => 'Kalender', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],
            ['masterId' => '', 'menuName' => 'Messenger', 'isActive' => '1', 'created_at' => now(), 'updated_at' => now(),],

        ];

        $menuMaster = [
            ['masterName' => 'Location', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['masterName' => 'Product', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['masterName' => 'Staff', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['masterName' => 'Customer', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now(),],
        ];

        $accesslimit = [
            ['timeLimit' => '60', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
            ['timeLimit' => '90', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
            ['timeLimit' => '120', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
        ];



        $accessControl = [
            ['menuListId' => '1', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '2', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '3', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '4', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '5', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '6', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '7', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '8', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
            ['menuListId' => '9', 'roleId' => '1', 'accessTypeId' => '4', 'accessLimitId' => '3', 'isDeleted' => '0', 'created_at' => now(), 'updated_at' => now(),],
        ];


        DB::table('data_static')->insert($data);
        DB::table('dataStaticStaff')->insert($data); //add by dw data static 
        DB::table('dataStaticCustomer')->insert($data); //add by dw data static customer
        DB::table('usersRoles')->insert($userRole);
        DB::table('accessType')->insert($accessType);
        DB::table('menuList')->insert($menulist);
        DB::table('menuMaster')->insert($menuMaster);
        DB::table('accessLimit')->insert($accesslimit);
        DB::table('accessControl')->insert($accessControl);
    }
}
