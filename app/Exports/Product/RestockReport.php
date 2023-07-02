<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RestockReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $supplierId;
    protected $role;
    protected $type;

    public function __construct($orderValue, $orderColumn, $locationId, $supplierId, $role, $type)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
        $this->supplierId = $supplierId;
        $this->role = $role;
        $this->type = $type;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataRecapRestock($this->orderValue, $this->orderColumn, $this->locationId, $this->supplierId, $this->role, $this->type)
        ];

        return $sheets;
    }
}
