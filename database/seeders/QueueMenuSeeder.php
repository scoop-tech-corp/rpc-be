<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QueueMenuSeeder extends Seeder
{
    public function run()
    {
        // ---------------------------------------------------------------
        // 1. Geser orderMenu semua menuGroups yang >= 2 naik satu slot
        //    agar group-queue bisa masuk di order 2 (di atas Transaksi)
        // ---------------------------------------------------------------
        DB::table('menuGroups')
            ->where('orderMenu', '>=', 2)
            ->where('groupName', '!=', 'group-queue') // idempotent guard
            ->orderBy('orderMenu', 'desc')
            ->get()
            ->each(function ($group) {
                DB::table('menuGroups')
                    ->where('id', $group->id)
                    ->update(['orderMenu' => $group->orderMenu + 1]);
            });

        // ---------------------------------------------------------------
        // 2. Upsert menuGroups: group-queue di order 2
        // ---------------------------------------------------------------
        $groupId = DB::table('menuGroups')
            ->where('groupName', 'group-queue')
            ->value('id');

        if (!$groupId) {
            $groupId = DB::table('menuGroups')->insertGetId([
                'groupName' => 'group-queue',
                'orderMenu' => 2,
                'isDeleted' => 0,
            ]);
        } else {
            DB::table('menuGroups')->where('id', $groupId)->update(['orderMenu' => 2]);
        }

        // ---------------------------------------------------------------
        // 3. Upsert childrenMenuGroups
        // ---------------------------------------------------------------
        $childId = DB::table('childrenMenuGroups')
            ->where('identify', 'queue-menu')
            ->value('id');

        if (!$childId) {
            $childId = DB::table('childrenMenuGroups')->insertGetId([
                'groupId'   => $groupId,
                'menuName'  => 'Queue Management',
                'identify'  => 'queue-menu',
                'title'     => 'queue-management',
                'type'      => 'collapse',
                'icon'      => 'OrderedListOutlined',
                'orderMenu' => 1,
                'isActive'  => 1,
                'isDeleted' => 0,
                'userId'    => 1,
            ]);
        } else {
            DB::table('childrenMenuGroups')->where('id', $childId)->update([
                'groupId' => $groupId,
            ]);
        }

        // ---------------------------------------------------------------
        // 4. Upsert grandChildrenMenuGroups
        // ---------------------------------------------------------------
        $grandId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'queue-management-menu')
            ->value('id');

        if (!$grandId) {
            $grandId = DB::table('grandChildrenMenuGroups')->insertGetId([
                'childrenId' => $childId,
                'orderMenu'  => 1,
                'menuName'   => 'Queue Management',
                'identify'   => 'queue-management-menu',
                'title'      => 'queue-management',
                'type'       => 'item',
                'url'        => '/queue',
                'icon'       => 'OrderedListOutlined',
                'isActive'   => 1,
                'isDeleted'  => 0,
                'userId'     => 1,
            ]);
        }

        // ---------------------------------------------------------------
        // 5. Upsert accessControl per role
        //    roleId: 1=Administrator, 2=Manager, 3=Staff, 6=Office, 7=Doctor
        //    accessTypeId: 1=Read, 4=Full
        // ---------------------------------------------------------------
        $roleAccess = [
            1 => 4, // Administrator → Full
            2 => 4, // Manager       → Full
            3 => 1, // Staff         → Read
            6 => 1, // Office        → Read
            7 => 1, // Doctor        → Read
        ];

        foreach ($roleAccess as $roleId => $accessTypeId) {
            $exists = DB::table('accessControl')
                ->where('menuListId', $grandId)
                ->where('roleId', $roleId)
                ->exists();

            if (!$exists) {
                DB::table('accessControl')->insert([
                    'roleId'       => $roleId,
                    'menuListId'   => $grandId,
                    'accessTypeId' => $accessTypeId,
                    'isDeleted'    => 0,
                ]);
            }
        }

        $this->command->info('QueueMenuSeeder: berhasil dijalankan.');
    }
}
