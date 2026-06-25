<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan 3 menu ke modul Customer:
 *   - Feedback              (/customer/feedback)
 *   - Support Requested     (/customer/support-request)
 *   - Help Center / Portal  (/customer/support-request/portal)
 *
 * childrenId diambil dinamis dari identify='customer' sehingga aman di
 * semua environment (local, staging, production).
 *
 * accessControl mengikuti pola umum:
 *   roleId 1 → 4 (Administrator – Full)
 *   roleId 2 → 2 (Manager – View)
 *   roleId 3 → 4 (Super Admin – Full)
 *   roleId 4 → 1 (Staff – None)
 *   roleId 5 → 2 (Kasir – View)
 *   roleId 6 → 1 (Staff Lain – None)
 *   roleId 7 → 4 (Owner – Full)
 */
return new class extends Migration
{
    private array $menus = [
        [
            'menuName' => 'Feedback',
            'identify' => 'customer-feedback',
            'title'    => 'feedback',
            'url'      => '/customer/feedback',
        ],
        [
            'menuName' => 'Support Request',
            'identify' => 'customer-support-request',
            'title'    => 'support-requested',
            'url'      => '/customer/support-request',
        ],
        [
            'menuName' => 'Help Center',
            'identify' => 'customer-support-portal',
            'title'    => 'help-center',
            'url'      => '/customer/support-request/portal',
        ],
    ];

    private array $accessRules = [
        ['roleId' => 1, 'accessTypeId' => 4],
        ['roleId' => 2, 'accessTypeId' => 2],
        ['roleId' => 3, 'accessTypeId' => 4],
        ['roleId' => 4, 'accessTypeId' => 1],
        ['roleId' => 5, 'accessTypeId' => 2],
        ['roleId' => 6, 'accessTypeId' => 1],
        ['roleId' => 7, 'accessTypeId' => 4],
    ];

    public function up(): void
    {
        $childrenId = DB::table('childrenMenuGroups')
            ->where('identify', 'customer')
            ->value('id');

        if (!$childrenId) {
            return; // customer group belum ada – skip
        }

        $maxOrder = DB::table('grandChildrenMenuGroups')
            ->where('childrenId', $childrenId)
            ->max('orderMenu') ?? 0;

        $now = now();

        foreach ($this->menus as $i => $menu) {
            // Idempotent: skip jika sudah ada
            $exists = DB::table('grandChildrenMenuGroups')
                ->where('identify', $menu['identify'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('grandChildrenMenuGroups')->insert([
                'childrenId'   => $childrenId,
                'orderMenu'    => $maxOrder + $i + 1,
                'menuName'     => $menu['menuName'],
                'identify'     => $menu['identify'],
                'title'        => $menu['title'],
                'type'         => 'item',
                'url'          => $menu['url'],
                'icon'         => 'DashboardIcon',
                'isActive'     => 1,
                'isDeleted'    => 0,
                'userId'       => 1,
                'userUpdateId' => null,
                'deletedBy'    => null,
                'deletedAt'    => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            $menuListId = DB::table('grandChildrenMenuGroups')
                ->where('identify', $menu['identify'])
                ->value('id');

            $acRows = array_map(fn($r) => array_merge($r, [
                'menuListId' => $menuListId,
                'isDeleted'  => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]), $this->accessRules);

            DB::table('accessControl')->insert($acRows);
        }
    }

    public function down(): void
    {
        foreach ($this->menus as $menu) {
            $menuListId = DB::table('grandChildrenMenuGroups')
                ->where('identify', $menu['identify'])
                ->value('id');

            if ($menuListId) {
                DB::table('accessControl')->where('menuListId', $menuListId)->delete();
            }

            DB::table('grandChildrenMenuGroups')
                ->where('identify', $menu['identify'])
                ->delete();
        }
    }
};
