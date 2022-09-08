<?php

namespace App\Imports;
use App\Models\kecamatan;
use Maatwebsite\Excel\Concerns\ToModel;

class KecamatanImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
	
    public function model(array $row)
    {

        return new kecamatan([
            "kodeKecamatan" =>  $row[0],
            "kodeKabupaten" =>  $row[1],
            "namaKecamatan" =>  $row[2],
        ]);
    }
}
