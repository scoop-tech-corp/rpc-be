<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\ProductTransferReport;
use App\Models\ProductClinic;
use App\Models\ProductClinicLocation;
use App\Models\ProductSell;
use App\Models\ProductSellLocation;
use App\Models\ProductTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use DB;
use Excel;

class TransferProductController
{
    public function transferProductNumber(Request $request)
    {
        $findData = ProductTransfer::whereDate('created_at', Carbon::today())->count();

        $number = "";

        if ($findData == 0) {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
        } else {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
        }

        return response()->json($number, 200);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transferNumber' => 'required|string',
            'transferName' => 'required|string',
            'locationId' => 'required|integer',
            'totalItem' => 'required|integer',
            'userIdReceiver' => 'required|integer',
            'productId' => 'required|integer',
            'productType' => 'required|string|in:productSell,productClinic',
            'additionalCost' => 'numeric',
            'remark' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $prodDest = null;

        $findData = ProductTransfer::whereDate('created_at', Carbon::today())->count();

        $number = "";

        if ($findData == 0) {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
        } else {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
        }

        //find product id destination
        if ($request->productType == 'productSell') {

            $prodOrigin = ProductSell::find($request->productId);

            if ($prodOrigin) {

                $prodDest = DB::table('productSells as ps')
                    ->join('productSellLocations as psl', 'ps.id', 'psl.productSellId')
                    ->select('ps.*', 'psl.diffStock')
                    ->where('psl.locationId', '=', $request->locationId)
                    ->where('ps.fullName', '=', $prodOrigin->fullName)
                    ->first();
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }
        } elseif ($request->productType == 'productClinic') {

            $prodOrigin = ProductClinic::find($request->productId);

            if ($prodOrigin) {

                $prodDest = DB::table('productClinics as pc')
                    ->join('productClinicLocations as pcl', 'pc.id', 'pcl.productClinicId')
                    ->select('pc.*', 'pcl.diffStock')
                    ->where('pcl.locationId', '=', $request->locationId)
                    ->where('pc.fullName', '=', $prodOrigin->fullName)
                    ->first();
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }
        }

        $checkAdminApproval = false;

        if ($prodDest) {

            if ($prodDest->diffStock > 0) {
                $checkAdminApproval = true;
            }

            $productType = "";

            if ($request->productType == 'productSell') {
                $productType = 'Product Sell';
            } elseif ($request->productType == 'productClinic') {
                $productType = 'Product Clinic';
            }

            ProductTransfer::create([
                'transferNumber' => $number,
                'transferName' => $request->transferName,
                'groupData' => 'product',
                'totalItem' => $request->totalItem,
                'userIdReceiver' => $request->userIdReceiver,
                'productIdOrigin' => $request->productId,
                'productIdDestination' => $prodDest->id,
                'productType' => $productType,
                'additionalCost' => $request->additionalCost,
                'remark' => $request->remark,
                'isAdminApproval' => $checkAdminApproval,
                'userId' => $request->user()->id,
            ]);
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product Destination does not exist!'],
            ], 422);
        }

        return response()->json(
            [
                'message' => 'Add Data Successful!',
            ],
            200
        );
    }

    public function index(Request $request)
    {
        $role = role($request->user()->id);

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

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
            ->where('pt.groupData', '=', $request->type);

        if ($role != "Administrator" && $role != "Office") {

            $data = $data->where('pt.userIdReceiver', '=', $request->user()->id);
        }

        $data = $data->orderBy('pt.updated_at', 'desc');

        if ($request->search) {

            $tmp = $this->search($request);

            if ($tmp) {
                $data = $data->where($tmp[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($tmp); $i++) {

                    $data = $data->orWhere($tmp[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

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

                if ($request->locationId) {

                    $res = $res->whereIn('lo.id', $request->locationId);
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

                if ($request->locationId) {

                    $res = $res->whereIn('lo.id', $request->locationId);
                }

                $res = $res->first();

                if ($res) {
                    array_push($tempData, $res);
                }
            }
        }

        $tempC = collect($tempData);
        $sorted = '';

        if ($request->orderValue == 'desc' && $request->orderColumn) {
            $tempData = $tempC->sortByDesc($request->orderColumn);
        } elseif ($request->orderValue == 'asc' && $request->orderColumn) {
            $sorted = $tempC->sortBy($request->orderColumn);
            $tempData = $sorted->values()->all();
        }

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $tempData
        ], 200);
    }

    private function search($request)
    {
        $temp_column = null;

        $data = DB::table('productTransfers as pt')
            ->select(
                'pt.productType'
            )
            ->where('pt.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pt.productType', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pt.productType';
        }

        $data = DB::table('productTransfers as pt')
            ->select(
                'pt.transferName'
            )
            ->where('pt.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pt.transferName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pt.transferName';
        }

        //
        $data = DB::table('productTransfers as pt')
            ->join('users as u', 'pt.userId', 'u.id')
            ->select(
                'u.firstName'
            )
            ->where('pt.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        ///
        $data = DB::table('productTransfers as pt')
            ->join('users as ur', 'pt.userIdReceiver', 'ur.id')
            ->select(
                'ur.firstName'
            )
            ->where('pt.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('ur.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ur.firstName';
        }

        ///
        $data = DB::table('productTransfers as pt')
            ->leftjoin('users as uo', 'pt.userIdOffice', 'uo.id')
            ->select(
                'uo.firstName'
            )
            ->where('pt.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('uo.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'uo.firstName';
        }

        $data = DB::table('productTransfers as pt')
            ->leftjoin('users as ua', 'pt.userIdAdmin', 'ua.id')
            ->select(
                'ua.firstName'
            )
            ->where('pt.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('ua.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ua.firstName';
        }

        return $temp_column;
    }

    public function detail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $findData = ProductTransfer::find($request->id);

        if ($findData) {

            if ($findData->productType == 'Product Sell') {

                $data = DB::table('productTransfers as pt')
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
                        'pt.id',
                        'pt.transferNumber',
                        'pt.transferName',
                        'pt.productIdOrigin',
                        'pt.productIdDestination',
                        'lo.locationName as origin',
                        'ld.locationName as destination',
                        'pso.fullName as productName',
                        'pt.transferName',
                        'pt.totalItem',
                        'pt.imagePath',
                        DB::raw("IFNULL(pt.remark,'') as remark"),
                        DB::raw("TRIM(pt.additionalCost)+0 as additionalCost"),
                        DB::raw("CASE WHEN pt.isAdminApproval = 1 THEN 'Yes' WHEN pt.isAdminApproval = 0 THEN 'No' END as isAdminApproval"),
                        DB::raw("IFNULL(pt.reference,'') as reference"),
                        DB::raw("CASE WHEN pt.isAdminApproval = 0 THEN '' WHEN pt.isApprovedAdmin = 0 THEN 'Waiting for approval' WHEN pt.isApprovedAdmin = 1 THEN 'Approved' ELSE 'Reject' END as statusAdmin"),
                        DB::raw("CASE WHEN pt.isApprovedOffice = 0 THEN 'Waiting for approval' WHEN pt.isApprovedOffice = 1 THEN 'Approved' ELSE 'Reject' END as statusOffice"),
                        'pt.isApprovedOffice',
                        'u.firstName as createdBy',
                        'ur.firstName as receivedBy',
                        'u.firstName as createdBy',

                        DB::raw("IFNULL(uo.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(ua.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(ur.firstName,'') as receivedBy"),

                        DB::raw("IFNULL(DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s'),'') as transferDate"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.officeApprovedAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.receivedAt, '%d/%m/%Y %H:%i:%s'),'') as receivedAt"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.adminApprovedAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                    )
                    ->where('pt.id', '=', $request->id)
                    ->first();

                return response()->json($data, 200);
            } elseif ($findData->productType == 'Product Clinic') {

                $data = DB::table('productTransfers as pt')
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
                        'pt.id',
                        'pt.transferNumber',
                        'pt.transferName',
                        'pt.productIdOrigin',
                        'pt.productIdDestination',
                        'lo.locationName as origin',
                        'ld.locationName as destination',
                        'pco.fullName as productName',
                        'pt.transferName',
                        'pt.totalItem',
                        'pt.imagePath',
                        DB::raw("IFNULL(pt.remark,'') as remark"),
                        DB::raw("TRIM(pt.additionalCost)+0 as additionalCost"),
                        DB::raw("CASE WHEN pt.isAdminApproval = 1 THEN 'Yes' WHEN pt.isAdminApproval = 0 THEN 'No' END as isAdminApproval"),
                        DB::raw("IFNULL(pt.reference,'') as reference"),
                        DB::raw("CASE WHEN pt.isAdminApproval = 0 THEN '' WHEN pt.isApprovedAdmin = 0 THEN 'Waiting for approval' WHEN pt.isApprovedAdmin = 1 THEN 'Approved' ELSE 'Reject' END as statusAdmin"),
                        DB::raw("CASE WHEN pt.isApprovedOffice = 0 THEN 'Waiting for approval' WHEN pt.isApprovedOffice = 1 THEN 'Approved' ELSE 'Reject' END as statusOffice"),
                        'pt.isApprovedOffice',
                        'u.firstName as createdBy',
                        'ur.firstName as receivedBy',
                        'u.firstName as createdBy',

                        DB::raw("IFNULL(uo.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(ua.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(ur.firstName,'') as receivedBy"),

                        DB::raw("IFNULL(DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s'),'') as transferDate"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s'),'') as createdAt"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.officeApprovedAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.receivedAt, '%d/%m/%Y %H:%i:%s'),'') as receivedAt"),
                        DB::raw("IFNULL(DATE_FORMAT(pt.adminApprovedAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                    )
                    ->where('pt.id', '=', $request->id)
                    ->first();

                return response()->json($data, 200);
            }
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data does not exist!'],
            ], 422);
        }
    }

    public function export(Request $request)
    {
        $tmp = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');
        $role = role($request->user()->id);
        $locations = $request->locationId;

        if (!$locations[0] == null) {

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
            $fileName = "Rekap Produk Transfer " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Produk Transfer " . $tmp . " " . $date . ".xlsx";
        }

        return Excel::download(
            new ProductTransferReport(
                $request->orderValue,
                $request->orderColumn,
                $request->locationId,
                $role
            ),
            $fileName
        );
    }

    private function validationApproval($request)
    {
        $role = role($request->user()->id);

        if ($role != 'Administrator' && $role != 'Office') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Acccess Denied!'],
            ], 422);
        }

        $product = ProductTransfer::find($request->id);

        if (!$product) {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data not exist!'],
            ], 422);
        }
    }

    public function approval(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'status' => 'required|integer|in:1,2',
            'reason' => 'nullable|string',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $status = $this->validationApproval($request);

        if ($status == '') {

            $role = role($request->user()->id);

            if ($role == 'Administrator') {

                if ($request->status == 2) {
                    ProductTransfer::where('id', '=', $request->id)
                        ->update(
                            [
                                'groupData' => 'history',
                                'userIdAdmin' => $request->user()->id,
                                'isApprovedAdmin' => $request->status,
                                'reasonAdmin' => $request->reason,
                                'adminApprovedAt' => Carbon::now()
                            ]
                        );
                } else {
                    ProductTransfer::where('id', '=', $request->id)
                        ->update(
                            [
                                'userIdAdmin' => $request->user()->id,
                                'isApprovedAdmin' => $request->status,
                                'reasonAdmin' => $request->reason,
                                'adminApprovedAt' => Carbon::now()
                            ]
                        );
                }
            } elseif ($role == 'Office') {

                if ($request->status == 2) {
                    ProductTransfer::where('id', '=', $request->id)
                        ->update(
                            [
                                'groupData' => 'history',
                                'userIdOffice' => $request->user()->id,
                                'isApprovedOffice' => $request->status,
                                'reasonOffice' => $request->reason,
                                'officeApprovedAt' => Carbon::now()
                            ]
                        );
                } else {
                    ProductTransfer::where('id', '=', $request->id)
                        ->update(
                            [
                                'userIdOffice' => $request->user()->id,
                                'isApprovedOffice' => $request->status,
                                'reasonOffice' => $request->reason,
                                'officeApprovedAt' => Carbon::now()
                            ]
                        );
                }
            }

            $prod = ProductTransfer::find($request->id);

            $status = 0;

            if ($prod->isAdminApproval == 0) {
                if ($prod->isApprovedOffice == 1) {
                    $status = 1;
                } elseif ($prod->isApprovedOffice == 2) {
                    $status = 2;
                }
            } elseif ($prod->isAdminApproval == 1) {

                if ($prod->isApprovedAdmin == 1 && $prod->isApprovedOffice == 1) {
                    $status = 1;
                } elseif ($prod->isApprovedAdmin == 2) {
                    $status = 2;
                }
            }

            ProductTransfer::where('id', '=', $request->id)
                ->update(
                    [
                        'status' => $status,
                    ]
                );
        } else {
            return $status;
        }

        return response()->json(
            [
                'message' => 'Approval Data Successful!',
            ],
            200
        );
    }

    public function receive(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'reference' => 'required|string|max:255',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        //validation
        $trf = ProductTransfer::find($request->id);

        if ($trf) {

            if ($trf->isAdminApproval == 1) {
                if ($trf->isApprovedAdmin == 0) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['The Item has not been approved by the Admin, contact the Admin to approve the Item'],
                    ], 422);
                } elseif ($trf->isApprovedAdmin == 2) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Item has been rejected'],
                    ], 422);
                }
            }

            if ($trf->isApprovedOffice == 0) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['The Item has not been approved by the Office, contact the Office to approve the Item'],
                ], 422);
            } elseif ($trf->isApprovedOffice == 2) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Item has been rejected'],
                ], 422);
            }

            if ($trf->isUserReceived == 1) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Item has already received'],
                ], 422);
            }

            $imagePath = "";
            $realImageName = "";

            if ($request->hasfile('image')) {
                $file = $request->file('image');

                $name = $file->hashName();

                $file->move(public_path() . '/ProductTransfer/', $name);

                $imagePath = "/ProductTransfer/" . $name;

                $realImageName = $file->getClientOriginalName();
            }

            ProductTransfer::where('id', '=', $request->id)
                ->update(
                    [
                        'groupData' => 'history',
                        'reference' => $request->reference,
                        'isUserReceived' => 1,
                        'imagePath' => $imagePath,
                        'realImageName' => $realImageName,
                        'status' => 3,
                        'receivedAt' => Carbon::now()
                    ]
                );

            //move product item and add log
            if ($trf->produtType == 'Product Sell') {

                $prodOrig = ProductSell::find($trf->productIdOrigin);
                $prodDest = ProductSell::find($trf->productIdDestination);

                $locOrig = ProductSellLocation::where('productSellId', '=', $prodOrig->id)->first();

                $inStockOrig = $locOrig->inStock;
                $lowStock = $locOrig->lowStock;

                $locOrig->inStock = $inStockOrig - $trf->totalItem;
                $locOrig->diffStock = ($inStockOrig - $trf->totalItem) - $lowStock;
                $locOrig->updated_at = Carbon::now();
                $locOrig->save();
                $finalStockOrig = $inStockOrig - $trf->totalItem;

                $locDest = ProductSellLocation::where('productSellId', '=', $prodDest->id)->first();

                $inStockDest = $locDest->inStock;
                $lowStock = $locDest->lowStock;

                $locDest->inStock = $inStockDest + $trf->totalItem;
                $locDest->diffStock = ($inStockDest + $trf->totalItem) - $lowStock;
                $locDest->updated_at = Carbon::now();
                $locDest->save();
                $finalStockDest = $inStockDest + $trf->totalItem;

                productSellLog($prodOrig->id, 'Transfer Item', 'Reduced item to be transferred', $trf->totalItem, $finalStockOrig, $trf->userId);
                productSellLog($prodDest->id, 'Transfer Item', 'Added item from transfer product', $trf->totalItem, $finalStockDest, $trf->userId);
            } elseif ($trf->productType == 'Product Clinic') {
                $prodOrig = ProductClinic::find($trf->productIdOrigin);
                $prodDest = ProductClinic::find($trf->productIdDestination);

                $locOrig = ProductClinicLocation::where('productClinicId', '=', $prodOrig->id)->first();

                $inStockOrig = $locOrig->inStock;
                $lowStock = $locOrig->lowStock;

                $locOrig->inStock = $inStockOrig - $trf->totalItem;
                $locOrig->diffStock = ($inStockOrig - $trf->totalItem) - $lowStock;
                $locOrig->updated_at = Carbon::now();
                $locOrig->save();
                $finalStockOrig = $inStockOrig - $trf->totalItem;

                $locDest = ProductClinicLocation::where('productClinicId', '=', $prodDest->id)->first();

                $inStockDest = $locDest->inStock;
                $lowStock = $locDest->lowStock;

                $locDest->inStock = $inStockDest + $trf->totalItem;
                $locDest->diffStock = ($inStockDest + $trf->totalItem) - $lowStock;
                $locDest->updated_at = Carbon::now();
                $locDest->save();
                $finalStockDest = $inStockDest + $trf->totalItem;

                productClinicLog($prodOrig->id, 'Transfer Item', 'Reduced item to be transferred', $trf->totalItem, $finalStockOrig, $trf->userId);
                productClinicLog($prodDest->id, 'Transfer Item', 'Added item from transfer product', $trf->totalItem, $finalStockDest, $trf->userId);
            }

            return response()->json(
                [
                    'message' => 'Receive Item Successful!',
                ],
                200
            );
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product does not exist!'],
            ], 422);
        }
    }
}
