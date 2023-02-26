<?php

namespace App\Exports\StaffLeave;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Exports\StaffLeave\DataStaffLeaveAll;

class exportStaffLeave implements WithMultipleSheets
{
    use Exportable;
    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $status;
    // protected $locationId;

    public function __construct($orderValue, $orderColumn, $status)
    {

        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->status = $status;
        // $this->locationId = $locationId;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            //  new DataStaffLeaveAll($this->orderValue, $this->orderColumn, $this->search, $this->locationId),
            new DataStaffLeaveAll($this->orderValue, $this->orderColumn, $this->status),
        ];

        return $sheets;
    }
}
