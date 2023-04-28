<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Product\AddProductSell;
use App\Exports\Product\ExampleAddProductSell;
use App\Exports\Product\BrandList;
use App\Exports\Product\SupplierList;
use App\Exports\Product\LocationList;
use App\Exports\Product\CategoryList;

class TemplateUploadProductSell implements WithMultipleSheets
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
            new AddProductSell(),
            new ExampleAddProductSell(),
            new BrandList(),
            new SupplierList(),
            new LocationList(),
            new CategoryList(),
        ];

        return $sheets;
    }
}
