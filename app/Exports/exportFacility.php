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
      
        $data = DB::table('location')
                ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                    function ($join) {
                        $join->on('facility.locationName', '=', 'location.locationName');
                    })
                ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                    function ($join) {
                        $join->on('facility_unit.locationName', '=', 'facility.locationName');
                    })
                ->select('location.id as id',
                    'location.locationName as locationName',
                    DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                    DB::raw("IFNULL (count(DISTINCT(facility.locationName)),0) as facilityVariation"),
                    DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
                ->where([['location.isDeleted', '=', '0']])
                ->groupBy('location.locationName', 'location.codeLocation', 'location.id', 'location.created_at')
                ->get();

        return collect($data);
    }


    public function headings(): array
    {
       return [
         'No',
         'Location Name',
         'Capacity Usage',
         'Facility Variation',
         'Unit Total',
       ];
    }

    public function title(): string
    {
        return 'Facility';
    }

}
