<?php

namespace App\Exports\Finance;

use App\Exports\Finance\DataQuotation;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class QuotationReport implements WithMultipleSheets
{
    use Exportable;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $status;
    protected $typeOfService;
    protected $dateFrom;
    protected $dateTo;
    protected $search;

    public function __construct(
        $orderValue,
        $orderColumn,
        $locationId,
        $status,
        $typeOfService,
        $dateFrom,
        $dateTo,
        $search
    ) {
        $this->orderValue    = $orderValue;
        $this->orderColumn   = $orderColumn;
        $this->locationId    = $locationId;
        $this->status        = $status;
        $this->typeOfService = $typeOfService;
        $this->dateFrom      = $dateFrom;
        $this->dateTo        = $dateTo;
        $this->search        = $search;
    }

    public function sheets(): array
    {
        return [
            new DataQuotation(
                $this->orderValue,
                $this->orderColumn,
                $this->locationId,
                $this->status,
                $this->typeOfService,
                $this->dateFrom,
                $this->dateTo,
                $this->search,
            ),
        ];
    }
}
