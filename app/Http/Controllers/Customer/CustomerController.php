<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CustomerGroups;
use App\Exports\Customer\exportCustomer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use File;
use DB;

class CustomerController extends Controller
{



    public function indexCustomer(Request $request)
    {

        // if (adminAccess($request->user()->id) != 1) {
        //     return response()->json([
        //         'result' => 'The user role was invalid.',
        //         'message' => ['User Access not Authorize!'],
        //     ], 403);
        // }

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

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

            if ($request->locationId) {

                $val = [];
                foreach ($request->locationId as $temp) {
                    $val = $temp;
                }

                if ($val) {
                    $data = $data->whereIn('a.locationid', $request->locationId);
                }
            }




            if ($request->search) {

                $res = $this->Search($request);

                if ($res) {

                    if ($res == "a.firstName") {

                        $data = $data->where('a.firstName', 'like', '%' . $request->search . '%');
                    } else if ($res == "b.jumlah") {

                        $data = $data->where('b.jumlah', 'like', '%' . $request->search . '%');
                    } else if ($res == "d.locationName") {

                        $data = $data->where('d.locationName', 'like', '%' . $request->search . '%');
                    } else if ($res == "e.phoneNumber") {

                        $data = $data->where('e.phoneNumber', 'like', '%' . $request->search . '%');
                    } else if ($res == "e.type") {

                        $data = $data->where('e.type', 'like', '%' . $request->search . '%');
                    } else if ($res == "f.username") {

                        $data = $data->where('f.username', 'like', '%' . $request->search . '%');
                    } else if ($res == "a.createdBy") {

                        $data = $data->where('a.createdBy', 'like', '%' . $request->search . '%');
                    } else if ($res == "a.created_at") {

                        $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                    } else {

                        $data = [];
                        return response()->json([
                            'totalPagination' => 0,
                            'data' => $data
                        ], 200);
                    }
                }
            }




            if ($request->orderValue) {

                $defaultOrderBy = $request->orderValue;
            }


            $checkOrder = null;
            if ($request->orderColumn && $defaultOrderBy) {

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

                if (!in_array($request->orderColumn, $listOrder)) {

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
                    ->orderBy($request->orderColumn, $defaultOrderBy)
                    ->orderBy('updated_at', 'desc');
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
                    ->orderBy('updated_at', 'desc');
            }


            if ($request->rowPerPage > 0) {
                $defaultRowPerPage = $request->rowPerPage;
            }

            $goToPage = $request->goToPage;

            $offset = ($goToPage - 1) * $defaultRowPerPage;

            $count_data = $data->count();
            $count_result = $count_data - $offset;

            if ($count_result < 0) {
                $data = $data->offset(0)->limit($defaultRowPerPage)->get();
            } else {
                $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
            }

            $total_paging = $count_data / $defaultRowPerPage;

            return response()->json(['totalPagination' => ceil($total_paging), 'data' => $data], 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }



    public function exportCustomer(Request $request)
    {
        $tmp = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-Y');
        $rolesIndex = roleStaffLeave($request->user()->id);

        try {
            //  admin sama office
            if ($rolesIndex == 1 || $rolesIndex == 6) {

                if ($request->locationId) {

                    $location = DB::table('location')
                        ->select('locationName')
                        ->whereIn('id', $request->locationId)
                        ->get();

                    if ($location) {

                        foreach ($location as $key) {
                            $tmp = $tmp . (string) $key->locationName . ",";
                        }
                    }
                    $tmp = rtrim($tmp, ", ");
                }

                if ($tmp == "") {
                    $fileName = "Customer " . ucfirst($request->status) . ' ' . $date . ".xlsx";
                } else {
                    $fileName = "Customer " .  ucfirst($request->status) . ' ' . $tmp . " " . $date . ".xlsx";
                }


                return Excel::download(
                    new exportCustomer(
                        $request->orderValue,
                        $request->orderColumn,
                        $request->locationId,
                    ),
                    $fileName
                );
            } else {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Your role not autorize to access this feature',
                ], 422);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }


    private function Search($request)
    {

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



        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }

        if ($request->search) {

            $data = $data->where('a.firstName', 'like', '%' . $request->search . '%');
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.firstName';
            return $temp_column;
        }

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


        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }



        if ($request->search) {
            $data = $data->where('b.jumlah', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'b.jumlah';
            return $temp_column;
        }




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


        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }


        if ($request->search) {
            $data = $data->where('d.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'd.locationName';
            return $temp_column;
        }




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


        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }


        if ($request->search) {
            $data = $data->where('e.phoneNumber', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'e.phoneNumber';
            return $temp_column;
        }



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

        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }



        if ($request->search) {
            $data = $data->where('e.type', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'e.type';
            return $temp_column;
        }


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

        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }


        if ($request->search) {
            $data = $data->where('f.username', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'f.username';
            return $temp_column;
        }


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

        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }



        if ($request->search) {
            $data = $data->where('a.createdBy', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.createdBy';
            return $temp_column;
        }


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

        if ($request->locationId) {

            $val = [];
            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationid', $request->locationId);
            }
        }



        if ($request->search) {
            $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.created_at';
            return $temp_column;
        }
    }


    public function createCustomer(Request $request)
    {

        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'result' => 'The user role was invalid.',
                'message' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'firstName' => 'required|string|max:100',
                    'middleName' => 'nullable|string|max:100',
                    'lastName' => 'nullable|string|max:100',
                    'titleCustomerId' => 'nullable|integer',
                    'nickName' => 'nullable|string|max:100',
                    'customerGroupId' => 'nullable|integer',
                    'locationId' => 'nullable|integer',
                    'notes' => 'nullable|string',
                    'joinDate' => 'required|date',
                    'typeId' => 'required|integer',
                    'numberId' => 'required|string|max:50',
                    'gender' => 'required|in:P,W',
                    'occupationId' => 'nullable|integer',
                    'birthDate' => 'nullable|date',
                    'referenceCustomerId' => 'required|integer',
                    'generalCustomerCanConfigReminderBooking' => 'integer|nullable',
                    'generalCustomerCanConfigReminderPayment' => 'integer|nullable',

                ]
            );


            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'result' => 'The given data was invalid.',
                    'message' => $errors,
                ], 422);
            }


            $data_item_pet = [];

            if ($request->customerPets) {

                $arrayCustomerPet = json_decode($request->customerPets, true);

                $messageCustomerPet = [
                    'petName.required' => 'Pet name on tab Customer Pet is required',
                    'petCategoryId.required' => 'Category Pet tab Customer Pet is required',
                    'races.required' => 'Pet Races in tab Customer Pet is required',
                    'condition.required' => 'Condition on tab Customer Pet is required',
                    'petGender.required' => 'Pet Gender on tab Cutomer Pet is required',
                    'isSteril.required' => 'Pet Steril  on tab Cutomer Pet is required',
                    'color.required' => 'Pet Color  on tab Cutomer Pet is required',
                ];


                foreach ($arrayCustomerPet as $key) {

                    $validateDetail = Validator::make(
                        $key,
                        [
                            'petName' => 'required|string|max:100',
                            'petCategoryId' => 'required:integer',
                            'race' => 'nullable|string|max:100',
                            'condition' => 'required|string|max:100',
                            'petGender' => 'required|in:J,B',
                            'isSteril' => 'required|in:1,0',
                            'color' => 'required|string|max:100',
                        ],
                        $messageCustomerPet
                    );



                    if ($key['petAge'] == ""  ||  $key['petAge'] == "0") {

                        if ($key['dateOfBirth'] == "") {
                            return response()->json([
                                'result' => 'Inputed data is not valid',
                                'message' => "Please check again, Pet must have Age",
                            ], 422);
                        }
                    }


                    if ($key['dateOfBirth'] == "") {

                        if ($key['petAge'] == ""  ||  $key['petAge'] == "0") {
                            return response()->json([
                                'result' => 'Inputed data is not valid',
                                'message' => "Please check again, Pet must have Age",
                            ], 422);
                        }
                    }


                    if ($validateDetail->fails()) {

                        $errors = $validateDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item_pet))) {
                                array_push($data_item_pet, $checkisu);
                            }
                        }
                    }
                }

                if ($data_item_pet) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_item_pet,
                    ], 422);
                }
            }


            $data_reminder_booking = [];

            if ($request->reminderBooking) {

                $arrayReminderBooking = json_decode($request->reminderBooking, true);

                $messageReminderBooking = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                    'notes.required' => 'Notes Reminder Booking is required',
                ];


                foreach ($arrayReminderBooking as $key) {

                    $validateReminderBooking = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                            'notes' => 'required',
                        ],
                        $messageReminderBooking
                    );


                    if (!($key['notes'] === "sebelum memulai")) {
                        return response()->json([
                            'result' => 'Inputed data is not valid',
                            'message' => 'Please check your notes for reminder booking, notes reminder booking must be sebelum memulai',
                        ], 422);
                    }

                    if ($validateReminderBooking->fails()) {

                        $errors = $validateReminderBooking->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_booking))) {
                                array_push($data_reminder_booking, $checkisu);
                            }
                        }
                    }
                }

                if ($data_reminder_booking) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_reminder_booking,
                    ], 422);
                }
            }



            $data_reminder_payment = [];

            if ($request->reminderPayment) {

                $arrayReminderPayment = json_decode($request->reminderPayment, true);

                $messageReminderPayment = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                    'notes.required' => 'Notes Reminder Payment is required',
                ];


                foreach ($arrayReminderPayment as $key) {

                    $validateReminderPayment = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                            'notes' => 'required',
                        ],
                        $messageReminderPayment
                    );

                    if (!($key['notes'] === "sebelum jatuh tempo")) {
                        return response()->json([
                            'result' => 'Inputed data is not valid',
                            'message' => 'Please check your notes for reminder payment, notes reminder payment must be sebelum jatuh tempo',
                        ], 422);
                    }


                    if ($validateReminderPayment->fails()) {

                        $errors = $validateReminderPayment->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_payment))) {
                                array_push($data_reminder_payment, $checkisu);
                            }
                        }
                    }
                }

                if ($data_reminder_payment) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_reminder_payment,
                    ], 422);
                }
            }


            $data_reminder_late_payment = [];

            if ($request->reminderLatePayment) {

                $reminderLatePayment = json_decode($request->reminderLatePayment, true);

                $messageReminderLatePayment = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                    'notes.required' => 'Notes Reminder Late Payment is required',
                ];


                foreach ($reminderLatePayment as $key) {

                    $validateReminderLatePayment = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                            'notes' => 'required',
                        ],
                        $messageReminderLatePayment
                    );

                    if (!($key['notes'] === "setelah jatuh tempo")) {
                        return response()->json([
                            'result' => 'Inputed data is not valid',
                            'message' => 'Please check your notes for reminder late payment, notes reminder late payment must be setelah jatuh tempo',
                        ], 422);
                    }

                    if ($validateReminderLatePayment->fails()) {

                        $errors = $validateReminderLatePayment->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_late_payment))) {
                                array_push($data_reminder_late_payment, $checkisu);
                            }
                        }
                    }
                }

                if ($data_reminder_late_payment) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_reminder_late_payment,
                    ], 422);
                }
            }



            $data_item = [];

            if ($request->detailAddresses) {

                $arrayDetailAddress = json_decode($request->detailAddresses, true);

                $messageAddress = [
                    'addressName.required' => 'Address name on tab Address is required',
                    'provinceCode.required' => 'Province code on tab Address is required',
                    'cityCode.required' => 'City code on tab Address is required',
                    'country.required' => 'Country on tab Address is required',
                ];


                foreach ($arrayDetailAddress as $key) {

                    $validateDetail = Validator::make(
                        $key,
                        [
                            'addressName' => 'required',
                            'provinceCode' => 'required',
                            'cityCode' => 'required',
                            'country' => 'required',
                        ],
                        $messageAddress
                    );

                    if ($validateDetail->fails()) {

                        $errors = $validateDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item))) {
                                array_push($data_item, $checkisu);
                            }
                        }
                    }
                }



                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }


            $data_error_telephone = [];

            if ($request->telephones) {

                $arraytelephone = json_decode($request->telephones, true);

                $messagePhone = [
                    'phoneNumber.required' => 'Phone Number on tab telephone is required',
                    'type.required' => 'Type on tab telephone is required',
                    'usage.required' => 'Usage on tab telephone is required',
                ];


                foreach ($arraytelephone as $key) {

                    $telephoneDetail = Validator::make(
                        $key,
                        [
                            'phoneNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messagePhone
                    );

                    if ($telephoneDetail->fails()) {

                        $errors = $telephoneDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_telephone))) {
                                array_push($data_error_telephone, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['phoneNumber'], 0, 3) === "+62")) {
                            return response()->json([
                                'result' => 'Inputed data is not valid',
                                'message' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }


                if ($data_error_telephone) {
                    return response()->json([
                        'result' => 'The given data was invalid.',
                        'message' => $data_error_telephone,
                    ], 422);
                }
            }

            $data_error_email = [];

            if ($request->emails) {

                $arrayemail = json_decode($request->emails, true);

                $messageEmail = [
                    'username.required' => 'Username on tab email is required',
                    'usage.required' => 'Usage on tab email is required',
                ];

                foreach ($arrayemail as $key) {

                    $emailDetail = Validator::make(
                        $key,
                        [
                            'username' => 'required',
                            'usage' => 'required',
                        ],
                        $messageEmail
                    );


                    if ($emailDetail->fails()) {

                        $errors = $emailDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_email))) {
                                array_push($data_error_email, $checkisu);
                            }
                        }
                    }
                }

                if ($data_error_email) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_email,
                    ], 422);
                }
            }


            $data_error_messengers = [];
            if ($request->messengers) {

                $arraymessenger = json_decode($request->messengers, true);

                $messageMessenger = [
                    'messengerNumber.required' => 'messenger number on tab messenger is required',
                    'type.required' => 'Type on tab messenger is required',
                    'usage.required' => 'Usage on tab messenger is required',
                ];

                foreach ($arraymessenger as $key) {

                    $messengerDetail = Validator::make(
                        $key,
                        [
                            'messengerNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messageMessenger
                    );

                    if ($messengerDetail->fails()) {

                        $errors = $messengerDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_messengers))) {
                                array_push($data_error_messengers, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['messageMessenger'], 0, 3) === "+62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }

                if ($data_error_messengers) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_messengers,
                    ], 422);
                }
            }



            $flag = false;

            if ($request->hasfile('images')) {

                $flag = true;

                $data_item = [];

                $files[] = $request->file('images');

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $file_size = $fil->getSize();

                        $file_size = $file_size / 1024;

                        $oldname = $fil->getClientOriginalName();

                        if ($file_size >= 5000) {

                            array_push($data_item, 'Photo ' . $oldname . ' size more than 5mb! Please upload less than 5mb!');
                        }
                    }
                }

                if ($data_item) {

                    return response()->json([
                        'message' => 'Inputed photo is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            }

            if ($flag == true) {
                if ($request->imagesName) {
                    $ResultImageDatas = json_decode($request->imagesName, true);

                    foreach ($ResultImageDatas as $value) {

                        if ($value['name'] == "") {

                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Image name can not be empty!'],
                            ], 422);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Image name can not be empty!'],
                    ], 422);
                }
            }

            // INSERT

            $lastInsertedID = DB::table('customer')
                ->insertGetId([
                    'firstName' => $request->firstName,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'nickName' => $request->nickName,
                    'gender' => $request->gender,
                    'titleCustomerId' => $request->titleCustomerId,
                    'customerGroupId' => $request->customerGroupId,
                    'locationId' => $request->locationId,
                    'notes' => $request->notes,
                    'joinDate' => $request->joinDate,
                    'typeId' => $request->typeId,
                    'numberId' => $request->numberId,
                    'occupationId' => $request->occupationId,
                    'birthDate' => $request->birthDate,
                    'referenceCustomerId' => $request->referenceCustomerId,
                    'generalCustomerCanConfigReminderBooking' => $request->generalCustomerCanConfigReminderBooking,
                    'generalCustomerCanConfigReminderPayment' => $request->generalCustomerCanConfigReminderPayment,
                    'isDeleted' => 0,
                    'createdBy' => $request->user()->firstName,
                    'created_at' => now(),
                    'updated_at' => now(),

                ]);




            if ($request->customerPets) {

                foreach ($arrayCustomerPet as $val) {


                    $dateOfBirth = Carbon::parse($val['dateOfBirth']);
                    $age = 0;
                    $month = 0;

                    if ($val['petAge'] === "" || $val['petAge'] === 0) {

                        $age = $dateOfBirth->diffInYears(Carbon::now());
                        $month = $dateOfBirth->diffInMonths(Carbon::now());
                    } else {

                        $age = $val['petAge'];
                    }

                    DB::table('customerPets')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'petName' => $val['petName'],
                            'petCategoryId' => $val['petCategoryId'],
                            'races' => $val['races'],
                            'condition' => $val['condition'],
                            'color' => $val['color'],
                            'petAge' => $age,
                            'petAgeMonth' => $month,
                            'dateOfBirth' => $val['dateOfBirth'],
                            'petGender' => $val['petGender'],
                            'isSteril' => $val['isSteril'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->reminderBooking) {

                foreach ($arrayReminderBooking as $val) {

                    DB::table('customerReminders')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'B',
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->reminderPayment) {

                foreach ($arrayReminderPayment as $val) {

                    DB::table('customerReminders')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'P',
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }


            if ($request->reminderLatePayment) {

                foreach ($reminderLatePayment as $val) {

                    DB::table('customerReminders')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'LP',
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }


            if ($request->detailAddresses) {

                foreach ($arrayDetailAddress as $val) {

                    DB::table('customerAddresses')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'addressName' => $val['addressName'],
                            'additionalInfo' => $val['additionalInfo'],
                            'provinceCode' => $val['provinceCode'],
                            'cityCode' => $val['cityCode'],
                            'postalCode' => $val['postalCode'],
                            'country' => $val['country'],
                            'isPrimary' => $val['isPrimary'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->telephones) {

                foreach ($arraytelephone as $val) {

                    DB::table('customerTelephones')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'phoneNumber' => $val['phoneNumber'],
                            'type' => $val['type'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }



            if ($request->emails) {

                foreach ($arrayemail as $val) {

                    DB::table('customerEmails')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'username' => $val['username'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->messengers) {

                foreach ($arraymessenger as $val) {

                    DB::table('customerMessengers')
                        ->insert([
                            'customerId' => $lastInsertedID,
                            'messengerNumber' => $val['messengerNumber'],
                            'type' => $val['type'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->hasfile('images')) {

                $json_array = json_decode($request->imagesName, true);
                $int = 0;

                if (count($files) != 0) {

                    foreach ($files as $file) {

                        foreach ($file as $fil) {

                            $name = $fil->hashName();
                            $fil->move(public_path() . '/CustomerImages/', $name);

                            $fileName = "/CustomerImages/" . $name;

                            DB::table('customerImages')
                                ->insert([
                                    'customerId' => $lastInsertedID,
                                    'labelName' => $json_array[$int]['name'],
                                    'realImageName' => $fil->getClientOriginalName(),
                                    'imageName' => $name,
                                    'imagePath' => $fileName,
                                    'isDeleted' => 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                            $int = $int + 1;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(
                [
                    'result' => 'success',
                    'message' => 'Insert Data Customer Successful!',
                ],
                200
            );
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }



    public function updateCustomer(Request $request)
    {

        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'result' => 'The user role was invalid.',
                'message' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'firstName' => 'required|string|max:100',
                    'middleName' => 'nullable|string|max:100',
                    'lastName' => 'nullable|string|max:100',
                    'titleCustomerId' => 'nullable|integer',
                    'nickName' => 'nullable|string|max:100',
                    'customerGroupId' => 'nullable|integer',
                    'locationId' => 'nullable|integer',
                    'notes' => 'nullable|string',
                    'joinDate' => 'required|date',
                    'typeId' => 'required|integer',
                    'numberId' => 'required|string|max:50',
                    'gender' => 'required|in:P,W',
                    'occupationId' => 'nullable|integer',
                    'birthDate' => 'nullable|date',
                    'referenceCustomerId' => 'required|integer',
                    'generalCustomerCanConfigReminderBooking' => 'integer|nullable',
                    'generalCustomerCanConfigReminderPayment' => 'integer|nullable',

                ]
            );


            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'result' => 'The given data was invalid.',
                    'message' => $errors,
                ], 422);
            }


            $data_item_pet = [];

            if ($request->customerPets) {


                $messageCustomerPet = [
                    'petName.required' => 'Pet name on tab Customer Pet is required',
                    'petCategoryId.required' => 'Category Pet tab Customer Pet is required',
                    'races.required' => 'Pet Races in tab Customer Pet is required',
                    'condition.required' => 'Condition on tab Customer Pet is required',
                    'petGender.required' => 'Pet Gender on tab Cutomer Pet is required',
                    'isSteril.required' => 'Pet Steril  on tab Cutomer Pet is required',
                    'color.required' => 'Pet Color  on tab Cutomer Pet is required',
                ];


                foreach ($request->customerPets as $key) {

                    $validateDetail = Validator::make(
                        $key,
                        [
                            'petName' => 'required|string|max:100',
                            'petCategoryId' => 'required:integer',
                            'race' => 'nullable|string|max:100',
                            'condition' => 'required|string|max:100',
                            'petGender' => 'required|in:J,B',
                            'isSteril' => 'required|in:1,0',
                            'color' => 'required|string|max:100',
                        ],
                        $messageCustomerPet
                    );



                    if ($key['petAge'] == ""  ||  $key['petAge'] == "0") {

                        if ($key['dateOfBirth'] == "") {
                            return response()->json([
                                'result' => 'Inputed data is not valid',
                                'message' => "Please check again, Pet must have Age",
                            ], 422);
                        }
                    }


                    if ($key['dateOfBirth'] == "") {

                        if ($key['petAge'] == ""  ||  $key['petAge'] == "0") {
                            return response()->json([
                                'result' => 'Inputed data is not valid',
                                'message' => "Please check again, Pet must have Age",
                            ], 422);
                        }
                    }


                    if ($validateDetail->fails()) {

                        $errors = $validateDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item_pet))) {
                                array_push($data_item_pet, $checkisu);
                            }
                        }
                    }
                }

                if ($data_item_pet) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_item_pet,
                    ], 422);
                }
            }


            $data_reminder_booking = [];

            if ($request->reminderBooking) {


                $messageReminderBooking = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                    'notes.required' => 'Notes Reminder Booking is required',
                ];


                foreach ($request->reminderBooking as $key) {

                    $validateReminderBooking = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                            'notes' => 'required',
                        ],
                        $messageReminderBooking
                    );


                    if (!($key['notes'] === "sebelum memulai")) {
                        return response()->json([
                            'result' => 'Inputed data is not valid',
                            'message' => 'Please check your notes for reminder booking, notes reminder booking must be sebelum memulai',
                        ], 422);
                    }

                    if ($validateReminderBooking->fails()) {

                        $errors = $validateReminderBooking->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_booking))) {
                                array_push($data_reminder_booking, $checkisu);
                            }
                        }
                    }
                }

                if ($data_reminder_booking) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_reminder_booking,
                    ], 422);
                }
            }



            $data_reminder_payment = [];

            if ($request->reminderPayment) {

                $messageReminderPayment = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                    'notes.required' => 'Notes Reminder Payment is required',
                ];


                foreach ($request->reminderPayment as $key) {

                    $validateReminderPayment = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                            'notes' => 'required',
                        ],
                        $messageReminderPayment
                    );

                    if (!($key['notes'] === "sebelum jatuh tempo")) {
                        return response()->json([
                            'result' => 'Inputed data is not valid',
                            'message' => 'Please check your notes for reminder payment, notes reminder payment must be sebelum jatuh tempo',
                        ], 422);
                    }


                    if ($validateReminderPayment->fails()) {

                        $errors = $validateReminderPayment->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_payment))) {
                                array_push($data_reminder_payment, $checkisu);
                            }
                        }
                    }
                }

                if ($data_reminder_payment) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_reminder_payment,
                    ], 422);
                }
            }


            $data_reminder_late_payment = [];

            if ($request->reminderLatePayment) {

                $messageReminderLatePayment = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                    'notes.required' => 'Notes Reminder Late Payment is required',
                ];


                foreach ($request->reminderLatePayment as $key) {

                    $validateReminderLatePayment = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                            'notes' => 'required',
                        ],
                        $messageReminderLatePayment
                    );

                    if (!($key['notes'] === "setelah jatuh tempo")) {
                        return response()->json([
                            'result' => 'Inputed data is not valid',
                            'message' => 'Please check your notes for reminder late payment, notes reminder late payment must be setelah jatuh tempo',
                        ], 422);
                    }

                    if ($validateReminderLatePayment->fails()) {

                        $errors = $validateReminderLatePayment->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_late_payment))) {
                                array_push($data_reminder_late_payment, $checkisu);
                            }
                        }
                    }
                }

                if ($data_reminder_late_payment) {
                    return response()->json([
                        'result' => 'Inputed data is not valid',
                        'message' => $data_reminder_late_payment,
                    ], 422);
                }
            }



            $data_item = [];

            if ($request->detailAddresses) {


                $messageAddress = [
                    'addressName.required' => 'Address name on tab Address is required',
                    'provinceCode.required' => 'Province code on tab Address is required',
                    'cityCode.required' => 'City code on tab Address is required',
                    'country.required' => 'Country on tab Address is required',
                ];


                foreach ($request->detailAddresses as $key) {

                    $validateDetail = Validator::make(
                        $key,
                        [
                            'addressName' => 'required',
                            'provinceCode' => 'required',
                            'cityCode' => 'required',
                            'country' => 'required',
                        ],
                        $messageAddress
                    );

                    if ($validateDetail->fails()) {

                        $errors = $validateDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item))) {
                                array_push($data_item, $checkisu);
                            }
                        }
                    }
                }



                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }


            $data_error_telephone = [];

            if ($request->telephones) {



                $messagePhone = [
                    'phoneNumber.required' => 'Phone Number on tab telephone is required',
                    'type.required' => 'Type on tab telephone is required',
                    'usage.required' => 'Usage on tab telephone is required',
                ];


                foreach ($request->telephones as $key) {

                    $telephoneDetail = Validator::make(
                        $key,
                        [
                            'phoneNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messagePhone
                    );

                    if ($telephoneDetail->fails()) {

                        $errors = $telephoneDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_telephone))) {
                                array_push($data_error_telephone, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['phoneNumber'], 0, 3) === "+62")) {
                            return response()->json([
                                'result' => 'Inputed data is not valid',
                                'message' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }


                if ($data_error_telephone) {
                    return response()->json([
                        'result' => 'The given data was invalid.',
                        'message' => $data_error_telephone,
                    ], 422);
                }
            }

            $data_error_email = [];

            if ($request->emails) {

                $messageEmail = [
                    'username.required' => 'Username on tab email is required',
                    'usage.required' => 'Usage on tab email is required',
                ];

                foreach ($request->emails as $key) {

                    $emailDetail = Validator::make(
                        $key,
                        [
                            'username' => 'required',
                            'usage' => 'required',
                        ],
                        $messageEmail
                    );


                    if ($emailDetail->fails()) {

                        $errors = $emailDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_email))) {
                                array_push($data_error_email, $checkisu);
                            }
                        }
                    }
                }

                if ($data_error_email) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_email,
                    ], 422);
                }
            }


            $data_error_messengers = [];
            if ($request->messengers) {

                $messageMessenger = [
                    'messengerNumber.required' => 'messenger number on tab messenger is required',
                    'type.required' => 'Type on tab messenger is required',
                    'usage.required' => 'Usage on tab messenger is required',
                ];

                foreach ($request->messengers as $key) {

                    $messengerDetail = Validator::make(
                        $key,
                        [
                            'messengerNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messageMessenger
                    );

                    if ($messengerDetail->fails()) {

                        $errors = $messengerDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_messengers))) {
                                array_push($data_error_messengers, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['messageMessenger'], 0, 3) === "+62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }

                if ($data_error_messengers) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_messengers,
                    ], 422);
                }
            }



            $flag = false;

            if ($request->hasfile('images')) {

                $flag = true;

                $data_item = [];

                $files[] = $request->file('images');

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $file_size = $fil->getSize();

                        $file_size = $file_size / 1024;

                        $oldname = $fil->getClientOriginalName();

                        if ($file_size >= 5000) {

                            array_push($data_item, 'Photo ' . $oldname . ' size more than 5mb! Please upload less than 5mb!');
                        }
                    }
                }

                if ($data_item) {

                    return response()->json([
                        'message' => 'Inputed photo is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            }

            if ($flag == true) {
                if ($request->imagesName) {

                    foreach ($request->imagesName as $value) {

                        if ($value['name'] == "") {

                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Image name can not be empty!'],
                            ], 422);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Image name can not be empty!'],
                    ], 422);
                }
            }

            // Update

            DB::table('customer')
                ->where('id', '=', $request->input('customerId'))
                ->update([
                    'firstName' => $request->firstName,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'nickName' => $request->nickName,
                    'gender' => $request->gender,
                    'titleCustomerId' => $request->titleCustomerId,
                    'customerGroupId' => $request->customerGroupId,
                    'locationId' => $request->locationId,
                    'notes' => $request->notes,
                    'joinDate' => $request->joinDate,
                    'typeId' => $request->typeId,
                    'numberId' => $request->numberId,
                    'occupationId' => $request->occupationId,
                    'birthDate' => $request->birthDate,
                    'referenceCustomerId' => $request->referenceCustomerId,
                    'generalCustomerCanConfigReminderBooking' => $request->generalCustomerCanConfigReminderBooking,
                    'generalCustomerCanConfigReminderPayment' => $request->generalCustomerCanConfigReminderPayment,
                    'isDeleted' => 0,
                    'createdBy' => $request->user()->firstName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($request->customerPets) {

                DB::table('customerPets')
                    ->where([['customerId', '=', $request->input('customerId')],])
                    ->delete();

                foreach ($request->customerPets as $val) {


                    $dateOfBirth = Carbon::parse($val['dateOfBirth']);
                    $age = 0;
                    $month = 0;

                    if ($val['petAge'] === "" || $val['petAge'] === 0) {

                        $age = $dateOfBirth->diffInYears(Carbon::now());
                        $month = $dateOfBirth->diffInMonths(Carbon::now());
                    } else {

                        $age = $val['petAge'];
                    }

                    DB::table('customerPets')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'petName' => $val['petName'],
                            'petCategoryId' => $val['petCategoryId'],
                            'races' => $val['races'],
                            'condition' => $val['condition'],
                            'color' => $val['color'],
                            'petAge' => $age,
                            'petAgeMonth' => $month,
                            'dateOfBirth' => $val['dateOfBirth'],
                            'petGender' => $val['petGender'],
                            'isSteril' => $val['isSteril'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->reminderBooking) {

                DB::table('customerReminders')
                    ->where([
                        ['customerId', '=', $request->input('customerId')],
                        ['type', '=', 'B'],
                    ])
                    ->delete();

                foreach ($request->reminderBooking as $val) {

                    DB::table('customerReminders')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'B',
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->reminderPayment) {

                DB::table('customerReminders')
                    ->where([
                        ['customerId', '=', $request->input('customerId')],
                        ['type', '=', 'P'],
                    ])
                    ->delete();

                foreach ($request->reminderBooking as $val) {

                    DB::table('customerReminders')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'P',
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }


            if ($request->reminderLatePayment) {

                DB::table('customerReminders')
                    ->where([
                        ['customerId', '=', $request->input('customerId')],
                        ['type', '=', 'LP'],
                    ])
                    ->delete();

                foreach ($request->reminderLatePayment as $val) {

                    DB::table('customerReminders')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'LP',
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }


            if ($request->detailAddresses) {

                DB::table('customerAddresses')->where('customerId', '=', $request->input('customerId'))->delete();

                foreach ($request->detailAddresses as $val) {

                    DB::table('customerAddresses')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'addressName' => $val['addressName'],
                            'additionalInfo' => $val['additionalInfo'],
                            'provinceCode' => $val['provinceCode'],
                            'cityCode' => $val['cityCode'],
                            'postalCode' => $val['postalCode'],
                            'country' => $val['country'],
                            'isPrimary' => $val['isPrimary'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->telephones) {

                DB::table('customerTelephones')->where('customerId', '=', $request->input('customerId'))->delete();

                foreach ($request->telephones as $val) {

                    DB::table('customerTelephones')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'phoneNumber' => $val['phoneNumber'],
                            'type' => $val['type'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }



            if ($request->emails) {

                DB::table('customerEmails')->where('customerId', '=', $request->input('customerId'))->delete();

                foreach ($request->emails as $val) {

                    DB::table('customerEmails')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'username' => $val['username'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->messengers) {

                DB::table('customerMessengers')->where('customerId', '=', $request->input('customerId'))->delete();

                foreach ($request->messengers as $val) {

                    DB::table('customerMessengers')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'messengerNumber' => $val['messengerNumber'],
                            'type' => $val['type'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::commit();

            return response()->json(
                [
                    'result' => 'success',
                    'message' => 'Update Data Customer Successful!',
                ],
                200
            );
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }


    public function getDetailCustomer(Request $request)
    {
        $request->validate(['customerId' => 'required|max:10000']);
        $customerId = $request->input('customerId');

        $checkIfValueExits = DB::table('customer')
            ->where([
                ['id', '=', $request->input('customerId')],
                ['isDeleted', '=', '0']
            ])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another customer id",
            ]);
        } else {

            $param_customer = DB::table('customer')
                ->select(
                    'firstName',
                    'middleName',
                    'lastName',
                    'nickName',
                    'gender',
                    'titleCustomerId',
                    'customerGroupId',
                    'locationId',
                    'notes',
                    'joinDate',
                    'typeId',
                    'numberId',
                    'occupationId',
                    'birthDate',
                    'referenceCustomerId',
                    'generalCustomerCanConfigReminderBooking',
                    'generalCustomerCanConfigReminderPayment'
                )
                ->where('id', '=', $customerId)
                ->first();


            $customerPets = DB::table('customerPets')
                ->select(
                    'customerId',
                    'petName',
                    'petCategoryId',
                    'races',
                    'condition',
                    'color',
                    'petAge',
                    'petAgeMonth',
                    'dateOfBirth',
                    'petGender',
                    'isSteril',
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->customerPets = $customerPets;


            $reminderBooking = DB::table('customerReminders')
                ->select(
                    'sourceCustomerId',
                    'unit',
                    'time',
                    'timeDate',
                    'notes'
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['type', '=', 'B'],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->reminderBooking = $reminderBooking;



            $reminderPayment = DB::table('customerReminders')
                ->select(
                    'sourceCustomerId',
                    'unit',
                    'time',
                    'timeDate',
                    'notes'
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['type', '=', 'P'],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->reminderPayment = $reminderPayment;


            $reminderLatePayment = DB::table('customerReminders')
                ->select(
                    'sourceCustomerId',
                    'unit',
                    'time',
                    'timeDate',
                    'notes'
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['type', '=', 'LP'],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->reminderLatePayment = $reminderLatePayment;

            $detailAddresses = DB::table('customerAddresses')
                ->select(
                    'addressName as addressName',
                    'additionalInfo as additionalInfo',
                    'provinceCode as provinceCode',
                    'cityCode as cityCode',
                    'postalCode as postalCode',
                    'country as country',
                    'isPrimary as isPrimary',
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->detailAddresses = $detailAddresses;

            $telephones = DB::table('customerTelephones')
                ->select(
                    'phoneNumber as phoneNumber',
                    'type as type',
                    'usage as usage',
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->telephones = $telephones;

            $emails = DB::table('customerEmails')
                ->select(
                    'username as username',
                    'usage as usage',
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->emails = $emails;


            $messengers = DB::table('customerMessengers')
                ->select(
                    'messengerNumber as messengerNumber',
                    'type as type',
                    'usage as usage',
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->messengers = $messengers;

            $customeImages = DB::table('customerImages')
                ->select(
                    'id as id',
                    'labelName as labelName',
                    'imagePath as imagePath',
                )
                ->where([
                    ['customerId', '=', $customerId],
                    ['isDeleted', '=', '0']
                ])
                ->get();

            $param_customer->images = $customeImages;


            return response()->json($param_customer, 200);
        }
    }



    public function uploadImageCustomer(Request $request)
    {

        try {

            $json_array = json_decode($request->imagesName, true);
            $files[] = $request->file('images');
            $index = 0;

            foreach ($json_array as $val) {


                if (($val['id'] == "" || $val['id'] == 0)  && ($val['status'] == "")) {

                    $name = $files[0][$index]->hashName();

                    $files[0][$index]->move(public_path() . '/CustomerImages/', $name);

                    $fileName = "/CustomerImages/" . $name;

                    DB::table('customerImages')
                        ->insert([
                            'customerId' => $request->input('customerId'),
                            'labelName' => $val['name'],
                            'realImageName' => $files[0][$index]->getClientOriginalName(),
                            'imageName' => $name,
                            'imagePath' => $fileName,
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);

                    $index = $index + 1;
                } elseif (($val['id'] != "" && $val['id'] != 0)  && ($val['status'] == "del")) {


                    $find_image = DB::table('customerImages')
                        ->select(
                            'imageName',
                            'imagePath'
                        )
                        ->where('id', '=', $val['id'])
                        ->where('customerId', '=', $request->input('customerId'))
                        ->first();

                    if ($find_image) {

                        if (file_exists(public_path() . $find_image->imagePath)) {

                            File::delete(public_path() . $find_image->imagePath);

                            DB::table('customerImages')->where([
                                ['customerId', '=', $request->input('customerId')],
                                ['id', '=', $val['id']]
                            ])->delete();
                        }
                    }
                } elseif (($val['id'] != "" || $val['id'] != 0)  && ($val['status'] == "")) {

                    $find_image = DB::table('customerImages')
                        ->select(
                            'imageName',
                            'imagePath'
                        )
                        ->where('id', '=', $val['id'])
                        ->where('customerId', '=', $request->input('customerId'))
                        ->first();

                    if ($find_image) {

                        DB::table('customerImages')
                            ->where([
                                ['customerId', '=', $request->input('customerId')],
                                ['id', '=', $val['id']]
                            ])
                            ->update([
                                'labelName' => $val['name'],
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'successfuly update image customer',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }


    public function deleteCustomer(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'result' => 'The user role was invalid.',
                'message' => ['User Access not Authorize!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'customerId' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'result' => 'The given data was invalid.',
                'message' => $errors,
            ], 422);
        }

        DB::beginTransaction();

        try {

            $data_item = [];
            foreach ($request->customerId as $val) {

                $checkIfDataExits = DB::table('customer')
                    ->where([
                        ['id', '=', $val],
                        ['isDeleted', '=', '0']
                    ])
                    ->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'customer id : ' . $val . ' not found, please try different customer id');
                }
            }

            if ($data_item) {
                return response()->json([
                    'result' => 'Inputed data is not valid',
                    'message' => $data_item,
                ], 422);
            }

            foreach ($request->customerId as $val) {

                DB::table('customer')
                    ->where('id', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('customerReminders')
                    ->where('customerId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('customerAddresses')
                    ->where('customerId', '=', $val)
                    ->update(['isDeleted' => 1]);


                DB::table('customerEmails')
                    ->where('customerId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('customerMessengers')
                    ->where('customerId', '=', $val)
                    ->update(['isDeleted' => 1]);


                DB::table('customerTelephones')
                    ->where('customerId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('customerImages')
                    ->where('customerId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::commit();
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted Customer',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }



    public function getSourceCustomer(Request $request)
    {

        try {

            $getSourceCustomer = DB::table('sourceCustomer as a')
                ->select(
                    'a.id as sourceId',
                    'a.sourceName as sourceName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getSourceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }

    public function insertSourceCustomer(Request $request)
    {

        $request->validate([
            'sourceName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('sourceCustomer as a')
                ->where([
                    ['a.sourceName', '=', $request->sourceName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Source Name Customer title already exists, please choose another name',
                ]);
            } else {

                DB::table('sourceCustomer')->insert([
                    'sourceName' => $request->sourceName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Source Customer',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }


    public function insertReferenceCustomer(Request $request)
    {

        $request->validate([
            'referenceName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('referenceCustomer as a')
                ->where([
                    ['a.referenceName', '=', $request->referenceName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Reference Customer title already exists, please choose another name',
                ]);
            } else {

                DB::table('referenceCustomer')->insert([
                    'referenceName' => $request->referenceName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Reference Customer',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }



    public function getReferenceCustomer(Request $request)
    {

        try {

            $getRefrenceCustomer = DB::table('referenceCustomer as a')
                ->select(
                    'a.id as referenceCustomerId',
                    'a.referenceName as referenceCustomerName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getRefrenceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }


    public function getPetCategory(Request $request)
    {

        try {

            $getCategory = DB::table('petCategory as a')
                ->select(
                    'a.id as petCategoryId',
                    'a.petCategoryName as petCategoryName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getCategory, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }



    public function insertPetCategory(Request $request)
    {

        $request->validate([
            'petCategoryName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('petCategory as a')
                ->where([
                    ['a.petCategoryName', '=', $request->petCategoryName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Pet category name already exists, please choose another name',
                ]);
            } else {

                DB::table('petCategory')->insert([
                    'petCategoryName' => $request->petCategoryName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'Success',
                    'message' => 'Successfully inserted Pet Category',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }







    public function getCustomerOccupation(Request $request)
    {

        try {

            $getRefrenceCustomer = DB::table('customerOccupation as a')
                ->select(
                    'a.id as occupationId',
                    'a.occupationName as occupationName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getRefrenceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }



    public function insertCustomerOccupation(Request $request)
    {

        $request->validate([
            'occupationName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('customerOccupation as a')
                ->where([
                    ['a.occupationName', '=', $request->occupationName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Jobname already exists, please choose another name',
                ]);
            } else {

                DB::table('customerOccupation')->insert([
                    'occupationName' => $request->occupationName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Job Customer',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }


    public function getTitleCustomer(Request $request)
    {

        try {

            $getRefrenceCustomer = DB::table('titleCustomer as a')
                ->select(
                    'a.id as titleCustomerId',
                    'a.titleName as titleCustomerName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getRefrenceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }

    public function insertTitleCustomer(Request $request)
    {

        $request->validate([
            'titleName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('titleCustomer as a')
                ->where([
                    ['a.titleName', '=', $request->titleName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Title Customer title already exists, please choose another name',
                ]);
            } else {

                DB::table('titleCustomer')->insert([
                    'titleName' => $request->titleName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Title Customer',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }

    public function getCustomerGroup()
    {
        $data = DB::table('customerGroups as u')
            ->select('u.id', 'u.customerGroup')
            ->where('u.isDeleted', '=', 0)
            ->get();

        return response()->json($data, 200);
    }

    public function createCustomerGroup(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'customerGroup' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('customerGroups')
            ->where('customerGroup', '=', $request->customerGroup)
            ->first();

        if ($checkIfValueExits === null) {

            CustomerGroups::create([
                'customerGroup' => $request->customerGroup,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Customer Group name already exists!'],
            ], 422);
        }
    }
}
