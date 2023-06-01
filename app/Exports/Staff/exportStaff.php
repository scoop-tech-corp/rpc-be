<?php

namespace App\Exports\Staff;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Staff\DataStaffAll;

class exportStaff implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $locationId;


    public function __construct($orderValue, $orderColumn,  $locationId)
    {

        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataStaffAll($this->orderValue, $this->orderColumn,  $this->locationId),
        ];

        return $sheets;
    }
}
