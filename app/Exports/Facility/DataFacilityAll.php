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
    protected $locationId;


    public function __construct($orderValue, $orderColumn, $search, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
        $this->locationId = $locationId;
    }

    public function collection()
    {
        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";


        
        $data = DB::table('location as location')
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
                DB::raw("IFNULL ((facility_unit.unitName),'-') as NamaFasilitas"),
                DB::raw("CASE WHEN facility_unit.status = 1 THEN 'Aktif' else 'Tidak Aktif' END as Status"),
                DB::raw("IFNULL ((facility_unit.capacity),'-') as Kapasitas"),
                DB::raw("IFNULL ((facility_unit.amount),'-') as Jumlah"),
                DB::raw("IFNULL ((facility_unit.notes),'-') as Catatan"),
            )
            ->where([['a.isDeleted', '=', '0']]);


        if ($this->locationId) {

            $val = [];
            foreach ($this->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('location.id', $this->locationId);
            }
        }

        if ($this->orderValue) {
            $defaultOrderBy = $this->orderValue;
        }

        if ($this->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'location.id',
                'location.locationName',
                'facility_unit.capacity',
                'facility.locationId',
                'facility_unit.unitName',
            );

            if (!in_array($this->orderColumn, $listOrder)) {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please try different Order Column',
                    'orderColumn' => $listOrder,
                ]);
            }

            if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'order value must Ascending: ASC or Descending: DESC ',
                ]);
            }

            $data = $data->orderBy($this->orderColumn, $defaultOrderBy);
        }

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

        if ($search) {
            $data = $data->where('a.locationName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {

            $temp_column = 'a.locationName';
            return $temp_column;
        }



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


        if ($search) {
            $data = $data->where('c.unitName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'c.unitName';
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

        if ($this->search) {

            return 'Data Facility';
        } else {

            return 'All Data Facility';
        }
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
