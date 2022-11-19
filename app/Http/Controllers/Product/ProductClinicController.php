<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductClinic;
use App\Models\ProductClinicCategory;
use App\Models\ProductClinicCustomerGroup;
use App\Models\ProductClinicImages;
use App\Models\ProductClinicLocation;
use App\Models\ProductClinicPriceLocation;
use App\Models\ProductClinicQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Validator;

class ProductClinicController
{
    public function index(Request $request)
    { 
        $itemPerPage = $request->itemPerPage;

        $page = $request->page;

        $data = DB::table('productClinics as ps')
            ->join('productClinicLocations as psl', 'psl.productClinicId', 'ps.id')
            ->join('location as loc', 'loc.Id', 'psl.locationId')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.id')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.Id')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id as id',
                'ps.fullName as fullName',
                'loc.locationName as locationName',
                DB::raw("IFNULL(psup.supplierName,'') as supplierName"),
                DB::raw("IFNULL(pb.brandName,'') as brandName"),
                DB::raw("TRIM(ps.price)+0 as price"),
                'ps.pricingStatus',
                DB::raw("TRIM(psl.inStock)+0 as stock"),
                'ps.status',
                'ps.isShipped',
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);


        if ($request->orderby) {
            $data = $data->orderBy($request->column, $request->orderby);
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
            'totalPaging' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    public function create(Request $request)
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
            'weight' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'introduction' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $ResultCategories = null;
        $ResultPriceLocations = null;
        $ResultQuantities = null;
        $ResultCustomerGroups = null;

        if ($request->categories) {
            $ResultCategories = json_decode($request->categories, true);
        }

        $ResultLocations = json_decode($request->locations, true);

        $validateLocation = Validator::make(
            $ResultLocations,
            [
                '*.locationId' => 'required|integer',
                '*.inStock' => 'required|integer',
                '*.lowStock' => 'required|integer',
            ],
            [
                '*.locationId.integer' => 'Location Id Should be Integer!',
                '*.inStock.integer' => 'In Stock Should be Integer',
                '*.lowStock.integer' => 'Low Stock Should be Integer'
            ]
        );

        if ($validateLocation->fails()) {
            $errors = $validateLocation->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        foreach ($ResultLocations as $Res) {

            $CheckDataBranch = DB::table('productClinics as ps')
                ->join('productClinicLocations as psl', 'psl.productClinicId', 'ps.id')
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
        }

        if ($request->hasfile('images')) {

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
                    'errors' => $data_item,
                ], 422);
            }
        }

        if ($request->pricingStatus == "CustomerGroups") {

            if ($request->customerGroups) {
                $ResultCustomerGroups = json_decode($request->customerGroups, true);

                $validateCustomer = Validator::make(
                    $ResultCustomerGroups,
                    [

                        '*.customerGroupId' => 'required|integer',
                        '*.price' => 'required|numeric',
                    ],
                    [
                        '*.customerGroupId.integer' => 'Customer Group Id Should be Integer!',
                        '*.price.numeric' => 'Price Should be Numeric!'
                    ]
                );

                if ($validateCustomer->fails()) {
                    $errors = $validateCustomer->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errors,
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

                        'priceLocations.*.locationId' => 'required|integer',
                        'priceLocations.*.price' => 'required|numeric',
                    ],
                    [
                        '*.locationId.integer' => 'Location Id Should be Integer!',
                        '*.price.numeric' => 'Price Should be Numeric!'
                    ]
                );

                if ($validatePriceLocations->fails()) {
                    $errors = $validatePriceLocations->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errors,
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

                        'quantities.*.fromQty' => 'required|integer',
                        'quantities.*.toQty' => 'required|integer',
                        'quantities.*.price' => 'required|numeric',
                    ],
                    [
                        '*.fromQty.integer' => 'From Quantity Should be Integer!',
                        '*.toQty.integer' => 'To Quantity Should be Integer!',
                        '*.price.numeric' => 'Price Should be Numeric!'
                    ]
                );

                if ($validateQuantity->fails()) {
                    $errors = $validateQuantity->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errors,
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

        foreach ($ResultLocations as $value) {

            $product = ProductClinic::create([
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
                'weight' => $request->weight,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'introduction' => $request->introduction,
                'description' => $request->description,
                'height' => $request->height,
                'userId' => $request->user()->id,
            ]);

            ProductClinicLocation::create([
                'productClinicId' => $product->id,
                'locationId' => $value['locationId'],
                'inStock' => $value['inStock'],
                'lowStock' => $value['lowStock'],
                'userId' => $request->user()->id,
            ]);

            if ($ResultCategories) {

                foreach ($ResultCategories as $valCat) {
                    ProductClinicCategory::create([
                        'productClinicId' => $product->id,
                        'productCategoryId' => $valCat,
                        'userId' => $request->user()->id,
                    ]);
                }
            }

            $count = 0;

            $ResImageDatas = json_decode($request->imageDatas, true);

            if ($flag == false) {

                if ($request->hasfile('images')) {
                    foreach ($files as $file) {

                        foreach ($file as $fil) {

                            $name = $fil->hashName();

                            $fil->move(public_path() . '/ProductClinicImages/', $name);

                            $fileName = "/ProductClinicImages/" . $name;

                            $file = new ProductClinicImages();
                            $file->productClinicId = $product->id;
                            $file->labelName = $ResImageDatas[$count];
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
                    ProductClinicImages::create([
                        'productClinicId' => $product->id,
                        'labelName' => $res['labelName'],
                        'realImageName' => $res['realImageName'],
                        'imagePath' => $res['imagePath'],
                        'userId' => $request->user()->id,
                    ]);
                }
            }

            if ($request->pricingStatus == "CustomerGroups") {

                foreach ($ResultCustomerGroups as $CustVal) {
                    ProductClinicCustomerGroup::create([
                        'productClinicId' => $product->id,
                        'customerGroupId' => $CustVal['customerGroupId'],
                        'price' => $CustVal['price'],
                        'userId' => $request->user()->id,
                    ]);
                }
            } else if ($request->pricingStatus == "PriceLocations") {

                foreach ($ResultPriceLocations as $PriceVal) {
                    ProductClinicPriceLocation::create([
                        'productClinicId' => $product->id,
                        'locationId' => $PriceVal['locationId'],
                        'price' => $PriceVal['price'],
                        'userId' => $request->user()->id,
                    ]);
                }
            } else if ($request->pricingStatus == "Quantities") {

                foreach ($ResultQuantities as $QtyVal) {
                    ProductClinicQuantity::create([
                        'productClinicId' => $product->id,
                        'fromQty' => $QtyVal['fromQty'],
                        'toQty' => $QtyVal['toQty'],
                        'price' => $QtyVal['price'],
                        'userId' => $request->user()->id,
                    ]);
                }
            }
        }

        return response()->json(
            [
                'message' => 'Insert Data Successful!',
            ],
            200
        );
    }

    public function detail(Request $request)
    { 
        $ProdClinic = DB::table('productClinics as ps')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.Id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.Id')
            ->select(
                'ps.id',
                'ps.fullName',
                DB::raw("IFNULL(ps.simpleName,'') as simpleName"),
                DB::raw("IFNULL(ps.sku,'') as sku"),
                'ps.productBrandId',
                'pb.brandName as brandName',
                'ps.productSupplierId',
                'psup.supplierName as supplierName',
                'ps.status',
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

        $location =  DB::table('productClinicLocations as psl')
            ->join('location as l', 'l.Id', 'psl.locationId')
            ->select('psl.Id', 'l.locationName', 'psl.inStock', 'psl.lowStock')
            ->where('psl.productClinicId', '=', $request->id)
            ->first();

        $ProdClinic->location = $location;

        if ($ProdClinic->pricingStatus == "CustomerGroups") {

            $CustomerGroups = DB::table('productClinicCustomerGroups as psc')
                ->join('productClinics as ps', 'psc.productClinicId', 'ps.id')
                ->join('customerGroups as cg', 'psc.customerGroupId', 'cg.id')
                ->select(
                    'psc.id as id',
                    'cg.customerGroup',
                    DB::raw("TRIM(psc.price)+0 as price")
                )
                ->where('psc.productClinicId', '=', $request->id)
                ->get();

            $ProdClinic->customerGroups = $CustomerGroups;
        } elseif ($ProdClinic->pricingStatus == "PriceLocations") {
            $PriceLocations = DB::table('productClinicPriceLocations as psp')
                ->join('productClinics as ps', 'psp.productClinicId', 'ps.id')
                ->join('location as l', 'psp.locationId', 'l.id')
                ->select(
                    'psp.id as id',
                    'l.locationName',
                    DB::raw("TRIM(psp.price)+0 as Price")
                )
                ->where('psp.productClinicId', '=', $request->id)
                ->get();

            $ProdClinic->priceLocations = $PriceLocations;
        } else if ($ProdClinic->pricingStatus == "Quantities") {

            $Quantities = DB::table('productClinicQuantities as psq')
                ->join('productClinics as ps', 'psq.productClinicId', 'ps.id')
                ->select(
                    'psq.id as id',
                    'psq.fromQty',
                    'psq.toQty',
                    DB::raw("TRIM(psq.Price)+0 as Price")
                )
                ->where('psq.ProductClinicId', '=', $request->id)
                ->get();

            $ProdClinic->quantities = $Quantities;
        }

        $ProdClinic->categories = DB::table('productClinicCategories as psc')
            ->join('productClinics as ps', 'psc.productClinicId', 'ps.id')
            ->join('productCategories as pc', 'psc.productCategoryId', 'pc.id')
            ->select(
                'psc.id as id',
                'pc.categoryName'
            )
            ->where('psc.ProductClinicId', '=', $request->id)
            ->get();

        $ProdClinic->images = DB::table('productClinicImages as psi')
            ->join('productClinics as ps', 'psi.productClinicId', 'ps.id')
            ->select(
                'psi.id as id',
                'psi.labelName',
                'psi.realImageName',
                'psi.imagePath'
            )
            ->where('psi.productClinicId', '=', $request->id)
            ->get();

        return response()->json($ProdClinic, 200);

    }

    public function update(Request $request)
    { }

    public function delete(Request $request)
    {
        //check product on DB
        foreach ($request->id as $va) {
            $res = ProductClinic::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        //process delete data
        foreach ($request->id as $va) {

            $ProdClinic = ProductClinic::find($va);

            $ProdClinicLoc = ProductClinicLocation::where('ProductClinicId', '=', $ProdClinic->id)->get();

            if ($ProdClinicLoc) {

                ProductClinicLocation::where('ProductClinicId', '=', $ProdClinic->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdClinicCat = ProductClinicCategory::where('ProductClinicId', '=', $ProdClinic->id)->get();

            if ($ProdClinicCat) {

                ProductClinicCategory::where('ProductClinicId', '=', $ProdClinic->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );

                $ProdClinicCat->DeletedBy = $request->user()->id;
                $ProdClinicCat->isDeleted = true;
                $ProdClinicCat->DeletedAt = Carbon::now();
            }

            $ProdClinicImg = ProductClinicImages::where('ProductClinicId', '=', $ProdClinic->id)->get();

            if ($ProdClinicImg) {

                ProductClinicImages::where('ProductClinicId', '=', $ProdClinic->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdCustGrp = ProductClinicCustomerGroup::where('ProductClinicId', '=', $ProdClinic->id)->get();
            if ($ProdCustGrp) {

                ProductClinicCustomerGroup::where('ProductClinicId', '=', $ProdClinic->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdClinicPrcLoc = ProductClinicPriceLocation::where('ProductClinicId', '=', $ProdClinic->id)->get();

            if ($ProdClinicPrcLoc) {

                ProductClinicPriceLocation::where('ProductClinicId', '=', $ProdClinic->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdClinicQty = ProductClinicQuantity::where('ProductClinicId', '=', $ProdClinic->id)->get();

            if ($ProdClinicQty) {

                ProductClinicQuantity::where('ProductClinicId', '=', $ProdClinic->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdClinic->DeletedBy = $request->user()->id;
            $ProdClinic->isDeleted = true;
            $ProdClinic->DeletedAt = Carbon::now();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}
