<?php

namespace App\Imports;
// use App\Models\kabupaten;
// use App\Models\kecamatan;
// use App\Models\kelurahan;
use App\Models\provinsi;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// class RegionImport implements ToModel, WithHeadingRow
class RegionImport implements ToModel
{

    // public function kabupaten(array $row)
    // {
    //     return new kabupaten([
    //         "kodeKabupaten" =>  $row[0],
    //         "kodeProvinsi" =>  $row[1],
    //         "namaKabupaten" =>  $row[2],
    //     ]);
    // }
  
    public function model(array $row)
    {   

        return new provinsi([
            "kodeProvinsi" =>  $row[0],
            "namaProvinsi" =>  $row[1],
        ]);
    }

    public function headingRow(): int
    {
       
        return 1; //masih issue mau mencoba menggunakan header
    }
  
    // public function kelurahan(array $row)
    // {
    //     return new kelurahan([
    //         "kodeKelurahan" =>  $row[0],
    //         "kodeKecamatan" =>  $row[1],
    //         "namaKelurahan" =>  $row[2],
    //     ]);
    // }
    			

    // public function kecamatan(array $row)
    // {
    //     return new kecamatan([
    //         "kodeKecamatan" =>  $row[0],
    //         "kodeKabupaten" =>  $row[1],
    //         "namaKecamatan" =>  $row[2],
    //     ]);
    // }

}


