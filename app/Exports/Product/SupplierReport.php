<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SupplierReport implements WithMultipleSheets
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
            new DataRecapSupplier($this->orderValue, $this->orderColumn)
        ];

        return $sheets;
    }
}
