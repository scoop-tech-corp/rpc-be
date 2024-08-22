<?php

namespace App\Exports\Promotion;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Exports\Promotion\DataPromo;

class PromoReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $type;

    public function __construct($orderValue, $orderColumn, $locationId, $type)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
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
            new DataPromo($this->orderValue, $this->orderColumn, $this->locationId, $this->type),
        ];

        return $sheets;
    }
}
