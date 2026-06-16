<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transaction sudah punya children group di DB (childrenId = 19, identify = 'transaction-menu').
     * Migration ini hanya menambahkan Dashboard sebagai item pertama (orderMenu = 45)
     * di atas Pet Clinic (orderMenu = 46).
     *
     * accessControl mengikuti pola yang sama dengan transaction-menu (menuListId=60):
     *   roleId 1→4, 2→1, 3→4, 4→1, 5→1, 6→4, 7→1
     */
    public function up(): void
    {
        $childrenId = DB::table('childrenMenuGroups')
            ->where('identify', 'transaction-menu')
            ->value('id');

        DB::table('grandChildrenMenuGroups')->insert([
            'childrenId'   => $childrenId,
            'orderMenu'    => 45,
            'menuName'     => 'Transaction Dashboard',
            'identify'     => 'transaction-dashboard',
            'title'        => 'Transaction Dashboard',
            'type'         => 'item',
            'url'          => '/transaction/dashboard',
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
            ->where('identify', 'transaction-dashboard')
            ->value('id');

        $now = now();
        DB::table('accessControl')->insert([
            ['menuListId' => $menuListId, 'roleId' => 1, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 2, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 3, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 4, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 5, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 6, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 7, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        $menuListId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'transaction-dashboard')
            ->value('id');

        if ($menuListId) {
            DB::table('accessControl')->where('menuListId', $menuListId)->delete();
        }

        DB::table('grandChildrenMenuGroups')
            ->where('identify', 'transaction-dashboard')
            ->delete();
    }
};
