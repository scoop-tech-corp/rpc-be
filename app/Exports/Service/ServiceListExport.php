<?php

namespace App\Exports\Service;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Service\DataRecapServiceList;

class ServiceListExport implements WithMultipleSheets
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

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataRecapServiceList($this->orderValue, $this->orderColumn),
        ];

        return $sheets;
    }

}
