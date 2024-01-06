<?php

namespace Database\Seeders\UserRole;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class userRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('en_US');
        DB::table('data_static')->truncate();
        DB::table('dataStaticStaff')->truncate();
        DB::table('dataStaticCustomer')->truncate();
        DB::table('usersRoles')->truncate();
        DB::table('accessType')->truncate();
        DB::table('menuList')->truncate();
        DB::table('menuMaster')->truncate();
        // DB::table('accessLimit')->truncate();
        DB::table('accessControl')->truncate();
        DB::table('accessControlHistory')->truncate();
        DB::table('statusSchedules')->truncate();

        $statusSchedules = [
            ['status' => 'Not Running', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'Ongoing', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'Finished', 'created_at' => now(), 'updated_at' => now()],
        ];



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



        $master = [
            ['masterName' => 'Customer', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['masterName' => 'Staff', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['masterName' => 'Promotion', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['masterName' => 'Service', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['masterName' => 'Product', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['masterName' => 'Location', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['masterName' => 'Finance', 'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now()],
        ];


        $menuList = [
            ['masterId' => '1', 'menuName' => 'Dashboard', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '1', 'menuName' => 'Customer List', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '1', 'menuName' => 'Template', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '1', 'menuName' => 'Merge', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '1', 'menuName' => 'Static Data', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '1', 'menuName' => 'Import', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],

            ['masterId' => '2', 'menuName' => 'Staff List', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '2', 'menuName' => 'Leave Approval', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '2', 'menuName' => 'Access Control', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '2', 'menuName' => 'Security Group', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '2', 'menuName' => 'Static Data', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],


            ['masterId' => '3', 'menuName' => 'Dashboard', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '3', 'menuName' => 'Discount', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '3', 'menuName' => 'Partner', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],

            ['masterId' => '4', 'menuName' => 'Dashboard', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '4', 'menuName' => 'Service List', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '4', 'menuName' => 'Treatment Plant', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '4', 'menuName' => 'Category', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '4', 'menuName' => 'Policies', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '4', 'menuName' => 'Template', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '4', 'menuName' => 'Static Data', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],


            ['masterId' => '5', 'menuName' => 'Dashboard', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Product List', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Bundle', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Category', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Supplier', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Policies', 'isActive' => 1, 'created_at' => now(), 'u    pdated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Restocks', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Delivery Agent', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '5', 'menuName' => 'Static Data', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],

            ['masterId' => '6', 'menuName' => 'Location List', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '6', 'menuName' => 'Facility', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '6', 'menuName' => 'Static Data', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],


            ['masterId' => '7', 'menuName' => 'Dashboard', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '7', 'menuName' => 'Sales', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '7', 'menuName' => 'Quotation', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '7', 'menuName' => 'Expenses', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['masterId' => '7', 'menuName' => 'Static Data', 'isActive' => 1, 'created_at' => now(), 'updated_at' => now()],

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

        // $accesslimit = [
        //     ['timeLimit' => '1 Hari 10 Jam', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
        //     ['timeLimit' => '20 Jam', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
        //     ['timeLimit' => '1 Hari 2 Jam', 'startDuration' => now(), 'created_at' => now(), 'updated_at' => now(),],
        // ];



        DB::table('data_static')->insert($data);
        DB::table('dataStaticStaff')->insert($data);
        DB::table('dataStaticCustomer')->insert($data);
        DB::table('usersRoles')->insert($userRole);
        DB::table('accessType')->insert($accessType);
        // DB::table('accessLimit')->insert($accesslimit);
        DB::table('statusSchedules')->insert($statusSchedules);
        DB::table('menuList')->insert($menuList);
        DB::table('menuMaster')->insert($master);




        $getAllMenuList = DB::table('menuList as a')
            ->get();

        $getAllRoleId = DB::table('usersRoles as a')
            ->get();

        $menuListCount = $getAllMenuList->count();
        $roleIdCount = $getAllRoleId->count();

        for ($j = 1; $j <=  $menuListCount; $j++) { // menuList

            for ($i = 1; $i <=  $roleIdCount; $i++) { //roleId

                $randomAccessType = $faker->numberBetween(1, 4); //accessType 


                $value = [
                    ["menuListId" => $j, "roleId" => $i, "accessTypeId" => $randomAccessType,  "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now()],
                ];

                DB::table('accessControl')->insert($value);
            }
        }
    }
}
