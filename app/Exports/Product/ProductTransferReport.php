<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductTransferReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $role;

    public function __construct($orderValue, $orderColumn, $locationId, $role)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
        $this->role = $role;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataRecapProductTransfer($this->orderValue, $this->orderColumn, $this->locationId, $this->role)
        ];

        return $sheets;
    }
}
