<?php

namespace App\Imports\Service;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Service\AddDataServiceList;

class ImportServiceList implements WithMultipleSheets
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function sheets(): array
    {
        return [
            0 => new AddDataServiceList($this->id),
        ];
    }
}
