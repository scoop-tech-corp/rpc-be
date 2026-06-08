<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ganti entry Facilities (id=37) menjadi Cage Management.
     * accessControl tetap mengacu menuListId=37 sehingga tidak perlu diubah.
     */
    public function up(): void
    {
        DB::table('grandChildrenMenuGroups')
            ->where('id', 37)
            ->update([
                'menuName'    => 'Cage Management',
                'identify'    => 'location-cage-management',
                'title'       => 'cage-management',
                'url'         => '/location/cage-management',
                'icon'        => 'GridViewIcon',
                'updated_at'  => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('grandChildrenMenuGroups')
            ->where('id', 37)
            ->update([
                'menuName'    => 'Facilities',
                'identify'    => 'Facilities',
                'title'       => 'Facilities',
                'url'         => '/location/facilities',
                'icon'        => 'HouseIcon',
                'updated_at'  => now(),
            ]);
    }
};
