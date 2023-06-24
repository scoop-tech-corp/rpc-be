<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\RestockReport;
use App\Http\Controllers\Controller;
use App\Models\ProductClinic;
use App\Models\ProductClinicLocation;
use App\Models\productRestockDetails;
use App\Models\productRestockImages;
use App\Models\productRestocks;
use App\Models\productRestockTracking;
use App\Models\ProductSell;
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
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
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

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
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
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
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
            'status' => 0,
            'isAdminApproval' => $checkAdminApproval,
            'userId' => $request->user()->id,
            //status 0 = waiting for approval, status 1 = approved, status 2 = reject, status 3 = product has arrive
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

        return response()->json(
            [
                'message' => 'Add Data Successful!',
            ],
            200
        );
    }

    public function createMultiple(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:draft,final',
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $datas = json_decode($request->productList, true);

        $variantProduct = 0;
        $totalProduct = 0;
        $totalImages = 0;
        $suppName = "";
        $checkAdminApproval = false;

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

        foreach ($datas as $val) {

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

        return response()->json(
            [
                'message' => 'Add Data Successful!',
            ],
            200
        );
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

        return Excel::download(
            new RestockReport(
                $request->orderValue,
                $request->orderColumn,
                $request->locationId,
                $request->supplierId,
                $request->user()->role
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

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        productRestockTracking::create([
            'productRestockId' => $request->productRestockId,
            'progress' => $request->progress,
            'userId' => $request->user()->id,
        ]);

        return response()->json(
            [
                'message' => 'Insert Data Successful!',
            ],
            200
        );
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

            return response()->json($restock, 200);
        }
    }

    public function detailHistory(Request $request)
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

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    public function listSupplier(Request $request)
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
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
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
                ->join('productSupplierAddresses as psa', 'psa.productSupplierId', 'ps.id')
                ->join('provinsi as p', 'p.kodeProvinsi', 'psa.province')
                ->join('kabupaten as k', 'k.kodeKabupaten', 'psa.city')
                ->join('productSupplierPhones as psp', 'ps.id', 'psp.productSupplierId')
                ->select(
                    'ps.id',
                    'ps.supplierName',
                    DB::raw("IFNULL(ps.pic,'-') as pic"),
                    'psa.streetAddress',
                    'p.namaProvinsi as provinsi',
                    'k.namaKabupaten as kota',
                    'psa.postalCode',
                    'psp.number',
                )
                ->where('ps.id', '=', $valSup)
                ->first();

            if (!$dataSupplier) {

                $dat = ProductSupplier::find($valSup);

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Please Input Address and Phone Number for supplier ' . $dat->supplierName],
                ], 422);
            }

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

            $sourceData = [
                'dataMaster' => $dataMaster,
                'data' => $data,
                'dataSupplier' => $dataSupplier,
                'dataWhatsApp' => $suppWa,
                'dataFax' => $suppFax,
                'dataPic' => $suppPic,
            ];

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
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
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

        foreach ($datas as $val) {

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

        return response()->json(
            [
                'message' => 'Update Data Successful!',
            ],
            200
        );
    }

    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            '.*id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $tmp_num = '';

        foreach ($request->id as $va) {
            $res = productRestocks::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }

            if ($res->status != 0) {
                $tmp_num = $tmp_num . (string) $res->numberId . ', ';
            }
        }

        if ($tmp_num != '') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Restock with ID Number ' . rtrim($tmp_num, ', ') . ' cannot be deleted. Becasue has already submited!'],
            ], 422);
        }

        foreach ($request->id as $va) {
            $res = productRestocks::find($va);

            $res->DeletedBy = $request->user()->id;
            $res->isDeleted = true;
            $res->DeletedAt = Carbon::now();
            $res->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }

    public function approval(Request $request)
    {
        //bisa ada kemungkinan diterima bisa juga di tolak
        $validate = Validator::make($request->all(), [
            'productRestockId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $find = productRestocks::find($request->productRestockId);

        if (!$find) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data Restock not found!'],
            ], 422);
        }

        $datas = json_decode($request->productRestocks, true);

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

            $findData = productRestockDetails::whereDate('updated_at', Carbon::today())
                ->where('purchaseOrderNumber', '!=', '')
                ->count();

            if ($findData == 0) {
                $number = Carbon::today();
                $number = 'RPC-PO-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
            } else {
                $number = Carbon::today();
                $number = 'RPC-PO-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
            }

            $findRestock->purchaseOrderNumber = $number;
            $findRestock->rejected = $value['rejected'];
            $findRestock->accepted = $value['accepted'];
            $findRestock->updated_at = Carbon::now();
            $findRestock->save();
        }

        $prodRestock = productRestocks::find($request->productRestockId);
        $prodRestock->status = 3;


        return response()->json([
            'message' => 'Update Data Successful',
        ], 200);
    }

    public function sentSupplier(Request $request)
    {
    }

    public function confirmReceive(Request $request)
    {
        # code...
    }
}
