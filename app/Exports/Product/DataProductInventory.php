<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataProductInventory implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $search;
    protected $locationId;
    protected $userRole;

    public function __construct($orderValue, $orderColumn, $locationId, $userRole)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
        $this->userRole = $userRole;
    }

    public function collection()
    {
        if ($this->userRole == 'Administrator') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'pl.id',
                    'pl.productType',
                )->distinct()
                ->where('p.isDeleted', '=', 0);
        } elseif ($this->userRole == 'Office') {
            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
                ->select(
                    'pl.id',
                    'pl.productType',
                )->distinct()
                ->where('p.isDeleted', '=', 0);
        }

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->orderBy('p.id', 'desc')->get();

        $number = 1;

        foreach ($data as $value) {

            $prodRes = DB::table('productInventoryLists as pi')
                    ->join('productInventories as pin', 'pin.id', 'pi.productInventoryId')
                    ->join('products as p', 'p.id', 'pi.productId')
                    ->join('usages as u', 'u.id', 'pi.usageId')
                    ->join('location as loc', 'loc.id', 'pin.locationId')
                    ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
                    ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
                    ->leftJoin('users as uCre', 'pi.userId', 'uCre.id')
                    ->select(
                        'pin.requirementName',
                        'loc.locationName',
                        DB::raw("CASE WHEN p.category = 'sell' THEN 'Produk Jual' WHEN p.category = 'clinic' THEN 'Produk Klinik' END as productType"),
                        'p.fullName as productName',
                        'u.usage',
                        'pi.quantity',

                        DB::raw("CASE WHEN pi.isApprovedOffice = 0 THEN 'Menunggu Persetujuan' WHEN pi.isApprovedOffice = 1 THEN 'Diterima' WHEN pi.isApprovedOffice = 2 THEN 'Ditolak' END as isApprovedOffice"),
                        DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

                        DB::raw("CASE WHEN pi.isApprovedAdmin = 0 THEN 'Menunggu Persetujuan' WHEN pi.isApprovedAdmin = 1 THEN 'Diterima' WHEN pi.isApprovedAdmin = 2 THEN 'Ditolak' END as isApprovedAdmin"),
                        DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                        DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

                        DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
                        DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),

                        DB::raw("IFNULL(DATE_FORMAT(pi.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
                        DB::raw("IFNULL(uCre.firstName,'') as createdBy"),
                    )
                    ->where('pi.id', '=', $value->id)
                    ->orderBy('pi.id', 'desc')
                    ->first();

                $result[] = array(
                    'number' => $number,
                    'requirementName' => $prodRes->requirementName,
                    'locationName' => $prodRes->locationName,
                    'productType' => $prodRes->productType,
                    'productName' => $prodRes->productName,
                    'usage' => $prodRes->usage,
                    'quantity' => $prodRes->quantity,
                    'isApprovedOffice' => $prodRes->isApprovedOffice,
                    'officeApprovedBy' => $prodRes->officeApprovedBy,
                    'officeApprovedAt' => $prodRes->officeApprovedAt,
                    'reasonOffice' => $prodRes->reasonOffice,
                    'isApprovedAdmin' => $prodRes->isApprovedAdmin,
                    'adminApprovedBy' => $prodRes->adminApprovedBy,
                    'adminApprovedAt' => $prodRes->adminApprovedAt,
                    'reasonAdmin' => $prodRes->reasonAdmin,
                    'dateCondition' => $prodRes->dateCondition,
                    'itemCondition' => $prodRes->itemCondition,
                    'createdAt' => $prodRes->createdAt,
                    'createdBy' => $prodRes->createdBy,
                );

            // if ($value->productType == 'productSell') {


            // } elseif ($value->productType == 'productClinic') {

            //     $prodRes = DB::table('productInventoryLists as pi')
            //         ->join('productInventories as pin', 'pin.id', 'pi.productInventoryId')
            //         ->join('productClinics as p', 'p.id', 'pi.productId')
            //         ->join('usages as u', 'u.id', 'pi.usageId')
            //         ->join('location as loc', 'loc.id', 'pin.locationId')
            //         ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
            //         ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
            //         ->leftJoin('users as uCre', 'pi.userId', 'uCre.id')
            //         ->select(
            //             'pin.requirementName',
            //             'loc.locationName',
            //             DB::raw("CASE WHEN pi.productType = 'productSell' THEN 'Produk Jual' WHEN pi.productType = 'productClinic' THEN 'Produk Klinik' END as productType"),
            //             'p.fullName as productName',
            //             'u.usage',
            //             'pi.quantity',

            //             DB::raw("CASE WHEN pi.isApprovedOffice = 0 THEN 'Menunggu Persetujuan' WHEN pi.isApprovedOffice = 1 THEN 'Diterima' WHEN pi.isApprovedOffice = 2 THEN 'Ditolak' END as isApprovedOffice"),
            //             DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
            //             DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
            //             DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

            //             DB::raw("CASE WHEN pi.isApprovedAdmin = 0 THEN 'Menunggu Persetujuan' WHEN pi.isApprovedAdmin = 1 THEN 'Diterima' WHEN pi.isApprovedAdmin = 2 THEN 'Ditolak' END as isApprovedAdmin"),
            //             DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
            //             DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
            //             DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

            //             DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
            //             DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),

            //             DB::raw("IFNULL(DATE_FORMAT(pi.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
            //             DB::raw("IFNULL(uCre.firstName,'') as createdBy"),
            //         )
            //         ->where('pi.id', '=', $value->id)
            //         ->orderBy('pi.id', 'desc')
            //         ->first();

            //     $result[] = array(
            //         'number' => $number,
            //         'requirementName' => $prodRes->requirementName,
            //         'locationName' => $prodRes->locationName,
            //         'productType' => $prodRes->productType,
            //         'productName' => $prodRes->productName,
            //         'usage' => $prodRes->usage,
            //         'quantity' => $prodRes->quantity,
            //         'isApprovedOffice' => $prodRes->isApprovedOffice,
            //         'officeApprovedBy' => $prodRes->officeApprovedBy,
            //         'officeApprovedAt' => $prodRes->officeApprovedAt,
            //         'reasonOffice' => $prodRes->reasonOffice,
            //         'isApprovedAdmin' => $prodRes->isApprovedAdmin,
            //         'adminApprovedBy' => $prodRes->adminApprovedBy,
            //         'adminApprovedAt' => $prodRes->adminApprovedAt,
            //         'reasonAdmin' => $prodRes->reasonAdmin,
            //         'dateCondition' => $prodRes->dateCondition,
            //         'itemCondition' => $prodRes->itemCondition,
            //         'createdAt' => $prodRes->createdAt,
            //         'createdBy' => $prodRes->createdBy,
            //     );
            // }

            $number++;
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            [
                'No.', 'Nama Kebutuhan', 'Lokasi', 'Tipe Produk',
                'Nama Produk', 'Kegunaan',
                'Jumlah', 'Kondisi Barang', 'Tanggal Kondisi',
                'Status Approval Office', 'Approval diberikan Oleh (Office)',
                'Approval diberikan Pada (Office)', 'Alasan (Office)',

                'Status Approval Admin', 'Approval diberikan Oleh (Admin)',
                'Approval diberikan Pada (Admin)', 'Alasan (Admin)',
                'Tanggal Dibuat', 'Dibuat Oleh'
            ],
        ];
    }

    public function title(): string
    {
        return 'Data Produk Inventori';
    }

    public function map($item): array
    {
        $res = [
            [
                $item['number'],
                $item['requirementName'],
                $item['locationName'],
                $item['productType'],
                $item['productName'],
                $item['usage'],
                strval($item['quantity']),
                $item['itemCondition'],
                $item['dateCondition'],

                $item['isApprovedOffice'],
                $item['officeApprovedBy'],
                $item['officeApprovedAt'],
                $item['reasonOffice'],

                $item['isApprovedAdmin'],
                $item['adminApprovedBy'],
                $item['adminApprovedAt'],
                $item['reasonAdmin'],

                $item['createdBy'],
                $item['createdAt']
            ],
        ];
        return $res;
    }
}
