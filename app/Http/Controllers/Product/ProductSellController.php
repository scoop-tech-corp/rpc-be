<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\ProductSellReport;
use App\Models\ProductSell;
use App\Models\ProductSellCategory;
use App\Models\ProductSellCustomerGroup;
use App\Models\ProductSellImages;
use App\Models\ProductSellLocation;
use App\Models\ProductSellPriceLocation;
use App\Models\ProductSellQuantity;
use App\Models\ProductSellReminder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Excel;
use Validator;

class ProductSellController
{
    public function Index(Request $request)
    {

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productSells as ps')
            ->join('productSellLocations as psl', 'psl.productSellId', 'ps.id')
            ->join('location as loc', 'loc.Id', 'psl.locationId')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.id')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.Id')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id as id',
                'ps.fullName as fullName',
                DB::raw("IFNULL(ps.sku,'') as sku"),
                'loc.id as locationId',
                'loc.locationName as locationName',
                DB::raw("IFNULL(psup.supplierName,'') as supplierName"),
                DB::raw("IFNULL(pb.brandName,'') as brandName"),
                DB::raw("TRIM(ps.price)+0 as price"),
                'ps.pricingStatus',
                DB::raw("TRIM(psl.inStock)+0 as stock"),
                'ps.status',
                'ps.isShipped',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('loc.id', $request->locationId);
        }

        if ($request->search) {
            $res = $this->Search($request);
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

        $data = $data->orderBy('ps.id', 'desc');

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

    private function Search($request)
    {
        $temp_column = null;

        $data = DB::table('productSells as ps')
            ->select(
                'ps.fullName as fullName'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('ps.fullName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ps.fullName';
        }
        //------------------------

        $data = DB::table('productSells as ps')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.id')
            ->select(
                DB::raw("IFNULL(psup.supplierName,'') as supplierName")
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('psup.supplierName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'psup.supplierName';
        }
        //------------------------

        $data = DB::table('productSells as ps')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.Id')
            ->select(
                DB::raw("IFNULL(pb.brandName,'') as brandName")
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pb.brandName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pb.brandName';
        }

        return $temp_column;
    }

    public function Detail(Request $request)
    {
        $prodSell = DB::table('productSells as ps')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.Id')
            ->select(
                'ps.id',
                'ps.fullName',
                DB::raw("IFNULL(ps.simpleName,'') as simpleName"),

                'ps.pricingStatus',
                DB::raw("TRIM(ps.costPrice)+0 as costPrice"),
                DB::raw("TRIM(ps.marketPrice)+0 as marketPrice"),
                DB::raw("TRIM(ps.price)+0 as price"),
                'ps.isShipped',
                DB::raw("TRIM(ps.weight)+0 as weight"),
                DB::raw("TRIM(ps.length)+0 as length"),
                DB::raw("TRIM(ps.width)+0 as width"),
                DB::raw("TRIM(ps.height)+0 as height"),
                DB::raw("TRIM(ps.weight)+0 as weight"),
                DB::raw("IFNULL(ps.introduction,'') as introduction"),
                DB::raw("IFNULL(ps.description,'') as description"),
            )
            ->where('ps.id', '=', $request->id)
            ->first();

        $prodSellDetails = DB::table('productSells as ps')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.id')
            ->select(
                'ps.status',
                'ps.productSupplierId',
                'psup.supplierName as supplierName',
                DB::raw("IFNULL(ps.sku,'') as sku"),
                'ps.productBrandId',
                'pb.brandName as brandName',
            )
            ->where('ps.id', '=', $request->id)
            ->first();

        $categories = DB::table('productCategories as pcat')
            ->join('productSellCategories as psc', 'psc.productCategoryId', 'pcat.id')
            ->join('productSells as pc', 'psc.productSellId', 'pc.id')
            ->select('pcat.id', 'pcat.categoryName')
            ->where('pc.id', '=', $request->id)
            ->where('psc.isDeleted', '=', 0)
            ->get();

        $prodSellDetails->categories = $categories;

        $prodSell->details = $prodSellDetails;

        $prodSellSetting = DB::table('productSells as ps')
            ->select(
                'ps.isCustomerPurchase as isCustomerPurchase',
                'ps.isCustomerPurchaseOnline as isCustomerPurchaseOnline',
                'ps.isCustomerPurchaseOutStock as isCustomerPurchaseOutStock',
                'ps.isStockLevelCheck as isStockLevelCheck',
                'ps.isNonChargeable as isNonChargeable',
                'ps.isOfficeApproval as isOfficeApproval',
                'ps.isAdminApproval as isAdminApproval',
            )
            ->where('ps.id', '=', $request->id)
            ->first();

        $prodSell->setting = $prodSellSetting;

        $location =  DB::table('productSellLocations as psl')
            ->join('location as l', 'l.Id', 'psl.locationId')
            ->select(
                'psl.id',
                'l.id as locationId',
                'l.locationName',
                'psl.inStock',
                'psl.lowStock',
                'psl.reStockLimit',
                DB::raw('(CASE WHEN psl.inStock = 0 THEN "NO STOCK" WHEN psl.inStock <= psl.lowStock THEN "LOW STOCK" ELSE "CLEAR" END) AS status')
            )
            ->where('psl.productSellId', '=', $request->id)
            ->first();

        $prodSell->location = $location;

        if ($prodSell->pricingStatus == "CustomerGroups") {

            $CustomerGroups = DB::table('productSellCustomerGroups as psc')
                ->join('productSells as ps', 'psc.productSellId', 'ps.id')
                ->join('customerGroups as cg', 'psc.customerGroupId', 'cg.id')
                ->select(
                    'psc.id as id',
                    'psc.customerGroupId',
                    'cg.customerGroup',
                    DB::raw("TRIM(psc.price)+0 as price")
                )
                ->where('psc.productSellId', '=', $request->id)
                ->where('psc.isDeleted', '=', 0)
                ->get();

            $prodSell->customerGroups = $CustomerGroups;
        } elseif ($prodSell->pricingStatus == "PriceLocations") {
            $PriceLocations = DB::table('productSellPriceLocations as psp')
                ->join('productSells as ps', 'psp.productSellId', 'ps.id')
                ->join('location as l', 'psp.locationId', 'l.id')
                ->select(
                    'psp.id as id',
                    'l.locationName',
                    DB::raw("TRIM(psp.price)+0 as Price")
                )
                ->where('psp.productSellId', '=', $request->id)
                ->where('psp.isDeleted', '=', 0)
                ->get();

            $prodSell->priceLocations = $PriceLocations;
        } else if ($prodSell->pricingStatus == "Quantities") {

            $Quantities = DB::table('productSellQuantities as psq')
                ->join('productSells as ps', 'psq.productSellId', 'ps.id')
                ->select(
                    'psq.id as id',
                    'psq.fromQty',
                    'psq.toQty',
                    DB::raw("TRIM(psq.Price)+0 as price")
                )
                ->where('psq.ProductSellId', '=', $request->id)
                ->where('psq.isDeleted', '=', 0)
                ->get();

            $prodSell->quantities = $Quantities;
        }

        $prodSell->images = DB::table('productSellImages as psi')
            ->join('productSells as ps', 'psi.productSellId', 'ps.id')
            ->select(
                'psi.id as id',
                'psi.labelName',
                'psi.realImageName',
                'psi.imagePath'
            )
            ->where('psi.productSellId', '=', $request->id)
            ->where('psi.isDeleted', '=', 0)
            ->get();

        $prodSell->reminders = DB::table('productSellReminders as psr')
            ->join('productSells as pc', 'psr.productSellId', 'pc.id')
            ->select(
                'psr.id',
                'psr.unit',
                'psr.timing',
                'psr.status',
            )
            ->where('psr.productSellId', '=', $request->id)
            ->where('psr.isDeleted', '=', 0)
            ->get();

        return response()->json($prodSell, 200);
    }

    public function Create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'fullName' => 'required|string|max:30',
            'simpleName' => 'nullable|string',
            'productBrandId' => 'nullable|integer',
            'productSupplierId' => 'nullable|integer',
            'sku' => 'nullable|string',
            'status' => 'required|bool',
            'expiredDate' => 'nullable|date',
            'pricingStatus' => 'required|string',

            'costPrice' => 'required|numeric',
            'marketPrice' => 'required|numeric',
            'price' => 'required|numeric',
            'isShipped' => 'required|bool',
            'introduction' => 'nullable|string',
            'description' => 'nullable|string',

            'isCustomerPurchase' => 'required|in:true,false,TRUE,FALSE',
            'isCustomerPurchaseOnline' => 'required|in:true,false,TRUE,FALSE',
            'isCustomerPurchaseOutStock' => 'required|in:true,false,TRUE,FALSE',
            'isStockLevelCheck' => 'required|in:true,false,TRUE,FALSE',
            'isNonChargeable' => 'required|in:true,false,TRUE,FALSE',
            'isOfficeApproval' => 'required|in:true,false,TRUE,FALSE',
            'isAdminApproval' => 'required|in:true,false,TRUE,FALSE',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        if ($request->isOfficeApproval == 'false' && $request->isAdminApproval == 'false') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Office Approval or Admin Approval cannot false'],
            ], 422);
        }

        $ResultCategories = null;
        $ResultPriceLocations = null;
        $ResultQuantities = null;
        $ResultCustomerGroups = null;
        $ResultReminders = null;

        if ($request->categories) {
            $ResultCategories = json_decode($request->categories, true);
        }

        $ResultLocations = json_decode($request->locations, true);

        $validateLocation = Validator::make(
            $ResultLocations,
            [
                '*.locationId' => 'required|integer|distinct',
                '*.inStock' => 'required|integer',
                '*.lowStock' => 'required|integer',
                '*.reStockLimit' => 'required|integer',
            ],
            [
                '*.locationId.required' => 'Location Id Should be Required!',
                '*.locationId.integer' => 'Location Id Should be Integer!',
                '*.locationId.distinct' => 'Cannot add duplicate Location!',
                '*.inStock.required' => 'In Stock Should be Required!',
                '*.inStock.integer' => 'In Stock Should be Integer!',
                '*.lowStock.required' => 'Low Stock Should be Required!',
                '*.lowStock.integer' => 'Low Stock Should be Integer!',
                '*.reStockLimit.required' => 'Restock Limit Should be Required!',
                '*.reStockLimit.integer' => 'Restock Limit Should be Integer!',
            ]
        );

        if ($validateLocation->fails()) {
            $errors = $validateLocation->errors()->first();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        foreach ($ResultLocations as $Res) {

            $CheckDataBranch = DB::table('productSells as ps')
                ->join('productSellLocations as psl', 'psl.productSellId', 'ps.id')
                ->join('location as loc', 'psl.locationId', 'loc.id')
                ->select('ps.fullName as fullName', 'loc.locationName')
                ->where('ps.fullName', '=', $request->fullName)
                ->where('psl.locationId', '=', $Res['locationId'])
                ->first();

            if ($CheckDataBranch) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product ' . $CheckDataBranch->fullName . ' Already Exist on Location ' . $CheckDataBranch->locationName . '!'],
                ], 422);
            }

            $checkLocation = DB::table('location as loc')
                ->where('loc.id', '=', $Res['locationId'])
                ->first();

            if (!$checkLocation) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any location on system that is no recorded'],
                ], 422);
            }
        }

        $ResultReminders = json_decode($request->reminders, true);

        if ($ResultReminders) {

            $validateReminders = Validator::make(
                $ResultReminders,
                [
                    '*.unit' => 'required|integer',
                    '*.timing' => 'required|string',
                    '*.status' => 'required|string',
                ],
                [
                    '*.unit.integer' => 'Unit Should be Integer!',
                    '*.unit.required' => 'Unit Should be Required!',
                    '*.timing.string' => 'Timing Should be String!',
                    '*.timing.required' => 'Timing Should be Required!',
                    '*.status.string' => 'Status Should be String!',
                    '*.status.required' => 'Status Should be Required!',
                ]
            );

            if ($validateReminders->fails()) {
                $errors = $validateReminders->errors()->first();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [$errors],
                ], 422);
            }
        }

        $this->ValidationImage($request);

        if ($request->pricingStatus == "CustomerGroups") {

            if ($request->customerGroups) {
                $ResultCustomerGroups = json_decode($request->customerGroups, true);

                $validateCustomer = Validator::make(
                    $ResultCustomerGroups,
                    [

                        '*.customerGroupId' => 'required|integer|distinct',
                        '*.price' => 'required|numeric',
                    ],
                    [
                        '*.customerGroupId.required' => 'Customer Group Id Should be Required!',
                        '*.customerGroupId.integer' => 'Customer Group Id Should be Integer!',
                        '*.customerGroupId.distinct' => 'Cannot add duplicate Customer!',
                        '*.price.numeric' => 'Price Should be Numeric!',
                        '*.price.required' => 'Price Should be Required!',

                    ]
                );

                if ($validateCustomer->fails()) {
                    $errors = $validateCustomer->errors()->first();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Customer Group can not be empty!'],
                ], 422);
            }
        } else if ($request->pricingStatus == "PriceLocations") {

            if ($request->priceLocations) {

                $ResultPriceLocations = json_decode($request->priceLocations, true);

                $validatePriceLocations = Validator::make(
                    $ResultPriceLocations,
                    [
                        '*.locationId' => 'required|integer|distinct',
                        '*.price' => 'required|numeric',
                    ],
                    [
                        '*.locationId.integer' => 'Location Id Should be Integer!',
                        '*.locationId.required' => 'Location Should be Required!',
                        '*.locationId.distinct' => 'Cannot add duplicate Location!',
                        '*.price.required' => 'Price Should be Required!',
                        '*.price.numeric' => 'Price Should be Numeric!',

                    ]
                );

                if ($validatePriceLocations->fails()) {
                    $errors = $validatePriceLocations->errors()->first();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Price Location can not be empty!'],
                ], 422);
            }
        } else if ($request->pricingStatus == "Quantities") {

            if ($request->quantities) {
                $ResultQuantities = json_decode($request->quantities, true);

                $validateQuantity = Validator::make(
                    $ResultQuantities,
                    [
                        '*.fromQty' => 'required|integer',
                        '*.toQty' => 'required|integer',
                        '*.price' => 'required|numeric',
                    ],
                    [
                        '*.fromQty.required' => 'From Quantity Should be Required!',
                        '*.fromQty.integer' => 'From Quantity Should be Integer!',
                        '*.toQty.required' => 'To Quantity Should be Required!',
                        '*.toQty.integer' => 'To Quantity Should be Integer!',
                        '*.price.required' => 'Price Should be Required!',
                        '*.price.numeric' => 'Price Should be Numeric!',
                    ]
                );

                if ($validateQuantity->fails()) {
                    $errors = $validateQuantity->errors()->first();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Quantity can not be empty!'],
                ], 422);
            }
        }

        //INSERT DATA
        $flag = false;
        $res_data = [];
        $files[] = $request->file('images');

        DB::beginTransaction();
        try {
            foreach ($ResultLocations as $value) {

                $weight = 0;
                if (!is_null($request->weight)) {
                    $weight = $request->weight;
                }

                $length = 0;
                if (!is_null($request->length)) {
                    $length = $request->length;
                }

                $width = 0;
                if (!is_null($request->width)) {
                    $width = $request->width;
                }

                $height = 0;
                if (!is_null($request->height)) {
                    $height = $request->height;
                }

                $product = ProductSell::create([
                    'fullName' => $request->fullName,
                    'simpleName' => $request->simpleName,
                    'sku' => $request->sku,
                    'productBrandId' => $request->productBrandId,
                    'productSupplierId' => $request->productSupplierId,
                    'status' => $request->status,
                    'expiredDate' => $request->expiredDate,
                    'pricingStatus' => $request->pricingStatus,
                    'costPrice' => $request->costPrice,
                    'marketPrice' => $request->marketPrice,
                    'price' => $request->price,
                    'isShipped' => $request->isShipped,
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'introduction' => $request->introduction,
                    'description' => $request->description,

                    'isCustomerPurchase' => convertTrueFalse($request->isCustomerPurchase),
                    'isCustomerPurchaseOnline' => convertTrueFalse($request->isCustomerPurchaseOnline),
                    'isCustomerPurchaseOutStock' => convertTrueFalse($request->isCustomerPurchaseOutStock),
                    'isStockLevelCheck' => convertTrueFalse($request->isStockLevelCheck),
                    'isNonChargeable' => convertTrueFalse($request->isNonChargeable),
                    'isOfficeApproval' => convertTrueFalse($request->isOfficeApproval),
                    'isAdminApproval' => convertTrueFalse($request->isAdminApproval),

                    'userId' => $request->user()->id,
                ]);

                ProductSellLocation::create([
                    'productSellId' => $product->id,
                    'locationId' => $value['locationId'],
                    'inStock' => $value['inStock'],
                    'lowStock' => $value['lowStock'],
                    'reStockLimit' => $value['reStockLimit'],
                    'diffStock' => $value['inStock'] - $value['lowStock'],
                    'userId' => $request->user()->id,
                ]);

                if ($ResultCategories) {

                    foreach ($ResultCategories as $valCat) {
                        ProductSellCategory::create([
                            'productSellId' => $product->id,
                            'productCategoryId' => $valCat,
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                $count = 0;

                $ResImageDatas = json_decode($request->imagesName, true);

                if ($flag == false) {

                    if ($request->hasfile('images')) {

                        foreach ($files as $file) {

                            foreach ($file as $fil) {

                                $name = $fil->hashName();

                                $fil->move(public_path() . '/ProductSellImages/', $name);

                                $fileName = "/ProductSellImages/" . $name;

                                $file = new ProductSellImages();
                                $file->productSellId = $product->id;
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
                        ProductSellImages::create([
                            'productSellId' => $product->id,
                            'labelName' => $res['labelName'],
                            'realImageName' => $res['realImageName'],
                            'imagePath' => $res['imagePath'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                foreach ($ResultReminders as $RemVal) {
                    ProductSellReminder::create([
                        'productSellId' => $product->id,
                        'unit' => $RemVal['unit'],
                        'timing' => $RemVal['timing'],
                        'status' => $RemVal['status'],
                        'userId' => $request->user()->id,
                    ]);
                }

                if ($request->pricingStatus == "CustomerGroups") {

                    foreach ($ResultCustomerGroups as $CustVal) {
                        ProductSellCustomerGroup::create([
                            'productSellId' => $product->id,
                            'customerGroupId' => $CustVal['customerGroupId'],
                            'price' => $CustVal['price'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                } else if ($request->pricingStatus == "PriceLocations") {

                    foreach ($ResultPriceLocations as $PriceVal) {
                        ProductSellPriceLocation::create([
                            'productSellId' => $product->id,
                            'locationId' => $PriceVal['locationId'],
                            'price' => $PriceVal['price'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                } else if ($request->pricingStatus == "Quantities") {

                    foreach ($ResultQuantities as $QtyVal) {
                        ProductSellQuantity::create([
                            'productSellId' => $product->id,
                            'fromQty' => $QtyVal['fromQty'],
                            'toQty' => $QtyVal['toQty'],
                            'price' => $QtyVal['price'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }
            }
            DB::commit();

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } catch (Exception $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage(),
                'errors' => ['Insert Failed!'],
            ], 422);
        }
    }

    private function ValidationImage($request)
    {
        try {


            $flag = false;

            if ($request->file('images')) {

                $flag = true;

                $data_item = [];

                $files[] = $request->file('images');

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $file_size = $fil->getSize();

                        $file_size = $file_size / 1024;

                        $oldname = $fil->getClientOriginalName();

                        if ($file_size >= 5000) {

                            array_push($data_item, 'Foto ' . $oldname . ' lebih dari 5mb! Harap upload gambar dengan ukuran lebih kecil!');
                        }
                    }
                }

                if ($data_item) {

                    return response()->json([
                        'message' => 'Foto yang dimasukkan tidak valid!',
                        'errors' => [$data_item],
                    ], 422);
                }
            }

            if ($flag == true) {
                if ($request->imagesName) {
                    $ResultImageDatas = json_decode($request->imagesName, true);

                    if (count($ResultImageDatas) != count($request->file('images'))) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Label Image and total image should same!'],
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Image label cannot be empty!'],
                    ], 422);
                }
            }
        } catch (Exception $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage(),
                'errors' => ['Insert Failed!'],
            ], 422);
        }
    }

    public function Update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'simpleName' => 'nullable|string',
            'fullName' => 'nullable|string|max:30',
            'productBrandId' => 'nullable|integer',
            'productSupplierId' => 'nullable|integer',
            'sku' => 'nullable|string',
            'status' => 'required|bool',
            'pricingStatus' => 'required|string',

            'costPrice' => 'required|numeric',
            'marketPrice' => 'required|numeric',
            'price' => 'required|numeric',
            'isShipped' => 'required|bool',
            'weight' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'introduction' => 'nullable|string',
            'description' => 'nullable|string',

            'isCustomerPurchase' => 'required|bool',
            'isCustomerPurchaseOnline' => 'required|bool',
            'isCustomerPurchaseOutStock' => 'required|bool',
            'isStockLevelCheck' => 'required|bool',
            'isNonChargeable' => 'required|bool',
            'isOfficeApproval' => 'required|bool',
            'isAdminApproval' => 'required|bool'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $prodSell = ProductSell::find($request->id);

        if (!$prodSell) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data not found!'],
            ], 422);
        }

        $ResultCategories = null;
        $ResultPriceLocations = null;
        $ResultQuantities = null;
        $ResultCustomerGroups = null;
        $ResultReminders = null;

        if ($request->categories) {
            $ResultCategories = $request->categories;
        }

        $ResultReminders = $request->reminders;

        $ResultQuantities = $request->quantities;

        $validateLocation = Validator::make(
            $request->locations,
            [
                'id' => 'required|integer',
                'locationId' => 'required|integer',
                'inStock' => 'required|integer',
                'lowStock' => 'required|integer',
                'reStockLimit' => 'required|integer',
            ],
            [
                'id.integer' => 'Id Should be Integer!',
                'locationId.integer' => 'Location Id Should be Integer!',
                'inStock.integer' => 'In Stock Should be Integer',
                'lowStock.integer' => 'Low Stock Should be Integer',
                'reStockLimit.integer' => 'Re Stock Limit Should be Integer',
            ]
        );

        if ($validateLocation->fails()) {
            $errors = $validateLocation->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        if ($request->pricingStatus == "CustomerGroups") {

            if ($request->customerGroups) {
                $ResultCustomerGroups = $request->customerGroups;

                $validateCustomer = Validator::make(
                    $request->customerGroups,
                    [
                        '*.id' => 'nullable|integer',
                        '*.customerGroupId' => 'required|integer|distinct',
                        '*.price' => 'required|numeric',
                        '*.status' => 'nullable|string',
                    ],
                    [
                        '*.id.integer' => 'Id Should be Integer!',
                        '*.customerGroupId.required' => 'Customer Group is Required!',
                        '*.customerGroupId.integer' => 'Customer Group Id Should be Integer!',
                        '*.customerGroupId.distinct' => 'Cannot add duplicate Customer Group!',
                        '*.price.required' => 'Price is Required!',
                        '*.price.numeric' => 'Price Should be Numeric!',
                        '*.status.string' => 'Status Should be String!'
                    ]
                );

                if ($validateCustomer->fails()) {
                    $errors = $validateCustomer->errors()->first();
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Customer Group can not be empty!'],
                ], 422);
            }
        } else if ($request->pricingStatus == "PriceLocations") {

            if ($request->priceLocations) {
                $ResultPriceLocations = $request->priceLocations;

                $validatePriceLocations = Validator::make(
                    $ResultPriceLocations,
                    [
                        '*.id' => 'nullable|integer',
                        '*.locationId' => 'required|integer|distinct',
                        '*.price' => 'required|numeric',
                        '*.status' => 'nullable|string',
                    ],
                    [
                        '*.id.integer' => 'Id Should be Integer!',
                        '*.locationId.required' => 'Location is Required!',
                        '*.locationId.integer' => 'Location Id Should be Integer!',
                        '*.locationId.distinct' => 'Cannot add duplicate Location!',
                        '*.price.required' => 'Price is Required!',
                        '*.price.numeric' => 'Price Should be Numeric!',
                        '*.status.string' => 'Status Should be String!'
                    ]
                );

                if ($validatePriceLocations->fails()) {
                    $errors = $validatePriceLocations->errors()->first();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Price Location can not be empty!'],
                ], 422);
            }
        } else if ($request->pricingStatus == "Quantities") {

            if ($request->quantities) {

                $validateQuantity = Validator::make(
                    $request->quantities,
                    [
                        '*.id' => 'nullable|integer',
                        '*.fromQty' => 'required|integer',
                        '*.toQty' => 'required|integer',
                        '*.price' => 'required|numeric',
                        '*.status' => 'nullable|string',
                    ],
                    [
                        '*.id.integer' => 'Id Should be Integer!',
                        '*.fromQty.required' => 'From Quantity is Required!',
                        '*.fromQty.integer' => 'From Quantity Should be Integer!',
                        '*.toQty.required' => 'To Quantity is Required!',
                        '*.toQty.integer' => 'To Quantity Should be Integer!',
                        '*.price.required' => 'Price is Required!',
                        '*.price.numeric' => 'Price Should be Numeric!',
                        '*.status.string' => 'Status Should be String!',
                    ]
                );

                if ($validateQuantity->fails()) {
                    $errors = $validateQuantity->errors()->first();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [$errors],
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Quantity can not be empty!'],
                ], 422);
            }
        }

        //UPDATE DATA

        $location = $request->locations;

        ProductSellLocation::updateOrCreate(
            ['id' => $location['id']],
            [
                'productSellId' => $request->id,
                'locationId' => $location['locationId'],
                'inStock' => $location['inStock'],
                'lowStock' => $location['lowStock'],
                'reStockLimit' => $location['reStockLimit'],
                'diffStock' => $location['inStock'] - $location['lowStock'],
                'userId' => $request->user()->id,
            ]
        );

        // try {

        $weight = 0;
        if (!is_null($request->weight)) {
            $weight = $request->weight;
        }

        $length = 0;
        if (!is_null($request->length)) {
            $length = $request->length;
        }

        $width = 0;
        if (!is_null($request->width)) {
            $width = $request->width;
        }

        $height = 0;
        if (!is_null($request->height)) {
            $height = $request->height;
        }

        $product = ProductSell::updateOrCreate(
            ['id' => $request->id],
            [
                'simpleName' => $request->simpleName,
                'fullName' => $request->fullName,
                'sku' => $request->sku,
                'productBrandId' => $request->productBrandId,
                'productSupplierId' => $request->productSupplierId,
                'status' => $request->status,
                'pricingStatus' => $request->pricingStatus,
                'costPrice' => $request->costPrice,
                'marketPrice' => $request->marketPrice,
                'price' => $request->price,
                'isShipped' => $request->isShipped,
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'introduction' => $request->introduction,
                'description' => $request->description,

                'isCustomerPurchase' => $request->isCustomerPurchase,
                'isCustomerPurchaseOnline' => $request->isCustomerPurchaseOnline,
                'isCustomerPurchaseOutStock' => $request->isCustomerPurchaseOutStock,
                'isStockLevelCheck' => $request->isStockLevelCheck,
                'isNonChargeable' => $request->isNonChargeable,
                'isOfficeApproval' => $request->isOfficeApproval,
                'isAdminApproval' => $request->isAdminApproval,

                'userId' => $request->user()->id,
            ]
        );

        if ($ResultCategories) {

            ProductSellCategory::where('ProductSellId', '=', $request->id)
                ->where('isDeleted', '=', 0)
                ->update(
                    [
                        'deletedBy' => $request->user()->id,
                        'isDeleted' => 1,
                        'deletedAt' => Carbon::now()
                    ]
                );

            foreach ($ResultCategories as $valCat) {
                ProductSellCategory::create(
                    [
                        'productSellId' => $request->id,
                        'productCategoryId' => $valCat['id'],
                        'userId' => $request->user()->id,
                    ]
                );
            }
        }

        foreach ($ResultReminders as $RemVal) {

            if ($RemVal['statusData'] == 'del') {

                ProductSellReminder::where('id', '=', $RemVal['id'])
                    ->where('isDeleted', '=', 0)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } else {

                ProductSellReminder::updateOrCreate(
                    ['id' => $RemVal['id']],
                    [
                        'productSellId' => $product->id,
                        'unit' => $RemVal['unit'],
                        'timing' => $RemVal['timing'],
                        'status' => $RemVal['status'],
                        'userId' => $request->user()->id,
                    ]
                );
            }
        }

        if ($request->pricingStatus == "CustomerGroups") {

            foreach ($ResultCustomerGroups as $CustVal) {

                if ($CustVal['status'] == 'del') {

                    ProductSellCustomerGroup::where('id', '=', $CustVal['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {

                    ProductSellCustomerGroup::updateOrCreate([
                        'productSellId' => $product->id,
                        'customerGroupId' => $CustVal['customerGroupId'],
                        'price' => $CustVal['price'],
                        'userId' => $request->user()->id,
                    ]);
                }
            }
        } else if ($request->pricingStatus == "PriceLocations") {

            foreach ($ResultPriceLocations as $PriceVal) {

                if ($PriceVal['status'] == 'del') {

                    ProductSellPriceLocation::where('id', '=', $PriceVal['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {

                    ProductSellPriceLocation::updateOrCreate([
                        'productSellId' => $product->id,
                        'locationId' => $PriceVal['locationId'],
                        'price' => $PriceVal['price'],
                        'userId' => $request->user()->id,
                    ]);
                }
            }
        } else if ($request->pricingStatus == "Quantities") {

            foreach ($ResultQuantities as $QtyVal) {

                if ($QtyVal['status'] == 'del') {
                    ProductSellQuantity::where('id', '=', $QtyVal['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {
                    ProductSellQuantity::updateOrCreate(
                        ['id' => $QtyVal['id']],
                        [
                            'productSellId' => $product->id,
                            'fromQty' => $QtyVal['fromQty'],
                            'toQty' => $QtyVal['toQty'],
                            'price' => $QtyVal['price'],
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }
        // }
        DB::commit();

        return response()->json(
            [
                'message' => 'Update Data Successful!',
            ],
            200
        );
        // } catch (Exception $th) {
        //     DB::rollback();

        //     return response()->json([
        //         'message' => 'Insert Failed',
        //         'errors' => $th,
        //     ], 422);
        // }
    }

    public function updateImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Produk tidak valid!',
                'errors' => [$errors],
            ], 422);
        }

        $product = ProductSell::find($request->id);

        if (!$product) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data not found!'],
            ], 422);
        }

        $count = 0;

        $files[] = $request->file('images');
        $tmpImages = [];

        if ($request->hasfile('images')) {
            foreach ($files as $file) {

                foreach ($file as $fil) {

                    $name = $fil->hashName();

                    $fil->move(public_path() . '/ProductSellImages/', $name);

                    $fileName = "/ProductSellImages/" . $name;

                    $file = new ProductSellImages();
                    $file->productSellId = 1;
                    $file->labelName = "";
                    $file->realImageName = $fil->getClientOriginalName();
                    $file->imagePath = $fileName;
                    $file->userId = $request->user()->id;

                    array_push($tmpImages, $file);
                }
            }
        }

        $imagesName = json_decode($request->imagesName, true);

        foreach ($imagesName as $value) {

            if ($value['status'] == 'new') {

                ProductSellImages::create([
                    'productSellId' => $request->id,
                    'labelName' => $value['name'],
                    'realImageName' => $tmpImages[$count]['realImageName'],
                    'imagePath' => $tmpImages[$count]['imagePath'],
                    'userId' => $request->user()->id,
                ]);

                $count += 1;
            } else {

                if ($value['status'] == 'change image') {
                    ProductSellImages::updateorCreate(
                        ['id' => $value['id']],
                        [
                            'productSellId' => $request->id,
                            'labelName' => $value['name'],
                            'realImageName' => $tmpImages[$count]['realImageName'],
                            'imagePath' => $tmpImages[$count]['imagePath'],
                            'userId' => $request->user()->id,
                        ]
                    );
                    $count += 1;
                } else {
                    ProductSellImages::updateorCreate(
                        ['id' => $value['id']],
                        [
                            'productSellId' => $request->id,
                            'labelName' => $value['name'],
                            'realImageName' => $tmpImages[$count]['realImageName'],
                            'imagePath' => $tmpImages[$count]['imagePath'],
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }

        return response()->json(
            [
                'message' => 'Update Data Successful!',
            ],
            200
        );
    }

    public function Delete(Request $request)
    {
        //check product on DB
        foreach ($request->id as $va) {
            $res = ProductSell::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        //process delete data
        foreach ($request->id as $va) {

            $ProdSell = ProductSell::find($va);

            $ProdSellLoc = ProductSellLocation::where('ProductSellId', '=', $ProdSell->id)->get();

            if ($ProdSellLoc) {

                ProductSellLocation::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdSellCat = ProductSellCategory::where('ProductSellId', '=', $ProdSell->id)->get();

            if ($ProdSellCat) {

                ProductSellCategory::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );

                $ProdSellCat->DeletedBy = $request->user()->id;
                $ProdSellCat->isDeleted = true;
                $ProdSellCat->DeletedAt = Carbon::now();
            }

            $ProdSellImg = ProductSellImages::where('ProductSellId', '=', $ProdSell->id)->get();

            if ($ProdSellImg) {

                ProductSellImages::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdCustGrp = ProductSellCustomerGroup::where('ProductSellId', '=', $ProdSell->id)->get();
            if ($ProdCustGrp) {

                ProductSellCustomerGroup::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdSellPrcLoc = ProductSellPriceLocation::where('ProductSellId', '=', $ProdSell->id)->get();

            if ($ProdSellPrcLoc) {

                ProductSellPriceLocation::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdSellQty = ProductSellQuantity::where('ProductSellId', '=', $ProdSell->id)->get();

            if ($ProdSellQty) {

                ProductSellQuantity::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdSellRem = ProductSellReminder::where('ProductSellId', '=', $ProdSell->id)->get();
            if ($ProdSellRem) {

                ProductSellReminder::where('ProductSellId', '=', $ProdSell->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdSell->DeletedBy = $request->user()->id;
            $ProdSell->isDeleted = true;
            $ProdSell->DeletedAt = Carbon::now();
            $ProdSell->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }

    public function Export(Request $request)
    {
        $tmp = "";
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

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
            $fileName = "Rekap Produk Jual " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Produk Jual " . $tmp . " " . $date . ".xlsx";
        }

        return Excel::download(
            new ProductSellReport(
                $request->orderValue,
                $request->orderColumn,
                $request->search,
                $request->locationId,
                $request->isExportAll,
                $request->isExportLimit,
                $request->user()->role
            ),
            $fileName
        );
    }

    public function Split(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'fullName' => 'required|string',
            'qtyReduction' => 'required|integer',
            'qtyIncrease' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        $product = ProductSell::find($request->id);

        if (!$product) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data not found!'],
            ], 422);
        }

        $currentBranch = DB::table('productSells as ps')
            ->join('productSellLocations as psl', 'ps.id', 'psl.productSellId')
            ->select('psl.locationId')
            ->where('ps.id', '=', $request->id)
            ->first();

        $findDuplicate = DB::table('productSells as ps')
            ->join('productSellLocations as psl', 'ps.id', 'psl.productSellId')
            ->select('psl.locationId')
            ->where('ps.fullName', '=', $request->fullName)
            ->where('psl.locationId', '=', $currentBranch->locationId)
            ->where('ps.isDeleted', '=', 0)
            ->get();

        if ($findDuplicate) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Name ' . $request->fullName . ' in this branch has already exist!'],
            ], 422);
        }

        $newProduct = $product->replicate();
        $newProduct->fullName = $request->fullName;
        $newProduct->created_at = Carbon::now();
        $newProduct->updated_at = Carbon::now();
        $newProduct->userId = $request->user()->id;
        $newProduct->save();

        $categories = ProductSellCategory::where('productSellId', '=', $request->id)->get();

        foreach ($categories as $res) {

            $category = ProductSellCategory::find($res['id']);

            if ($category) {
                $newCategory = $category->replicate();
                $newCategory->productSellId = $newProduct->id;
                $newCategory->created_at = Carbon::now();
                $newCategory->updated_at = Carbon::now();
                $newCategory->userId = $request->user()->id;
                $newCategory->save();
            }
        }

        $prodSellLoc = ProductSellLocation::find($request->id);

        $newProdSellLoc = $prodSellLoc->replicate();
        $newProdSellLoc->productSellId = $newProduct->id;
        $newProdSellLoc->inStock = $request->qtyIncrease;
        $newProdSellLoc->diffStock = $request->qtyIncrease - $prodSellLoc->lowStock;
        $newProdSellLoc->userId = $request->user()->id;
        $newProdSellLoc->created_at = Carbon::now();
        $newProdSellLoc->updated_at = Carbon::now();
        $newProdSellLoc->save();

        if ($product->pricingStatus == "CustomerGroups") {

            $productCustomerGroups = ProductSellCustomerGroup::where('productSellId', '=', $request->id)->get();

            foreach ($productCustomerGroups as $res) {

                $prod = ProductSellCustomerGroup::find($res['id']);

                if ($prod) {
                    $newProductSell = $prod->replicate();
                    $newProductSell->productSellId = $newProduct->id;
                    $newProductSell->created_at = Carbon::now();
                    $newProductSell->updated_at = Carbon::now();
                    $newProductSell->userId = $request->user()->id;
                    $newProductSell->save();
                }
            }
        }

        if ($product->pricingStatus == "PriceLocations") {

            $prodSellPriceLoc = ProductSellPriceLocation::where('productSellId', '=', $request->id)->get();

            foreach ($prodSellPriceLoc as $res) {

                $prodSellLoc = ProductSellPriceLocation::find($res['id']);

                if ($prodSellLoc) {
                    $newProductSellLoc = $prodSellLoc->replicate();
                    $newProductSellLoc->productSellId = $newProduct->id;
                    $newProductSellLoc->created_at = Carbon::now();
                    $newProductSellLoc->updated_at = Carbon::now();
                    $newProductSellLoc->userId = $request->user()->id;
                    $newProductSellLoc->save();
                }
            }
        }

        if ($product->pricingStatus == "Quantities") {

            $prodQty = ProductSellQuantity::where('productSellId', '=', $request->id)->get();

            foreach ($prodQty as $res) {

                $prodSellQty = ProductSellQuantity::find($res['id']);

                if ($prodSellQty) {
                    $newProductSellQty = $prodSellQty->replicate();
                    $newProductSellQty->productSellId = $newProduct->id;
                    $newProductSellQty->created_at = Carbon::now();
                    $newProductSellQty->updated_at = Carbon::now();
                    $newProductSellQty->userId = $request->user()->id;
                    $newProductSellQty->save();
                }
            }
        }

        $prodReminder = ProductSellReminder::where('productSellId', '=', $request->id)->get();

        foreach ($prodReminder as $res) {

            $prodSellReminder = ProductSellReminder::find($res['id']);

            if ($prodSellReminder) {
                $newProductSellReminder = $prodSellReminder->replicate();
                $newProductSellReminder->productSellId = $newProduct->id;
                $newProductSellReminder->created_at = Carbon::now();
                $newProductSellReminder->updated_at = Carbon::now();
                $newProductSellReminder->userId = $request->user()->id;
                $newProductSellReminder->save();
            }
        }

        $oldProdLoc = ProductSellLocation::where('productSellId', '=', $request->id)->first();

        $instock = $oldProdLoc->inStock;
        $lowstock = $oldProdLoc->lowStock;

        $oldProdLoc->inStock = $instock - $request->qtyReduction;
        $oldProdLoc->diffStock = ($instock - $request->qtyReduction) - $lowstock;
        $oldProdLoc->updated_at = Carbon::now();
        $oldProdLoc->save();

        return response()->json([
            'message' => 'Split Data Successful',
        ], 200);
    }
}
