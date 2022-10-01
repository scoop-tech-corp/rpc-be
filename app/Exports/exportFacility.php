<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class exportFacility implements FromCollection, WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
      
        $data = DB::table('facility')
                 ->select('facility.id as id',
                          'facility.facilityCode as facilityCode',
                          'facility.facilityName as facilityName',
                          'facility.locationName as locationName',
                          'facility.capacity as capacity',
                  DB::raw("CASE WHEN facility.status=1 then 'Active' else 'Non Active' end as status" ),)
                  ->where('facility.isDeleted', '=', '0')
                  ->get();
        return collect($data);
    }


    public function headings(): array
    {
       return [
         'No',
         'Facility Code',
         'Facility Name',
         'Location Name',
         'Capacity',
         'Status'
       ];
    }

    public function title(): string
    {
        return 'Facility';
    }

}
