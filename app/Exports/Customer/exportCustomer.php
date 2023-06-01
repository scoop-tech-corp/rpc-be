<?php

namespace App\Exports\Customer;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Exports\Customer\DataCustomerAll;
use Carbon\Carbon;

class exportCustomer implements WithMultipleSheets
{
    use Exportable;
    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $locationId;

    public function __construct($orderValue, $orderColumn, $locationId)
    {

        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
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
            new DataCustomerAll($this->orderValue, $this->orderColumn, $this->locationId),
        ];

        return $sheets;
    }
}
