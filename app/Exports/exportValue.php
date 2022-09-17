<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\Location;
use DB;

class exportValue implements FromCollection, WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
     
        $data = DB::table('location')
        ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
        ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
        ->select('location.id as id',
            'location.codeLocation as codeLocation',
            'location.locationName as locationName',
            DB::raw("CASE WHEN location.isBranch=1 then 'Aktif' else 'Non Aktif' end as isBranch" ),
            'location_detail_address.addressName as addressName', 
            'location_detail_address.cityName as cityName',    
            DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
            DB::raw("CASE WHEN location.status=1 then 'Aktif' else 'Non Aktif' end as status" ),
            )
         ->where([
                  ['location_detail_address.usage', '=', 'utama'], 
                 ['location_telephone.usage', '=', 'utama'],
                 ['location.isDeleted', '=', '0']

         ])
         ->get();


        return collect($data);
    }

    public function headings(): array
    {
       return [
         'No',
         'Kode Lokasi',
         'Nama Lokasi',
         'Status Cabang',
         'Alamat',
         'Kota',
         'Nomor Telepon',
         'Status'
       ];
    }

    public function title(): string
    {
        return 'Location';
    }

}
