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
            'menuName'     => 'Package Summary',
            'url'          => 'report-detail?type=sales&detail=package-summary',
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
            ->where('url', 'report-detail?type=sales&detail=package-summary')
            ->where('roleId', 1)
            ->delete();
    }
};
