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

class DataStaffAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping, WithColumnFormatting
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

    public function collection()
    {
        $defaultOrderBy = "asc";

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,a.firstName) ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'e.locationId as locationId',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
            ]);


        $data = DB::table($subquery, 'a');

        if ($this->locationId) {

            $data = $data->whereIn('a.locationid', $this->locationId);
        }

        if ($this->orderValue) {

            $defaultOrderBy = $this->orderValue;
        }

        if ($this->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'id',
                'name',
                'jobTitle',
                'emailAddress',
                'phoneNumber',
                'isWhatsapp',
                'status',
                'location',
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
                'emailAddress',
                'phoneNumber',
                'isWhatsapp',
                'status',
                'location',
                'createdBy',
                'createdAt'
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
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Nama Staff ',
                'Email Address',
                'Phone Number',
                'Nomor Whatshapp Aktif',
                'Status',
                'Location',
                'Created By',
                'Created At',
            ],
        ];
    }

    public function title(): string
    {
        return 'Data Staff';
    }

    public function map($item): array
    {

        $res = [
            [
                $item->number,
                $item->name,
                $item->emailAddress,
                $item->phoneNumber,
                $item->isWhatsapp,
                $item->status,
                $item->location,
                $item->createdBy,
                $item->createdAt,
            ],
        ];

        return $res;
    }
}
