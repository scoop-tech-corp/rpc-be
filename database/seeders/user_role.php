<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class user_role extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userRole = [
            ['roleName' => 'Administrator', 'isActive' => 1, 'created_at' => now()],
            ['roleName' => 'Manager', 'isActive' => 1, 'created_at' => now()],
            ['roleName' => 'Staff', 'isActive' => 1, 'created_at' => now()],
            ['roleName' => 'Customer', 'isActive' => 1, 'created_at' => now()],
            ['roleName' => 'Intership', 'isActive' => 1, 'created_at' => now()],
            ['roleName' => 'Office', 'isActive' => 1, 'created_at' => now()],
        ];
        DB::table('usersRoles')->insert($userRole);

        $roleAccess = [
            ['accessType' => 'Read', 'created_at' => now()],
            ['accessType' => 'Write', 'created_at' => now()],
            ['accessType' => 'None', 'created_at' => now()],
            ['accessType' => 'Full', 'created_at' => now()],
        ];
        DB::table('tableRoleAccess')->insert($roleAccess);

        $menulist = [
            ['menuName' => 'Location', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Facility', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Product', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Staff', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Services', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Customer', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Promo', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Kalender', 'isActive' => '1', 'created_at' => now()],
            ['menuName' => 'Messenger', 'isActive' => '1', 'created_at' => now()],

        ];
        DB::table('menuList')->insert($menulist);


        $accesslimit = [
            ['timeLimit' => 60, 'startDuration' => now()->addMinute(8), 'created_at' => now()],
            ['timeLimit' => 180, 'startDuration' => now()->addMinute(10), 'created_at' => now()],
            ['timeLimit' => 240, 'startDuration' => now()->addMinute(20), 'created_at' => now()],
        ];

        DB::table('accessLimit')->insert($accesslimit);

        $tableaccess = [
            ['menuListId' => '1', 'roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '2', 'roleId' => '2', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '3', 'roleId' => '3', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '4', 'roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '5', 'roleId' => '2', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '6', 'roleId' => '2', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '7', 'roleId' => '2', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '8', 'roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '9', 'roleId' => '3', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            ['menuListId' => '9', 'roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
        ];
        DB::table('tableAccess')->insert($tableaccess);
    }
}
