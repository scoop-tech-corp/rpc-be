<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Product\DataProductInventoryApproval;

class ProductInventoryApprovalReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $search;
    protected $locationId;
    protected $role;

    public function __construct($orderValue, $orderColumn, $search, $locationId, $role)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
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
            new DataProductInventoryApproval($this->orderValue, $this->orderColumn, $this->search, $this->locationId, $this->role),
        ];

        return $sheets;
    }
}
