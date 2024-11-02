<?php

namespace App\Imports\Customer;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Customer\AddDataCustomer;

class ImportCustomer implements WithMultipleSheets
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function sheets(): array
    {
        return [
            0 => new AddDataCustomer($this->id),
            1 => new AddDataCustomer($this->id),
            2 => new AddDataCustomer($this->id),
            3 => new AddDataCustomer($this->id),
            4 => new AddDataCustomer($this->id),
            5 => new AddDataCustomer($this->id),
        ];
    }
}
