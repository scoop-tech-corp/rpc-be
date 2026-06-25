<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan menu Finance > Installment ke grandChildrenMenuGroups + accessControl.
 *
 * title disimpan sebagai 'installment' (all-lowercase i18n key) agar
 * mappingMasterMenu() membungkusnya dengan <FormattedMessage id="installment" />
 * sehingga label bisa switch bahasa.
 *
 * (Jika title berisi huruf kapital, FE menampilkannya as-is → tidak bisa translate.)
 *
 * accessControl mengikuti pola finance-sales / finance-expenses:
 *   roleId 1 → 4 (Administrator – Full)
 *   roleId 2 → 1 (Manager – Limited)
 *   roleId 6 → 2 (Office – View)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip jika sudah ada (idempotent)
        if (DB::table('grandChildrenMenuGroups')->where('identify', 'finance-installment')->exists()) {
            // Pastikan title sudah lowercase (fix sekalian jika ada tapi masih 'Cicilan')
            DB::table('grandChildrenMenuGroups')
                ->where('identify', 'finance-installment')
                ->where('title', '!=', 'installment')
                ->update(['title' => 'installment', 'updated_at' => now()]);

            return;
        }

        $childrenId = DB::table('childrenMenuGroups')
            ->where('identify', 'finance')
            ->value('id');

        if (!$childrenId) return;

        $maxOrder = DB::table('grandChildrenMenuGroups')
            ->where('childrenId', $childrenId)
            ->max('orderMenu') ?? 50;

        $now = now();

        DB::table('grandChildrenMenuGroups')->insert([
            'childrenId'   => $childrenId,
            'orderMenu'    => $maxOrder + 1,
            'menuName'     => 'Installment',
            'identify'     => 'finance-installment',
            'title'        => 'installment',
            'type'         => 'item',
            'url'          => '/finance/installment',
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
            ->where('identify', 'finance-installment')
            ->value('id');

        DB::table('accessControl')->insert([
            ['menuListId' => $menuListId, 'roleId' => 1, 'accessTypeId' => 4, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 2, 'accessTypeId' => 1, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['menuListId' => $menuListId, 'roleId' => 6, 'accessTypeId' => 2, 'isDeleted' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        $menuListId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'finance-installment')
            ->value('id');

        if ($menuListId) {
            DB::table('accessControl')->where('menuListId', $menuListId)->delete();
        }

        DB::table('grandChildrenMenuGroups')
            ->where('identify', 'finance-installment')
            ->delete();
    }
};
