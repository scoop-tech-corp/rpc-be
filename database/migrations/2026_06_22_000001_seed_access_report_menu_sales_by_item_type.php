<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $row = [
            'groupName'    => 'Sales',
            'menuName'     => 'Value by Item Type',
            'url'          => 'report-detail?type=sales&detail=by-item-type',
            'roleId'       => 1,
            'accessTypeId' => 3,
            'userId'       => 1,
            'isDeleted'    => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        $exists = DB::table('accessReportMenus')
            ->where('url', $row['url'])
            ->where('roleId', $row['roleId'])
            ->where('isDeleted', 0)
            ->exists();

        if (!$exists) {
            DB::table('accessReportMenus')->insert($row);
        }
    }

    public function down(): void
    {
        DB::table('accessReportMenus')
            ->where('url', 'report-detail?type=sales&detail=by-item-type')
            ->where('roleId', 1)
            ->delete();
    }
};
