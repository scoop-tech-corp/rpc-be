<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductClinicDosage;
use App\Exports\Product\ProductClinicReport;
use App\Exports\Product\TemplateUploadProductClinic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Excel;
use Validator;
use App\Models\ProductBrand;
use App\Models\ProductCategories;
use App\Models\ProductCoreCategories;
use App\Models\ProductCustomerGroups;
use App\Models\ProductImages;
use App\Models\ProductLocations;
use App\Models\ProductPriceLocations;
use App\Models\ProductQuantitiess;
use App\Models\ProductReminders;
use App\Models\Products;
use App\Models\ProductSupplier;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProductClinicController
{
    public function index(Request $request)
    {
        if (!checkAccessIndex('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('products as pc')
            ->join('productLocations as pcl', 'pcl.productId', 'pc.id')
            ->join('location as loc', 'loc.Id', 'pcl.locationId')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
            ->join('users as u', 'pc.userId', 'u.id')
            ->select(
                'pc.id as id',
                'pc.fullName as fullName',
                DB::raw("IFNULL(pc.sku,'') as sku"),
                'loc.id as locationId',
                'loc.locationName as locationName',
                DB::raw("IFNULL(psup.supplierName,'') as supplierName"),
                DB::raw("IFNULL(pb.brandName,'') as brandName"),
                DB::raw("TRIM(pc.price)+0 as price"),
                'pc.pricingStatus',
                DB::raw("TRIM(pcl.inStock)+0 as stock"),
                'pc.status',
                'pc.isShipped',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pc.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pc.isDeleted', '=', 0)
            ->where('pc.category', '=', 'clinic');

        if ($request->locationId) {

            $data = $data->whereIn('loc.id', $request->locationId);
        }

        if ($request->stock) {

            if ($request->stock == "highStock") {
                $data = $data->where('pcl.diffStock', '>', 0);
            } elseif ($request->stock == "lowStock") {

                $data = $data->where('pcl.diffStock', '<=', 0);
            }
        }

        if ($request->category) {

            $cat = DB::table('productCoreCategories as pc')
                ->select('productId')
                ->whereIn('productCategoryId', $request->category)
                ->where('pc.isDeleted', '=', 0)
                ->distinct()
                ->pluck('productId');

            $data = $data->whereIn('pc.id', $cat);
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

        $data = $data->orderBy('pc.updated_at', 'desc');

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

        $data = DB::table('products as pc')
            ->select(
                'pc.fullName as fullName'
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pc.fullName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pc.fullName';
        }
        //------------------------

        // $data = DB::table('productClinics as pc')
        //     ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
        //     ->select(
        //         DB::raw("IFNULL(psup.supplierName,'') as supplierName")
        //     )
        //     ->where('pc.isDeleted', '=', 0);

        // if ($request->search) {
        //     $data = $data->where('psup.supplierName', 'like', '%' . $request->search . '%');
        // }

        // $data = $data->get();

        // if (count($data)) {
        //     $temp_column[] = 'psup.supplierName';
        // }
        //------------------------

        // $data = DB::table('productClinics as pc')
        //     ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
        //     ->select(
        //         DB::raw("IFNULL(pb.brandName,'') as brandName")
        //     )
        //     ->where('pc.isDeleted', '=', 0);

        // if ($request->search) {
        //     $data = $data->where('pb.brandName', 'like', '%' . $request->search . '%');
        // }

        // $data = $data->get();

        // if (count($data)) {
        //     $temp_column[] = 'pb.brandName';
        // }

        return $temp_column;
    }

    public function create(Request $request)
    {

        if (!checkAccessModify('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

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
        $ResultDosages = null;

        if ($request->categories) {
            $ResultCategories = json_decode($request->categories, true);
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Category Product must be selected'],
            ], 422);
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
                '*.locationId.integer' => 'Location Id Should be Filled!',
                '*.locationId.distinct' => 'Cannot add duplicate Location!',
                '*.inStock.integer' => 'In Stock Should be Filled',
                '*.inStock.required' => 'In Stock Should be Required',
                '*.lowStock.required' => 'Low Stock Should be Integer',
                '*.lowStock.integer' => 'Low Stock Should be Filled',
                '*.reStockLimit.required' => 'Restock Limit Should be Required',
                '*.reStockLimit.integer' => 'Restock Limit Should be Filled',
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

            $CheckDataBranch = DB::table('products as pc')
                ->join('productLocations as pcl', 'pcl.productId', 'pc.id')
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
                    '*.unit.required' => 'Unit Should be Required!',
                    '*.unit.integer' => 'Unit Should be Integer!',
                    '*.timing.required' => 'Timing Should be Required',
                    '*.timing.string' => 'Timing Should be String',
                    '*.status.required' => 'Status Should be Required',
                    '*.status.string' => 'Status Should be String',
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

        $ResultDosages = json_decode($request->dosages, true);

        if ($ResultDosages) {

            $validateDosages = Validator::make(
                $ResultDosages,
                [
                    '*.from' => 'required|integer',
                    '*.to' => 'required|integer',
                    '*.dosage' => 'required|numeric',
                    '*.unit' => 'required|string',
                ],
                [
                    '*.from.required' => 'From Weight Should be Required!',
                    '*.from.integer' => 'From Weight Should be Integer!',
                    '*.to.required' => 'To Weight Should be Required!',
                    '*.to.integer' => 'To Weight Should be Integer!',
                    '*.dosage.required' => 'Dosage Should be Required!',
                    '*.dosage.numeric' => 'Dosage Should be Numeric!',
                    '*.unit.required' => 'Unit Should be Required!',
                    '*.unit.string' => 'Unit Should be String!',
                ]
            );

            if ($validateDosages->fails()) {
                $errors = $validateDosages->errors()->first();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [$errors],
                ], 422);
            }

            $temp = "";

            foreach ($ResultDosages as $value) {

                if ($temp == "") {
                    $temp = $value['unit'];
                } else if ($temp != $value['unit']) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Unit type must be same!'],
                    ], 422);
                } else {
                    $temp = $value['unit'];
                }
            }
        }
        //validasi gambar

        $this->ValidationImage($request);

        if ($request->pricingStatus == "CustomerGroups") {

            if ($request->customerGroups) {
                $ResultCustomerGroups = json_decode($request->customerGroups, true);

                $count = 0;
                while ($count < count($ResultCustomerGroups)) {

                    if ($ResultCustomerGroups[$count]['status'] == 'del') {
                        array_splice($ResultCustomerGroups, $count, 1);
                    } else {
                        $count++;
                    }
                }

                $validateCustomer = Validator::make(
                    $ResultCustomerGroups,
                    [

                        '*.customerGroupId' => 'required|integer|distinct',
                        '*.price' => 'required|numeric',
                    ],
                    [
                        '*.customerGroupId.required' => 'Customer Group Id Should be Required!',
                        '*.customerGroupId.integer' => 'Customer Group Id Should be Integer!',
                        '*.customerGroupId.distinct' => 'Cannot add duplicate Customer Group!',
                        '*.price.required' => 'Price Should be Required!',
                        '*.price.numeric' => 'Price Should be Numeric!',

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

                $count = 0;
                while ($count < count($ResultPriceLocations)) {

                    if ($ResultPriceLocations[$count]['status'] == 'del') {
                        array_splice($ResultPriceLocations, $count, 1);
                    } else {
                        $count++;
                    }
                }

                $validatePriceLocations = Validator::make(
                    $ResultPriceLocations,
                    [
                        '*.locationId' => 'required|integer|distinct',
                        '*.price' => 'required|numeric',
                    ],
                    [
                        '*.locationId.required' => 'Location Id Should be Required!',
                        '*.locationId.integer' => 'Location Id Should be Integer!',
                        '*.locationId.distinct' => 'Cannot add duplicate Location!',
                        '*.price.numeric' => 'Price Should be Numeric!',
                        '*.price.required' => 'Price Should be Required!',

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

                $count = 0;
                while ($count < count($ResultQuantities)) {

                    if ($ResultQuantities[$count]['status'] == 'del') {
                        array_splice($ResultQuantities, $count, 1);
                    } else {
                        $count++;
                    }
                }

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

        //validation category
        foreach ($ResultCategories as  $validCat) {
            $dat = ProductCategories::find($validCat);
            $diff = 0;
            $date = $request->expiredDate;

            if ($request->expiredDate > Carbon::now()) {
                $diff = now()->diffInDays(Carbon::parse($date));
            } else {
                $diff = now()->diffInDays(Carbon::parse($date)) * -1;
            }

            if ($dat[0]->expiredDay > $diff) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Expired Days should more than expired date inserted! At Category ' . $dat[0]->categoryName],
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

                $product = Products::create([
                    'category' => 'clinic',
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

                ProductLocations::create([
                    'productId' => $product->id,
                    'locationId' => $value['locationId'],
                    'inStock' => $value['inStock'],
                    'lowStock' => $value['lowStock'],
                    'reStockLimit' => $value['reStockLimit'],
                    'diffStock' => $value['inStock'] - $value['lowStock'],
                    'userId' => $request->user()->id,
                ]);

                if ($ResultCategories) {

                    foreach ($ResultCategories as $valCat) {
                        ProductCoreCategories::create([
                            'productId' => $product->id,
                            'productCategoryId' => $valCat['id'],
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

                                $fil->move(public_path() . '/ProductImages/', $name);

                                $fileName = "/ProductImages/" . $name;

                                $file = new ProductImages();
                                $file->productId = $product->id;
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
                        ProductImages::create([
                            'productId' => $product->id,
                            'labelName' => $res['labelName'],
                            'realImageName' => $res['realImageName'],
                            'imagePath' => $res['imagePath'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                foreach ($ResultReminders as $RemVal) {
                    ProductReminders::create([
                        'productId' => $product->id,
                        'unit' => $RemVal['unit'],
                        'timing' => $RemVal['timing'],
                        'status' => $RemVal['status'],
                        'userId' => $request->user()->id,
                    ]);
                }

                foreach ($ResultDosages as $dos) {
                    ProductClinicDosage::create([
                        'productClinicId' => $product->id,
                        'from' => $dos['from'],
                        'to' => $dos['to'],
                        'dosage' => $dos['dosage'],
                        'unit' => $dos['unit'],
                        'userId' => $request->user()->id,
                    ]);
                }

                if ($request->pricingStatus == "CustomerGroups") {

                    foreach ($ResultCustomerGroups as $CustVal) {
                        ProductCustomerGroups::create([
                            'productId' => $product->id,
                            'customerGroupId' => $CustVal['customerGroupId'],
                            'price' => $CustVal['price'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                } else if ($request->pricingStatus == "PriceLocations") {

                    foreach ($ResultPriceLocations as $PriceVal) {
                        ProductPriceLocations::create([
                            'productId' => $product->id,
                            'locationId' => $PriceVal['locationId'],
                            'price' => $PriceVal['price'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                } else if ($request->pricingStatus == "Quantities") {

                    foreach ($ResultQuantities as $QtyVal) {
                        ProductQuantitiess::create([
                            'productId' => $product->id,
                            'fromQty' => $QtyVal['fromQty'],
                            'toQty' => $QtyVal['toQty'],
                            'price' => $QtyVal['price'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                productClinicLog($product->id, "Create new Item", "", $value['inStock'], $value['inStock'], $request->user()->id);
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
    }

    public function detail(Request $request)
    {
        if (!checkAccessIndex('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

        $prod = DB::table('products as pc')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.id')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.Id')
            ->select(
                'pc.id',
                'pc.fullName',
                DB::raw("IFNULL(pc.simpleName,'') as simpleName"),

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

        $prodDetails = DB::table('products as pc')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.id')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
            ->select(
                'pc.status',
                'pc.productSupplierId',
                'psup.supplierName as supplierName',
                DB::raw("IFNULL(pc.sku,'') as sku"),
                'pc.productBrandId',
                'pb.brandName as brandName',
            )
            ->where('pc.id', '=', $request->id)
            ->first();

        $categories = DB::table('productCategories as pcat')
            ->join('productCoreCategories as pcc', 'pcc.productCategoryId', 'pcat.id')
            ->join('products as pc', 'pcc.productId', 'pc.id')
            ->select('pcat.id', 'pcat.categoryName')
            ->where('pc.id', '=', $request->id)
            ->where('pcc.isDeleted', '=', 0)
            ->get();

        $prodDetails->categories = $categories;

        $prod->details = $prodDetails;


        $prodSetting = DB::table('products as pc')
            ->select(
                'pc.isCustomerPurchase as isCustomerPurchase',
                'pc.isCustomerPurchaseOnline as isCustomerPurchaseOnline',
                'pc.isCustomerPurchaseOutStock as isCustomerPurchaseOutStock',
                'pc.isStockLevelCheck as isStockLevelCheck',
                'pc.isNonChargeable as isNonChargeable',
                'pc.isOfficeApproval as isOfficeApproval',
                'pc.isAdminApproval as isAdminApproval',
            )
            ->where('pc.id', '=', $request->id)
            ->first();

        $prod->setting = $prodSetting;

        $location =  DB::table('productLocations as pcl')
            ->join('location as l', 'l.Id', 'pcl.locationId')
            ->select(
                'pcl.id',
                'l.id as locationId',
                'l.locationName',
                'pcl.inStock',
                'pcl.lowStock',
                'pcl.reStockLimit',
                DB::raw('(CASE WHEN pcl.inStock = 0 THEN "NO STOCK" WHEN pcl.inStock <= pcl.lowStock THEN "LOW STOCK" ELSE "CLEAR" END) AS status')
            )

            ->where('pcl.productId', '=', $request->id)
            ->where('pcl.isDeleted', '=', 0)
            ->first();

        $prod->location = $location;

        if ($prod->pricingStatus == "CustomerGroups") {

            $CustomerGroups = DB::table('productCustomerGroups as pcc')
                ->join('products as pc', 'pcc.productId', 'pc.id')
                ->join('customerGroups as cg', 'pcc.customerGroupId', 'cg.id')
                ->select(
                    'pcc.id',
                    'pcc.customerGroupId',
                    'cg.customerGroup',
                    DB::raw("TRIM(pcc.price)+0 as price")
                )
                ->where('pcc.productId', '=', $request->id)
                ->where('pcc.isDeleted', '=', 0)
                ->get();

            $prod->customerGroups = $CustomerGroups;
        } elseif ($prod->pricingStatus == "PriceLocations") {
            $PriceLocations = DB::table('productPriceLocations as pcp')
                ->join('products as pc', 'pcp.productId', 'pc.id')
                ->join('location as l', 'pcp.locationId', 'l.id')
                ->select(
                    'pcp.id',
                    'l.locationName',
                    'l.id as locationId',
                    DB::raw("TRIM(pcp.price)+0 as price")
                )
                ->where('pcp.productId', '=', $request->id)
                ->where('pcp.isDeleted', '=', 0)
                ->get();

            $prod->priceLocations = $PriceLocations;
        } else if ($prod->pricingStatus == "Quantities") {

            $Quantities = DB::table('productQuantities as pcq')
                ->join('products as pc', 'pcq.productId', 'pc.id')
                ->select(
                    'pcq.id',
                    'pcq.fromQty',
                    'pcq.toQty',
                    DB::raw("TRIM(pcq.price)+0 as price")
                )
                ->where('pcq.ProductId', '=', $request->id)
                ->where('pcq.isDeleted', '=', 0)
                ->get();

            $prod->quantities = $Quantities;
        }

        $prod->images = DB::table('productImages as pci')
            ->join('products as pc', 'pci.productId', 'pc.id')
            ->select(
                'pci.id',
                'pci.labelName',
                'pci.realImageName',
                'pci.imagePath'
            )
            ->where('pci.productId', '=', $request->id)
            ->where('pci.isDeleted', '=', 0)
            ->get();

        $prod->dosages = DB::table('productClinicDosages as pcd')
            ->join('products as pc', 'pcd.productClinicId', 'pc.id')
            ->select(
                'pcd.id',
                DB::raw("TRIM(pcd.from)+0 as fromWeight"),
                DB::raw("TRIM(pcd.to)+0 as toWeight"),
                DB::raw("TRIM(pcd.dosage)+0 as dosage"),
                'pcd.unit',
            )
            ->where('pcd.productClinicId', '=', $request->id)
            ->where('pcd.isDeleted', '=', 0)
            ->get();

        $prod->reminders = DB::table('productReminders as pcr')
            ->join('products as pc', 'pcr.productId', 'pc.id')
            ->select(
                'pcr.id',
                'pcr.unit',
                'pcr.timing',
                'pcr.status',
            )
            ->where('pcr.productId', '=', $request->id)
            ->where('pcr.isDeleted', '=', 0)
            ->get();

        $prodLog = DB::table('products as ps')
            ->join('productLogs as psl', 'psl.productId', 'ps.id')
            ->join('users as u', 'u.id', 'psl.userId')
            ->select(
                'psl.id',
                'psl.transaction',
                'psl.remark',
                'psl.quantity',
                'psl.balance',
                DB::raw("CONCAT(u.firstName,' ',u.middleName,CASE WHEN u.middleName = '' THEN '' ELSE ' ' END,u.lastName) as fullName"),
                DB::raw("DATE_FORMAT(psl.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('ps.id', '=', $request->id)
            ->get();

        $prod->log = $prodLog;

        $productBatch = DB::table('productBatches as psb')
            ->leftJoin('products as ps', 'psb.productId', 'ps.id')
            ->leftJoin('productRestocks as pr', 'psb.productRestockId', 'pr.id')
            ->leftJoin('productRestockDetails as prd', 'psb.productRestockDetailId', 'prd.id')
            ->leftJoin('productTransfers as pt', 'psb.productTransferId', 'pt.id')
            ->select(
                'psb.id',
                'psb.batchNumber',
                'ps.fullName',
                'psb.purchaseOrderNumber',
                'psb.purchaseRequestNumber',
                'psb.expiredDate',
                'psb.sku'
            )
            ->where('psb.productId', '=', $request->id)
            ->get();

        $prod->batches = $productBatch;

        return response()->json($prod, 200);
    }

    public function update(Request $request)
    {
        if (!checkAccessModify('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

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

        $prod = Products::find($request->id);

        if (!$prod) {
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
        $ResultDosages = null;

        if ($request->categories) {
            $ResultCategories = $request->categories;
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Category Product must be selected'],
            ], 422);
        }

        $ResultReminders = $request->reminders;

        $ResultQuantities = $request->quantities;

        $ResultDosages = $request->dosages;

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
                'errors' => $errors,
            ], 422);
        }

        if ($request->pricingStatus == "CustomerGroups") {

            if ($request->customerGroups) {
                $ResultCustomerGroups = $request->customerGroups;

                $validateCustomer = Validator::make(
                    $request->customerGroups,
                    [
                        '*.id' => 'nullable|integer',
                        '*.customerGroupId' => 'required|integer',
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
                    $request->priceLocations,
                    [
                        '*.id' => 'nullable|integer',
                        '*.locationId' => 'required|integer',
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

        //validation category
        // foreach ($ResultCategories as  $validCat) {
        //     $dat = ProductCategories::find($validCat);
        //     $diff = 0;
        //     $date = $request->expiredDate;

        //     if ($request->expiredDate > Carbon::now()) {
        //         $diff = now()->diffInDays(Carbon::parse($date));
        //     } else {
        //         $diff = now()->diffInDays(Carbon::parse($date)) * -1;
        //     }

        //     if ($dat[0]->expiredDay > $diff) {
        //         return response()->json([
        //             'message' => 'The given data was invalid.',
        //             'errors' => ['Expired Days should more than expired date inserted! At Category ' . $dat[0]->categoryName],
        //         ], 422);
        //     }
        // }

        //UPDATE DATA

        $location = $request->locations;

        ProductLocations::updateOrCreate(
            ['id' => $location['id']],
            [
                'inStock' => $location['inStock'],
                'lowStock' => $location['lowStock'],
                'reStockLimit' => $location['reStockLimit'],
                'diffStock' => $location['inStock'] - $location['lowStock'],
            ]
        );

        try {

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

        $product = Products::updateOrCreate(
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

                'updated_at' => Carbon::now(),

                'userId' => $request->user()->id,
            ]
        );

        ProductCoreCategories::where('ProductId', '=', $request->id)
            ->where('isDeleted', '=', 0)
            ->update(
                [
                    'deletedBy' => $request->user()->id,
                    'isDeleted' => 1,
                    'deletedAt' => Carbon::now()
                ]
            );

        if ($ResultCategories) {

            foreach ($ResultCategories as $valCat) {
                ProductCoreCategories::create(
                    [
                        'productId' => $request->id,
                        'productCategoryId' => $valCat['id'],
                        'userId' => $request->user()->id,
                    ]
                );
            }
        }

        foreach ($ResultReminders as $RemVal) {

            if ($RemVal['statusData'] == 'del') {

                ProductReminders::where('id', '=', $RemVal['id'])
                    ->where('isDeleted', '=', 0)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } else {

                ProductReminders::updateOrCreate(
                    ['id' => $RemVal['id']],
                    [
                        'productId' => $product->id,
                        'unit' => $RemVal['unit'],
                        'timing' => $RemVal['timing'],
                        'status' => $RemVal['status'],
                        'userId' => $request->user()->id,
                    ]
                );
            }
        }

        if ($ResultDosages) {

            foreach ($ResultDosages as $val) {

                if ($val['status'] == 'del') {
                    ProductClinicDosage::where('id', '=', $val['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {
                    ProductClinicDosage::updateOrCreate(
                        ['id' => $val['id']],
                        [
                            'productClinicId' => $product->id,
                            'from' => $val['from'],
                            'to' => $val['to'],
                            'dosage' => $val['dosage'],
                            'unit' => $val['unit'],
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }

        if ($request->pricingStatus == "CustomerGroups") {

            foreach ($ResultCustomerGroups as $CustVal) {

                if ($CustVal['status'] == 'del') {

                    ProductCustomerGroups::where('id', '=', $CustVal['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {

                    ProductCustomerGroups::updateOrCreate(
                        ['id' => $CustVal['id']],
                        [
                            'productId' => $product->id,
                            'customerGroupId' => $CustVal['customerGroupId'],
                            'price' => $CustVal['price'],
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        } else if ($request->pricingStatus == "PriceLocations") {

            foreach ($ResultPriceLocations as $PriceVal) {

                if ($PriceVal['status'] == 'del') {

                    ProductPriceLocations::where('id', '=', $PriceVal['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {

                    ProductPriceLocations::updateOrCreate(
                        ['id' => $PriceVal['id']],
                        [
                            'productId' => $product->id,
                            'locationId' => $PriceVal['locationId'],
                            'price' => $PriceVal['price'],
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        } else if ($request->pricingStatus == "Quantities") {

            foreach ($ResultQuantities as $QtyVal) {

                if ($QtyVal['status'] == 'del') {
                    ProductQuantitiess::where('id', '=', $QtyVal['id'])
                        ->where('isDeleted', '=', 0)
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } else {
                    ProductQuantitiess::updateOrCreate(
                        ['id' => $QtyVal['id']],
                        [
                            'productId' => $product->id,
                            'fromQty' => $QtyVal['fromQty'],
                            'toQty' => $QtyVal['toQty'],
                            'price' => $QtyVal['price'],
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }
        //}
        DB::commit();

        return response()->json(
            [
                'message' => 'Update Data Successful!',
            ],
            200
        );
        } catch (Exception $th) {
            DB::rollback();

            return response()->json([
                'message' => 'Insert Failed',
                'errors' => [$th],
            ], 422);
        }
    }

    public function updateImages(Request $request)
    {
        if (!checkAccessModify('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Produk tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        $product = Products::find($request->id);

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

                    $fil->move(public_path() . '/ProductImages/', $name);

                    $fileName = "/ProductImages/" . $name;

                    $file = new ProductImages();
                    $file->productId = 1;
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

            if ($value['status'] == '' && $value['id'] == 0) {

                ProductImages::create([
                    'productId' => $request->id,
                    'labelName' => $value['name'],
                    'realImageName' => $tmpImages[$count]['realImageName'],
                    'imagePath' => $tmpImages[$count]['imagePath'],
                    'userId' => $request->user()->id,
                ]);

                $count += 1;
            } else if ($value['status'] == 'del' && $value['id'] != 0) {

                $prodImg = ProductImages::find($value['id']);
                $prodImg->DeletedBy = $request->user()->id;
                $prodImg->isDeleted = true;
                $prodImg->DeletedAt = Carbon::now();
                $prodImg->save();
            } else if ($value['id'] != 0) {

                ProductImages::updateorCreate(
                    ['id' => $value['id']],
                    [
                        'productId' => $request->id,
                        'labelName' => $value['name'],
                        'userId' => $request->user()->id,
                    ]
                );
            }
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
        if (!checkAccessDelete('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }
        //check product on DB
        foreach ($request->id as $va) {
            $res = Products::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        //process delete data
        foreach ($request->id as $va) {

            $Prod = Products::find($va);

            $ProdLoc = ProductLocations::where('ProductId', '=', $Prod->id)->get();

            if ($ProdLoc) {

                ProductLocations::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdCat = ProductCoreCategories::where('ProductId', '=', $Prod->id)->get();

            if ($ProdCat) {

                ProductCoreCategories::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );

                $ProdCat->DeletedBy = $request->user()->id;
                $ProdCat->isDeleted = true;
                $ProdCat->DeletedAt = Carbon::now();
            }

            $ProdImg = ProductImages::where('ProductId', '=', $Prod->id)->get();

            if ($ProdImg) {

                ProductImages::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdCustGrp = ProductCustomerGroups::where('ProductId', '=', $Prod->id)->get();
            if ($ProdCustGrp) {

                ProductCustomerGroups::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdPrcLoc = ProductPriceLocations::where('ProductId', '=', $Prod->id)->get();

            if ($ProdPrcLoc) {

                ProductPriceLocation::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdQty = ProductQuantitiess::where('ProductId', '=', $Prod->id)->get();

            if ($ProdQty) {

                ProductQuantitiess::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $ProdRem = ProductReminders::where('ProductId', '=', $Prod->id)->get();
            if ($ProdRem) {

                ProductReminders::where('ProductId', '=', $Prod->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $Prod->DeletedBy = $request->user()->id;
            $Prod->isDeleted = true;
            $Prod->DeletedAt = Carbon::now();
            $Prod->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }

    public function export(Request $request)
    {
        if (!checkAccessIndex('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

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

        $lowStockLabel = "";

        if ($request->isExportLimit == 1) {
            $lowStockLabel = "Low Stock";
        }

        if ($tmp == "") {
            $fileName = "Rekap Produk Klinik " . $lowStockLabel . " " . $date . ".xlsx";
        } else {
            $fileName = "Rekap Produk Klinik " . $lowStockLabel . " " . $tmp . " " . $date . ".xlsx";
        }

        return Excel::download(
            new ProductClinicReport(
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

    public function downloadTemplate(Request $request)
    {
        if (!checkAccessModify('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

        return (new TemplateUploadProductClinic())->download('Template Upload Produk Klinik.xlsx');
    }

    public function import(Request $request)
    {
        if (!checkAccessModify('product-list', $request->user()->roleId)) {

            return responseUnauthorize();
        }

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

        $rows = Excel::toArray(new ImportProduct($id), $request->file('file'));
        $src = $rows[0];

        $count_row = 1;
        if ($src) {
            foreach ($src as $value) {

                if ($value['nama'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Nama at row ' . $count_row],
                    ], 422);
                }

                $name = Products::where('fullName', '=', $value['nama'])->where('isDeleted', '=', 0)->first();

                if ($name) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any Nama has already exist on system at row ' . $count_row],
                    ], 422);
                }

                if ($value['kode_merk']) {
                    $brandCode = ProductBrand::where('id', '=', $value['kode_merk'])->where('isDeleted', '=', 0)->first();

                    if (!$brandCode) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is any invalid Kode Merk at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['kode_penyedia']) {
                    $supplierCode = ProductSupplier::where('id', '=', $value['kode_penyedia'])->where('isDeleted', '=', 0)->first();

                    if (!$supplierCode) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is any invalid Kode Penyedia at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['status'] || $value['status'] == 0) {

                    if ($value['status'] != 0 && $value['status'] != 1) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Invalid format for column Status at row ' . $count_row],
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty Status please check again at row ' . $count_row],
                    ], 422);
                }
                $expiredDate = Carbon::instance(Date::excelToDateTimeObject((int) $value['tanggal_kedaluwarsa']));

                $codeLocation = explode(';', $value['kode_lokasi']);
                $inStock = explode(';', $value['stok']);
                $lowStock = explode(';', $value['stok_rendah']);
                $reStockLimit = explode(';', $value['batas_restock_ulang']);

                $a = count($codeLocation);
                $b = count($inStock);
                $c = count($lowStock);
                $d = count($reStockLimit);

                if (
                    $a !== $b ||
                    $a !== $c ||
                    $a !== $d ||
                    $b !== $c ||
                    $b !== $d ||
                    $c !== $d
                ) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Total data on column Kode Lokasi, Stok, Stok Rendah, and Batas Restok Ulang not same at row ' . $count_row],
                    ], 422);
                }

                if (count($codeLocation) !== count(array_unique($codeLocation))) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any duplicate kode lokasi. Please check again at row ' . $count_row],
                    ], 422);
                }

                foreach ($codeLocation as $valcode) {

                    $chk = DB::table('location')
                        ->where('id', '=', $valcode)->where('isDeleted', '=', 0)->first();

                    if (!$chk) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Invalid data at column Kode Lokasi at row ' . $count_row],
                        ], 422);
                    }
                }

                foreach ($inStock as $valStock) {

                    if (is_numeric($valStock) == false) {
                        return $valStock;
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Any column Stok is not a number at row ' . $count_row],
                        ], 422);
                    }
                }

                foreach ($lowStock as $valLowStock) {
                    if (is_numeric($valLowStock) == false) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Any column Stok Rendah is not a number at row ' . $count_row],
                        ], 422);
                    }
                }

                foreach ($reStockLimit as $valStock) {
                    if (is_numeric($valStock) == false) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Any column Batas Restock Ulang is not a number at row ' . $count_row],
                        ], 422);
                    }
                }

                $isCanBuy = $value['dapat_membeli_produk'];
                $isDeliver = $value['dapat_dikirim'];
                $isBuyOnline = $value['dapat_membeli_secara_online'];
                $isBuyNoStock = $value['dapat_membeli_saat_stok_habis'];
                $isCheckStockOnCreateReceipt = $value['pengecekan_stok_selama_ada_penambahan_atau_pembuatan_resep'];
                $isNoAnyCharge = $value['tidak_dikenakan_biaya'];
                $officeApproval = $value['persetujuan_office'];
                $adminApproval = $value['persetujuan_admin'];
                $productCategory = explode(';', $value['kode_kategori_produk']);

                if ($isDeliver != 0 && $isDeliver != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Dapat Dikirim at row ' . $count_row],
                    ], 422);
                }

                if ($isBuyOnline != 0 && $isBuyOnline != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Dapat Membeli Secara Online at row ' . $count_row],
                    ], 422);
                }

                if ($isBuyNoStock != 0 && $isBuyNoStock != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Dapat Membeli Saat Stok Habis at row ' . $count_row],
                    ], 422);
                }

                if ($isCheckStockOnCreateReceipt != 0 && $isCheckStockOnCreateReceipt != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Pengecekan stok selama ada penambahan atau pembuatan resep at row ' . $count_row],
                    ], 422);
                }

                if ($isNoAnyCharge != 0 && $isNoAnyCharge != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Tidak Dikenakan Biaya at row ' . $count_row],
                    ], 422);
                }
                if ($officeApproval != 0 && $officeApproval != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Persetujuan Office'],
                    ], 422);
                }
                if ($adminApproval != 0 && $adminApproval != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['Invalid format for column Persetujuan Admin at row ' . $count_row],
                    ], 422);
                }

                if (count($productCategory) !== count(array_unique($productCategory))) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any duplicate Kategori Produk. Please check again at row ' . $count_row],
                    ], 422);
                }

                foreach ($productCategory as $valProdCat) {

                    $chk = ProductCategories::where('id', '=', $valProdCat)->where('isDeleted', '=', 0)->first();

                    if (!$chk) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is any invalid Kode Kategori Produk at row ' . $count_row],
                        ], 422);
                    }

                    if (is_numeric($valProdCat) == false) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Any column Stok is not a number at row ' . $count_row],
                        ], 422);
                    }
                }
                $count_row += 1;
            }

            //here

            foreach ($src as $value) {

                $codeLocation = explode(';', $value['kode_lokasi']);
                $inStock = explode(';', $value['stok']);
                $lowStock = explode(';', $value['stok_rendah']);
                $reStockLimit = explode(';', $value['batas_restock_ulang']);
                $productCategory = explode(';', $value['kode_kategori_produk']);
                $isCanBuy = $value['dapat_membeli_produk'];
                $isBuyOnline = $value['dapat_membeli_secara_online'];
                $isBuyNoStock = $value['dapat_membeli_saat_stok_habis'];
                $isCheckStockOnCreateReceipt = $value['pengecekan_stok_selama_ada_penambahan_atau_pembuatan_resep'];
                $isNoAnyCharge = $value['tidak_dikenakan_biaya'];
                $officeApproval = $value['persetujuan_office'];
                $adminApproval = $value['persetujuan_admin'];
                $expiredDate = Carbon::instance(Date::excelToDateTimeObject((int) $value['tanggal_kedaluwarsa']));

                $count = 0;

                foreach ($codeLocation as $locIns) {

                    $product = Products::create([
                        'category' => 'clinic',
                        'fullName' => $value['nama'],
                        'simpleName' => $value['nama_sederhana'],
                        'sku' => $value['sku'],
                        'productBrandId' => $value['kode_merk'],
                        'productSupplierId' => $value['kode_penyedia'],
                        'status' => $value['status'],
                        'expiredDate' => $expiredDate,
                        'pricingStatus' => 'Basic',
                        'costPrice' => $value['pengeluaran'],
                        'marketPrice' => $value['harga_pasar'],
                        'price' => $value['harga_jual'],
                        'isShipped' => $value['dapat_dikirim'],
                        'weight' => $value['berat'],
                        'length' => $value['panjang'],
                        'width' => $value['lebar'],
                        'height' => $value['tinggi'],
                        'introduction' => $value['perkenalan'],
                        'description' => $value['deskripsi'],

                        'isCustomerPurchase' => $isCanBuy,
                        'isCustomerPurchaseOnline' => $isBuyOnline,
                        'isCustomerPurchaseOutStock' => $isBuyNoStock,
                        'isStockLevelCheck' => $isCheckStockOnCreateReceipt,
                        'isNonChargeable' => $isNoAnyCharge,
                        'isOfficeApproval' => $officeApproval,
                        'isAdminApproval' => $adminApproval,

                        'userId' => $request->user()->id,
                    ]);

                    ProductLocations::create([
                        'productId' => $product->id,
                        'locationId' => $locIns,
                        'inStock' => $inStock[$count],
                        'lowStock' => $lowStock[$count],
                        'reStockLimit' => $reStockLimit[$count],
                        'diffStock' => $inStock[$count] - $lowStock[$count],
                        'userId' => $request->user()->id,
                    ]);

                    if ($productCategory) {

                        foreach ($productCategory as $valCat) {
                            ProductCoreCategories::create([
                                'productId' => $product->id,
                                'productCategoryId' => $valCat,
                                'userId' => $request->user()->id,
                            ]);
                        }
                    }

                    productClinicLog($product->id, "Create New Item with Import Excel", "", $inStock[$count], $inStock[$count], $request->user()->id);

                    $count += 1;
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
