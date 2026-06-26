<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Queue Management menu ditambahkan via QueueMenuSeeder (bukan migration),
 * sehingga tidak otomatis jalan di production saat `php artisan migrate`.
 *
 * Migration ini mengkonversi seeder tersebut agar deployment berikutnya
 * langsung membuat menu di semua environment.
 *
 * Idempotent: setiap step cek keberadaan data sebelum insert/update.
 *
 * Struktur:
 *   menuGroups       → group-queue   (orderMenu = 2, sisanya digeser +1)
 *   childrenMenuGroups → queue-menu  (collapse, icon OrderedListOutlined)
 *   grandChildrenMenuGroups → queue-management-menu (/queue)
 *   accessControl    → role 1,2 Full; role 3,6,7 Read
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. Geser orderMenu group yang >= 2 agar queue bisa masuk di slot 2 ─
        if (!DB::table('menuGroups')->where('groupName', 'group-queue')->exists()) {
            DB::table('menuGroups')
                ->where('orderMenu', '>=', 2)
                ->orderBy('orderMenu', 'desc')
                ->get()
                ->each(function ($group) {
                    DB::table('menuGroups')
                        ->where('id', $group->id)
                        ->update(['orderMenu' => $group->orderMenu + 1]);
                });
        }

        // ── 2. menuGroups: group-queue ────────────────────────────────────────
        $groupId = DB::table('menuGroups')->where('groupName', 'group-queue')->value('id');

        if (!$groupId) {
            $groupId = DB::table('menuGroups')->insertGetId([
                'groupName' => 'group-queue',
                'orderMenu' => 2,
                'isDeleted' => 0,
            ]);
        }

        // ── 3. childrenMenuGroups: queue-menu ────────────────────────────────
        $childId = DB::table('childrenMenuGroups')->where('identify', 'queue-menu')->value('id');

        if (!$childId) {
            $childId = DB::table('childrenMenuGroups')->insertGetId([
                'groupId'    => $groupId,
                'menuName'   => 'Queue Management',
                'identify'   => 'queue-menu',
                'title'      => 'queue-management',
                'type'       => 'collapse',
                'icon'       => 'OrderedListOutlined',
                'orderMenu'  => 1,
                'isActive'   => 1,
                'isDeleted'  => 0,
                'userId'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('childrenMenuGroups')->where('id', $childId)->update([
                'groupId'    => $groupId,
                'updated_at' => $now,
            ]);
        }

        // ── 4. grandChildrenMenuGroups: queue-management-menu ────────────────
        $grandId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'queue-management-menu')
            ->value('id');

        if (!$grandId) {
            $grandId = DB::table('grandChildrenMenuGroups')->insertGetId([
                'childrenId'   => $childId,
                'orderMenu'    => 1,
                'menuName'     => 'Queue Management',
                'identify'     => 'queue-management-menu',
                'title'        => 'queue-management',
                'type'         => 'item',
                'url'          => '/queue',
                'icon'         => 'OrderedListOutlined',
                'isActive'     => 1,
                'isDeleted'    => 0,
                'userId'       => 1,
                'userUpdateId' => null,
                'deletedBy'    => null,
                'deletedAt'    => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // ── 5. accessControl ─────────────────────────────────────────────────
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
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $grandId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'queue-management-menu')
            ->value('id');

        if ($grandId) {
            DB::table('accessControl')->where('menuListId', $grandId)->delete();
            DB::table('grandChildrenMenuGroups')->where('id', $grandId)->delete();
        }

        $childId = DB::table('childrenMenuGroups')->where('identify', 'queue-menu')->value('id');
        if ($childId) {
            DB::table('childrenMenuGroups')->where('id', $childId)->delete();
        }

        DB::table('menuGroups')->where('groupName', 'group-queue')->delete();
    }
};
