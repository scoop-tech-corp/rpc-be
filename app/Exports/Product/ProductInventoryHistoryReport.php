<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Product\DataProductInventoryHistory;

class ProductInventoryHistoryReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $fromDate;
    protected $toDate;
    protected $search;
    protected $locationId;
    protected $role;

    public function __construct($orderValue, $orderColumn, $fromDate, $toDate, $search, $locationId, $role)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
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
            new DataProductInventoryHistory($this->orderValue, $this->orderColumn, 
            $this->fromDate, $this->toDate, $this->search, $this->locationId, $this->role),
        ];

        return $sheets;
    }
}
