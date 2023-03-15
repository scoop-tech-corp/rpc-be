<?php

namespace App\Exports\StaffLeave;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataBalanceAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;
    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $rolesIndex;
    protected $userId;
    protected $locationId;



    public function __construct($orderValue, $orderColumn, $rolesIndex, $userId, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->rolesIndex = $rolesIndex;
        $this->userId = $userId;
        $this->locationId = $locationId;
    }

    public function collection()
    {
        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = null;

        if ($this->rolesIndex == 1) {

            $data = DB::table('users as a')
                ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
                ->select(
                    DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                    'a.annualLeaveAllowance',
                    'a.annualLeaveAllowanceRemaining',
                    'a.annualSickAllowance',
                    'a.annualSickAllowanceRemaining',
                    'a.updated_at',
                )
                ->where([
                    ['a.isDeleted', '=', '0'],
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

            $data = DB::table('users as a')
                ->select(
                    DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                    'a.annualLeaveAllowance',
                    'a.annualLeaveAllowanceRemaining',
                    'a.annualSickAllowance',
                    'a.annualSickAllowanceRemaining',
                    'a.updated_at',
                )
                ->where([
                    ['a.isDeleted', '=', '0'],
                    ['a.id', '=', $this->userId],
                ]);
        }

        if ($this->orderValue) {
            $defaultOrderBy = $this->orderValue;
        }

        $checkOrder = null;

        if ($this->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'name',
                'annualLeaveAllowance',
                'annualLeaveAllowanceRemaining',
                'annualSickAllowance',
                'annualSickAllowanceRemaining',
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
                    'name',
                    'annualLeaveAllowance',
                    'annualLeaveAllowanceRemaining',
                    'annualSickAllowance',
                    'annualSickAllowanceRemaining'
                )
                ->orderBy($this->orderColumn, $defaultOrderBy)
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {

            $data = DB::table($data)
                ->select(
                    'name',
                    'annualLeaveAllowance',
                    'annualLeaveAllowanceRemaining',
                    'annualSickAllowance',
                    'annualSickAllowanceRemaining'
                )
                ->orderBy('updated_at', 'desc')
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
                'name',
                'annualLeaveAllowance',
                'annualLeaveAllowanceRemaining',
                'annualSickAllowance',
                'annualSickAllowanceRemaining',
            ],
        ];
    }

    public function title(): string
    {
        return 'Leave Balance';
    }

    public function map($item): array
    {

        $res = [
            [
                $item->number,
                $item->name,
                $item->annualLeaveAllowance,
                $item->annualLeaveAllowanceRemaining,
                $item->annualSickAllowance,
                $item->annualSickAllowanceRemaining,
            ],
        ];

        return $res;
    }
}
