<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix dua masalah di modul Customer (UAT):
 *
 * 1. Icon Feedback, Support Request, Help Center → masih DashboardIcon
 *    karena migration seed menggunakan 'icon' => 'DashboardIcon'.
 *    Diganti ke icon yang sesuai setiap fitur.
 *
 * 2. Menu 'customer-adii' muncul di sidebar padahal tidak seharusnya.
 *    Di-soft-delete (isDeleted=1) beserta accessControl-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. Perbaiki icon 3 menu baru ─────────────────────────────────────
        $iconMap = [
            'customer-feedback'        => 'ListAltIcon',
            'customer-support-request' => 'PolicyIcon',
            'customer-support-portal'  => 'ComputerIcon',
        ];

        foreach ($iconMap as $identify => $icon) {
            DB::table('grandChildrenMenuGroups')
                ->where('identify', $identify)
                ->update(['icon' => $icon, 'updated_at' => $now]);
        }

        // ── 2. Soft-delete customer-adii ─────────────────────────────────────
        $adiiId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'customer-adii')
            ->value('id');

        if ($adiiId) {
            DB::table('grandChildrenMenuGroups')
                ->where('id', $adiiId)
                ->update([
                    'isDeleted'  => 1,
                    'deletedBy'  => 1,
                    'deletedAt'  => $now,
                    'updated_at' => $now,
                ]);

            DB::table('accessControl')
                ->where('menuListId', $adiiId)
                ->update(['isDeleted' => 1, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $now = now();

        // Kembalikan icon ke DashboardIcon
        foreach (['customer-feedback', 'customer-support-request', 'customer-support-portal'] as $identify) {
            DB::table('grandChildrenMenuGroups')
                ->where('identify', $identify)
                ->update(['icon' => 'DashboardIcon', 'updated_at' => $now]);
        }

        // Restore customer-adii
        $adiiId = DB::table('grandChildrenMenuGroups')
            ->where('identify', 'customer-adii')
            ->value('id');

        if ($adiiId) {
            DB::table('grandChildrenMenuGroups')
                ->where('id', $adiiId)
                ->update([
                    'isDeleted'  => 0,
                    'deletedBy'  => null,
                    'deletedAt'  => null,
                    'updated_at' => $now,
                ]);

            DB::table('accessControl')
                ->where('menuListId', $adiiId)
                ->update(['isDeleted' => 0, 'updated_at' => $now]);
        }
    }
};
