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

        $groupedAccessSchedules = AccessControlSchedule::select('usersId', DB::raw('COUNT(*) as totalAccessMenu'))
            ->groupBy('usersId');

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->leftJoinSub($groupedAccessSchedules, 'f', function ($join) {
                $join->on('f.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as id',
                DB::raw("
                REPLACE(
                    TRIM(
                        REPLACE(
                            CONCAT(
                                IFNULL(a.firstName, ''),
                                IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
                                IFNULL(CONCAT(' ', a.lastName), ''),
                                IFNULL(CONCAT(' (', a.nickName, ')'), '')
                            ),
                            '  (',
                            '('
                        )
                    ),
                    ' (',
                    '('
                ) AS name
                "),
                'b.jobName as jobTitle',
                'e.locationName as location',
                'e.locationId as locationId',
                DB::raw('IFNULL(f.totalAccessMenu, 0) as totalAccessMenu'),
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d/%m/%Y %H:%i:%s") as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0']
            ]);


        info($subquery->get());
        $data = DB::table($subquery, 'a');

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
                'id',
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
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            )
            ->orderBy('updated_at', 'desc')
            ->get();

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
