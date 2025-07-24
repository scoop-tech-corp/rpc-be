<?php

namespace App\Http\Controllers\Product;

use App\Models\Product;
use App\Models\ProductAdjustment;
use App\Models\ProductBrand;
use App\Models\ProductCategories;
use App\Models\ProductSellLocation;
use App\Models\ProductClinicLocation;
use App\Models\ProductSell;
use App\Models\ProductClinic;
use App\Models\ProductLocations;
use App\Models\Products;
use App\Models\productSupplierTypeMessenger;
use App\Models\productSupplierTypePhone;
use App\Models\productSupplierUsage;
use App\Models\usages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Carbon;

class ProductController
{
    public function IndexProductBrand(Request $request)
    {
        $Data = DB::table('productBrands')
            ->select('id', 'brandName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($Data, 200);
    }

    public function addProductBrand(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'brandName' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIfValueExits = DB::table('productBrands')
                ->where('brandName', '=', $request->brandName)
                ->first();

            if ($checkIfValueExits === null) {

                ProductBrand::create([
                    'brandName' => $request->brandName,
                    'userId' => $request->user()->id,
                ]);

                recentActivity(
                    $request->user()->id,
                    'Product Brand',
                    'Add Brand',
                    'Added product brand "' . $request->brandName . '"'
                );

                return response()->json(
                    [
                        'message' => 'Insert Data Successful!',
                    ],
                    200
                );
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Brand name already exists, please try different name!'],
                ], 422);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json(
                [
                    'message' => $e,
                ],
                500
            );
        }
    }

    public function IndexProductSell(Request $request)
    {
        if ($request->locationId) {

            $data = DB::table('products as ps')
                ->join('productLocations as pl', 'ps.id', 'pl.productId')
                ->select(
                    'ps.id',
                    'ps.fullName',
                    DB::raw("TRIM(ps.price)+0 as price"),
                    'pl.inStock',
                    DB::raw('(CASE WHEN pl.inStock = 0 THEN "NO STOCK" WHEN pl.inStock <= pl.lowStock THEN "LOW STOCK" ELSE "CLEAR" END) AS status'),
                )
                ->where('ps.isDeleted', '=', 0)
                ->where('ps.status', '=', 1)
                ->where('ps.category', '=', 'sell')
                ->where('pl.locationId', '=', $request->locationId);

            if ($request->brandId || $request->brandId != '') {
                $data = $data->where('ps.productBrandId', '=', $request->brandId);
            }

            $data = $data->get();

            return response()->json($data, 200);
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Id location or Id Brand is invalid!'],
            ], 422);
        }
    }

    public function IndexProductClinic(Request $request)
    {

        if ($request->locationId) {

            $data = DB::table('products as p')
                ->join('productLocations as pl', 'p.id', 'pl.productId')
                ->select(
                    'p.id',
                    'p.fullName',
                    DB::raw("TRIM(p.price)+0 as price"),
                    'pl.inStock',
                    DB::raw('(CASE WHEN pl.inStock = 0 THEN "NO STOCK" WHEN pl.inStock <= pl.lowStock THEN "LOW STOCK" ELSE "CLEAR" END) AS status'),
                )
                ->where('p.isDeleted', '=', 0)
                ->where('p.status', '=', 1)
                ->where('p.category', '=', 'clinic')
                ->where('pl.locationId', '=', $request->locationId);

            if ($request->brandId || $request->brandId != '') {
                $data = $data->where('p.productBrandId', '=', $request->brandId);
            }

            $data = $data->get();

            return response()->json($data, 200);
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Id location is invalid!'],
            ], 422);
        }
    }

    public function IndexUsage()
    {

        $data = DB::table('usages as u')
            ->select('u.id', 'u.usage')
            ->where('u.isDeleted', '=', 0)
            ->get();

        return response()->json($data, 200);
    }

    public function CreateUsage(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'usage' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('usages')
            ->where('usage', '=', $request->usage)
            ->first();

        if ($checkIfValueExits === null) {

            usages::create([
                'usage' => $request->usage,
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
                'errors' => ['Usage name already exists!'],
            ], 422);
        }
    }

    public function IndexProductSellSplit(Request $request)
    {
        if ($request->locationId && $request->productSellId) {

            $product = DB::table('products as ps')
                ->join('productLocations as psl', 'ps.id', 'psl.productId')
                ->select('ps.id', 'ps.fullName')
                ->where('ps.isDeleted', '=', 0)
                ->where('psl.locationId', '=', $request->locationId)
                ->where('ps.id', '<>', $request->productSellId)
                ->orderBy('ps.created_at', 'desc')
                ->get();

            return response()->json($product, 200);
        }
    }

    public function adjust(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'productId' => 'required|integer',
            'productType' => 'required|string|in:productSell,productClinic',
            // 'adjustment' => 'required|string|in:increase,decrease',
            'totalAdjustment' => 'required|integer|min:1',
            'different' => 'required|integer',
            'remark' => 'required|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        if ($request->productType == 'productSell') {

            $prod = Products::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is no any data found!'],
                ], 422);
            }

            // $num = 0;


            // if ($request->adjustment == 'increase') {
            //     $num = $request->totalAdjustment;
            //     $transaction = 'Stock Adjustment Increase';
            // } elseif ($request->adjustment == 'decrease') {
            //     $num = $request->totalAdjustment * -1;
            //     $transaction = 'Stock Adjustment Decrease';
            // }

            $prodStock = ProductLocations::where('productId', '=', $request->productId)->first();

            if (!$prodStock) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is no any data found!'],
                ], 422);
            }

            ProductAdjustment::create([
                'productId' => $request->productId,
                'productType' => $request->productType,
                'adjustment' => "",
                'totalAdjustment' => $request->totalAdjustment,
                'remark' => $request->remark,
                'userId' => $request->user()->id,
            ]);

            $inStock = $prodStock->inStock;
            $lowStock = $prodStock->lowStock;

            $prodStock->inStock = $request->totalAdjustment;
            $prodStock->diffStock = $request->totalAdjustment - $lowStock;
            $prodStock->userId = $request->user()->id;
            $prodStock->updated_at = Carbon::now();
            $prodStock->save();

            $prod->updated_at = Carbon::now();
            $prod->save();

            $transaction = "";

            if ($request->different > 0) {
                $transaction = "Stock Adjustment Decrease";
            } else if ($request->different < 0) {
                $transaction = "Stock Adjustment Increase";
            }

            ProductSellLog($request->productId, $transaction, $request->remark, $request->totalAdjustment, $request->totalAdjustment, $request->user()->id);
        } elseif ($request->productType == 'productClinic') {

            $prod = Products::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is no any data found!'],
                ], 422);
            }

            $num = 0;
            $transaction = "";

            if ($request->adjustment == 'increase') {
                $num = $request->totalAdjustment;
                $transaction = 'Stock Adjustment Increase';
            } elseif ($request->adjustment == 'decrease') {
                $num = $request->totalAdjustment * -1;
                $transaction = 'Stock Adjustment Decrease';
            }

            $prodStock = ProductLocations::where('productId', '=', $request->productId)->first();

            if (!$prodStock) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is no any data found!'],
                ], 422);
            }

            ProductAdjustment::create([
                'productId' => $request->productId,
                'productType' => $request->productType,
                'adjustment' => $request->adjustment,
                'totalAdjustment' => $num,
                'remark' => $request->remark,
                'userId' => $request->user()->id,
            ]);

            $inStock = $prodStock->inStock;
            $lowStock = $prodStock->lowStock;

            $prodStock->inStock = $inStock + ($num);
            $prodStock->diffStock = ($inStock + ($num)) - $lowStock;
            $prodStock->userId = $request->user()->id;
            $prodStock->updated_at = Carbon::now();
            $prodStock->save();

            $prod->updated_at = Carbon::now();
            $prod->save();

            ProductClinicLog($request->productId, $transaction, $request->remark, $request->totalAdjustment, $inStock + ($num), $request->user()->id);
        }

        return response()->json([
            'message' => 'Adjustment Data Successful',
        ], 200);
    }

    public function indexLog(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        if ($request->productType == 'productSell') {

            $data = DB::table('products as ps')
                ->join('productLogs as psl', 'psl.productId', 'ps.id')
                ->join('users as u', 'u.id', 'psl.userId')
                ->join('productLocations as pLoc', 'ps.id', 'pLoc.productId')
                ->select(
                    'psl.id',
                    'psl.transaction',
                    'psl.remark',
                    'psl.quantity',
                    'psl.balance',
                    DB::raw("CONCAT(u.firstName,' ',u.middleName,CASE WHEN u.middleName = '' THEN '' ELSE ' ' END,u.lastName) as fullName"),
                    'u.id as userId',
                    DB::raw("DATE_FORMAT(psl.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )->where('ps.id', '=', $request->productId);

            if ($request->dateFrom && $request->dateTo) {

                $data = $data->whereBetween(DB::raw('DATE(psl.created_at)'), [$request->dateFrom, $request->dateTo]);
            }

            if ($request->staffId) {

                $data = $data->whereIn('u.id', $request->staffId);
            }

            if ($request->transaction) {
                $data = $data->where('psl.transaction', 'like', '%' . $request->transaction . '%');
            }

            if ($request->orderValue) {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }

            $data = $data->orderBy('psl.id', 'desc');

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
        } elseif ($request->productType == 'productClinic') {

            $data = DB::table('products as pc')
                ->join('productLogs as pcl', 'pcl.productId', 'pc.id')
                ->join('users as u', 'u.id', 'pcl.userId')
                ->join('productLocations as pLoc', 'pc.id', 'pLoc.productId')
                ->select(
                    'pcl.id',
                    'pcl.transaction',
                    'pcl.remark',
                    'pcl.quantity',
                    'pcl.balance',
                    DB::raw("CONCAT(u.firstName,' ',u.middleName,CASE WHEN u.middleName = '' THEN '' ELSE ' ' END,u.lastName) as fullName"),
                    'u.id as userId',
                    DB::raw("DATE_FORMAT(pcl.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )->where('pc.id', '=', $request->productId);

            if ($request->dateFrom && $request->dateTo) {

                $data = $data->whereBetween(DB::raw('DATE(pcl.created_at)'), [$request->dateFrom, $request->dateTo]);
            }

            if ($request->staffId) {

                $data = $data->whereIn('u.id', $request->staffId);
            }

            if ($request->transaction) {
                $data = $data->where('pcl.transaction', 'like', '%' . $request->transaction . '%');
            }

            if ($request->orderValue) {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }

            $data = $data->orderBy('pcl.id', 'desc');

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
    }

    public function transaction()
    {
        $data = ['Adjustment Increase', 'Adjustment Decrease', 'Transfer Item', 'Create New', 'Split Product', 'Restock Product'];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function indexDataStatic(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $dataMessenger = DB::table('productSupplierTypeMessengers as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.typeName',
                DB::raw("'messenger' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $dataTypePhone = DB::table('productSupplierTypePhones as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.typeName',
                DB::raw("'phone' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $dataTypeUsages = DB::table('productSupplierUsages as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.usageName',
                DB::raw("'usage' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $dataTypePhone = $dataTypePhone
            ->union($dataMessenger)
            ->union($dataTypeUsages);

        $data = DB::query()->fromSub($dataTypePhone, 'p_pn')
            ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

        $dataTemp = DB::query()->fromSub($dataTypePhone, 'p_pn')
            ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

        $temp_column = null;

        if ($request->search) {

            $data1 = $dataTemp->where('typeName', 'like', '%' . $request->search . '%')->get();

            if (count($data1)) {
                $temp_column[] = 'typeName';
            }

            $dataTemp = DB::query()->fromSub($dataTypePhone, 'p_pn')
                ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

            $data2 = $dataTemp->where('type', 'like', '%' . $request->search . '%')->get();

            if (count($data2)) {
                $temp_column[] = 'type';
            }

            $dataTemp = DB::query()->fromSub($dataTypePhone, 'p_pn')
                ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

            $data3 = $dataTemp->where('createdBy', 'like', '%' . $request->search . '%')->get();

            if (count($data3)) {
                $temp_column[] = 'createdBy';
            }

            $res = $temp_column;

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

        $data = $data->orderBy('createdAt', 'desc');

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

    public function deleteDataStatic(Request $request)
    {

        $validate = Validator::make(
            $request->datas,
            [
                '*.id' => 'required|integer',
                '*.type' => 'required|string|in:phone,messenger,usage',
            ],
            [
                '*.id.required' => 'Id Should be Required!',
                '*.id.integer' => 'Id Should be Integer!',

                '*.type.required' => 'Type Should be Required!',
                '*.type.string' => 'Type Should be String!',
                '*.type.in' => 'Type Should be Phone, Messenger, or Usage!',
            ]
        );

        if ($validate->fails()) {
            $errors = $validate->errors()->first();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        foreach ($request->datas as $value) {
            if ($value['type'] == 'phone') {

                productSupplierTypePhone::where('id', '=', $value['id'])
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } elseif ($value['type'] == 'messenger') {
                productSupplierTypeMessenger::where('id', '=', $value['id'])
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } elseif ($value['type'] == 'usage') {
                productSupplierUsage::where('id', '=', $value['id'])
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }
        }

        return responseDelete();
    }

    public function ListProductSellWithLocation(Request $request)
    {
        $arr = $request->locationId;
        //json_decode(, true);

        if (count($arr) == 0) {

            $data = DB::table('products as ps')
                ->join('productLocations as psl', 'ps.id', 'psl.productId')
                ->select('ps.id', 'ps.fullName')
                ->where('ps.isDeleted', '=', 0)
                ->where('ps.category', '=', 'sell')
                ->distinct()
                ->get();
        } else {
            $data = DB::table('products as ps')
                ->join('productLocations as psl', 'ps.id', 'psl.productId')
                ->select('ps.id', 'ps.fullName')
                ->wherein('psl.locationId', $arr)
                ->where('ps.isDeleted', '=', 0)
                ->where('ps.category', '=', 'sell')
                ->distinct()
                ->get();
        }

        return responseList($data);
    }

    public function ListProductClinicWithLocation(Request $request)
    {
        $arr = $request->locationId;

        if (count($arr) == 0) {

            $data = DB::table('products as pc')
                ->join('productLocations as pcl', 'pc.id', 'pcl.productId')
                ->select('pc.id', 'pc.fullName')
                ->where('pc.isDeleted', '=', 0)
                ->where('pc.category', '=', 'clinic')
                ->distinct()
                ->get();
        } else {
            $data = DB::table('products as pc')
                ->join('productLocations as pcl', 'pc.id', 'pcl.productId')
                ->select('pc.id', 'pc.fullName')
                ->wherein('pcl.locationId', $arr)
                ->where('pc.isDeleted', '=', 0)
                ->where('pc.category', '=', 'clinic')
                ->distinct()
                ->get();
        }

        return responseList($data);
    }
}
