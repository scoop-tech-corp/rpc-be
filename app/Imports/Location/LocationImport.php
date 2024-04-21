<?php

namespace App\Imports\Location;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LocationImport implements WithMultipleSheets
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function sheets(): array
    {
        return [
            0 => new AddDetail($this->id),
            1 => new AddAddress($this->id),
            2 => new AddPhone($this->id),
            3 => new AddEmail($this->id),
        ];
    }
}
