<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = [
            [
                'groupName'    => 'Products',
                'menuName'     => 'Batches',
                'url'          => 'report-detail?type=products&detail=batches',
                'roleId'       => 1,
                'accessTypeId' => 3,
                'userId'       => 1,
                'isDeleted'    => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'groupName'    => 'Products',
                'menuName'     => 'Expiry',
                'url'          => 'report-detail?type=products&detail=expiry',
                'roleId'       => 1,
                'accessTypeId' => 3,
                'userId'       => 1,
                'isDeleted'    => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('accessReportMenus')
                ->where('url', $row['url'])
                ->where('roleId', $row['roleId'])
                ->where('isDeleted', 0)
                ->exists();

            if (!$exists) {
                DB::table('accessReportMenus')->insert($row);
            }
        }
    }

    public function down(): void
    {
        DB::table('accessReportMenus')
            ->whereIn('url', [
                'report-detail?type=products&detail=batches',
                'report-detail?type=products&detail=expiry',
            ])
            ->where('roleId', 1)
            ->delete();
    }
};
