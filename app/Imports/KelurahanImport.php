<?php

namespace App\Imports;
use App\Models\kelurahan;
use Maatwebsite\Excel\Concerns\ToModel;

class KelurahanImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
		
    public function model(array $row)
    {
        return new kelurahan([
            "kodeKelurahan" =>  $row[0],
            "kodeKecamatan" =>  $row[1],
            "namaKelurahan" =>  $row[2],
        ]);
    }

}
