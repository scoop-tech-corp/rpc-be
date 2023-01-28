<?php

namespace App\Exports\Facility;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Facility\DataFacilityAll;

class exportFacility implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $search;
    protected $locationId;


    public function __construct($orderValue, $orderColumn, $search, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
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
            new DataFacilityAll($this->orderValue, $this->orderColumn, $this->search, $this->locationId),
        ];

        return $sheets;
    }
}
