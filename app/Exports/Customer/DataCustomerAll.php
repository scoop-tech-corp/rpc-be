<?php

namespace App\Exports\Customer;

use DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataCustomerAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $locationId;



    public function __construct($orderValue, $orderColumn, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
    }

    public function collection()
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = null;



        $data = DB::table('customer as a')
            ->leftjoin(
                DB::raw('(
            select count(id)as jumlah,customerId from `customerPets` where isDeleted=0
            GROUP by customerId
        ) as b'),
                function ($join) {
                    $join->on('b.customerId', '=', 'a.id');
                }
            )
            ->leftjoin('customerAddresses as c', 'c.customerId', '=', 'a.id')
            ->leftjoin('location as d', 'd.id', '=', 'a.locationId')
            ->leftjoin('customerTelephones as e', 'e.customerId', '=', 'a.id')
            ->leftjoin('customerEmails as f', 'f.customerId', '=', 'a.id')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ) as customerName"),
                DB::raw("IFNULL ((b.jumlah),0) as totalPet"),
                'd.locationName as location',
                'a.locationId as locationId',
                DB::raw("CONCAT(e.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(e.type)='whatshapp' then true else false end as isWhatsapp"),
                'f.username as emailAddress',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['c.isDeleted', '=', '0'],
                ['c.isPrimary', '=', '1'],
                ['d.isDeleted', '=', '0'],
                ['e.isDeleted', '=', '0'],
                ['e.usage', '=', 'Utama'],
                ['f.isDeleted', '=', '0'],
                ['f.usage', '=', 'Utama'],
            ]);

        if ($this->locationId) {

            $val = [];
            foreach ($this->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $this->locationId);
            }
        }


        $checkOrder = null;
        if ($this->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'id',
                'customerName',
                'totalPet',
                'location',
                'phoneNumber',
                'isWhatsapp',
                'emailAddress',
                'createdBy',
                'createdAt',
            );

            if (!in_array($this->orderColumn, $listOrder)) {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please try different order column',
                    'orderColumn' => $listOrder,
                ]);
            }

            if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'order value must Ascending: ASC or Descending: DESC ',
                ]);
            }

            $checkOrder = true;
        }



        if ($checkOrder) {

            $data = DB::table($data)
                ->select(
                    'id',
                    'customerName',
                    'totalPet',
                    'location',
                    'phoneNumber',
                    'isWhatsapp',
                    'emailAddress',
                    'createdBy',
                    'createdAt',
                )
                ->orderBy($this->orderColumn, $defaultOrderBy)
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {


            $data = DB::table($data)
                ->select(
                    'id',
                    'customerName',
                    'totalPet',
                    'location',
                    'phoneNumber',
                    'isWhatsapp',
                    'emailAddress',
                    'createdBy',
                    'createdAt',
                )
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    public function headings(): array
    {

        return [
            [
                'No.',
                'Nama Customer',
                'Jumlah Pet',
                'Lokasi.',
                'Nomor Telepon',
                'Alamat Email',
                'Dibuat Oleh',
                'Dibuat Tanggal',

            ],
        ];
    }

    public function title(): string
    {
        return 'Customer';
    }

    public function map($item): array
    {

        $res = [
            [
                $item->number,
                $item->customerName,
                $item->totalPet,
                $item->location,
                $item->phoneNumber,
                $item->emailAddress,
                $item->createdBy,
                $item->createdAt,
            ],
        ];


        return $res;
    }
}
