<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Product\DataRecapProductSellLimit;
use App\Exports\Product\DataRecapProductSellAll;

class ProductSellReport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $search;
    protected $locationId;
    protected $isExportAll;
    protected $isExportLimit;

    public function __construct($orderValue, $orderColumn, $search, $locationId, $isExportAll, $isExportLimit)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
        $this->locationId = $locationId;
        $this->isExportAll = $isExportAll;
        $this->isExportLimit = $isExportLimit;
    }

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->isExportAll == 1 && $this->isExportLimit == 1) {

            $sheets = [
                new DataRecapProductSellAll($this->orderValue, $this->orderColumn, $this->search, $this->locationId),
                new DataRecapProductSellLimit($this->orderValue, $this->orderColumn, $this->search, $this->locationId)
            ];
        } elseif ($this->isExportAll == 1 && $this->isExportLimit == 0) {

            $sheets = [
                new DataRecapProductSellAll($this->orderValue, $this->orderColumn, $this->search, $this->locationId),
            ];
        } elseif ($this->isExportAll == 0 && $this->isExportLimit == 1) {

            $sheets = [
                new DataRecapProductSellLimit($this->orderValue, $this->orderColumn, $this->search, $this->locationId),
            ];
        }

        return $sheets;
    }
}
