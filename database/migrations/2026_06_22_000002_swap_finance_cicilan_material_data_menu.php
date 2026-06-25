<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tukar urutan menu Cicilan dan Material Data di grup Finance,
 * sehingga Cicilan tampil sebelum Material Data (Material Data di paling bawah).
 *
 * Sebelum : Material Data (orderMenu=39) → Cicilan (orderMenu=40)
 * Sesudah : Cicilan (orderMenu=39)       → Material Data (orderMenu=40)
 */
return new class extends Migration
{
    private string $CICILAN_IDENTIFY  = 'finance-installment';
    private string $MATERIAL_IDENTIFY = 'finance-material-data';

    public function up(): void
    {
        $cicilan  = DB::table('grandChildrenMenuGroups')->where('identify', $this->CICILAN_IDENTIFY)->first(['id', 'orderMenu']);
        $material = DB::table('grandChildrenMenuGroups')->where('identify', $this->MATERIAL_IDENTIFY)->first(['id', 'orderMenu']);

        if (!$cicilan || !$material) return;

        // Hanya swap jika Cicilan masih di bawah Material Data (belum dalam urutan benar)
        if ($cicilan->orderMenu > $material->orderMenu) {
            DB::table('grandChildrenMenuGroups')->where('id', $cicilan->id)->update(['orderMenu' => $material->orderMenu]);
            DB::table('grandChildrenMenuGroups')->where('id', $material->id)->update(['orderMenu' => $cicilan->orderMenu]);
        }
    }

    public function down(): void
    {
        $cicilan  = DB::table('grandChildrenMenuGroups')->where('identify', $this->CICILAN_IDENTIFY)->first(['id', 'orderMenu']);
        $material = DB::table('grandChildrenMenuGroups')->where('identify', $this->MATERIAL_IDENTIFY)->first(['id', 'orderMenu']);

        if (!$cicilan || !$material) return;

        // Kembalikan: Material Data di atas Cicilan
        if ($cicilan->orderMenu < $material->orderMenu) {
            DB::table('grandChildrenMenuGroups')->where('id', $cicilan->id)->update(['orderMenu' => $material->orderMenu]);
            DB::table('grandChildrenMenuGroups')->where('id', $material->id)->update(['orderMenu' => $cicilan->orderMenu]);
        }
    }
};
