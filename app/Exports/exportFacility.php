<?php

namespace App\Exports\Product;

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
    protected $isExportAll;
    protected $isExportLimit;
    protected $role;

    public function __construct($orderValue, $orderColumn, $search)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataFacilityAll($this->orderValue, $this->orderColumn, $this->search),
        ];

        return $sheets;
    }
}
