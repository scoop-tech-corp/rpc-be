<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapProductTransfer implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $role;

    public function __construct($orderValue, $orderColumn, $locationId, $role)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
        $this->role = $role;
    }

    public function collection()
    {
        $data = DB::table('productTransfers as pt')
            ->join('users as u', 'pt.userId', 'u.id')
            ->join('users as ur', 'pt.userIdReceiver', 'ur.id')
            ->leftjoin('users as uo', 'pt.userIdOffice', 'uo.id')
            ->leftjoin('users as ua', 'pt.userIdAdmin', 'ua.id')
            ->select(
                'pt.id as id',
                'pt.productType',
                'pt.productIdOrigin',
                'pt.productIdDestination',
                'pt.transferName',
                'pt.transferNumber',
                'pt.totalItem',
                'pt.isAdminApproval',
                DB::raw("CASE pt.isAdminApproval = 1 WHEN pt.isApprovedAdmin = 0 THEN 'Waiting for approval' WHEN pt.isApprovedAdmin = 1 THEN 'Approved' ELSE 'Reject' END as Status"),
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                'u.firstName as createdBy',
                'ur.firstName as receivedBy',

                DB::raw("IFNULL(uo.firstName,'') as officeApprovedBy"),
                DB::raw("IFNULL(ua.firstName,'') as adminApprovedBy"),
                DB::raw("IFNULL(ur.firstName,'') as receivedBy"),
            )
            ->where('pt.isDeleted', '=', 0)
            ->where('pt.groupData', '=', 'history');

        $data = $data->orderBy('pt.updated_at', 'desc')->get();

        $tempData = [];

        foreach ($data as $value) {

            if ($value->productType == "Product Sell") {

                $res = DB::table('productTransfers as pt')
                    ->join('productSells as pso', 'pt.productIdOrigin', 'pso.id')
                    ->join('productSellLocations as pslo', 'pso.id', 'pslo.productSellId')
                    ->join('location as lo', 'pslo.locationId', 'lo.id')

                    ->join('productSells as psd', 'pt.productIdDestination', 'psd.id')
                    ->join('productSellLocations as psld', 'psd.id', 'psld.productSellId')
                    ->join('location as ld', 'psld.locationId', 'ld.id')

                    ->join('users as u', 'pt.userId', 'u.id')
                    ->join('users as ur', 'pt.userIdReceiver', 'ur.id')
                    ->leftjoin('users as uo', 'pt.userIdOffice', 'uo.id')
                    ->leftjoin('users as ua', 'pt.userIdAdmin', 'ua.id')
                    ->select(
                        'pt.id as id',
                        'pt.productType',
                        'pt.productIdOrigin',
                        'pt.productIdDestination',
                        'lo.locationName as from',
                        'lo.id as locationIdOrigin',
                        'ld.locationName as to',
                        'ld.id as locationIdDestination',
                        'pso.fullName as productName',
                        'pt.transferName',
                        'pt.transferNumber',
                        'pt.totalItem',
                        'pt.status',
                        'u.firstName as createdBy',
                        'ur.firstName as receivedBy',
                        DB::raw("IFNULL(ur.firstName,'') as receivedBy"),

                        DB::raw("IFNULL(DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
                    )
                    ->where('pt.id', '=', $value->id);

                $locations = $this->locationId;

                if (!$locations[0] == null) {

                    $data = $data->whereIn('lo.id', $this->locationId);
                }

                $res = $res->first();

                if ($res) {
                    array_push($tempData, $res);
                }
            } elseif ($value->productType == "Product Clinic") {
                $res = DB::table('productTransfers as pt')

                    ->join('productClinics as pco', 'pt.productIdOrigin', 'pco.id')
                    ->join('productClinicLocations as pclo', 'pco.id', 'pclo.productClinicId')
                    ->join('location as lo', 'pclo.locationId', 'lo.id')

                    ->join('productClinics as pcd', 'pt.productIdDestination', 'pcd.id')
                    ->join('productClinicLocations as pcld', 'pcd.id', 'pcld.productClinicId')
                    ->join('location as ld', 'pcld.locationId', 'ld.id')

                    ->join('users as u', 'pt.userId', 'u.id')
                    ->join('users as ur', 'pt.userIdReceiver', 'ur.id')
                    ->leftjoin('users as uo', 'pt.userIdOffice', 'uo.id')
                    ->leftjoin('users as ua', 'pt.userIdAdmin', 'ua.id')
                    ->select(
                        'pt.id as id',
                        'pt.productType',
                        'pt.productIdOrigin',
                        'pt.productIdDestination',
                        'lo.locationName as from',
                        'lo.id as locationIdOrigin',
                        'ld.locationName as to',
                        'ld.id as locationIdDestination',
                        'ld.locationName as to',
                        'pco.fullName as productName',
                        'pt.transferName',
                        'pt.transferNumber',
                        'pt.totalItem',
                        'pt.status',
                        'ur.firstName as receivedBy',
                        'u.firstName as createdBy',
                        DB::raw("IFNULL(ur.firstName,'') as receivedBy"),

                        DB::raw("IFNULL(DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
                    )
                    ->where('pt.id', '=', $value->id);

                $locations = $this->locationId;

                if (!$locations[0] == null) {

                    $data = $data->whereIn('lo.id', $this->locationId);
                }

                $res = $res->first();

                if ($res) {
                    array_push($tempData, $res);
                }
            }
        }

        $tempC = collect($tempData);
        $sorted = '';

        if ($this->orderValue == 'desc' && $this->orderColumn) {
            $tempData = $tempC->sortByDesc($this->orderColumn);
        } elseif ($this->orderValue == 'asc' && $this->orderColumn) {
            $sorted = $tempC->sortBy($this->orderColumn);
            $tempData = $sorted->values()->all();
        }

        $val = 1;
        foreach ($tempData as $key) {
            $key->number = $val;
            $val++;
        }

        return collect($tempData);
    }

    public function headings(): array
    {
        return [
            [
                'No.', 'Nomor Transfer', 'Nama Transfer', 'Asal',
                'Tujuan', 'Tipe Produk',
                'Nama Produk', 'Jumlah Item', 'Status', 'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Transfer';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->transferNumber,
                $item->transferName,
                $item->from,
                $item->to,
                $item->productType,
                $item->productName,
                strval($item->totalItem),
                $item->status,
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
