<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\ProductInventoryApprovalReport;
use App\Exports\Product\TemplateUploadProductInventory;
use App\Exports\Product\ProductInventoryHistoryReport;
use App\Exports\Product\ProductInventoryReport;
use App\Imports\Product\ImportProductInventory;
use App\Models\ProductClinic;
use App\Models\ProductClinicLocation;
use App\Models\ProductInventory;
use App\Models\ProductInventoryList;
use App\Models\ProductInventoryListImages;
use App\Models\ProductSell;
use App\Models\ProductSellLocation;
use App\Models\usages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Validator;
use Illuminate\Support\Carbon;
use Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProductInventoryController
{
    public function index(Request $request)
    {

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productInventories as p')
            ->join('users as u', 'p.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'p.locationId')
            ->select(
                'p.id',
                'p.requirementName',
                'p.locationId',
                'loc.locationName as locationName',
                'p.totalItem',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )->where('p.isDeleted', '=', 0);

        if ($request->locationId) {
            $data = $data->whereIn('p.locationId', $request->locationId);
        }

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('p.id', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    public function Search($request)
    {
        $temp_column = null;

        $data = DB::table('productInventories as p')
            ->select(
                'p.requirementName as requirementName'
            )
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('p.requirementName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'p.requirementName';
            return $temp_column;
        }
    }

    public function indexHistory(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        if (role($request->user()->id) == 'Administrator') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pil', 'pil.productInventoryId', 'p.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                )->distinct()
                ->whereIn('pil.isApprovedAdmin', array(1, 2));
        } elseif (role($request->user()->id) == 'Office') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pil', 'pil.productInventoryId', 'p.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                )->distinct()
                ->whereIn('pil.isApprovedOffice', array(1, 2));
        }

        if ($request->search) {
            $res = $this->SearchHistory($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('p.id', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    public function exportHistory(Request $request)
    {
        $tmp = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');
        $role = role($request->user()->id);

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
            $fileName = "Rekap Riwayat Produk Inventori " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Riwayat Produk Inventori " . $tmp . " " . $date . ".xlsx";
        }

        return Excel::download(
            new ProductInventoryHistoryReport(
                $request->orderValue,
                $request->orderColumn,
                $request->fromDate,
                $request->toDate,
                $request->search,
                $request->locationId,
                $role
            ),
            $fileName
        );
    }

    public function SearchHistory($request)
    {
        $data = DB::table('productInventories as p')
            ->select('p.requirementName')
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('p.requirementName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'p.requirementName';
        }

        $data = DB::table('productInventories as p')
            ->join('users as u', 'p.userId', 'u.id')
            ->select('u.firstName')
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }

    public function indexApproval(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        if (role($request->user()->id) == 'Administrator') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )->distinct()
                ->where('p.isApprovalAdmin', '=', 1)
                ->where('pl.isApprovedAdmin', '=', 0)
                ->where('p.isDeleted', '=', 0);
        } elseif (role($request->user()->id) == 'Office') {
            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )->distinct()
                ->where('p.isApprovalOffice', '=', 1)
                ->where('pl.isApprovedOffice', '=', 0)
                ->where('p.isDeleted', '=', 0);
        }

        if ($request->search) {
            $res = $this->SearchApproval($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('p.id', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    public function exportApproval(Request $request)
    {
        $tmp = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

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
            $fileName = "Rekap List Approval Produk Inventori " . $date . ".xlsx";
        } else {
            $fileName = "Rekap List Approval Produk Inventori " . $tmp . " " . $date . ".xlsx";
        }

        return Excel::download(
            new ProductInventoryApprovalReport(
                $request->orderValue,
                $request->orderColumn,
                $request->search,
                $request->locationId,
                role($request->user()->id)
            ),
            $fileName
        );
    }

    public function SearchApproval($request)
    {
        $data = DB::table('productInventories as p')
            ->select('p.requirementName')
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('p.requirementName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'p.requirementName';
        }

        $data = DB::table('productInventories as p')
            ->join('users as u', 'p.userId', 'u.id')
            ->select('u.firstName')
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }

    public function detail(Request $request)
    {
        $prod = ProductInventory::find($request->id);

        if (!$prod) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product Inventory Request not found!'],
            ], 422);
        }

        $header = DB::table('productInventories as pi')
            ->join('location as l', 'pi.locationId', 'l.id')
            ->join('users as u', 'u.id', 'pi.userId')

            ->select(
                'pi.requirementName',
                'l.locationName',
                DB::raw("CONCAT(u.firstName,' ',u.middleName,CASE WHEN u.middleName = '' THEN '' ELSE ' ' END,u.lastName) as createdBy"),
                DB::raw("DATE_FORMAT(pi.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )

            ->where('pi.id', '=', $request->id)
            ->first();

        $prodDetail = ProductInventoryList::where('productInventoryId', '=', $prod->id)->get();

        foreach ($prodDetail as $value) {

            if ($value['productType'] == 'productSell') {

                $prodRes = DB::table('productInventoryLists as pi')
                    ->join('productSells as p', 'p.id', 'pi.productId')
                    ->join('usages as u', 'u.id', 'pi.usageId')
                    ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
                    ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
                    ->leftJoin('productInventoryListImages as pimg', 'pi.id', 'pimg.productInventoryListId')
                    ->select(
                        'pi.id',
                        DB::raw("CASE WHEN pi.productType = 'productSell' THEN 'Produk Jual' WHEN pi.productType = 'productClinic' THEN 'Produk Klinik' END as productType"),
                        'pi.productId',
                        'p.fullName as productName',
                        'pi.usageId',
                        'u.usage',
                        'pi.quantity',

                        'pi.isApprovedOffice',
                        DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

                        'pi.isApprovedAdmin',
                        DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                        DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

                        DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
                        DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),

                        DB::raw("IFNULL(pimg.imagePath,'') as imagePath"),
                        DB::raw("IFNULL(pimg.realImageName,'') as realImageName"),
                    )
                    ->where('pi.id', '=', $value['id'])
                    ->orderBy('pi.id', 'desc')
                    ->first();
            } elseif ($value['productType'] == 'productClinic') {

                $prodRes = DB::table('productInventoryLists as pi')
                    ->join('productClinics as p', 'p.id', 'pi.productId')
                    ->join('usages as u', 'u.id', 'pi.usageId')
                    ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
                    ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
                    ->leftJoin('productInventoryListImages as pimg', 'pi.id', 'pimg.productInventoryListId')
                    ->select(
                        'pi.id',
                        DB::raw("CASE WHEN pi.productType = 'productSell' THEN 'Produk Jual' WHEN pi.productType = 'productClinic' THEN 'Produk Klinik' END as productType"),
                        'pi.productId',
                        'p.fullName as productName',
                        'pi.usageId',
                        'u.usage',
                        'pi.quantity',

                        'pi.isApprovedOffice',
                        DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

                        'pi.isApprovedAdmin',
                        DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                        DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

                        DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
                        DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),
                        DB::raw("IFNULL(pimg.imagePath,'') as imagePath"),
                        DB::raw("IFNULL(pimg.realImageName,'') as realImageName"),
                    )
                    ->where('pi.id', '=', $value['id'])
                    ->orderBy('pi.id', 'desc')
                    ->first();
            }

            $data[] = array(
                'id' => $prodRes->id,
                'productType' => $prodRes->productType,
                'productId' => $prodRes->productId,
                'productName' => $prodRes->productName,
                'usageId' => $prodRes->usageId,
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
                'imagePath' => $prodRes->imagePath,
                'realImageName' => $prodRes->realImageName,
            );
        }

        return response()->json([
            'header' => $header,
            'data' => $data
        ], 200);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'requirementName' => 'required|string|max:30',
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $ResultProducts = null;

        if ($request->listProducts) {
            $ResultProducts = json_decode($request->listProducts, true);
        }

        $validateProducts = Validator::make(
            $ResultProducts,
            [
                '*.productType' => 'required|string',
                '*.productId' => 'required|integer',
                '*.usageId' => 'required|integer',
                '*.quantity' => 'required|integer',
                '*.dateCondition' => 'required|date',
                '*.itemCondition' => 'required|string',
            ],
            [
                '*.productType.required' => 'Product Type Should be Required!',
                '*.productType.string' => 'Product Type Should be String!',
                '*.productId.required' => 'Product Id Should be Required',
                '*.productId.integer' => 'Product Id Should be Integer',
                '*.usage.required' => 'Usage Should be Required',
                '*.usage.integer' => 'Usage Should be Integer',
                '*.quantity.required' => 'Quantity Should be Required',
                '*.quantity.integer' => 'Quantity Should be Integer',
                '*.dateCondition.required' => 'Quantity Should be Required',
                '*.dateCondition.date' => 'Quantity Should be Date',
                '*.itemCondition.required' => 'Quantity Should be Required',
                '*.itemCondition.string' => 'Quantity Should be String',
            ]
        );

        if ($validateProducts->fails()) {
            $errors = $validateProducts->errors()->first();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        $approvalAdmin = 0;
        $approvalOffice = 0;

        foreach ($ResultProducts as $value) {

            if ($value['productType'] == 'productSell') {

                $findProduct = ProductSell::find($value['productId']);

                if ($findProduct->isAdminApproval == 1) {
                    $approvalAdmin = 1;
                }

                if ($findProduct->isOfficeApproval == 1) {
                    $approvalOffice = 1;
                }
            } elseif ($value['productType'] == 'productClinic') {

                $findProduct = ProductClinic::find($value['productId']);

                if ($findProduct->isAdminApproval == 1) {
                    $approvalAdmin = 1;
                }

                if ($findProduct->isOfficeApproval == 1) {
                    $approvalOffice = 1;
                }
            }
        }

        DB::beginTransaction();
        try {

            $prod =  ProductInventory::create([
                'requirementName' => $request->requirementName,
                'locationId' => $request->locationId,
                'totalItem' => count($ResultProducts),
                'isApprovalAdmin' => $approvalAdmin,
                'isApprovalOffice' => $approvalOffice,
                'userId' => $request->user()->id,
            ]);

            $count = 0;

            $files[] = $request->file('images');
            $tmpImages = [];

            if ($request->hasfile('images')) {
                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $name = $fil->hashName();

                        $fil->move(public_path() . '/ProductInventoryImages/', $name);

                        $fileName = "/ProductInventoryImages/" . $name;

                        $file = new ProductInventoryListImages();
                        $file->productInventoryListId = 1;
                        $file->realImageName = $fil->getClientOriginalName();
                        $file->imagePath = $fileName;
                        $file->userId = $request->user()->id;

                        array_push($tmpImages, $file);
                    }
                }
            }

            foreach ($ResultProducts as $value) {

                $prodList = ProductInventoryList::create([
                    'productInventoryId' => $prod->id,
                    'productType' => $value['productType'],
                    'productId' => $value['productId'],
                    'usageId' => $value['usageId'],
                    'quantity' => $value['quantity'],
                    'dateCondition' => $value['dateCondition'],
                    'itemCondition' => $value['itemCondition'],
                    'isAnyImage' => $value['isAnyImage'],
                    'userId' => $request->user()->id,
                ]);

                if ($value['isAnyImage'] == 1) {

                    ProductInventoryListImages::create([
                        'productInventoryListId' => $prodList->id,
                        'realImageName' => $tmpImages[$count]['realImageName'],
                        'imagePath' => $tmpImages[$count]['imagePath'],
                        'userId' => $request->user()->id,
                    ]);

                    $count += 1;
                }
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } catch (Throwable $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Insert Failed',
                'errors' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|',
            'requirementName' => 'required|string|max:30',
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $product = ProductInventory::where('id', '=', $request->id)
            ->where('isDeleted', '=', 0)->get();

        if (!$product) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product inventory does not exist'],
            ], 422);
        }

        $ResultProducts = null;

        if ($request->listProducts) {
            $ResultProducts = $request->listProducts;
        }

        $validateProducts = Validator::make(
            $ResultProducts,
            [
                '*.productType' => 'required|string',
                '*.productId' => 'required|integer',
                '*.usageId' => 'required|integer',
                '*.quantity' => 'required|integer',
                '*.dateCondition' => 'required|date',
                '*.itemCondition' => 'required|string',
                '*.status' => 'nullable|string',
            ],
            [
                '*.productType.required' => 'Product Type is Required!',
                '*.productType.string' => 'Product Type Should be String!',

                '*.productId.required' => 'Product Id Should be Required',
                '*.productId.integer' => 'Product Id Should be Integer',

                '*.usage.required' => 'Usage Should be Required',
                '*.usage.integer' => 'Usage Should be Integer',

                '*.quantity.required' => 'Quantity Should be Required',
                '*.quantity.integer' => 'Quantity Should be Integer',

                '*.dateCondition.required' => 'Quantity Should be Required',
                '*.dateCondition.date' => 'Quantity Should be Date',

                '*.itemCondition.required' => 'Quantity Should be Required',
                '*.itemCondition.string' => 'Quantity Should be String',
            ]
        );

        if ($validateProducts->fails()) {
            $errors = $validateProducts->errors()->first();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        $approvalAdmin = 0;
        $approvalOffice = 0;

        foreach ($ResultProducts as $value) {

            if ($value['productType'] == 'productSell') {

                $findProduct = ProductSell::find($value['productId']);

                if ($findProduct->isAdminApproval == 1) {
                    $approvalAdmin = 1;
                }

                if ($findProduct->isOfficeApproval == 1) {
                    $approvalOffice = 1;
                }
            } elseif ($value['productType'] == 'productClinic') {

                $findProduct = ProductClinic::find($value['productId']);

                if ($findProduct->isAdminApproval == 1) {
                    $approvalAdmin = 1;
                }

                if ($findProduct->isOfficeApproval == 1) {
                    $approvalOffice = 1;
                }
            }
        }

        ProductInventory::where('id', '=', $request->id)
            ->update(
                [
                    'requirementName' => $request->requirementName,
                    'locationId' => $request->locationId,
                    'totalItem' => count($ResultProducts),
                    'isApprovalAdmin' => $approvalAdmin,
                    'isApprovalOffice' => $approvalOffice,
                    'userUpdateId' => $request->user()->id,
                    'updated_at' => Carbon::now(),
                ]
            );

        foreach ($ResultProducts as $value) {

            ProductInventoryList::updateOrCreate(
                ['id' => $value['id']],
                [
                    'productInventoryId' => $request->id,
                    'productType' => $value['productType'],
                    'productId' => $value['productId'],
                    'usageId' => $value['usageId'],
                    'quantity' => $value['quantity'],
                    'dateCondition' => $value['dateCondition'],
                    'itemCondition' => $value['itemCondition'],
                    'isAnyImage' => $value['isAnyImage'],
                    'userUpdateId' => $request->user()->id,
                ]
            );
        }

        return response()->json(
            [
                'message' => 'Update Data Successful!',
            ],
            200
        );
    }

    public function updateApproval(Request $request)
    {
        $prod = ProductInventoryList::find($request->id);

        if (!$prod) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['The Product Request not found!'],
            ], 422);
        }

        if (role($request->user()->id) == 'Office' && $prod->isApprovedOffice != 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product has already signed by Office!'],
            ], 422);
        }

        if (role($request->user()->id) == 'Administrator' && $prod->isApprovedAdmin != 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product has already signed by Administrator!'],
            ], 422);
        }

        if ($request->status == 2 && $request->reason == "") {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Reason should be filled when to set reject!'],
            ], 422);
        } elseif ($request->status == 1 && $request->reason != "") {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Reason should be empty when to set approve!'],
            ], 422);
        }

        if (role($request->user()->id) == 'Office') {
            ProductInventoryList::where('id', '=', $request->id)
                ->update(
                    [
                        'userApproveOfficeId' => $request->user()->id,
                        'isApprovedOffice' => $request->status,
                        'reasonOffice' => $request->reason,
                        'userApproveOfficeAt' => Carbon::now()
                    ]
                );
        } elseif (role($request->user()->id) == 'Administrator') {
            ProductInventoryList::where('id', '=', $request->id)
                ->update(
                    [
                        'userApproveAdminId' => $request->user()->id,
                        'isApprovedAdmin' => $request->status,
                        'reasonAdmin' => $request->reason,
                        'userApproveAdminAt' => Carbon::now()
                    ]
                );
        }

        if ($prod->isApprovedAdmin == 1 && $prod->isApprovedOffice == 1) {
            // update untuk mengurangi stok barang
            $prodInventory = ProductInventory::find($prod->productInventoryId);

            $prodList = ProductInventoryList::where('id', '=', $request->id)->get();

            //check validation update
            $msg = $this->ValidateUpdate($prodList, $prodInventory);
            if ($msg != "") {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [$msg],
                ], 422);
            }

            foreach ($prodList as $value) {

                if ($value['productType'] == 'productSell') {

                    $product = ProductSellLocation::where('productSellId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->first();

                    ProductSellLocation::where('productSellId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->update([
                            'inStock' => $product->inStock - $value['quantity'],
                            'userUpdateId' => $request->user()->id,
                            'updated_at' => Carbon::now()
                        ]);
                } elseif ($value['productType'] == 'productClinic') {
                    $product = ProductClinicLocation::where('productClinicId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->first();

                    ProductClinicLocation::where('productClinicId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->update([
                            'inStock' => $product->inStock - $value['quantity'],
                            'userUpdateId' => $request->user()->id,
                            'updated_at' => Carbon::now()
                        ]);
                }
            }
        }

        return response()->json(
            [
                'message' => 'Update Status Successful!',
            ],
            200
        );
    }

    private function ValidateUpdate($prodList, $prodInventory)
    {
        $msg = '';
        foreach ($prodList as $value) {

            if ($value['productType'] == 'productSell') {

                $product = ProductSellLocation::where('productSellId', '=', $value['productId'])
                    ->where('locationId', '=', $prodInventory->locationId)
                    ->first();

                if (!$product) {
                    $msg = 'Data not exist';
                }
            } elseif ($value['productType'] == 'productClinic') {
                $product = ProductClinicLocation::where('productClinicId', '=', $value['productId'])
                    ->where('locationId', '=', $prodInventory->locationId)
                    ->first();

                if (!$product) {
                    $msg = 'Data not exist';
                }
            }
        }

        return $msg;
    }

    public function delete(Request $request)
    {
        $userId = $request->user()->id;

        foreach ($request->id as $va) {

            $prod = ProductInventory::find($va);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product Inventory Request not found!'],
                ], 422);
            }

            if ($userId != $prod->userId) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is a difference between the user who deletes and the user who creates inventory!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {

            $prod = ProductInventory::find($va);

            $prodList = ProductInventoryList::where('ProductInventoryId', '=', $prod->id)->get();

            foreach ($prodList as $value) {
                if ($value['isApprovedAdmin'] == 1 || $value['isApprovedAdmin'] == 2) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['There is any product has already approved by Admin!'],
                    ], 422);
                } elseif ($value['isApprovedOffice'] == 1 || $value['isApprovedOffice'] == 2) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['There is any product has already approved by Office!'],
                    ], 422);
                }
            }
        }

        foreach ($request->id as $va) {

            $prod = ProductInventory::find($va);

            $prodList = ProductInventoryList::where('ProductInventoryId', '=', $prod->id)->get();

            if ($prodList) {

                ProductInventoryList::where('ProductSellId', '=', $prodList->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $prodList->DeletedBy = $request->user()->id;
            $prodList->isDeleted = true;
            $prodList->DeletedAt = Carbon::now();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }

    public function exportInventory(Request $request)
    {
        $tmp = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

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
            $fileName = "Rekap Produk Inventori " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Produk Inventori " . $tmp . " " . $date . ".xlsx";
        }

        return Excel::download(
            new ProductInventoryReport(
                $request->orderValue,
                $request->orderColumn,
                $request->locationId,
                role($request->user()->id)
            ),
            $fileName
        );
    }

    public function downloadTemplate(Request $request)
    {
        return (new TemplateUploadProductInventory())->download('Template Upload Produk Inventori.xlsx');
    }

    public function Import(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'errors' => 'The given data was invalid.',
                'message' => $errors,
            ], 422);
        }

        $id = $request->user()->id;

        $rows = Excel::toArray(new ImportProductInventory($id), $request->file('file'));
        $src = $rows[0];

        $count_row = 1;

        // return $src;
        $err = '';

        if ($src) {
            foreach ($src as $value) {

                if ($value['nama'] == '') {
                    $err = 'There is any empty cell on column Nama at row ' . $count_row;
                    break;
                }

                if ($value['kode_lokasi'] == '') {
                    $err = 'There is any empty cell on column Kode Lokasi at row ' . $count_row;
                    break;
                }

                $loc = DB::table('location as l')
                    ->where('l.id', '=', $value['kode_lokasi'])
                    ->where('l.isDeleted', '=', 0)
                    ->first();

                if (!$loc) {
                    $err = 'Invalid Kode Lokasi at row ' . $count_row;
                    break;
                }

                $productType = explode(';', $value['tipe_produk']);
                $productCode = explode(';', $value['kode_produk']);
                $usageCode = explode(';', $value['kode_penggunaan']);
                $dateCondition = explode(';', $value['tanggal_kondisi']);
                $itemCondition = explode(';', $value['kondisi_barang']);
                $qty = explode(';', $value['jumlah']);

                $a = count($productType);
                $b = count($productCode);
                $c = count($usageCode);
                $d = count($dateCondition);
                $e = count($itemCondition);
                $f = count($qty);

                if (
                    $a !== $b ||
                    $a !== $c ||
                    $a !== $d ||
                    $b !== $e ||
                    $b !== $f
                ) {
                    $err = 'Total data on column Tipe Produk, Kode Produk, Kode Penggunaan, Tanggal Kondisi, Kondisi Barang, and Jumlah are not same at row ' . $count_row;
                    break;
                }

                if ($value['tipe_produk'] == '') {
                    $err = 'There is any empty cell on column Tipe Produk at row ' . $count_row;
                    break;
                }

                foreach ($productType as $valueT) {
                    if ($valueT != 'Jual' && $valueT != 'Klinik') {
                        $err = 'Invalid Type Product format at row ' . $count_row;
                        break;
                    }
                }

                $cn = 1;
                foreach ($productCode as $valuePC) {

                    if ($valueT[$cn] == 'Jual') {

                        $check = ProductSell::find($valuePC);
                        if (!$check) {
                            $err = 'There is any Invalid data at column Kode Produk Jual at row ' . $count_row;
                            break;
                        }
                        $cn += 1;
                    } elseif ($valueT[$cn] == 'Klinik') {
                        $check = ProductClinic::find($valuePC);
                        if (!$check) {
                            $err = 'There is any Invalid data at column Kode Produk Klinik at row ' . $count_row;
                            break;
                        }
                        $cn += 1;
                    }
                }

                foreach ($usageCode as $valueU) {
                    $chk = usages::find($valueU);

                    if (!$chk) {
                        $err = 'There is any Invalid data at column Kode Penggunaan at row ' . $count_row;
                        break;
                    }
                }
                // $expiredDate = Carbon::instance(Date::excelToDateTimeObject((int) $value['tanggal_kedaluwarsa']));

                $count_row += 1;
            }

            if ($err != '') {
                return response()->json([
                    'errors' => 'The given data was invalid.',
                    'message' => [$err],
                ], 422);
            }

            //INSERT DATA
            foreach ($src as $value) {

                $productType = explode(';', $value['tipe_produk']);
                $productCode = explode(';', $value['kode_produk']);
                $usageCode = explode(';', $value['kode_penggunaan']);
                $datesCondition = explode(';', $value['tanggal_kondisi']);
                $itemCondition = explode(';', $value['kondisi_barang']);
                $qty = explode(';', $value['jumlah']);

                $approvalAdmin = 0;
                $approvalOffice = 0;

                $cn = 0;
                foreach ($productType as $valueType) {

                    if ($valueType == 'Jual') {

                        $findProduct = ProductSell::find($productCode[$cn]);

                        if ($findProduct->isAdminApproval == 1) {
                            $approvalAdmin = 1;
                        }

                        if ($findProduct->isOfficeApproval == 1) {
                            $approvalOffice = 1;
                        }
                    } elseif ($valueType == 'Klinik') {

                        $findProduct = ProductClinic::find($productCode[$cn]);

                        if ($findProduct->isAdminApproval == 1) {
                            $approvalAdmin = 1;
                        }

                        if ($findProduct->isOfficeApproval == 1) {
                            $approvalOffice = 1;
                        }
                    }
                    $cn += 1;
                }

                $inv = ProductInventory::create([
                    'requirementName' => $value['nama'],
                    'locationId' => $value['kode_lokasi'],
                    'totalItem' => count($productType),
                    'isApprovalAdmin' => $approvalAdmin,
                    'isApprovalOffice' => $approvalOffice,
                    'userId' => $request->user()->id,
                ]);

                $cnLi = 0;

                foreach ($productType as $valueType2) {
                    $type = '';
                    if ($valueType2 == 'Jual') {
                        $type = 'productSell';
                    } elseif ($valueType2 == 'Klinik') {
                        $type = 'productClinic';
                    }

                    ProductInventoryList::create([
                        'productInventoryId' => $inv->id,
                        'productType' => $type,
                        'productId' => $productCode[$cnLi],
                        'usageId' => $usageCode[$cnLi],
                        'quantity' => $qty[$cnLi],
                        'dateCondition' => $datesCondition[$cnLi],
                        'itemCondition' => $itemCondition[$cnLi],
                        'isAnyImage' => 0,
                        'userId' => $request->user()->id,
                    ]);

                    $cnLi += 1;
                }
            }
        } else {
            return response()->json([
                'errors' => 'The given data was invalid.',
                'message' => ['There is no any data to import'],
            ], 422);
        }

        return response()->json(
            [
                'message' => 'Insert Data Successful!',
            ],
            200
        );
    }
}
