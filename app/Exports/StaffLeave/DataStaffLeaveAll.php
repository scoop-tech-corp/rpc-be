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
    //protected $locationId;


    public function __construct($orderValue, $orderColumn, $status)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->status = $status;
        // $this->locationId = $locationId;
    }

    public function collection()
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = DB::table('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
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


        if ($this->orderValue) {
            $defaultOrderBy = $this->orderValue;
        }

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

            $data = $data->orderBy($this->orderColumn, $defaultOrderBy);
        }

        $data = $data->orderBy('updatedAt', 'desc');

        $data = DB::table($data)
            ->select(
                'requester',
                'jobName',
                'leaveType',
                'date',
                'days',
                'remark',
                'createdAt',
            )->get();

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
                'Requester',
                'Job Name',
                'Leave Type',
                'Date',
                'Days',
                'Remark',
                'Created At'
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
