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
            [ 'roleName' => 'Administrator','isActive' =>1,'created_at' => now()],
            [ 'roleName' => 'Manager','isActive' =>1,'created_at' => now()],
            [ 'roleName' => 'Staff','isActive' =>1,'created_at' => now()],
            [ 'roleName' => 'Customer','isActive' =>1,'created_at' => now()],
            [ 'roleName' => 'Intership','isActive' =>1,'created_at' => now()],
            [ 'roleName' => 'Office','isActive' =>1,'created_at' => now()],
        ];
        DB::table('users_role')->insert($userRole); 

        $roleAccess = [
            [ 'accessName' => 'Read','created_at' => now()],
            [ 'accessName' => 'Write','created_at' => now()],
            [ 'accessName' => 'None','created_at' => now()],
            [ 'accessName' => 'Full','created_at' => now()],
        ];
        DB::table('tableroleaccess')->insert($roleAccess); 

        $menulist = [
            [ 'menuName' => 'Location','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Facility','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Product','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Staff','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Services','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Customer','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Promo','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Kalender','isActive' => '1','created_at' => now()],
            [ 'menuName' => 'Messenger','isActive' => '1','created_at' => now()],

        ];
        DB::table('menulist')->insert($menulist); 


        $accesslimit = [
            [ 'timeLimit' => '1Hour', 'created_at' => now()],
            [ 'timeLimit' => '2Hour', 'created_at' => now()],
            [ 'timeLimit' => '3Hour', 'created_at' => now()],
        ];
        DB::table('accesslimit')->insert($accesslimit);  


        $tableaccess = [
            [ 'menuListId' => '1','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '2','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '3','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '4','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '5','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '6','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '7','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '8','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
            [ 'menuListId' => '9','roleId' => '1', 'roleAccessId' => '4', 'accessLimitId' => '3',  'created_at' => now()],
        ];
        DB::table('tableaccess')->insert($tableaccess);  


    }
}
