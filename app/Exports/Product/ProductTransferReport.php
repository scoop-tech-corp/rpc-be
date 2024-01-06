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
    protected $locationDestinationId;
    protected $status;

    public function __construct($orderValue, $orderColumn, $locationDestinationId, $status)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationDestinationId = $locationDestinationId;
        $this->status = $status;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new DataRecapProductTransfer($this->orderValue, $this->orderColumn, $this->locationDestinationId, $this->status)
        ];

        return $sheets;
    }
}
