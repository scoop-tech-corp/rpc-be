<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\RestockReport;
use App\Http\Controllers\Controller;
use App\Models\ProductClinic;
use App\Models\productClinicBatch;
use App\Models\ProductClinicLocation;
use App\Models\productRestockDetails;
use App\Models\productRestockImageReceive;
use App\Models\productRestockImages;
use App\Models\productRestocks;
use App\Models\productRestockTracking;
use App\Models\ProductSell;
use App\Models\productSellBatch;
use App\Models\ProductSellLocation;
use App\Models\ProductSupplier;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use DB;
use Excel;
use PDF;
use File;
use Illuminate\Support\Str;

class RestockController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productRestocks as pr');
        if ($request->supplierId) {
            $data = $data->join('productRestockDetails as prd', 'prd.productRestockId', 'pr.id');
        }
        $data = $data->join('location as loc', 'loc.Id', 'pr.locationId')
            ->join('users as u', 'pr.userId', 'u.id')
            ->select(
                'pr.id',
                'pr.numberId',
                'loc.locationName',
                'pr.supplierName',
                'pr.variantProduct as products',
                'pr.totalProduct as quantity',
                'pr.status',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pr.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pr.isDeleted', '=', 0);

        if ($request->type == 'approval') {
            $data = $data->whereIn('pr.status', array(1, 3, 4));
        }

        if ($request->type == 'history') {
            $data = $data->whereIn('pr.status', array(2, 5));
        }

        if ($request->locationId) {

            $data = $data->whereIn('loc.id', $request->locationId);
        }

        if ($request->supplierId) {
            $data = $data->whereIn('prd.supplierId', $request->supplierId);
        }

        if ($request->search) {
            $res = $this->search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return responseIndex(0, $data);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('pr.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    private function search($request)
    {
        $temp_column = null;

        $data = DB::table('productRestocks as pr')
            ->select(
                'pr.numberId'
            )
            ->where('pr.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pr.numberId', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pr.numberId';
        }

        return $temp_column;
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'productId' => 'required|integer',
            'productType' => 'required|string|in:productSell,productClinic',
            'supplierId' => 'required|integer',
            'requireDate' => 'required|date',
            'reStockQuantity' => 'required|integer',
            'costPerItem' => 'required|numeric',
            'total' => 'required|numeric',
            'remark' => 'required|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $prodType = "";
        $checkAdminApproval = false;
        $locationId = 0;
        $suppName = '';
        $currentStock = 0;

        if ($request->productType == 'productSell') {

            $prodType = "productSell";

            $prod = ProductSell::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }

            $supp = ProductSupplier::find($prod->productSupplierId);

            $suppName = $supp->supplierName;

            $stockProd = ProductSellLocation::where('productSellId', '=', $request->productId)->first();

            $locationId = $stockProd->locationId;
            $currentStock = $stockProd->inStock;
        } else {
            $prodType = "productClinic";

            $prod = ProductClinic::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }

            $supp = ProductSupplier::find($prod->productSupplierId);

            $suppName = $supp->supplierName;

            $stockProd = ProductClinicLocation::where('productClinicId', '=', $request->productId)->first();

            $locationId = $stockProd->locationId;
            $currentStock = $stockProd->inStock;
        }

        if ($stockProd->reStockLimit < $request->reStockQuantity) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Restock Quantity can not be greater than Restock Limit!'],
            ], 422);
        }

        $findData = productRestockDetails::whereDate('updated_at', Carbon::today())
            ->where('purchaseRequestNumber', '!=', '')
            ->count();

        $number = "";

        if ($findData == 0) {
            $number = Carbon::today();
            $number = 'RPC-PR-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
        } else {
            $number = Carbon::today();
            $number = 'RPC-PR-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
        }

        if ($stockProd->diffStock > 0) {
            $checkAdminApproval = true;
        }

        $cntNum = DB::table('productRestocks')
            ->where('status', '!=', 0)
            ->count();

        if ($cntNum == 0) {
            $numberId = '#' . str_pad(1, 8, 0, STR_PAD_LEFT);
        } else {
            $numberId = '#' . str_pad($cntNum + 1, 8, 0, STR_PAD_LEFT);
        }

        $prodRstk = productRestocks::create([
            'numberId' => $numberId,
            'locationId' => $locationId,
            'variantProduct' => 1,
            'totalProduct' => $request->reStockQuantity,
            'supplierName' => $suppName,
            'status' => 1,
            'isAdminApproval' => $checkAdminApproval,
            'userId' => $request->user()->id,
            //status 0 = waiting for approval, status 1 = approved, status 2 = reject, status 3 = product has arrive
            //0 = draft, 1 = waiting for approval, 2 = reject, 3 = approved, 4 = submit to supplier, 5 = product received
        ]);

        productRestockDetails::create([

            'purchaseRequestNumber' => '',
            'purchaseOrderNumber' => '',
            'productRestockId' => $prodRstk->id,
            'productId' => $request->productId,
            'productType' => $prodType,
            'supplierId' => $request->supplierId,
            'requireDate' => $request->requireDate,
            'currentStock' => $currentStock,
            'reStockQuantity' => $request->reStockQuantity,
            'rejected' => '0',
            'canceled' => '0',
            'accepted' => '0',
            'received' => '0',
            'total' => $request->total,
            'costPerItem' => $request->costPerItem,
            'remark' => $request->remark,
            'isAdminApproval' => $checkAdminApproval,
            'userId' => $request->user()->id,
        ]);

        $count = 0;

        $flag = false;
        $res_data = [];
        $files[] = $request->file('images');

        $ResImageDatas = json_decode($request->imagesName, true);

        if ($flag == false) {

            if ($request->hasfile('images')) {

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $name = $fil->hashName();

                        $fil->move(public_path() . '/ProductRestock/', $name);

                        $fileName = "/ProductRestock/" . $name;

                        $file = new productRestockImages();
                        $file->productRestockId = $prodRstk->id;
                        $file->labelName = $ResImageDatas[$count]['name'];
                        $file->realImageName = $fil->getClientOriginalName();
                        $file->imagePath = $fileName;
                        $file->userId = $request->user()->id;
                        $file->save();

                        array_push($res_data, $file);

                        $count += 1;
                    }
                }

                $flag = true;
            }
        } else {

            foreach ($res_data as $res) {
                productRestockImages::create([
                    'productRestockId' => $prodRstk->id,
                    'labelName' => $res['labelName'],
                    'realImageName' => $res['realImageName'],
                    'imagePath' => $res['imagePath'],
                    'userId' => $request->user()->id,
                ]);
            }
        }

        return responseCreate();
    }

    public function createMultiple(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:draft,final',
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $datas = json_decode($request->productList, true);

        $variantProduct = 0;
        $totalProduct = 0;
        $totalImages = 0;
        $suppName = "";
        $diffStock = 0;

        //validasi data

        $validate = Validator::make(
            $datas,
            [
                '*.productType' => 'required|string|in:productSell,productClinic',
                '*.productId' => 'required|integer',
                '*.supplierId' => 'required|integer',
                '*.requireDate' => 'required|date',
                '*.currentStock' => 'required|integer',
                '*.restockQuantity' => 'required|integer',
                '*.costPerItem' => 'required|numeric',
                '*.total' => 'required|numeric',
                '*.remark' => 'nullable|string',
                '*.totalImage' => 'required|integer',
            ],
            [
                '*.productType.required' => 'Product Type Should be Required!',
                '*.productType.string' => 'Product Type Should be Filled!',

                '*.productId.required' => 'Product Id Should be Required!',
                '*.productId.integer' => 'Product Id Should be Filled!',

                '*.supplierId.required' => 'Supplier Id Should be Required!',
                '*.supplierId.integer' => 'Supplier Id Should be Filled!',

                '*.requireDate.required' => 'Require Date Should be Required!',
                '*.requireDate.date' => 'Require Date Should be Date!',

                '*.currentStock.required' => 'Current Stock Should be Required!',
                '*.currentStock.integer' => 'Current Stock Should be Filled!',

                '*.restockQuantity.required' => 'Restock Quantity Should be Required!',
                '*.restockQuantity.integer' => 'Restock Quantity Should be Filled!',

                '*.costPerItem.required' => 'Cost per Item Should be Required!',
                '*.costPerItem.integer' => 'Cost per Item Should be Filled!',

                '*.total.required' => 'Total Should be Required!',
                '*.total.integer' => 'Total Should be Filled!',

                '*.totalImage.required' => 'Total Image Should be Required!',
                '*.totalImage.integer' => 'Total Image Should be Filled!',
            ]
        );

        if ($validate->fails()) {
            $errors = $validate->errors()->first();

            return responseInvalid([$errors]);
        }

        foreach ($datas as $value) {

            if ($value['productType'] === 'productSell') {

                $findProd = ProductSell::find($value['productId']);

                $find = ProductSellLocation::where('productSellId', '=', $value['productId'])->first();

                if ($find) {
                    if ($value['restockQuantity'] > $find->reStockLimit) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Restock Quantity on product ' . $findProd->fullName . ' can not be greater than Restock Limit!'],
                        ], 422);
                    }
                }
            } elseif ($value['productType'] === 'productClinic') {
                $findProd = ProductClinic::find($value['productId']);

                $find = ProductClinicLocation::where('productClinicId', '=', $value['productId'])->first();

                if ($find) {
                    if ($value['restockQuantity'] > $find->reStockLimit) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Restock Quantity on product ' . $findProd->fullName . ' can not be greater than Restock Limit!'],
                        ], 422);
                    }
                }
            }

            $totalProduct += $value['restockQuantity'];
            $variantProduct++;
            $totalImages += $value['totalImage'];

            $supp = DB::table('productSuppliers as ps')
                ->where('ps.id', '=', $value['supplierId'])
                ->first();

            $suppName = $suppName . $supp->supplierName . ', ';
        }

        $suppName = rtrim($suppName, ", ");

        $statusdata = '';
        $number = '';

        if ($request->status == 'draft') {
            $statusdata = '0';
            $number = 'draft';
        } elseif ($request->status == 'final') {
            $statusdata = '1';

            $cntNum = DB::table('productRestocks')
                ->where('status', '!=', 0)
                ->count();

            if ($cntNum == 0) {
                $number = '#' . str_pad(1, 8, 0, STR_PAD_LEFT);
            } else {
                $number = '#' . str_pad($cntNum + 1, 8, 0, STR_PAD_LEFT);
            }
        }

        //insert data

        $prodRestock = productRestocks::create([
            'numberId' => $number,
            'locationId' => $request->locationId,
            'variantProduct' => $variantProduct,
            'totalProduct' => $totalProduct,
            'supplierName' => $suppName,
            'status' => $statusdata,
            'isAdminApproval' => 0,
            'userId' => $request->user()->id,
        ]);

        if ($number == 'draft') {
            productRestockLog($prodRestock->id, "Created", "Draft", $request->user()->id);
        } else {
            productRestockLog($prodRestock->id, "Created", "Waiting for Approval", $request->user()->id);
        }

        $number = "";
        $prNumber = "";

        $adminApprovalMaster = false;

        foreach ($datas as $val) {
            $checkAdminApproval = false;

            if ($request->status == 'final') {

                $findDataPr = DB::table('productRestockDetails')
                    ->select('purchaseRequestNumber')
                    ->whereDate('updated_at', Carbon::today())
                    ->where('purchaseRequestNumber', '!=', '')
                    ->groupby('purchaseRequestNumber')
                    ->get();

                $findDataSup = productRestockDetails::where('productRestockId', '=', $prodRestock->id)
                    ->where('supplierId', '=', $val['supplierId'])
                    ->first();

                $number = Carbon::today();

                if (count($findDataPr) == 0) {
                    $prNumber = 'RPC-PR-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
                } else {

                    if ($findDataSup) {

                        $prNumber = $findDataSup->purchaseRequestNumber;
                    } else {
                        $prNumber = 'RPC-PR-' . $number->format('Ymd') . str_pad(count($findDataPr) + 1, 5, 0, STR_PAD_LEFT);
                    }
                }
            }

            if ($val['productType'] === 'productSell') {

                $find = ProductSellLocation::where('productSellId', '=', $val['productId'])->first();
                $diffStock = $find->diffStock;
            } elseif ($val['productType'] === 'productClinic') {

                $find = ProductClinicLocation::where('productClinicId', '=', $val['productId'])->first();
                $diffStock = $find->diffStock;
            }

            if ($diffStock > 0) {
                $checkAdminApproval = true;
                $adminApprovalMaster = true;
            }

            $prodDetail = productRestockDetails::create([
                'purchaseRequestNumber' => $prNumber,
                'purchaseOrderNumber' => '',
                'productRestockId' => $prodRestock->id,
                'productId' => $val['productId'],
                'productType' => $val['productType'],
                'supplierId' => $val['supplierId'],
                'requireDate' => $val['requireDate'],
                'currentStock' => $val['currentStock'],
                'reStockQuantity' => $val['restockQuantity'],
                'rejected' => '0',
                'canceled' => '0',
                'accepted' => '0',
                'received' => '0',
                'costPerItem' => $val['costPerItem'],
                'total' => $val['total'],
                'remark' => $val['remark'],
                'isAdminApproval' => $checkAdminApproval,
                'userId' => $request->user()->id,
            ]);

            foreach ($val['images'] as $valueImg) {
                if ($valueImg['imagePath'] != '') {
                    $image = str_replace('data:image/', '', $valueImg['imagePath']);
                    $image = explode(';base64,', $image);
                    $imageName = Str::random(40) . '.' . $image[0];
                    File::put(public_path('ProductRestockImages') . '/' . $imageName, base64_decode($image[1]));

                    productRestockImages::create([
                        'productRestockDetailId' => $prodDetail->id,
                        'labelName' => $valueImg['label'],
                        'realImageName' => $valueImg['originalName'],
                        'imagePath' => '/ProductRestockImages' . '/' . $imageName,
                        'userId' => $request->user()->id,
                    ]);
                }
            }
        }

        if ($adminApprovalMaster == true) {

            $res = productRestocks::find($prodRestock->id);
            $res->isAdminApproval = $adminApprovalMaster;
            $res->save();
        }

        return responseCreate();
    }

    public function export(Request $request)
    {
        $tmpLoc = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

        $locations = $request->locationId;

        if ($locations) {

            $location = DB::table('location')
                ->select('locationName')
                ->whereIn('id', $request->locationId)
                ->get();

            if ($location) {

                foreach ($location as $key) {
                    $tmpLoc = $tmpLoc . (string) $key->locationName . ",";
                }
            }
            $tmpLoc = rtrim($tmpLoc, ",");
        }

        if ($tmpLoc == "") {
            $fileName = "Rekap Restock Produk " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Restock Produk " . $tmpLoc . " " . $date . ".xlsx";
        }

        $type = $request->type;

        return Excel::download(
            new RestockReport(
                $request->orderValue,
                $request->orderColumn,
                $request->locationId,
                $request->supplierId,
                $request->user()->role,
                $type
            ),
            $fileName
        );
    }

    public function createTracking(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'progress' => 'required|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid([$errors]);
        }

        productRestockTracking::create([
            'productRestockId' => $request->productRestockId,
            'progress' => $request->progress,
            'userId' => $request->user()->id,
        ]);

        return responseCreate();
    }

    public function detail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        if ($request->type == 'edit') {

            $prd = DB::table('productRestocks as pr')
                ->join('location as loc', 'loc.Id', 'pr.locationId')
                ->select('pr.id', 'pr.locationId', 'loc.locationName')
                ->where('pr.id', '=', $request->id)
                ->first();

            $chk = DB::table('productRestockDetails as pr')
                ->join('productSuppliers as sup', 'sup.Id', 'pr.supplierId')
                ->select(
                    'pr.id',
                    'pr.productId',
                    'pr.productType',
                    DB::raw('CASE WHEN pr.productType = "productSell" THEN (select fullName from productSells where id=pr.productId)
                    WHEN pr.productType = "productClinic" THEN (select fullName from productClinics where id=pr.productId) END as productName'),
                    'pr.supplierId',
                    'sup.supplierName',
                    'pr.requireDate',
                    'pr.currentStock',
                    DB::raw("TRIM(pr.reStockQuantity)+0 as reStockQuantity"),
                    DB::raw("TRIM(pr.costPerItem)+0 as costPerItem"),
                    DB::raw("TRIM(pr.total)+0 as total"),
                    'pr.remark'
                )
                ->where('pr.productRestockId', '=', $request->id)
                ->get();

            foreach ($chk as $val) {

                $image = DB::table('productRestockImages as pri')
                    ->select('pri.id', 'pri.labelName', 'pri.realImageName', 'pri.imagePath')
                    ->where('pri.productRestockDetailId', '=', $val->id)
                    ->get();

                $data[] = array(
                    'id' => $val->id,
                    'productId' => $val->productId,
                    'productType' => $val->productType,
                    'productName' => $val->productName,
                    'supplierId' => $val->supplierId,
                    'supplierName' => $val->supplierName,
                    'requireDate' => $val->requireDate,
                    'currentStock' => $val->currentStock,
                    'reStockQuantity' => $val->reStockQuantity,
                    'costPerItem' => $val->costPerItem,
                    'total' => $val->total,
                    'remark' => $val->remark,
                    'images' => $image
                );
            }

            $prd->detail = $data;

            return response()->json($prd, 200);
        } elseif ($request->type == 'approval') {

            $restock = productRestocks::find($request->id);

            // if ($restock->status != 3) {
            //     $restock = [];
            //     return responseList($restock);
            // }

            $isAdmin = false;
            $isOffice = false;

            if (adminAccess($request->user()->id)) {
                $isAdmin = true;
            }

            if (officeAccess($request->user()->id)) {
                $isOffice = true;
            }

            $prodList = DB::table('productRestockDetails as pr')
                ->where('pr.productRestockId', '=', $request->id)
                ->where('pr.isDeleted', '=', 0);

            if ($isAdmin) {
                $prodList = $prodList->where('pr.isAdminApproval', '=', 1)
                    ->get();
            } else if ($isOffice) {
                $prodList = $prodList->get();
            }

            $data = null;

            foreach ($prodList as $value) {

                // if ($value->reStockQuantity != $value->rejected) {
                if ($value->productType == 'productSell') {
                    $prd = DB::table('productSells as ps')
                        ->join('productRestockDetails as prd', 'ps.id', 'prd.productId')
                        ->select(
                            'prd.id',
                            'prd.purchaseRequestNumber',
                            'ps.fullName',
                            DB::raw("TRIM(prd.costPerItem)+0 as costPerItem"),
                            'prd.reStockQuantity',
                            'prd.id'
                        )
                        ->where('prd.id', '=', $value->id)
                        ->first();
                } elseif ($value->productType == 'productClinic') {
                    $prd = DB::table('productClinics as pc')
                        ->join('productRestockDetails as prd', 'pc.id', 'prd.productId')
                        ->select(
                            'prd.id',
                            'prd.purchaseRequestNumber',
                            'pc.fullName',
                            DB::raw("TRIM(prd.costPerItem)+0 as costPerItem"),
                            'prd.reStockQuantity',
                            'prd.id'
                        )
                        ->where('prd.id', '=', $value->id)
                        ->first();
                }

                $data[] = array(
                    'id' => $prd->id,
                    'purchaseRequestNumber' => $prd->purchaseRequestNumber,
                    'fullName' => $prd->fullName,
                    'unitCost' => $prd->costPerItem,
                    'orderQuantity' => $prd->reStockQuantity,
                );
                // }
            }

            return responseList($data);
        } elseif ($request->type == 'receive') {
        } else {
            $chk = productRestocks::find($request->id);

            if (!$chk) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }

            $restock = DB::table('productRestocks as pres')
                ->join('location as loc', 'loc.Id', 'pres.locationId')
                ->join('users as u', 'pres.userId', 'u.id')
                ->select(
                    'pres.id',
                    'pres.numberId',
                    'loc.locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(pres.created_at, '%W, %d %M %Y') as createdAt")
                )
                ->where('pres.id', '=', $request->id)
                ->first();

            $tracking = DB::table('productRestockTrackings as pt')
                ->join('users as u', 'pt.userId', 'u.id')
                ->select(
                    'pt.progress',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )
                ->where('pt.productRestockId', '=', $request->id)
                ->get();

            $restock->tracking = $tracking;

            $suppList = DB::table('productRestockDetails as prd')
                ->select('ps.id')
                ->join('productSuppliers as ps', 'prd.supplierId', 'ps.id')
                ->where('prd.productRestockId', '=', $request->id)
                ->groupby('ps.id')
                ->orderBy('ps.updated_at', 'desc')
                ->distinct()
                ->pluck('ps.id');

            foreach ($suppList as $value) {

                $prodSingle = DB::table('productRestockDetails as prd')
                    ->join('productSuppliers as ps', 'prd.supplierId', 'ps.id')
                    ->where('prd.productRestockId', '=', $request->id)
                    ->where('prd.supplierId', '=', $value)
                    ->first();

                $prodList = DB::table('productRestockDetails as prd')
                    ->join('productSuppliers as ps', 'prd.supplierId', 'ps.id')
                    ->where('prd.productRestockId', '=', $request->id)
                    ->where('prd.supplierId', '=', $value)
                    ->get();

                $cntProdList = DB::table('productRestockDetails as prd')
                    ->where('prd.productRestockId', '=', $request->id)
                    ->where('prd.supplierId', '=', $value)
                    ->count();

                foreach ($prodList as $list) {
                    if ($list->productType == 'productSell') {
                        $prd = DB::table('productSells as ps')
                            ->join('productRestockDetails as prd', 'ps.id', 'prd.productId')
                            ->select('prd.id', 'ps.fullName', DB::raw("TRIM(prd.costPerItem)+0 as costPerItem"), 'prd.reStockQuantity', 'prd.rejected', 'prd.canceled', 'prd.accepted', 'prd.received', 'prd.id')
                            ->where('ps.id', '=', $list->productId)
                            ->where('prd.productType', '=', 'productSell')
                            ->first();
                    } elseif ($list->productType == 'productClinic') {
                        $prd = DB::table('productClinics as pc')
                            ->join('productRestockDetails as prd', 'pc.id', 'prd.productId')
                            ->select('prd.id', 'pc.fullName', DB::raw("TRIM(prd.costPerItem)+0 as costPerItem"), 'prd.reStockQuantity', 'prd.rejected', 'prd.canceled', 'prd.accepted', 'prd.received', 'prd.id')
                            ->where('pc.id', '=', $list->productId)
                            ->where('prd.productType', '=', 'productClinic')
                            ->first();
                    }

                    $image = DB::table('productRestockImages as pri')
                        ->select('pri.id', 'pri.labelName', 'pri.realImageName', 'pri.imagePath')
                        ->where('pri.productRestockDetailId', '=', $prd->id)
                        ->get();

                    $data[] = array(
                        'id' => $prd->id,
                        'fullName' => $prd->fullName,
                        'unitCost' => $prd->costPerItem,
                        'orderQuantity' => $prd->reStockQuantity,
                        'rejected' => $prd->rejected,
                        'canceled' => $prd->canceled,
                        'accepted' => $prd->accepted,
                        'received' => $prd->received,
                        'images' => $image
                    );
                }

                $dataSup[] = array(
                    'supplierName' => $prodSingle->supplierName,
                    'quantity' => $cntProdList,
                    'purchaseRequestNumber' => $prodSingle->purchaseRequestNumber,
                    'detail' => $data
                );

                $data = [];
            }

            $restock->dataSupplier = $dataSup;
            return responseList($restock);
        }
    }

    public function detailHistory(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productRestockLogs as prl')
            ->join('users as u', 'prl.userId', 'u.id')
            ->select(
                DB::raw("DATE_FORMAT(prl.created_at, '%W, %d %M %Y') as date"),
                DB::raw("DATE_FORMAT(prl.created_at, '%H:%i') as time"),
                'u.firstName as createdBy',
                'prl.details',
                'prl.event'
            )
            ->where('prl.productRestockId', '=', $request->id);

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('prl.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    public function listSupplier(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $data = DB::table('productRestockDetails as prd')
            ->join('productSuppliers as ps', 'prd.supplierId', 'ps.id')
            ->select('prd.supplierId as id', 'ps.supplierName')
            ->groupBy('prd.supplierId')
            ->groupBy('ps.supplierName')
            ->where('prd.productRestockId', '=', $request->id)
            ->get();

        return response()->json($data, 200);
    }

    public function exportPDF(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'isExportAll' => 'required|boolean',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $time = Carbon::now()->format('YmdHisu');

        $path = public_path() . '/ProductRestock/' . $time;

        File::makeDirectory($path);

        $supplierIdList = [];

        if ($request->isExportAll == true) {

            $dataSupp = DB::table('productRestockDetails as prd')
                ->select('prd.supplierId')
                ->where('prd.productRestockId', '=', $request->id)
                ->distinct()
                ->pluck('prd.supplierId');

            $supplierIdList = $dataSupp;
        } else {
            $supplierIdList = $request->supplierId;
        }

        foreach ($supplierIdList as $valSup) {
            $supp = DB::table('productRestockDetails as prd')
                ->where('prd.productRestockId', '=', $request->id)
                ->where('prd.supplierId', '=', $valSup)
                ->get();

            foreach ($supp as $value) {

                if ($value->productType == 'productSell') {
                    $prd = DB::table('productSells as ps')
                        ->join('productRestockDetails as prd', 'ps.id', 'prd.productId')
                        ->select(
                            'prd.purchaseRequestNumber',
                            'prd.purchaseOrderNumber',
                            'ps.fullName',
                            'prd.reStockQuantity as quantity',
                            'prd.costPerItem',
                            'prd.total'
                        )
                        ->where('ps.id', '=', $value->productId)
                        ->where('prd.productRestockId', '=', $request->id)
                        ->where('prd.supplierId', '=', $valSup)
                        ->where('prd.productType', '=', 'productSell')
                        ->first();
                } elseif ($value->productType == 'productClinic') {
                    $prd = DB::table('productClinics as pc')
                        ->join('productRestockDetails as prd', 'pc.id', 'prd.productId')
                        ->select(
                            'prd.purchaseRequestNumber',
                            'prd.purchaseOrderNumber',
                            'pc.fullName',
                            'prd.reStockQuantity as quantity',
                            'prd.costPerItem',
                            'prd.total'
                        )
                        ->where('pc.id', '=', $value->productId)
                        ->where('prd.productRestockId', '=', $request->id)
                        ->where('prd.supplierId', '=', $valSup)
                        ->where('prd.productType', '=', 'productClinic')
                        ->first();
                }

                $data[] = array(
                    'purchaseRequestNumber' => $prd->purchaseRequestNumber,
                    'purchaseOrderNumber' => $prd->purchaseOrderNumber,
                    'fullName' => $prd->fullName,
                    'quantity' => $prd->quantity,
                    'costPerItem' => $prd->costPerItem,
                    'total' => $prd->total,
                );
            }

            $dataSupplier = DB::table('productSuppliers as ps')
                ->Leftjoin('productSupplierAddresses as psa', 'psa.productSupplierId', 'ps.id')
                ->Leftjoin('provinsi as p', 'p.kodeProvinsi', 'psa.province')
                ->Leftjoin('kabupaten as k', 'k.kodeKabupaten', 'psa.city')
                ->Leftjoin('productSupplierPhones as psp', 'ps.id', 'psp.productSupplierId')
                ->select(
                    'ps.id',
                    'ps.supplierName',
                    DB::raw("IFNULL(ps.pic,'-') as pic"),
                    DB::raw("IFNULL(psa.streetAddress,'-') as streetAddress"),
                    DB::raw("IFNULL(p.namaProvinsi,'-') as namaProvinsi"),
                    DB::raw("IFNULL(k.namaKabupaten,'-') as namaKabupaten"),
                    DB::raw("IFNULL(psa.postalCode,'-') as postalCode"),
                    DB::raw("IFNULL(psp.number,'-') as number"),
                )
                ->where('ps.id', '=', $valSup)
                ->first();

            $dataWhatsApp = DB::table('productSupplierTypePhones')
                ->where('typeName', 'like', '%whatsapp%')
                ->first();

            $dataFax = DB::table('productSupplierTypePhones')
                ->where('typeName', 'like', '%fax%')
                ->first();

            $dataPic = DB::table('productSupplierTypePhones')
                ->where('typeName', 'like', '%pic%')
                ->first();

            $suppWa = null;

            if ($dataWhatsApp) {
                $suppWa = DB::table('productSupplierPhones as psp')
                    ->where('psp.productSupplierId', '=', $dataSupplier->id)
                    ->where('psp.typePhoneId', '=', $dataWhatsApp->id)
                    ->first();
            }

            $suppFax = null;

            if ($dataFax) {
                $suppFax = DB::table('productSupplierPhones as psp')
                    ->where('psp.productSupplierId', '=', $dataSupplier->id)
                    ->where('psp.typePhoneId', '=', $dataFax->id)
                    ->first();
            }

            $suppPic = null;

            if ($dataPic) {
                $suppPic = DB::table('productSupplierPhones as psp')
                    ->where('psp.productSupplierId', '=', $dataSupplier->id)
                    ->where('psp.typePhoneId', '=', $dataPic->id)
                    ->first();
            }

            $dataMaster = DB::table('productRestocks as pr')
                ->join('location as loc', 'loc.Id', 'pr.locationId')
                ->join('users as u', 'pr.userId', 'u.id')
                ->join('usersTelephones as ut', 'ut.usersId', 'u.id')
                ->join('usersRoles as ur', 'ur.id', 'u.jobTitleId')
                ->select(
                    'loc.locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(pr.created_at, '%d/%m/%Y') as createdAt"),
                    'ut.phoneNumber',
                    'ur.roleName'
                )
                ->where('pr.id', '=', $request->id)
                ->where('ut.usage', 'like', '%utama%')
                ->first();

            $dataFooter = DB::table('productRestockDetails as prd')
                ->leftjoin('users as ua', 'prd.userIdAdmin', 'ua.id')
                ->leftjoin('users as uo', 'prd.userIdOffice', 'uo.id')
                ->select(
                    DB::raw("DATE_FORMAT(prd.requireDate, '%d/%m/%Y') as requireDate"),
                    DB::raw("IFNULL(prd.purchaseOrderNumber,'-') as purchaseOrderNumber"),
                    DB::raw("IFNULL(ua.firstName,'-') as adminApprovedBy"),
                    DB::raw("IFNULL(uo.firstName,'-') as officeApprovedBy"),
                )
                ->where('prd.productRestockId', '=', $request->id)
                ->where('prd.supplierId', '=', $dataSupplier->id)
                ->first();

            $sourceData = [
                'dataMaster' => $dataMaster,
                'data' => $data,
                'dataSupplier' => $dataSupplier,
                'dataWhatsApp' => $suppWa,
                'dataFax' => $suppFax,
                'dataPic' => $suppPic,
                'dataFooter' => $dataFooter
            ];

            $data = [];

            $pdf = PDF::loadview('restock-pr-template', $sourceData);
            $pdf->save($path . '/' . 'Restock PR Produk Supplier ' . $dataSupplier->supplierName . '.pdf');
        }

        $zip_file = 'Restock Product.zip';
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

        foreach ($files as $name => $file) {
            // We're skipping all subfolders
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();

                // extracting filename with substr/strlen
                $relativePath = 'Restock Product/' . substr($filePath, strlen($path) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        File::deleteDirectory($path);

        return response()->download($zip_file);
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'status' => 'required|in:draft,final',
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $datas = $request->productList;

        $variantProduct = 0;
        $totalProduct = 0;
        $totalImages = 0;
        $suppName = "";

        //validasi data

        $validate = Validator::make(
            $datas,
            [
                '*.id' => 'nullable',
                '*.productType' => 'required|string|in:productSell,productClinic',
                '*.productId' => 'required|integer',
                '*.supplierId' => 'required|integer',
                '*.requireDate' => 'required|date',
                '*.currentStock' => 'required|integer',
                '*.restockQuantity' => 'required|integer',
                '*.costPerItem' => 'required|numeric',
                '*.total' => 'required|numeric',
                '*.remark' => 'nullable|string',
            ],
            [
                '*.productType.required' => 'Product Type Should be Required!',
                '*.productType.string' => 'Product Type Should be Filled!',

                '*.productId.required' => 'Product Id Should be Required!',
                '*.productId.integer' => 'Product Id Should be Filled!',

                '*.supplierId.required' => 'Supplier Id Should be Required!',
                '*.supplierId.integer' => 'Supplier Id Should be Filled!',

                '*.requireDate.required' => 'Require Date Should be Required!',
                '*.requireDate.date' => 'Require Date Should be Date!',

                '*.currentStock.required' => 'Current Stock Should be Required!',
                '*.currentStock.integer' => 'Current Stock Should be Filled!',

                '*.restockQuantity.required' => 'Restock Quantity Should be Required!',
                '*.restockQuantity.integer' => 'Restock Quantity Should be Filled!',

                '*.costPerItem.required' => 'Cost per Item Should be Required!',
                '*.costPerItem.integer' => 'Cost per Item Should be Filled!',

                '*.total.required' => 'Total Should be Required!',
                '*.total.integer' => 'Total Should be Filled!',
            ]
        );

        if ($validate->fails()) {
            $errors = $validate->errors()->first();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        foreach ($datas as $value) {

            if ($value['productType'] === 'productSell') {

                $findProd = ProductSell::find($value['productId']);

                $find = ProductSellLocation::where('productSellId', '=', $value['productId'])->first();

                if ($find) {
                    if ($value['restockQuantity'] > $find->reStockLimit) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Restock Quantity on product ' . $findProd->fullName . ' can not be greater than Restock Limit!'],
                        ], 422);
                    }
                }
            } elseif ($value['productType'] === 'productClinic') {
                $findProd = ProductClinic::find($value['productId']);

                $find = ProductClinicLocation::where('productClinicId', '=', $value['productId'])->first();

                if ($find) {
                    if ($value['restockQuantity'] > $find->reStockLimit) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Restock Quantity on product ' . $findProd->fullName . ' can not be greater than Restock Limit!'],
                        ], 422);
                    }
                }
            }

            if ($value['status'] != "del") {
                $totalProduct += $value['restockQuantity'];
                $variantProduct++;

                $supp = DB::table('productSuppliers as ps')
                    ->where('ps.id', '=', $value['supplierId'])
                    ->first();

                $suppName = $suppName . $supp->supplierName . ', ';
            }
        }

        $suppName = rtrim($suppName, ", ");

        $statusdata = '';
        $number = '';

        if ($request->status == 'draft') {
            $statusdata = '0';
            $number = 'draft';
        } elseif ($request->status == 'final') {
            $statusdata = '1';

            $cntNum = DB::table('productRestocks')
                ->where('status', '!=', 0)
                ->count();

            if ($cntNum == 0) {
                $number = '#' . str_pad(1, 8, 0, STR_PAD_LEFT);
            } else {
                $number = '#' . str_pad($cntNum + 1, 8, 0, STR_PAD_LEFT);
            }
        }

        //update data

        productRestocks::updateOrCreate(
            ['id' => $request->id],
            [
                'numberId' => $number,
                'locationId' => $request->locationId,
                'variantProduct' => $variantProduct,
                'totalProduct' => $totalProduct,
                'supplierName' => $suppName,
                'status' => $statusdata,
                'isAdminApproval' => 0,
                'updated_at' => Carbon::now(),
                'userUpdateId' => $request->user()->id,
            ]
        );

        if ($number == 'draft') {
            productRestockLog($request->id, "Updated", "Draft", $request->user()->id);
        } else {
            productRestockLog($request->id, "Updated", "Waiting for Approval", $request->user()->id);
        }

        $number = "";
        $prNumber = "";

        $adminApprovalMaster = false;

        foreach ($datas as $val) {
            $checkAdminApproval = false;

            if ($val['productType'] === 'productSell') {

                $find = ProductSellLocation::where('productSellId', '=', $val['productId'])->first();
                $diffStock = $find->diffStock;
            } elseif ($val['productType'] === 'productClinic') {

                $find = ProductClinicLocation::where('productClinicId', '=', $val['productId'])->first();
                $diffStock = $find->diffStock;
            }

            if ($diffStock > 0) {
                $checkAdminApproval = true;
                $adminApprovalMaster = true;
            }

            if ($request->status == 'final') {

                $findDataPr = DB::table('productRestockDetails')
                    ->select('purchaseRequestNumber')
                    ->whereDate('updated_at', Carbon::today())
                    ->where('purchaseRequestNumber', '!=', '')
                    ->groupby('purchaseRequestNumber')
                    ->get();

                $findDataSup = productRestockDetails::where('productRestockId', '=', $request->id)
                    ->where('supplierId', '=', $val['supplierId'])
                    ->where('purchaseRequestNumber', '!=', '')
                    ->first();

                $number = Carbon::today();

                if (count($findDataPr) == 0) {
                    $prNumber = 'RPC-PR-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
                } else {

                    if ($findDataSup) {

                        $prNumber = $findDataSup->purchaseRequestNumber;
                    } else {
                        $prNumber = 'RPC-PR-' . $number->format('Ymd') . str_pad(count($findDataPr) + 1, 5, 0, STR_PAD_LEFT);
                    }
                }
            }

            if ($val['status'] === 'del') {
                if ($val['id']) {
                    $res = productRestockDetails::find($val['id']);

                    $res->DeletedBy = $request->user()->id;
                    $res->isDeleted = true;
                    $res->DeletedAt = Carbon::now();
                    $res->save();
                }
            } else {

                $prod = productRestockDetails::find($val['id']);
                $userId = '';
                if ($prod) {
                    $userId = $prod->userId;
                } else {
                    $userId = $request->user()->id;
                }

                productRestockDetails::updateOrCreate(
                    ['id' => $val['id']],
                    [
                        'purchaseRequestNumber' => $prNumber,
                        'purchaseOrderNumber' => '',
                        'productRestockId' => $request->id,
                        'productId' => $val['productId'],
                        'productType' => $val['productType'],
                        'supplierId' => $val['supplierId'],
                        'requireDate' => $val['requireDate'],
                        'currentStock' => $val['currentStock'],
                        'reStockQuantity' => $val['restockQuantity'],
                        'rejected' => '0',
                        'canceled' => '0',
                        'accepted' => '0',
                        'received' => '0',
                        'costPerItem' => $val['costPerItem'],
                        'total' => $val['total'],
                        'remark' => $val['remark'],
                        'isAdminApproval' => $checkAdminApproval,
                        'updated_at' => Carbon::now(),
                        'userId' => $userId,
                        'userUpdateId' => $request->user()->id,
                    ]
                );
            }

            // foreach ($val['images'] as $valueImg) {

            //     if ($valueImg['status'] == 'del') {
            //         $res = productRestockImages::find($valueImg['id']);

            //         $res->DeletedBy = $request->user()->id;
            //         $res->isDeleted = true;
            //         $res->DeletedAt = Carbon::now();
            //         $res->save();
            //     } else {
            //         $image = str_replace('data:image/', '', $valueImg['imagePath']);
            //         $image = explode(';base64,', $image);
            //         $imageName = Str::random(40) . '.' . $image[0];
            //         File::put(public_path('ProductRestockImages') . '/' . $imageName, base64_decode($image[1]));

            //         productRestockImages::updateOrCreate(
            //             ['id' => $valueImg['id']],
            //             [
            //                 'productRestockDetailId' => $val['id'],
            //                 'labelName' => $valueImg['label'],
            //                 'realImageName' => $valueImg['originalName'],
            //                 'imagePath' => '/ProductRestockImages' . '/' . $imageName,
            //                 'updated_at' => Carbon::now(),
            //                 'userUpdateId' => $request->user()->id,
            //             ]
            //         );
            //     }
            // }
        }

        if ($adminApprovalMaster == true) {

            $res = productRestocks::find($request->id);
            $res->isAdminApproval = $adminApprovalMaster;
            $res->save();
        }

        return responseUpdate();
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'id.*' => 'required|integer',
                ],
                [
                    'id.*.required' => 'Product Type Should be Required!',
                    'id.*.integer' => 'Product Type Should be Integer!',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }

            if (adminAccess($request->user()->id)) {

                $tmp_num = '';

                foreach ($request->id as $va) {

                    $res = productRestocks::find($va);

                    if (!$res) {

                        return responseInvalid(['There is any Data not found!']);
                    }

                    if ($res->status == 5) {
                        $tmp_num = $tmp_num . (string) $res->numberId . ', ';
                    }
                }

                if ($tmp_num != '') {
                    return responseInvalid(['Restock with ID Number ' . rtrim($tmp_num, ', ') . ' cannot be deleted. Becasue has already received!']);
                }
            } else {

                $tmp_num = '';

                foreach ($request->id as $va) {
                    $res = productRestocks::find($va);

                    if (!$res) {

                        return responseInvalid(['There is any Data not found!']);
                    }

                    if ($res->status != 0) {
                        $tmp_num = $tmp_num . (string) $res->numberId . ', ';
                    }
                }

                if ($tmp_num != '') {
                    return responseInvalid(['Restock with ID Number ' . rtrim($tmp_num, ', ') . ' cannot be deleted. Becasue has already submited, has sent to Supplier or has already received!']);
                }
            }

            foreach ($request->id as $va) {
                $res = productRestocks::find($va);

                $res->DeletedBy = $request->user()->id;
                $res->isDeleted = true;
                $res->DeletedAt = Carbon::now();
                $res->save();

                DB::table('productRestockDetails')
                    ->where('productRestockId', '=', $va)
                    ->update([
                        'isDeleted' => true,
                        'DeletedBy' => $request->user()->id,
                        'DeletedAt' => Carbon::now()
                    ]);
            }

            DB::commit();
            return responseDelete();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function approval(Request $request)
    {
        DB::beginTransaction();
        try {
            $validate = Validator::make($request->all(), [
                'productRestockId' => 'required|integer',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return responseInvalid([$errors]);
            }

            $isAdmin = false;

            if (adminAccess($request->user()->id)) {
                $isAdmin = true;
            }

            $find = productRestocks::find($request->productRestockId);

            if (!$find) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data Restock not found!'],
                ], 422);
            }

            if ($request->isAcceptedAll == '1') {

                $detail = productRestockDetails::where('productRestockId', '=', $request->productRestockId)->get();

                foreach ($detail as $value) {

                    $detail2 = productRestockDetails::find($value['id']);

                    if ($isAdmin) {

                        if ($detail2->isAdminApproval == 1) {
                            $detail2->isApprovedAdmin = 1;
                            $detail2->userIdAdmin = $request->user()->id;
                            $detail2->adminApprovedAt = Carbon::now();
                        }
                    } else {
                        $detail2->isApprovedOffice = 1;
                        $detail2->userIdOffice = $request->user()->id;
                        $detail2->officeApprovedAt = Carbon::now();
                    }

                    $detail2->accepted = $detail2->reStockQuantity;
                    $detail2->updated_at = Carbon::now();
                    $detail2->save();
                }

                $suppList = DB::table('productRestockDetails as prd')
                    ->select('prd.supplierId')
                    ->where('prd.productRestockId', '=', $request->productRestockId)
                    ->groupby('prd.supplierId')
                    ->distinct()
                    ->pluck('prd.supplierId');

                foreach ($suppList as $supp) {

                    $findData = DB::table('productRestockDetails')
                        ->select('purchaseOrderNumber')
                        ->whereDate('updated_at', Carbon::today())
                        ->where('purchaseOrderNumber', '!=', '')
                        ->groupBy('purchaseOrderNumber')
                        ->get();

                    if (count($findData) == 0) {
                        $number = Carbon::today();
                        $number = 'RPC-PO-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
                    } else {
                        $number = Carbon::today();
                        $number = 'RPC-PO-' . $number->format('Ymd') . str_pad(count($findData) + 1, 5, 0, STR_PAD_LEFT);
                    }

                    DB::table('productRestockDetails')
                        ->where('productRestockId', '=', $request->productRestockId)
                        ->where('supplierId', '=', $supp)
                        ->update([
                            'purchaseOrderNumber' => $number
                        ]);
                }

                $checkAdminApproval = DB::table('productRestockDetails')
                    ->where('productRestockId', '=', $request->productRestockId)
                    ->where('isAdminApproval', '=', 1)
                    ->get();

                if ($checkAdminApproval) {

                    $adminApproved = DB::table('productRestockDetails')
                        ->where('productRestockId', '=', $request->productRestockId)
                        ->where('isApprovedAdmin', '=', 1)
                        ->get();

                    if (count($checkAdminApproval) == count($adminApproved)) {
                        $find->status = 3;
                        $find->updated_at = Carbon::now();
                        $find->userUpdateId = $request->user()->id;
                        $find->save();
                    }
                } else {
                    $find->status = 3;
                    $find->updated_at = Carbon::now();
                    $find->userUpdateId = $request->user()->id;
                    $find->save();
                }
            } elseif ($request->isRejectedAll == '1') {
                $find->status = 2;
                $find->updated_at = Carbon::now();
                $find->userUpdateId = $request->user()->id;
                $find->save();

                $detail = productRestockDetails::where('productRestockId', '=', $request->productRestockId)->get();

                foreach ($detail as $value) {

                    $detail2 = productRestockDetails::find($value['id']);

                    if ($isAdmin) {
                        $detail2->isApprovedAdmin = 2;
                        $detail2->userIdAdmin = $request->user()->id;
                        $detail2->adminApprovedAt = Carbon::now();
                        $detail2->reasonAdmin = $request->reasonRejectAll;
                    } else {
                        $detail2->isApprovedOffice = 2;
                        $detail2->userIdOffice = $request->user()->id;
                        $detail2->officeApprovedAt = Carbon::now();
                        $detail2->reasonOffice = $request->reasonRejectAll;
                    }

                    $detail2->rejected = $detail2->reStockQuantity;
                    $detail2->updated_at = Carbon::now();
                    $detail2->save();
                }
            } else {

                $datas = json_decode($request->productRestocks, true);

                $validate = Validator::make(
                    $datas,
                    [
                        '*.productRestockDetailId' => 'required|integer',
                        '*.reStockQuantity' => 'required|integer',
                        '*.accepted' => 'required|integer',
                        '*.rejected' => 'required|integer',
                    ],
                    [
                        '*.productRestockDetailId.required' => 'Product Restock Detail Id Should be Required!',
                        '*.productRestockDetailId.integer' => 'Product Restock Detail Id Should be Integer!',
                        '*.reStockQuantity.required' => 'Restock Quantity Should be Required!',
                        '*.reStockQuantity.integer' => 'Restock Quantity Should be Integer!',
                        '*.accepted.required' => 'Accepeted Should be Required!',
                        '*.accepted.integer' => 'Accepeted Should be Integer!',
                        '*.rejected.required' => 'Rejected Should be Required!',
                        '*.rejected.integer' => 'Rejected Should be Integer!',
                    ]
                );

                if ($validate->fails()) {
                    $errors = $validate->errors()->first();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }

                foreach ($datas as $value) {
                    $findRestock = productRestockDetails::find($value['productRestockDetailId']);

                    if (!$findRestock) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Data Restock Detail not found!'],
                        ], 422);
                    }

                    if ($findRestock->purchaseRequestNumber == null) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Product do not have Purchase Request Number!'],
                        ], 422);
                    }

                    if ($findRestock->reStockQuantity != $value['reStockQuantity']) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Restock Quantity not same with system!'],
                        ], 422);
                    }

                    $totalApproval = $value['accepted'] + $value['rejected'];

                    if ($totalApproval != $value['reStockQuantity']) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Total data approval are not same with total Restock!'],
                        ], 422);
                    }

                    $findData = DB::table('productRestockDetails')
                        ->whereDate('updated_at', Carbon::today())
                        ->where('purchaseOrderNumber', '!=', '')
                        ->get();

                    if (count($findData) == 0) {
                        $number = Carbon::today();
                        $number = 'RPC-PO-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
                    } else {
                        $number = Carbon::today();
                        $number = 'RPC-PO-' . $number->format('Ymd') . str_pad(count($findData) + 1, 5, 0, STR_PAD_LEFT);
                    }

                    if ($isAdmin) {
                        //kalo tolak semua
                        //kalo terima sebagian
                        //kalo terima semua
                        if ($value['rejected'] == $value['reStockQuantity']) {

                            $findRestock->isApprovedAdmin = 2;
                            $findRestock->userIdAdmin = $request->user()->id;
                            $findRestock->adminApprovedAt = Carbon::now();
                            $findRestock->reasonAdmin = $value['reasonReject'];

                            $findRestock->rejected = $value['rejected'];
                            $findRestock->updated_at = Carbon::now();
                            $findRestock->save();
                        } else if ($value['rejected'] > 0) {

                            $findRestock->isApprovedAdmin = 1;
                            $findRestock->userIdAdmin = $request->user()->id;
                            $findRestock->adminApprovedAt = Carbon::now();
                            $findRestock->reasonAdmin = $value['reasonReject'];

                            $findRestock->rejected = $value['rejected'];
                            $findRestock->accepted = $value['accepted'];
                            $findRestock->updated_at = Carbon::now();
                            $findRestock->save();
                        } elseif ($value['accepted'] == $value['reStockQuantity']) {
                            $findRestock->isApprovedAdmin = 1;
                            $findRestock->userIdAdmin = $request->user()->id;
                            $findRestock->adminApprovedAt = Carbon::now();

                            $findRestock->accepted = $value['accepted'];
                            $findRestock->updated_at = Carbon::now();
                            $findRestock->save();
                        }
                    } else {
                        if ($value['rejected'] == $value['reStockQuantity']) {

                            $findRestock->isApprovedOffice = 2;
                            $findRestock->userIdOffice = $request->user()->id;
                            $findRestock->officeApprovedAt = Carbon::now();
                            $findRestock->reasonOffice = $value['reasonReject'];

                            $findRestock->rejected = $value['rejected'];
                            $findRestock->updated_at = Carbon::now();
                            $findRestock->save();
                        } else if ($value['rejected'] > 0) {

                            $findRestock->isApprovedOffice = 1;
                            $findRestock->userIdOffice = $request->user()->id;
                            $findRestock->officeApprovedAt = Carbon::now();
                            $findRestock->reasonOffice = $value['reasonReject'];

                            $findRestock->rejected = $value['rejected'];
                            $findRestock->accepted = $value['accepted'];
                            $findRestock->updated_at = Carbon::now();
                            $findRestock->save();
                        } elseif ($value['accepted'] == $value['reStockQuantity']) {
                            $findRestock->isApprovedOffice = 1;
                            $findRestock->userIdOffice = $request->user()->id;
                            $findRestock->officeApprovedAt = Carbon::now();

                            $findRestock->accepted = $value['accepted'];
                            $findRestock->updated_at = Carbon::now();
                            $findRestock->save();
                        }
                    }

                    $findRestock2 = productRestockDetails::find($value['productRestockDetailId']);

                    if ($findRestock2->isAdminApproval == 1) {

                        if ($findRestock2->isApprovedAdmin == 1 && $findRestock2->isApprovedOffice) {
                            $findRestock2->purchaseOrderNumber = $number;
                        }
                    } else {
                        if ($findRestock2->isApprovedOffice == 1) {
                            $findRestock2->purchaseOrderNumber = $number;
                        }
                    }

                    $findRestock2->save();
                }

                $prodRestock = productRestocks::find($request->productRestockId);

                $findDetailAdmin = DB::table('productRestockDetails')
                    ->where('productRestockId', '=', $request->productRestockId)
                    ->where('isAdminApproval', '=', 1)
                    ->get();

                if (count($findDetailAdmin) > 0) {
                    $findAdminApproval = DB::table('productRestockDetails')
                        ->where('productRestockId', '=', $request->productRestockId)
                        ->where('isApprovedAdmin', '=', 1)
                        ->get();

                    if (count($findDetailAdmin) == count($findAdminApproval)) {
                        $prodRestock->status = 3;
                        $prodRestock->updated_at = Carbon::now();
                    }
                } else {
                    $findOfficeApproval = DB::table('productRestockDetails')
                        ->where('productRestockId', '=', $request->productRestockId)
                        ->where('isApprovedOffice', '=', 1)
                        ->get();

                    if (count($findOfficeApproval) > 0) {
                        $prodRestock->status = 3;
                        $prodRestock->updated_at = Carbon::now();
                    }
                }

                $prodRestock->save();
            }
            DB::commit();
            return responseUpdate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function sentSupplier(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'productRestockId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid([$errors]);
        }

        $prod = productRestocks::find($request->productRestockId);

        if ($prod->status != 3) {
            return responseInvalid(['Only accepted restock can be sent to Supplier!']);
        }

        $prod->status = 4;
        $prod->save();

        return responseUpdate();
    }

    public function confirmReceive(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'productRestockId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $datas = json_decode($request->productRestocks, true);

        $validate = Validator::make(
            $datas,
            [
                '*.productRestockDetailId' => 'required|integer',
                '*.accepted' => 'required|integer',
                '*.received' => 'required|integer',
                '*.canceled' => 'required|integer',
                '*.expiredDate' => 'required|date',
            ],
            [
                '*.productRestockDetailId.required' => 'Product Restock Detail Id Should be Required!',
                '*.productRestockDetailId.integer' => 'Product Restock Detail Id Should be Integer!',
                '*.received.required' => 'Received Should be Required!',
                '*.received.integer' => 'Received Should be Integer!',
                '*.accepted.required' => 'Accepted Should be Required!',
                '*.accepted.integer' => 'Accepted Should be Integer!',
                '*.expiredDate.required' => 'Expired Date Should be Required!',
                '*.expiredDate.date' => 'Expired Date Should be Date!',
            ]
        );

        if ($validate->fails()) {
            $errors = $validate->errors()->first();

            return responseInvalid([$errors]);
        }

        foreach ($datas as $value) {

            $dtl = productRestockDetails::find($value['productRestockDetailId']);

            if(!$dtl){
                return responseInvalid(['There is no any data Detail Restock!']);
            }

            if ($value['accepted'] != ($value['received'] + $value['canceled'])) {
                if ($dtl->productType == 'productSell') {
                    $find = ProductSell::find($dtl->productId);
                } elseif ($dtl->productType == 'productClinic') {
                    $find = ProductClinic::find($dtl->productId);
                }
                return responseInvalid(['Total accepted not same with received and canceled at product ' . $find->fullName]);
            }

            if ($dtl->accepted != $value['accepted']) {
                if ($dtl->productType == 'productSell') {
                    $find = ProductSell::find($dtl->productId);
                } elseif ($dtl->productType == 'productClinic') {
                    $find = ProductClinic::find($dtl->productId);
                }
                return responseInvalid(['Total accepted not same with system at product ' . $find->fullName]);
            }
        }

        foreach ($datas as $value) {

            $dtl = productRestockDetails::find($value['productRestockDetailId']);

            $dtl->received = $value['received'];
            $dtl->canceled = $value['canceled'];
            $dtl->reasonCancel = $value['reasonCancel'];
            $dtl->userId = $request->user()->id;
            $dtl->updated_at = Carbon::now();
            $dtl->save();

            $img = $value['imagePath'];

            list($type, $img) = explode(';', $img);
            list(, $img)      = explode(',', $img);
            $img = base64_decode($img);

            $image = str_replace('data:image/', '', $value['imagePath']);
            $image = explode(';base64,', $image);
            $imageName = Str::random(40) . '.' . $image[0];

            file_put_contents(public_path() . '/ProductRestockReceiveImages/' . $imageName, $img);

            productRestockImageReceive::create(
                [
                    'productRestockDetailId' => $value['productRestockDetailId'],
                    'realImageName' => $value['originalName'],
                    'imagePath' => '/ProductRestockReceiveImages' . '/' . $imageName,
                    'userId' => $request->user()->id,
                ]
            );
            //masuk ke produk restock dan batch
            $detail = productRestockDetails::find($value['productRestockDetailId']);

            if ($detail->productType == 'productSell') {
                $find = ProductSell::find($detail->productId);
                $sellLoc = ProductSellLocation::where('productSellId', '=', $detail->productId)
                    ->first();

                //ngecek ke category untuk tanggal expirednya
                //masukin ke product log
                productSellBatch::create([
                    'batchNumber' => '123',
                    'productId' => $detail->productId,
                    'productRestockId' => $request->productRestockId,
                    'productTransferId' => 0,
                    'transferNumber' => '',
                    'productRestockDetailId' => $detail->id,
                    'purchaseRequestNumber' => $detail->purchaseRequestNumber,
                    'purchaseOrderNumber' => $detail->purchaseOrderNumber,
                    'expiredDate' => $value['expiredDate'],
                    'sku' => $value['sku'],
                    'userId' => $request->user()->id,

                ]);

                $inStock = $sellLoc->inStock;
                $diffStock = $sellLoc->diffStock;

                $newStock =  $value['received'];

                productSellLog($dtl->productId, 'Restock Product', 'Add New Stock', $newStock, ($inStock + $newStock), $request->user()->id);

                $prodLoc = ProductSellLocation::find($sellLoc->id);
                $prodLoc->inStock = $inStock + $newStock;
                $prodLoc->diffStock = $diffStock + $newStock;
                $prodLoc->save();
            } elseif ($detail->productType == 'productClinic') {
                $find = ProductClinic::find($detail->productId);
                $clinicLoc = ProductClinicLocation::where('productClinicId', '=', $detail->productId)
                    ->first();

                productClinicBatch::create([
                    'batchNumber' => '123',
                    'productId' => $detail->productId,
                    'productRestockId' => $request->productRestockId,
                    'productTransferId' => 0,
                    'transferNumber' => '',
                    'productRestockDetailId' => $detail->id,
                    'purchaseRequestNumber' => $detail->purchaseRequestNumber,
                    'purchaseOrderNumber' => $detail->purchaseOrderNumber,
                    'expiredDate' => $value['expiredDate'],
                    'sku' => $value['sku'],
                    'userId' => $request->user()->id,

                ]);

                $inStock = $clinicLoc->inStock;
                $diffStock = $clinicLoc->diffStock;

                $newStock =  $value['received'];

                productClinicLog($dtl->productId, 'Restock Product', 'Add New Stock', $newStock, ($inStock + $newStock), $request->user()->id);

                $prodLoc = ProductClinicLocation::find($clinicLoc->id);
                $prodLoc->inStock = $inStock + $newStock;
                $prodLoc->diffStock = $diffStock + $newStock;
                $prodLoc->save();
            }
        }

        $restock = DB::table('productRestockDetails as pr')
            ->where('pr.isDeleted', '=', 0)
            ->where('pr.productRestockId', '=', $request->productRestockId)
            ->get();

        $statusReceive = true;

        foreach ($restock as $value) {

            if ($value->accepted == ($value->received + $value->canceled)) {
                $statusReceive = false;
                break;
            }
        }

        if ($statusReceive == true) {

            $dt = productRestocks::find($request->productRestockId);
            $dt->status = 5;
            $dt->userId = $request->user()->id;
            $dt->updated_at = Carbon::now();
            $dt->save();
        }

        return responseUpdate();
    }
}
