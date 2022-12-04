<?php

namespace App\Exports;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

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
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as id',
                'location.locationName as locationName',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'location.created_at');

                
        $data = $data->orderBy('location.id', 'asc');

        $data=$data->get();
      
        return collect($data);
    }

    public function headings(): array
    {
        return [
            'ID',
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
