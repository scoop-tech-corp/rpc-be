<?php

namespace App\Imports;

use App\Models\exceltesting;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        // echo ("hello");
        // echo ($row[0]);

        return new exceltesting([
            "a" =>  $row[0],
            "b" =>  $row[1],
            "c" =>  $row[2],
            "d" =>  $row[3],
            "e" =>  $row[4],
        ]);
    }
}
