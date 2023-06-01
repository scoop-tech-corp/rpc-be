<?php

namespace App\Imports\Product;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Product\AddDataProductClinic;

class ImportProductClinic implements WithMultipleSheets
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
