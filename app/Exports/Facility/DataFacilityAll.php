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
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($this->search || $this->search == 0) {

            $res = $this->Search($this);

            if (str_contains($res, "location.id")) {

                $data = $data->where($res, '=', $this->search);
            } else if (str_contains($res, "location.locationName")) {

                $data = $data->having($res, 'like', '%' . $this->search . '%');
            } else if (str_contains($res, "facility_unit.capacity")) {

                $data = $data->having(DB::raw('IFNULL(SUM(facility_unit.capacity),0)'), '=', $this->search);
            } else if (str_contains($res, "facility_unit.unitName")) {

                $data = $data->having(DB::raw('IFNULL(count(facility_unit.unitName),0)'), '=', $this->search);
            } else if (str_contains($res, "facility.locationId")) {

                $data = $data->having(DB::raw('IFNULL(count(DISTINCT(facility.locationId)),0)'), '=', $this->search);
            } else {

                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }



        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }


        $data = $data->orderBy('facility.created_at', 'desc')->get();
        $number = 1;
        $result=[] ;
        foreach ($data as $value) {


            // $result[] = array(
            //     'number' => $number,
            //     'requirementName' => $value->requirementName,
            //     'locationName' => $prodRes->locationName,
            //     'productType' => $prodRes->productType,
            //     'productName' => $prodRes->productName,
            //     'usage' => $prodRes->usage,
            //     'quantity' => $prodRes->quantity,
            //     'isApprovedOffice' => $prodRes->isApprovedOffice,
            //     'officeApprovedBy' => $prodRes->officeApprovedBy,
            //     'officeApprovedAt' => $prodRes->officeApprovedAt,
            //     'reasonOffice' => $prodRes->reasonOffice,
            //     'isApprovedAdmin' => $prodRes->isApprovedAdmin,
            //     'adminApprovedBy' => $prodRes->adminApprovedBy,
            //     'adminApprovedAt' => $prodRes->adminApprovedAt,
            //     'reasonAdmin' => $prodRes->reasonAdmin,
            //     'dateCondition' => $prodRes->dateCondition,
            //     'itemCondition' => $prodRes->itemCondition,
            //     'createdAt' => $prodRes->createdAt,
            //     'createdBy' => $prodRes->createdBy,
            // );

            $number++;
        }



        return collect($result);




        // if ($request->orderColumn && $defaultOrderBy) {

        //     $listOrder = array(
        //         'location.id',
        //         'location.locationName',
        //         'facility_unit.capacity',
        //         'facility.locationId',
        //         'facility_unit.unitName',
        //     );

        //     if (!in_array($request->orderColumn, $listOrder)) {

        //         return response()->json([
        //             'result' => 'failed',
        //             'message' => 'Please try different Order Column',
        //             'orderColumn' => $listOrder,
        //         ]);
        //     }

        //     if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
        //         return response()->json([
        //             'result' => 'failed',
        //             'message' => 'order value must Ascending: ASC or Descending: DESC ',
        //         ]);
        //     }

        //     $data = $data->orderBy($this->orderColumn, $defaultOrderBy);
        // }

        // $data = $data->orderBy('facility.created_at', 'desc');


        // $goToPage = $request->goToPage;

        // $offset = ($goToPage - 1) * $defaultRowPerPage;

        // $count_data = $data->count();
        // $count_result = $count_data - $offset;

        // if ($count_result < 0) {
        //     $data = $data->offset(0)->limit($defaultRowPerPage)->get();
        // } else {
        //     $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
        // }

        // $total_paging = $count_data / $defaultRowPerPage;
        // return response()->json(['totalPagination' => ceil($total_paging), 'data' => $data], 200);



        //ahmad punya
        // if ($this->userRole == 'Administrator') {

        //     $data = DB::table('productInventories as p')
        //         ->join('users as u', 'p.userId', 'u.id')
        //         ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
        //         ->join('location as loc', 'loc.Id', 'p.locationId')
        //         ->select(
        //             'pl.id',
        //             'pl.productType',
        //         )->distinct()
        //         ->where('p.isApprovalAdmin', '=', 1)
        //         ->where('pl.isApprovedAdmin', '=', 0)
        //         ->where('p.isDeleted', '=', 0);
        // } elseif ($this->userRole == 'Office') {
        //     $data = DB::table('productInventories as p')
        //         ->join('users as u', 'p.userId', 'u.id')
        //         ->join('location as loc', 'loc.Id', 'p.locationId')
        //         ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
        //         ->select(
        //             'pl.id',
        //             'pl.productType',
        //         )->distinct()
        //         ->where('p.isApprovalOffice', '=', 1)
        //         ->where('pl.isApprovedOffice', '=', 0)
        //         ->where('p.isDeleted', '=', 0);
        // }

        // if ($this->search) {
        //     $res = $this->Search($this);
        //     if ($res) {
        //         $data = $data->where($res[0], 'like', '%' . $this->search . '%');

        //         for ($i = 1; $i < count($res); $i++) {

        //             $data = $data->orWhere($res[$i], 'like', '%' . $this->search . '%');
        //         }
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

        // $data = $data->orderBy('p.id', 'desc')->get();

        // $number = 1;

        // foreach ($data as $value) {

        //     if ($value->productType == 'productSell') {

        //         $prodRes = DB::table('productInventoryLists as pi')
        //             ->join('productInventories as pin', 'pin.id', 'pi.productInventoryId')
        //             ->join('productSells as p', 'p.id', 'pi.productId')
        //             ->join('usages as u', 'u.id', 'pi.usageId')
        //             ->join('location as loc', 'loc.id', 'pin.locationId')
        //             ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
        //             ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
        //             ->leftJoin('users as uCre', 'pi.userId', 'uCre.id')
        //             ->select(
        //                 'pin.requirementName',
        //                 'loc.locationName',
        //                 DB::raw("CASE WHEN pi.productType = 'productSell' THEN 'Produk Jual' WHEN pi.productType = 'productClinic' THEN 'Produk Klinik' END as productType"),
        //                 'p.fullName as productName',
        //                 'u.usage',
        //                 'pi.quantity',

        //                 'pi.isApprovedOffice',
        //                 DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
        //                 DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
        //                 DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

        //                 'pi.isApprovedAdmin',
        //                 DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
        //                 DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
        //                 DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

        //                 DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
        //                 DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),

        //                 DB::raw("IFNULL(DATE_FORMAT(pi.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
        //                 DB::raw("IFNULL(uCre.firstName,'') as createdBy"),
        //             )
        //             ->where('pi.id', '=', $value->id)
        //             ->orderBy('pi.id', 'desc')
        //             ->first();

        //         $result[] = array(
        //             'number' => $number,
        //             'requirementName' => $prodRes->requirementName,
        //             'locationName' => $prodRes->locationName,
        //             'productType' => $prodRes->productType,
        //             'productName' => $prodRes->productName,
        //             'usage' => $prodRes->usage,
        //             'quantity' => $prodRes->quantity,
        //             'isApprovedOffice' => $prodRes->isApprovedOffice,
        //             'officeApprovedBy' => $prodRes->officeApprovedBy,
        //             'officeApprovedAt' => $prodRes->officeApprovedAt,
        //             'reasonOffice' => $prodRes->reasonOffice,
        //             'isApprovedAdmin' => $prodRes->isApprovedAdmin,
        //             'adminApprovedBy' => $prodRes->adminApprovedBy,
        //             'adminApprovedAt' => $prodRes->adminApprovedAt,
        //             'reasonAdmin' => $prodRes->reasonAdmin,
        //             'dateCondition' => $prodRes->dateCondition,
        //             'itemCondition' => $prodRes->itemCondition,
        //             'createdAt' => $prodRes->createdAt,
        //             'createdBy' => $prodRes->createdBy,
        //         );
        //     } elseif ($value->productType == 'productClinic') {

        //         $prodRes = DB::table('productInventoryLists as pi')
        //             ->join('productInventories as pin', 'pin.id', 'pi.productInventoryId')
        //             ->join('productClinics as p', 'p.id', 'pi.productId')
        //             ->join('usages as u', 'u.id', 'pi.usageId')
        //             ->join('location as loc', 'loc.id', 'pin.locationId')
        //             ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
        //             ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
        //             ->leftJoin('users as uCre', 'pi.userId', 'uCre.id')
        //             ->select(
        //                 'pin.requirementName',
        //                 DB::raw("CASE WHEN pi.productType = 'productSell' THEN 'Produk Jual' WHEN pi.productType = 'productClinic' THEN 'Produk Klinik' END as productType"),
        //                 'pi.productType',
        //                 'p.fullName as productName',
        //                 'u.usage',
        //                 'pi.quantity',

        //                 'pi.isApprovedOffice',
        //                 DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
        //                 DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
        //                 DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

        //                 'pi.isApprovedAdmin',
        //                 DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
        //                 DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
        //                 DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

        //                 DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
        //                 DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),

        //                 DB::raw("IFNULL(DATE_FORMAT(pi.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
        //                 DB::raw("IFNULL(uCre.firstName,'') as createdBy"),
        //             )
        //             ->where('pi.id', '=', $value->id)
        //             ->orderBy('pi.id', 'desc')
        //             ->first();

        //         $result[] = array(
        //             'number' => $number,
        //             'requirementName' => $prodRes->requirementName,
        //             'locationName' => $prodRes->locationName,
        //             'productType' => $prodRes->productType,
        //             'productName' => $prodRes->productName,
        //             'usage' => $prodRes->usage,
        //             'quantity' => $prodRes->quantity,
        //             'isApprovedOffice' => $prodRes->isApprovedOffice,
        //             'officeApprovedBy' => $prodRes->officeApprovedBy,
        //             'officeApprovedAt' => $prodRes->officeApprovedAt,
        //             'reasonOffice' => $prodRes->reasonOffice,
        //             'isApprovedAdmin' => $prodRes->isApprovedAdmin,
        //             'adminApprovedBy' => $prodRes->adminApprovedBy,
        //             'adminApprovedAt' => $prodRes->adminApprovedAt,
        //             'reasonAdmin' => $prodRes->reasonAdmin,
        //             'dateCondition' => $prodRes->dateCondition,
        //             'itemCondition' => $prodRes->itemCondition,
        //             'createdAt' => $prodRes->createdAt,
        //             'createdBy' => $prodRes->createdBy,
        //         );
        //     }

        //     $number++;
        // }

        // return collect($result);
    }






    private function Search($search)
    {
        // $temp_column = null;

        // $data = DB::table('productInventories as p')
        //     ->select('p.requirementName')
        //     ->where('p.isDeleted', '=', 0);

        // if ($search) {
        //     $data = $data->where('p.requirementName', 'like', '%' . $search . '%');
        // }

        // $data = $data->get();

        // if (count($data)) {
        //     $temp_column[] = 'p.requirementName';
        // }

        // $data = DB::table('productInventories as p')
        //     ->join('users as u', 'p.userId', 'u.id')
        //     ->select('u.firstName')
        //     ->where('p.isDeleted', '=', 0);

        // if ($search) {
        //     $data = $data->where('u.firstName', 'like', '%' . $search . '%');
        // }

        // $data = $data->get();

        // if (count($data)) {
        //     $temp_column[] = 'u.firstName';
        // }

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
                'No.', 'Nama Lokasi ', 'Nama Fasilitas', 'Status',
                'Kapasitas', 'Jumlah',
                'Catatan '
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
                // $item['number'],
                // $item['requirementName'],
                // $item['locationName'],
                // $item['productType'],
                // $item['productName'],
                // $item['usage'],
                // strval($item['quantity']),
                // $item['itemCondition'],
                // $item['dateCondition'],

                // $item['isApprovedOffice'],
                // $item['officeApprovedBy'],
                // $item['officeApprovedAt'],
                // $item['reasonOffice'],

                // $item['isApprovedAdmin'],
                // $item['adminApprovedBy'],
                // $item['adminApprovedAt'],
                // $item['reasonAdmin'],

                // $item['createdBy'],
                // $item['createdAt']
            ],
        ];
        return $res;
    }
}
