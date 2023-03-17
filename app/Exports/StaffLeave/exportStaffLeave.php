<?php

namespace App\Exports\StaffLeave;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Exports\StaffLeave\DataStaffLeaveAll;
use Carbon\Carbon;
class exportStaffLeave implements WithMultipleSheets
{
    use Exportable;
    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $status;
    protected $rolesIndex;
    protected $fromDate;
    protected $toDate;
    protected $userId;
    protected $locationId;

    public function __construct($orderValue, $orderColumn, $status, $rolesIndex, $fromDate, $toDate, $userId, $locationId)
    {

        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->status = $status;
        $this->rolesIndex = $rolesIndex;
        $this->fromDate =   $fromDate;
        $this->toDate = $toDate;
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
            new DataStaffLeaveAll($this->orderValue, $this->orderColumn, $this->status, $this->rolesIndex,$this->fromDate,$this->toDate, $this->userId, $this->locationId),
        ];

        return $sheets;
    }
}
