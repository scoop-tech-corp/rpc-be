<?php

namespace App\Imports\Staff;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Staff\AddDataStaff;

class ImportStaff implements WithMultipleSheets
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function sheets(): array
    {
        return [
            0 => new AddDataStaff($this->id),
            1 => new AddDataStaff($this->id),
            2 => new AddDataStaff($this->id),
            3 => new AddDataStaff($this->id),
            4 => new AddDataStaff($this->id),
            5 => new AddDataStaff($this->id),
        ];
    }
}
