<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductClinic;
use App\Models\ProductClinicCategory;
use App\Models\ProductClinicCustomerGroup;
use App\Models\ProductClinicImages;
use App\Models\ProductClinicLocation;
use App\Models\ProductClinicPriceLocation;
use App\Models\ProductClinicQuantity;
use App\Models\ProductClinicReminder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Validator;

class ProductClinicController
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productClinics as pc')
            ->join('productClinicLocations as pcl', 'pcl.productClinicId', 'pc.id')
            ->join('location as loc', 'loc.Id', 'pcl.locationId')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
            ->join('users as u', 'pc.userId', 'u.id')
            ->select(
                'pc.id as id',
                'pc.fullName as fullName',
                DB::raw("IFNULL(pc.sku,'') as sku"),
                'loc.locationName as locationName',
                DB::raw("IFNULL(psup.supplierName,'') as supplierName"),
                DB::raw("IFNULL(pb.brandName,'') as brandName"),
                DB::raw("TRIM(pc.price)+0 as price"),
                'pc.pricingStatus',
                DB::raw("TRIM(pcl.inStock)+0 as stock"),
                'pc.status',
                'pc.isShipped',
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(pc.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pc.isDeleted', '=', 0);

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

        $data = $data->orderBy('pc.id', 'desc');

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
        $temp_column = '';

        $data = DB::table('productClinics as pc')
            ->select(
                'pc.fullName as fullName'
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pc.fullName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'pc.fullName';
            return $temp_column;
        }
        //------------------------

        $data = DB::table('productClinics as pc')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
            ->select(
                DB::raw("IFNULL(psup.supplierName,'') as supplierName")
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('psup.supplierName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'psup.supplierName';
            return $temp_column;
        }
        //------------------------

        $data = DB::table('productClinics as pc')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
            ->select(
                DB::raw("IFNULL(pb.brandName,'') as brandName")
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pb.brandName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'pb.brandName';
            return $temp_column;
        }
    }

    public function create(Request $request)
    {
        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

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
        $ResultReminders = null;

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
                '*.reStockLimit' => 'required|integer',
            ],
            [
                '*.locationId.integer' => 'Location Id Should be Integer!',
                '*.inStock.integer' => 'In Stock Should be Integer',
                '*.lowStock.integer' => 'Low Stock Should be Integer',
                '*.reStockLimit.integer' => 'Restock Limit Should be Integer'
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

            $CheckDataBranch = DB::table('productClinics as pc')
                ->join('productClinicLocations as pcl', 'pcl.productClinicId', 'pc.id')
                ->join('location as loc', 'pcl.locationId', 'loc.id')
                ->select('pc.fullName as fullName', 'loc.locationName')
                ->where('pc.fullName', '=', $request->fullName)
                ->where('pcl.locationId', '=', $Res['locationId'])
                ->first();

            if ($CheckDataBranch) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product ' . $CheckDataBranch->fullName . ' Already Exist on Location ' . $CheckDataBranch->locationName . '!'],
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
                    '*.timing.string' => 'Timing Should be String',
                    '*.status.string' => 'Status Should be String'
                ]
            );

            if ($validateReminders->fails()) {
                $errors = $validateReminders->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
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
        $files[] = $request->file('images');

        DB::beginTransaction();
        try {
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
                    'reStockLimit' => $value['reStockLimit'],
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

                foreach ($ResultReminders as $RemVal) {
                    ProductClinicReminder::create([
                        'productClinicId' => $product->id,
                        'unit' => $RemVal['unit'],
                        'timing' => $RemVal['timing'],
                        'status' => $RemVal['status'],
                        'userId' => $request->user()->id,
                    ]);
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
                'message' => 'Insert Failed',
                'errors' => $th,
            ]);
        }
    }

    private function ValidationImage($request)
    {
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
                    'errors' => $data_item,
                ], 422);
            }
        }

        if ($flag == true) {
            if ($request->imageDatas) {
                $ResultImageDatas = json_decode($request->imageDatas, true);

                if (count($ResultImageDatas) != count($request->file('images'))) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Label Image and total image should same!'],
                    ], 422);
                } else {
                    foreach ($ResultImageDatas as $value) {
                        if ($value == "") {

                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Label Image can not be empty!'],
                            ], 422);
                        }
                    }
                }
            } else {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Image label cannot be empty!!'],
                ], 422);
            }
        }
    }

    public function detail(Request $request)
    {
        $ProdClinic = DB::table('productClinics as pc')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.Id')
            ->select(
                'pc.id',
                'pc.fullName',
                DB::raw("IFNULL(pc.simpleName,'') as simpleName"),
                DB::raw("IFNULL(pc.sku,'') as sku"),
                'pc.productBrandId',
                'pb.brandName as brandName',
                'pc.productSupplierId',
                'psup.supplierName as supplierName',
                'pc.status',
                'pc.pricingStatus',
                DB::raw("TRIM(pc.costPrice)+0 as costPrice"),
                DB::raw("TRIM(pc.marketPrice)+0 as marketPrice"),
                DB::raw("TRIM(pc.price)+0 as price"),
                'pc.isShipped',
                DB::raw("TRIM(pc.weight)+0 as weight"),
                DB::raw("TRIM(pc.length)+0 as length"),
                DB::raw("TRIM(pc.width)+0 as width"),
                DB::raw("TRIM(pc.height)+0 as height"),
                DB::raw("TRIM(pc.weight)+0 as weight"),
                DB::raw("IFNULL(pc.introduction,'') as introduction"),
                DB::raw("IFNULL(pc.description,'') as description"),
            )
            ->where('pc.id', '=', $request->id)
            ->first();

        $location =  DB::table('productClinicLocations as pcl')
            ->join('location as l', 'l.Id', 'pcl.locationId')
            ->select('pcl.Id', 'l.locationName', 'pcl.inStock', 'pcl.lowStock')
            ->where('pcl.productClinicId', '=', $request->id)
            ->first();

        $ProdClinic->location = $location;

        if ($ProdClinic->pricingStatus == "CustomerGroups") {

            $CustomerGroups = DB::table('productClinicCustomerGroups as pcc')
                ->join('productClinics as pc', 'pcc.productClinicId', 'pc.id')
                ->join('customerGroups as cg', 'pcc.customerGroupId', 'cg.id')
                ->select(
                    'pcc.id as id',
                    'cg.customerGroup',
                    DB::raw("TRIM(pcc.price)+0 as price")
                )
                ->where('pcc.productClinicId', '=', $request->id)
                ->get();

            $ProdClinic->customerGroups = $CustomerGroups;
        } elseif ($ProdClinic->pricingStatus == "PriceLocations") {
            $PriceLocations = DB::table('productClinicPriceLocations as pcp')
                ->join('productClinics as pc', 'pcp.productClinicId', 'pc.id')
                ->join('location as l', 'pcp.locationId', 'l.id')
                ->select(
                    'pcp.id as id',
                    'l.locationName',
                    DB::raw("TRIM(pcp.price)+0 as Price")
                )
                ->where('pcp.productClinicId', '=', $request->id)
                ->get();

            $ProdClinic->priceLocations = $PriceLocations;
        } else if ($ProdClinic->pricingStatus == "Quantities") {

            $Quantities = DB::table('productClinicQuantities as pcq')
                ->join('productClinics as pc', 'pcq.productClinicId', 'pc.id')
                ->select(
                    'pcq.id as id',
                    'pcq.fromQty',
                    'pcq.toQty',
                    DB::raw("TRIM(pcq.Price)+0 as Price")
                )
                ->where('pcq.ProductClinicId', '=', $request->id)
                ->get();

            $ProdClinic->quantities = $Quantities;
        }

        $ProdClinic->categories = DB::table('productClinicCategories as pcc')
            ->join('productClinics as pc', 'pcc.productClinicId', 'pc.id')
            ->join('productCategories as pc', 'pcc.productCategoryId', 'pc.id')
            ->select(
                'pcc.id as id',
                'pc.categoryName'
            )
            ->where('pcc.ProductClinicId', '=', $request->id)
            ->get();

        $ProdClinic->images = DB::table('productClinicImages as pci')
            ->join('productClinics as pc', 'pci.productClinicId', 'pc.id')
            ->select(
                'pci.id as id',
                'pci.labelName',
                'pci.realImageName',
                'pci.imagePath'
            )
            ->where('pci.productClinicId', '=', $request->id)
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

            $ProdClinicRem = ProductClinicReminder::where('ProductClinicId', '=', $ProdClinic->id)->get();
            if ($ProdClinicRem) {

                ProductClinicReminder::where('ProductClinicId', '=', $ProdClinic->id)
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
            $ProdClinic->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}
