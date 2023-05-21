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
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use DB;
use Excel;
use PDF;

class RestockController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productRestocks as pr')
            ->join('location as loc', 'loc.Id', 'pr.locationId')
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

            $detail = DB::table('productRestockDetails as pr')
                ->select('pr.productRestockId')
                ->whereIn('pr.supplierId', $request->supplierId)
                ->where('pr.isDeleted', '=', 0)
                ->distinct()
                ->pluck('pr.productRestockId');

            $data = $data->whereIn('pr.id', $detail);
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
            ->where('ps.isDeleted', '=', 0);

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

        if ($request->productType == 'productSell') {

            $prodType = "Product Sell";

            $prod = ProductSell::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }

            $stockProd = ProductSellLocation::where('productSellId', '=', $request->productId)->first();
        } else {
            $prodType = "Product Clinic";

            $prod = ProductClinic::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }

            $stockProd = ProductClinicLocation::where('productClinicId', '=', $request->productId)->first();
        }

        if ($stockProd->reStockLimit < $request->reStockQuantity) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Restock Quantity can not be greater than Restock Limit!'],
            ], 422);
        }

        $findData = productRestocks::whereDate('created_at', Carbon::today())->count();

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

        $prodRstk = productRestocks::create([
            'purchaseRequestNumber' => $number,
            'status' => 0,
            'isAdminApproval' => $checkAdminApproval,
            'userId' => $request->user()->id,
            //status 0 = waiting for approval, status 1 = approved, status 2 = reject, status 3 = product has arrive
        ]);

        productRestockDetails::create([

            'productRestockId' => $prodRstk->id,
            'productId' => $request->productId,
            'productType' => $prodType,
            'supplierId' => $request->supplierId,
            'requireDate' => $request->requireDate,
            'reStockQuantity' => $request->reStockQuantity,
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
        $files[] = $request->file('images');
        $ResImageDatas = json_decode($request->imagesName, true);
        $res_data = [];
        $countresData = 0;
        $count = 0;

        if ($request->hasfile('images')) {

            foreach ($files as $file) {

                foreach ($file as $fil) {
                    $name = $fil->hashName();

                    $fil->move(public_path() . '/ProductRestockImages/', $name);

                    $fileName = "/ProductRestockImages/" . $name;

                    $file = new productRestockImages();
                    $file->productRestockDetailId = 0;
                    $file->labelName = $ResImageDatas[$count]['name'];
                    $file->realImageName = $fil->getClientOriginalName();
                    $file->imagePath = $fileName;
                    $file->userId = $request->user()->id;

                    array_push($res_data, $file);
                    $count += 1;
                }
            }
        }

        if ($totalImages != count($ResImageDatas) && $totalImages != count($res_data)) {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Any issue on insert image, please refresh your page!'],
            ], 422);
        }

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


        $findData = productRestocks::whereDate('created_at', Carbon::today())->count();

        $number = "";
        $prNumber = "";

        foreach ($datas as $val) {

            if ($request->status == 'final') {

                if ($findData == 0) {
                    $number = Carbon::today();
                    $number = 'RPC-PR-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
                } else {
                    $number = Carbon::today();
                    $number = 'RPC-PR-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
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

            for ($i = 0; $i < $value['totalImage']; $i++) {
                productRestockImages::create([
                    'productRestockDetailId' => $prodDetail->id,
                    'labelName' => $res_data[$countresData]['labelName'],
                    'realImageName' => $res_data[$countresData]['realImageName'],
                    'imagePath' => $res_data[$countresData]['imagePath'],
                    'userId' => $request->user()->id,
                ]);
                $countresData += 1;
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
        $tmp = "";
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
                    $tmp = $tmp . (string) $key->locationName . ",";
                }
            }
            $tmp = rtrim($tmp, ", ");
        }

        if ($tmp == "") {
            $fileName = "Rekap Restock Produk " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Restock Produk " . $tmp . " " . $date . ".xlsx";
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

        $restock = DB::table('productRestocks as pres')
            ->join('location as loc', 'loc.Id', 'pres.locationId')
            ->join('users as u', 'pres.userId', 'u.id')
            ->select(
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
                        ->select('ps.fullName', 'prd.reStockQuantity', 'prd.rejected', 'prd.canceled', 'prd.accepted', 'prd.received')
                        ->where('ps.id', '=', $list->productId)
                        ->where('prd.productType', '=', 'productSell')
                        ->first();
                } elseif ($list->productType == 'productClinic') {
                    $prd = DB::table('productCLinics as pc')
                        ->join('productRestockDetails as prd', 'pc.id', 'prd.productId')
                        ->select('pc.fullName', 'prd.reStockQuantity', 'prd.rejected', 'prd.canceled', 'prd.accepted', 'prd.received')
                        ->where('pc.id', '=', $list->productId)
                        ->where('prd.productType', '=', 'productClinic')
                        ->first();
                }

                $data[] = array(
                    'fullName' => $prd->fullName,
                    'reStockQuantity' => $prd->reStockQuantity,
                    'rejected' => $prd->rejected,
                    'canceled' => $prd->canceled,
                    'accepted' => $prd->accepted,
                    'received' => $prd->received
                );
            }

            $dataSup[] = array(
                'supplierName' => $prodSingle->supplierName,
                'quantity' => $cntProdList,
                'purchaseOrderNumber' => $prodSingle->purchaseOrderNumber,
                'detail' => $data
            );

            $data = [];
        }

        $restock->dataSupplier = $dataSup;

        return response()->json($restock, 200);
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
            ->select('ps.id', 'ps.supplierName')
            ->where('prd.productRestockId', '=', $request->id)
            ->get();

        return response()->json($data, 200);
    }

    public function exportPDF(Request $request)
    {
        $location = productRestocks::all();

        $data = [
            'location' => $location
        ];

        $pdf = PDF::loadview('index', $data);
        return $pdf->download('restok produk.pdf');
    }

    public function update(Request $request)
    {
    }

    public function delete(Request $request)
    {
    }
}
