<?php

namespace App\Exports\Staff;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Models\Staff\AccessControlSchedule;

class DataAccessControlScheduleAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping, WithColumnFormatting
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $locationId;


    public function __construct($orderValue, $orderColumn, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
    }

    public function getAllData()
    {
        $group =  DB::table('accessControlSchedules as a')
            ->select(
                'locationId',
                'usersId',
                DB::raw('COUNT(listMenuId) as totalAccessMenu'),
                DB::raw('CAST(MAX(createdBy) AS SIGNED) as createdBy'),
                DB::raw('MAX(created_at) as created_at')
            )->where([
                ['isDeleted', '=', 0]
            ])
            ->groupBy('locationId', 'usersId')
            ->orderByDesc('created_at');

        $data = DB::table(DB::raw("({$group->toSql()}) as a"))
            ->mergeBindings($group)
            ->leftJoin('users as b', function ($join) {
                $join->on('a.usersId', '=', 'b.id');
            })
            ->leftJoin('users as x', function ($join) {
                $join->on('a.createdBy', '=', 'x.id');
            })
            ->leftJoin('location as c', 'c.id', '=', 'a.locationId')
            ->leftjoin('jobTitle as d', 'd.id', '=', 'b.jobTitleId')
            ->select(
                'a.usersId',
                DB::raw("
            REPLACE(
                TRIM(
                    REPLACE(
                        CONCAT(
                            IFNULL(b.firstName, ''),
                            IF(b.middleName IS NOT NULL AND b.middleName != '', CONCAT(' ', b.middleName), ''),
                            IFNULL(CONCAT(' ', b.lastName), ''),
                            IFNULL(CONCAT(' (', b.nickName, ')'), '')
                        ),
                        '  (',
                        '('
                    )
                ),
                ' (',
                '('
            ) AS name"),
                'd.jobName as jobTitle',
                'c.locationName as location',
                'a.locationId as locationId',
                DB::raw('IFNULL(a.totalAccessMenu, 0) as totalAccessMenu'),
                'x.firstName as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d/%m/%Y %H:%i:%s") as createdAt'),
            )->where([
                ['b.isDeleted', '=', '0'],
                ['x.isDeleted', '=', '0']
            ]);

        return $data;

        return $data;
    }



    public function collection()
    {

        $data = $this->getAllData();


        if ($this->locationId) {

            $test = $this->locationId;
            if ((!is_null($test[0]))) {

                $data = $data->where(function ($query) use ($test) {
                    foreach ($test as $id) {
                        $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                    }
                });
            }
        }

        if ($this->orderValue) {

            $defaultOrderBy = $this->orderValue;
        }

        if ($this->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'usersId',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

            if (!in_array($this->orderColumn, $listOrder)) {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please try different Order Column',
                    'orderColumn' => $listOrder,
                ]);
            }

            if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'order value must Ascending: ASC or Descending: DESC ',
                ]);
            }

            $data = $data->orderBy($this->orderColumn, $defaultOrderBy);
        }


        $data = DB::table($data)
            ->select(
                'usersId',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            )->get();

        $val = 1;

        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }
    public function columnFormats(): array
    {

        return [
            'E' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Nama Staff',
                'Posisi',
                'Location',
                'Total Akses Menu',
                'Created By',
                'Created At',
            ],
        ];
    }

    public function title(): string
    {
        return 'Access Control Schedule';
    }


    public function map($item): array
    {
        $totalAccessMenu = $item->totalAccessMenu ?? 0;
        $res = [
            [
                $item->number,
                $item->name,
                $item->jobTitle,
                $item->location,
                (string)$totalAccessMenu,
                $item->createdBy,
                $item->createdAt,
            ],
        ];

        return $res;
    }
}
