<?php

namespace Database\Seeders\Menu;

use Illuminate\Database\Seeder;

class MenuProfilesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('menuProfiles')->delete();

        \DB::table('menuProfiles')->insert(array (
            0 =>
            array (
                'id' => 1,
                'title' => 'edit-profile',
                'url' => '/staff/profile/edit',
                'icon' => 'EditOutlined',
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
                'title' => 'view-profile',
                'url' => '/staff/profile/view',
                'icon' => 'UserOutlined',
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
