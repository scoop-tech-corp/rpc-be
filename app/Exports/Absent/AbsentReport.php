<?php

namespace App\Exports\Absent;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Absent\DataAbsent;

class AbsentReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $dateFrom;
    protected $dateTo;
    protected $locationId;
    protected $staff;
    protected $statusPresent;

    public function __construct($orderValue, $orderColumn, $dateFrom, $dateTo, $locationId, $staff, $statusPresent, $role,$id)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->locationId = $locationId;
        $this->staff = $staff;
        $this->statusPresent = $statusPresent;
        $this->role = $role;
        $this->id = $id;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataAbsent(
                $this->orderValue,
                $this->orderColumn,
                $this->dateFrom,
                $this->dateTo,
                $this->locationId,
                $this->staff,
                $this->statusPresent,
                $this->role,
                $this->id,
            ),
        ];

        return $sheets;
    }
}
