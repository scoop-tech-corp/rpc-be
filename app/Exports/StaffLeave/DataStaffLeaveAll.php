<?php

namespace App\Exports\StaffLeave;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataStaffLeaveAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $status;
    protected $locationId;
    protected $userId;
    protected $rolesIndex;


    public function __construct($orderValue, $orderColumn, $status, $rolesIndex, $userId, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->status = $status;
        $this->rolesIndex = $rolesIndex;
        $this->userId = $userId;
        $this->locationId = $locationId;
    }

    public function collection()
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = null;

        if ($this->rolesIndex  == 1) {

            $data = DB::table('leaveRequest as a')
                ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requester',
                    'c.locationName as locationName',
                    'b.jobName as jobName',
                    'a.leaveType as leaveType',
                    'a.fromDate as date',
                    'a.duration as days',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $this->status],
                ]);

            if ($this->locationId) {

                $val = [];

                foreach ($this->locationId as $temp) {
                    $val = $temp;
                }

                if ($val) {
                    $data = $data->whereIn('a.locationId', $this->locationId);
                }
            }
        } else {
            $data = DB::table('leaveRequest as a')
                ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requester',
                    'c.locationName as locationName',
                    'b.jobName as jobName',
                    'a.leaveType as leaveType',
                    'a.fromDate as date',
                    'a.duration as days',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $this->status],
                    ['a.usersId', '=', $this->userId],
                ]);
        }

        if ($this->orderValue) {
            $defaultOrderBy = $this->orderValue;
        }


        $checkOrder = null;

        if ($this->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'requesterName',
                'jobName',
                'leaveType',
                'fromDate',
                'duration',
                'remark',
                'created_at',
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

            $checkOrder = true;
        }

        if ($checkOrder) {

            $data = DB::table($data)
                ->select(
                    'requester',
                    'jobName',
                    'locationName',
                    'leaveType',
                    'date',
                    'days',
                    'remark',
                    'createdAt',
                )
                ->orderBy($this->orderColumn, $defaultOrderBy)
                ->orderBy('updatedAt', 'desc')
                ->get();
        } else {
            $data = DB::table($data)
                ->select(
                    'requester',
                    'jobName',
                    'locationName',
                    'leaveType',
                    'date',
                    'days',
                    'remark',
                    'createdAt',
                )
                ->orderBy('updatedAt', 'desc')
                ->get();
        }

        $val = 1;

        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Pemohon',
                'Jabatan',
                'Lokasi',
                'Tipe Cuti',
                'Tanggal Cuti',
                'Hari',
                'Keterangan',
                'Dibuat Pada'
            ],
        ];
    }

    public function title(): string
    {
        return 'Leave ' . $this->status;
    }

    public function map($item): array
    {

        $res = [
            [
                $item->number,
                $item->requester,
                $item->jobName,
                $item->locationName,
                $item->leaveType,
                $item->date,
                $item->days,
                $item->remark,
                $item->createdAt,
            ],
        ];

        return $res;
    }
}
