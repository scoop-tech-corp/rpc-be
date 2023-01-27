<?php

namespace App\Exports\Facility;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataFacilityAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $search;


    public function __construct($orderValue, $orderColumn, $search)
    {

        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
    }

    public function collection()
    {


        $defaultOrderBy = "asc";


        $data = DB::table('location as a')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as b'),
                function ($join) {
                    $join->on('b.locationId', '=', 'a.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as c'),
                function ($join) {
                    $join->on('c.locationId', '=', 'b.locationId');
                }
            )
            ->select(
                'a.locationName as locationName',
                DB::raw("IFNULL ((c.unitName),'-') as NamaFasilitas"),
                DB::raw("CASE WHEN c.status = 1 THEN 'Aktif' else 'Tidak Aktif' END as Status"),
                DB::raw("IFNULL ((c.capacity),'-') as Kapasitas"),
                DB::raw("IFNULL ((c.amount),'-') as Jumlah"),
                DB::raw("IFNULL ((c.notes),'-') as Catatan"),
            )
            ->where([['a.isDeleted', '=', '0']]);
            //     ->where([['location.isDeleted', '=', '0']])


            // DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"), 


        // $data = DB::table('location')
        //     ->leftjoin(
        //         DB::raw('(select * from facility where isDeleted=0) as facility'),
        //         function ($join) {
        //             $join->on('facility.locationId', '=', 'location.id');
        //         }
        //     )
        //     ->leftjoin(
        //         DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
        //         function ($join) {
        //             $join->on('facility_unit.locationId', '=', 'facility.locationId');
        //         }
        //     )
        //     ->select(
        //         'location.id as locationId',
        //         'location.locationName as locationName',
        //         'facility.created_at as createdAt',
        //         DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
        //         DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
        //         DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
        //     );


        // $data = DB::table('location')
        //     ->leftjoin(
        //         DB::raw('(select * from facility where isDeleted=0) as facility'),
        //         function ($join) {
        //             $join->on('facility.locationId', '=', 'location.id');
        //         }
        //     )
        //     ->leftjoin(
        //         DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
        //         function ($join) {
        //             $join->on('facility_unit.locationId', '=', 'facility.locationId');
        //         }
        //     )
        //     ->select(
        //         'location.id as locationId',
        //         'location.locationName as locationName',
        //         'facility.created_at as createdAt',
        //         DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
        //         DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
        //         DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
        //     );
        // ->groupBy('location.locationName', 'location.id', 'facility.created_at');



        // if ($this->search) {

        //     $res = $this->Search($this);

        //     echo ($res);

        //     if (str_contains($res, "location.id")) {

        //         $data = $data->where($res, '=', $this->search);
        //     } else if (str_contains($res, "location.locationName")) {

        //         $data = $data->having($res, 'like', '%' . $this->search . '%');
        //     } else if (str_contains($res, "facility_unit.capacity")) {

        //         $data = $data->having(DB::raw('IFNULL(SUM(facility_unit.capacity),0)'), '=', $this->search);
        //     } else if (str_contains($res, "facility_unit.unitName")) {

        //         $data = $data->having(DB::raw('IFNULL(count(facility_unit.unitName),0)'), '=', $this->search);
        //     } else if (str_contains($res, "facility.locationId")) {

        //         $data = $data->having(DB::raw('IFNULL(count(DISTINCT(facility.locationId)),0)'), '=', $this->search);
        //     } else {

        //         $data = [];
        //         return response()->json([
        //             'totalPagination' => 0,
        //             'data' => $data
        //         ], 200);
        //     }
        // }



        // if ($this->orderValue) {
        //     $data = $data->orderBy($this->orderColumn, $this->orderValue);
        // }

        // $data =  $data->get();
        $data = $data->orderBy('b.created_at', 'desc')->get();

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }


        return $data;
    }






    private function Search($search)
    {

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($search || $search == 0) {
            $data = $data->where('location.id', '=', $search);
        }

        $data = $data->get();

        if (count($data)) {

            $temp_column = 'location.id';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($search || $search == 0) {
            $data = $data->where('location.locationName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location.locationName';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($search || $search == 0) {
            $data = $data->having(DB::raw('IFNULL (SUM(facility_unit.capacity),0)'), '=', $search);
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(SUM(facility_unit.capacity),0)';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($search || $search == 0) {
            $data = $data->having(DB::raw('IFNULL(count(DISTINCT(facility.locationId)),0)'), '=', $search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(count(DISTINCT(facility.locationId)),0)';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($search || $search == 0) {
            $data = $data->having(DB::raw('IFNULL(count(facility_unit.unitName),0)'), '=', $search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(count(facility_unit.unitName),0)';
            return $temp_column;
        }
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Nama Lokasi ',
                'Nama Fasilitas',
                'Status',
                'Kapasitas',
                'Jumlah',
                'Catatan'
            ],
        ];
    }

    public function title(): string
    {
        return 'All Data Facility';
    }

    public function map($item): array
    {

        $res = [
            [
                $item->number,
                $item->locationName,
                $item->NamaFasilitas,
                $item->Status,
                $item->Kapasitas,
                $item->Jumlah,
                $item->Catatan,
            ],
        ];

        return $res;
    }
}
