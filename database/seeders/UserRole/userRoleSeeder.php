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
        DB::table('accessControlHistory')->truncate();
        

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
            ['roleName' => 'Doctor', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now(),],
        ];


        $accessType = [
            ['accessType' => 'Read', 'created_at' => now(), 'updated_at' => now(),],
            ['accessType' => 'Write', 'created_at' => now(), 'updated_at' => now(),],
            ['accessType' => 'None', 'created_at' => now(), 'updated_at' => now(),],
            ['accessType' => 'Full', 'created_at' => now(), 'updated_at' => now(),],
        ];

        $accesslimit = [
            ['timeLimit' => '60', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
            ['timeLimit' => '90', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
            ['timeLimit' => '120', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
        ];



        DB::table('data_static')->insert($data);
        DB::table('dataStaticStaff')->insert($data);
        DB::table('dataStaticCustomer')->insert($data);
        DB::table('usersRoles')->insert($userRole);
        DB::table('accessType')->insert($accessType);
        DB::table('accessLimit')->insert($accesslimit);
    }
}
