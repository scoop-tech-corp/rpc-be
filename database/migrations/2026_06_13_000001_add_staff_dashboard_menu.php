<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * childrenId = 5  → grup "Staff" di childrenMenuGroups
     * orderMenu  = 6  → sebelum Staff List (orderMenu=7)
     *
     * accessControl mengikuti pola yang sama dengan staff-list (menuListId=10):
     *   roleId 1→4, 2→2, 3→4, 4→1, 5→2, 6→1, 7→4
     */
    public function up(): void
    {
        DB::table('grandChildrenMenuGroups')->insert([
            'childrenId'   => 5,
            'orderMenu'    => 6,
            'menuName'     => 'Staff Dashboard',
            'identify'     => 'staff-dashboard',
            'title'        => 'Staff Dashboard',
            'type'         => 'item',
            'url'          => '/staff/dashboard',
            'icon'         => 'DashboardIcon',
            'isActive'     => 1,
            'isDeleted'    => 0,
            'userId'       => 1,
            'userUpdateId' => null,
            'deletedBy'    => null,
            'deletedAt'    => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $menuListId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'staff-dashboard')
            ->value('id');

        $now = now();
        DB::table('accessControl')->insert([
            ['menuListId' => $menuListId, 'roleId' => 1, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 2, 'accessTypeId' => 2, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 3, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 4, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 5, 'accessTypeId' => 2, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 6, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 7, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        $menuListId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'staff-dashboard')
            ->value('id');

        if ($menuListId) {
            DB::table('accessControl')->where('menuListId', $menuListId)->delete();
        }

        DB::table('grandChildrenMenuGroups')
            ->where('identify', 'staff-dashboard')
            ->delete();
    }
};
