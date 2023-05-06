<?php

namespace App\Imports\Product;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Product\AddDataProductInventory;

class ImportProductInventory implements WithMultipleSheets
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function sheets(): array
    {
        return [
            0 => new AddDataProductInventory($this->id),
        ];
    }
}
