<?php

namespace App\Imports\Product;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Product\AddDataProductSell;

class ImportProductSell implements WithMultipleSheets
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function sheets(): array
    {
        return [
            0 => new AddDataProductSell($this->id),
        ];
    }
}
