<?php

namespace App\Exports\StaffLeave;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Staff\DataStaffLeaveAll;

class exportBalance implements WithMultipleSheets
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

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
  
        $sheets = [];

        $sheets = [
            new DataBalanceAll($this->orderValue, $this->orderColumn, $this->rolesIndex, $this->userId, $this->locationId),
        ];

        return $sheets;
    }
}
