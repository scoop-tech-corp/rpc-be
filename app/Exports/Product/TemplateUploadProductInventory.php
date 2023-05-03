<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Product\AddProductInventory;
use App\Exports\Product\ExampleAddProductInventory;
use App\Exports\Product\ProductSaleList;
use App\Exports\Product\ProductClinicList;
use App\Exports\Product\LocationList;
use App\Exports\Product\UsageList;

class TemplateUploadProductInventory implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new AddProductInventory(),
            new ExampleAddProductInventory(),
            new ProductSaleList(),
            new ProductClinicList(),
            new LocationList(),
            new UsageList,
        ];

        return $sheets;
    }
}
