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
        $groupDetails =  DB::table('accessControlSchedulesDetail as a')
            ->select(
                'scheduleMasterId',
                DB::raw('CAST(COUNT(listMenuId) AS SIGNED) as totalAccessMenu'),
                DB::raw('CAST(MAX(createdBy) AS SIGNED) as createdBy'),
                DB::raw('MAX(created_at) as created_at')
            )->where([
                ['isDeleted', '=', 0]
            ])
            ->groupBy('scheduleMasterId')
            ->orderByDesc('created_at');

        $data = DB::table('accessControlSchedulesMaster as a')
            ->leftJoinSub($groupDetails, 'b', function ($join) {
                $join->on('b.scheduleMasterId', '=', 'a.id');
            })
            ->leftJoin('users as c', function ($join) {
                $join->on('a.usersId', '=', 'c.id');
            })
            ->leftJoin('users as d', function ($join) {
                $join->on('a.createdBy', '=', 'd.id');
            })
            ->leftJoin('location as e', 'e.id', '=', 'a.locationId')
            ->leftjoin('jobTitle as f', 'f.id', '=', 'c.jobTitleId')
            ->select(
                'a.id',
                DB::raw('CAST((a.usersId) AS SIGNED) as usersId'),
                DB::raw("
                    REPLACE(
                        TRIM(
                            REPLACE(
                                CONCAT(
                                    IFNULL(c.firstName, ''),
                                    IF(c.middleName IS NOT NULL AND c.middleName != '', CONCAT(' ', c.middleName), ''),
                                    IFNULL(CONCAT(' ', c.lastName), ''),
                                    IFNULL(CONCAT(' (', c.nickName, ')'), '')
                                ),
                                '  (',
                                '('
                            )
                        ),
                        ' (',
                        '('
                    ) AS name"),
                'f.jobName as position',
                DB::raw('CAST((a.locationId) AS SIGNED) as locationId'),
                'e.locationName as location',
                DB::raw('CAST(IFNULL(b.totalAccessMenu, 0) AS SIGNED) as totalAccessMenu'),
                'd.firstName as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d/%m/%Y %H:%i:%s") as createdAt'),
            )
            ->where([
                ['c.isDeleted', '=', '0'],
                ['d.isDeleted', '=', '0']
            ]);

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
                'position',
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
                'position',
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
                $item->position,
                $item->location,
                (string)$totalAccessMenu,
                $item->createdBy,
                $item->createdAt,
            ],
        ];

        return $res;
    }
}
