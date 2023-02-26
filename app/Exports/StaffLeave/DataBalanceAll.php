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

    public function __construct($orderValue, $orderColumn)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
    }

    public function collection()
    {
        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";


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
            ]);

        if ($this->orderValue) {
            $defaultOrderBy = $this->orderValue;
        }

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

            $data = $data->orderBy($this->orderColumn, $defaultOrderBy);
        }

        $data = $data->orderBy('updated_at', 'desc');
        $data = $data->get();

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
