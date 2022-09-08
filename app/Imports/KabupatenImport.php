<?php

namespace App\Imports;
use App\Models\kabupaten;
use Maatwebsite\Excel\Concerns\ToModel;

class KabupatenImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    		
    public function model(array $row)
    {
        return new kabupaten([
            "kodeKabupaten" =>  $row[0],
            "kodeProvinsi" =>  $row[1],
            "namaKabupaten" =>  $row[2],
        ]);
    }
}
