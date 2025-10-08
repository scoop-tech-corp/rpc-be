<?php

namespace App\Http\Controllers\Promotion;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\Promotion\PromoReport;
use App\Models\Customer\Customer;
use App\Models\promotion_bundle_detail_products;
use App\Models\promotion_bundle_detail_services;
use App\Models\promotion_discount_product;
use App\Models\promotion_discount_services;
use App\Models\PromotionBasedSales;
use App\Models\PromotionBundle;
use App\Models\PromotionBundleDetail;
use App\Models\PromotionCustomerGroup;
use App\Models\PromotionDiscount;
use App\Models\PromotionFreeItem;
use App\Models\PromotionLocation;
use App\Models\PromotionMaster;
use Validator;
use DB;
use Illuminate\Support\Carbon;

class DiscountController extends Controller
{
    public function create(Request $request)
    {
        //type => 1 = Free Item, 2 = Discount, 3 = Bundle, 4 = Based Sales
        $validate = Validator::make($request->all(), [
            'type' => 'required|integer|in:1,2,3,4',
            'name' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'status' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        if (!$request->locations) {

            return responseInvalid(['Location cannot be empty!']);
        }

        if (is_string($request->locations)) {
            $ResultLocations = json_decode($request->locations, true);
        } elseif (is_array($request->locations)) {
            $ResultLocations = $request->locations;
        }

        if (is_string($request->customerGroups)) {
            $ResultCustGroup = json_decode($request->customerGroups, true);
        } elseif (is_array($request->customerGroups)) {
            $ResultCustGroup = $request->customerGroups;
        }

        if ($request->type == 1) {

            $ResultFreeItem = json_decode($request->freeItem, true);

            $validateLocation = Validator::make(
                $ResultFreeItem,
                [
                    'quantityBuy' => 'required|integer',
                    'productBuyId' => 'required|integer',
                    'quantityFree' => 'required|integer',
                    'productFreeId' => 'required|integer',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'quantityBuy.required' => 'Quantity Buy Item Should be Required!',
                    'quantityBuy.integer' => 'Quantity Buy Item Should be Filled!',
                    'productBuyId.required' => 'Product Buy Id Should be Required!',
                    'productBuyId.integer' => 'Product Buy Id Should be Filled!',
                    'quantityFree.required' => 'Quantity Free Item Should be Required!',
                    'quantityFree.integer' => 'Quantity Free Item Should be Filled!',
                    'productFreeId.required' => 'Product Free Id Should be Required!',
                    'productFreeId.integer' => 'Product Free Id Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateLocation->fails()) {
                $errors = $validateLocation->errors()->first();

                return responseInvalid([$errors]);
            }

            foreach ($ResultLocations as $value) {

                $checkDataBuy = DB::table('products as p')
                    ->join('productLocations as pl', 'p.id', 'pl.productId')
                    ->select('p.id', 'p.fullName')
                    ->where('p.id', '=', $ResultFreeItem['productBuyId'])
                    ->where('pl.locationId', '=', $value)
                    ->first();

                if (!$checkDataBuy) {
                    return responseInvalid(['Product Buy available in selected location!']);
                }

                $chekDataFree = DB::table('products as p')
                    ->join('productLocations as pl', 'p.id', 'pl.productId')
                    ->select('p.id', 'p.fullName')
                    ->where('p.id', '=', $ResultFreeItem['productFreeId'])
                    ->where('pl.locationId', '=', $value)
                    ->first();

                if (!$chekDataFree) {
                    return responseInvalid(['Product Free available in selected location!']);
                }
            }
        } elseif ($request->type == 2) {
            $ResultDiscountProducts = json_decode($request->discountProducts, true);

            if ($ResultDiscountProducts) {
                $validate = Validator::make(
                    $ResultDiscountProducts,
                    [
                        'discountType' => 'required|string',
                        'productId' => 'required|integer',
                        'amount' => 'nullable|numeric',
                        'percent' => 'nullable|numeric',
                        'totalMaxUsage' => 'required|integer',
                        'maxUsagePerCustomer' => 'required|integer',
                    ],
                    [
                        'discountType.required' => 'Percent or Amount Should be Required!',
                        'discountType.string' => 'Percent or Amount Should be Filled!',
                        'productId.required' => 'Product Id Should be Required!',
                        'productId.integer' => 'Product Id Should be Filled!',
                        'amount.numeric' => 'Amount Should be Filled!',
                        'percent.numeric' => 'Percent Should be Filled!',
                        'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                        'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                        'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                        'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                    ]
                );

                if ($validate->fails()) {
                    $errors = $validate->errors()->first();

                    return responseInvalid([$errors]);
                }
            }



            $ResultDiscountServices = json_decode($request->discountServices, true);
            //$ResultDiscountServices = $request->discountServices;

            if ($ResultDiscountServices) {
                $validate = Validator::make(
                    $ResultDiscountServices,
                    [
                        'discountType' => 'required|string',
                        'serviceId' => 'required|integer',
                        'amount' => 'nullable|numeric',
                        'percent' => 'nullable|numeric',
                        'totalMaxUsage' => 'required|integer',
                        'maxUsagePerCustomer' => 'required|integer',
                    ],
                    [

                        'discountType.required' => 'Percent or Amount Should be Required!',
                        'discountType.string' => 'Percent or Amount Should be Filled!',
                        'serviceId.required' => 'Service Id Should be Required!',
                        'serviceId.integer' => 'Service Id Should be Filled!',
                        'amount.numeric' => 'Amount Should be Filled!',
                        'percent.numeric' => 'Percent Should be Filled!',
                        'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                        'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                        'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                        'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                    ]
                );

                if ($validate->fails()) {
                    $errors = $validate->errors()->first();

                    return responseInvalid([$errors]);
                }
            }
        } elseif ($request->type == 3) {

            //$ResultBundle = $request->bundle;
            $ResultBundle = json_decode($request->bundle, true);

            $validateBundle = Validator::make(
                $ResultBundle,
                [
                    'amount' => 'nullable|numeric',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'amount.numeric' => 'Amount Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateBundle->fails()) {
                $errors = $validateBundle->errors()->first();

                return responseInvalid([$errors]);
            }

            $bundleDetailProduct = json_decode($request->bundleDetailProducts, true);
            //$bundleDetailProduct = $request->bundleDetailProducts;

            $bundleDetailService = json_decode($request->bundleDetailServices, true);
            //$bundleDetailService = $request->bundleDetailServices;

            if ($bundleDetailProduct) {
                $validateBundleDetailProduct = Validator::make(
                    $bundleDetailProduct,
                    [
                        '*.productId' => 'nullable|integer',
                        '*.quantity' => 'required|integer',
                    ],
                    [
                        '*.productId.integer' => 'Product Id Should be Filled!',
                        '*.quantity.required' => 'Quantity Should be Required!',
                        '*.quantity.integer' => 'Quantity Should be Filled!',
                    ]
                );

                if ($validateBundleDetailProduct->fails()) {
                    $errors = $validateBundleDetailProduct->errors()->first();

                    return responseInvalid([$errors]);
                }
            }

            if ($bundleDetailService) {

                $validateBundleDetailService = Validator::make(
                    $bundleDetailService,
                    [
                        '*.serviceId' => 'nullable|integer',
                        '*.quantity' => 'required|integer',
                    ],
                    [
                        '*.serviceId.integer' => 'Service Id Should be Filled!',
                        '*.quantity.required' => 'Quantity Should be Required!',
                        '*.quantity.integer' => 'Quantity Should be Filled!',
                    ]
                );

                if ($validateBundleDetailService->fails()) {
                    $errors = $validateBundleDetailService->errors()->first();

                    return responseInvalid([$errors]);
                }
            }
        } elseif ($request->type == 4) {

            $ResultBasedSale = json_decode($request->basedSale, true);
            //$ResultBasedSale = $request->basedSale;

            $validateLocation = Validator::make(
                $ResultBasedSale,
                [
                    'minPurchase' => 'required|integer',
                    'maxPurchase' => 'required|integer',
                    'percentOrAmount' => 'required|string',
                    'amount' => 'nullable|numeric',
                    'percent' => 'nullable|numeric',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'minPurchase.required' => 'Min Purchase Should be Required!',
                    'minPurchase.integer' => 'Min Purchase Should be Filled!',
                    'maxPurchase.required' => 'Max Purchase Should be Required!',
                    'maxPurchase.integer' => 'Max Purchase Should be Filled!',
                    'percentOrAmount.required' => 'Percent or Amount Should be Required!',
                    'percentOrAmount.string' => 'Percent or Amount Should be Filled!',
                    'amount.numeric' => 'Amount Should be Filled!',
                    'percent.numeric' => 'Percent Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateLocation->fails()) {
                $errors = $validateLocation->errors()->first();

                return responseInvalid([$errors]);
            }
        }

        //INSERT
        DB::beginTransaction();
        try {

            $idPromo = PromotionMaster::create([
                'type' => $request->type,
                'name' => $request->name,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'status' => $request->status,
                'userId' => $request->user()->id,
            ]);

            foreach ($ResultLocations as $value) {
                PromotionLocation::create([
                    'promoMasterId' => $idPromo->id,
                    'locationId' => $value,
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($ResultCustGroup as $value) {
                PromotionCustomerGroup::create([
                    'promoMasterId' => $idPromo->id,
                    'customerGroupId' => $value,
                    'userId' => $request->user()->id,
                ]);
            }

            if ($request->type == 1) {

                $dataProdBuy = DB::table('products')
                    ->select('id', 'fullName')
                    ->where('id', '=', $ResultFreeItem['productBuyId'])
                    ->first();

                $dataProdFree = DB::table('products')
                    ->select('id', 'fullName')
                    ->where('id', '=', $ResultFreeItem['productFreeId'])
                    ->first();

                foreach ($ResultLocations as $value) {

                    $listProdBuy = DB::table('products')
                        ->join('productLocations as pl', 'products.id', 'pl.productId')
                        ->select('products.id', 'products.fullName')
                        ->where('fullName', '=', $dataProdBuy->fullName)
                        ->where('pl.locationId', '=', $value)
                        ->first();

                    //free

                    $listProdFree = DB::table('products')
                        ->join('productLocations as pl', 'products.id', 'pl.productId')
                        ->select('products.id', 'products.fullName')
                        ->where('fullName', '=', $dataProdFree->fullName)
                        ->where('pl.locationId', '=', $value)
                        ->first();

                    PromotionFreeItem::create([
                        'promoMasterId' => $idPromo->id,
                        'quantityBuyItem' => $ResultFreeItem['quantityBuy'],
                        'productBuyId' => $listProdBuy->id,
                        'quantityFreeItem' => $ResultFreeItem['quantityFree'],
                        'productFreeId' => $listProdFree->id,
                        'totalMaxUsage' => $ResultFreeItem['totalMaxUsage'],
                        'maxUsagePerCustomer' => $ResultFreeItem['maxUsagePerCustomer'],
                        'userId' => $request->user()->id,
                    ]);
                }
            } elseif ($request->type == 2) {

                if ($ResultDiscountProducts) {
                    $dataProd = DB::table('products')
                        ->select('id', 'fullName')
                        ->where('id', '=', $ResultDiscountProducts['productId'])
                        ->where('isDeleted', '=', 0)
                        ->first();

                    foreach ($ResultLocations as $value) {

                        $listProd = DB::table('products')
                            ->join('productLocations as pl', 'products.id', 'pl.productId')
                            ->select('products.id', 'products.fullName')
                            ->where('fullName', '=', $dataProd->fullName)
                            ->where('pl.locationId', '=', $value)
                            ->first();

                        $percent = 0;
                        $amount = 0;

                        if ($ResultDiscountProducts['discountType'] == 'percent') {
                            $percent = $ResultDiscountProducts['percent'];
                        } else {
                            $amount = $ResultDiscountProducts['amount'];
                        }

                        promotion_discount_product::create([
                            'promoMasterId' => $idPromo->id,
                            'discountType' => $ResultDiscountProducts['discountType'],
                            'productId' => $listProd->id,
                            'amount' => $amount,
                            'percent' => $percent,
                            'totalMaxUsage' => $ResultDiscountProducts['totalMaxUsage'],
                            'maxUsagePerCustomer' => $ResultDiscountProducts['maxUsagePerCustomer'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                if ($ResultDiscountServices) {
                    $dataService = DB::table('services')
                        ->select('id', 'fullName')
                        ->where('id', '=', $ResultDiscountServices['serviceId'])
                        ->where('isDeleted', '=', 0)
                        ->first();

                    foreach ($ResultLocations as $value) {

                        $listService = DB::table('services as s')
                            ->join('servicesLocation as sl', 's.id', 'sl.service_id')
                            ->select('s.id', 's.fullName')
                            ->where('fullName', '=', $dataService->fullName)
                            ->where('sl.location_id', '=', $value)
                            ->first();

                        $percent = 0;
                        $amount = 0;

                        if ($ResultDiscountServices['discountType'] == 'percent') {
                            $percent = $ResultDiscountServices['percent'];
                        } else {
                            $amount = $ResultDiscountServices['amount'];
                        }

                        promotion_discount_services::create([
                            'promoMasterId' => $idPromo->id,
                            'discountType' => $ResultDiscountServices['discountType'],
                            'serviceId' => $listService->id,
                            'amount' => $amount,
                            'percent' => $percent,
                            'totalMaxUsage' => $ResultDiscountServices['totalMaxUsage'],
                            'maxUsagePerCustomer' => $ResultDiscountServices['maxUsagePerCustomer'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }
            } elseif ($request->type == 3) {

                $idBundle = PromotionBundle::create([
                    'promoMasterId' => $idPromo->id,
                    'price' => $ResultBundle['price'],
                    'totalMaxUsage' => $ResultBundle['totalMaxUsage'],
                    'maxUsagePerCustomer' => $ResultBundle['maxUsagePerCustomer'],
                    'userId' => $request->user()->id,
                ]);

                if ($bundleDetailProduct) {
                    foreach ($bundleDetailProduct as $res) {

                        promotion_bundle_detail_products::create([
                            'promoBundleId' => $idBundle->id,
                            'productId' => $res['productId'],
                            'quantity' => $res['quantity'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                if ($bundleDetailService) {

                    foreach ($bundleDetailService as $res) {

                        promotion_bundle_detail_services::create([
                            'promoBundleId' => $idBundle->id,
                            'serviceId' => $res['serviceId'],
                            'quantity' => $res['quantity'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }
            } elseif ($request->type == 4) {

                $percent = 0;
                $amount = 0;

                if ($ResultBasedSale['percentOrAmount'] == 'percent') {
                    $percent = $ResultBasedSale['percent'];
                } else {
                    $amount = $ResultBasedSale['amount'];
                }

                PromotionBasedSales::create([
                    'promoMasterId' => $idPromo->id,
                    'minPurchase' => $ResultBasedSale['minPurchase'],
                    'maxPurchase' => $ResultBasedSale['maxPurchase'],
                    'percentOrAmount' => $ResultBasedSale['percentOrAmount'],
                    'amount' => $amount,
                    'percent' => $percent,
                    'totalMaxUsage' => $ResultBasedSale['totalMaxUsage'],
                    'maxUsagePerCustomer' => $ResultBasedSale['maxUsagePerCustomer'],
                    'userId' => $request->user()->id,
                ]);
            }

            DB::commit();

            recentActivity(
                $request->user()->id,
                'Promotion',
                'Create Promotion',
                'Create new promotion' . $request->name
            );
            return responseCreate();
        } catch (Exception $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('promotionMasters as pm')
            ->join('promotionTypes as pt', 'pm.type', 'pt.id')
            ->join('promotionLocations as pl', 'pl.promoMasterId', 'pm.id')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.id as id',
                'pm.name',
                'pt.typeName as type',
                DB::raw("DATE_FORMAT(pm.startDate, '%d/%m/%Y') as startDate"),
                DB::raw("DATE_FORMAT(pm.endDate, '%d/%m/%Y') as endDate"),
                DB::raw("CASE WHEN pm.status = 1 then 'Active' ELSE 'Inactive' END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('pl.locationId', $request->locationId);
        }

        if ($request->type) {

            $data = $data->whereIn('pm.type', $request->type);
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

        $data = $data->groupBy(
            'pm.id',
            'pm.name',
            'pt.typeName',
            'pm.startDate',
            'pm.endDate',
            'pm.status',
            'pm.created_at',
            'u.firstName',
        );

        $data = $data->orderBy('pm.updated_at', 'desc');

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

    private function Search($request)
    {
        $temp_column = null;

        $data = DB::table('promotionMasters as pm')
            ->select(
                'pm.name'
            )
            ->where('pm.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pm.name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pm.name';
        }

        $data = DB::table('promotionMasters as pm')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'u.firstName'
            )
            ->where('pm.isDeleted', '=', 0);

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
        $data = DB::table('promotionMasters as pm')
            ->join('promotionTypes as pt', 'pm.type', 'pt.id')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.id as id',
                'pm.name',
                'pm.type as typeId',
                'pt.typeName as type',
                DB::raw("DATE_FORMAT(pm.startDate, '%Y-%m-%d') as startDate"),
                DB::raw("DATE_FORMAT(pm.endDate, '%Y-%m-%d') as endDate"),
                DB::raw("CASE WHEN pm.status = 1 then 'Active' ELSE 'Inactive' END as status"),
                'pm.status as statusId',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.id', '=', $request->id)
            ->first();


        $dataLoc = DB::table('promotionLocations as pl')
            ->join('location as l', 'pl.locationId', 'l.id')
            ->select('l.id', 'l.locationName')
            ->where('pl.promoMasterId', '=', $request->id)
            ->distinct()
            ->get();

        $dataCust = DB::table('promotionCustomerGroups as pc')
            ->join('customerGroups as cg', 'pc.customerGroupId', 'cg.id')
            ->select('cg.id', 'cg.customerGroup')
            ->where('pc.promoMasterId', '=', $request->id)
            ->distinct()
            ->get();

        $data->location = $dataLoc;
        $data->customerGroup = $dataCust;

        if ($data->typeId == 1) {

            $temp = DB::table('promotionFreeItems as pf')
                ->where('pf.promoMasterId', '=', $request->id)
                ->first();

            $dataProdBuy = DB::table('products')
                ->select('fullName', 'category')
                ->where('id', '=', $temp->productBuyId)
                ->first();

            $dataProdFree = DB::table('products')
                ->select('fullName', 'category')
                ->where('id', '=', $temp->productFreeId)
                ->first();

            $data->quantityBuyItem = $temp->quantityBuyItem;
            $data->productBuyType = $dataProdBuy->category;
            $data->productBuyId = $temp->productBuyId;
            $data->productBuyName = $dataProdBuy->fullName;

            $data->quantityFreeItem = $temp->quantityFreeItem;
            $data->productFreeType = $dataProdFree->category;
            $data->productFreeId = $temp->productFreeId;
            $data->productFreeName = $dataProdFree->fullName;

            $data->totalMaxUsage = $temp->totalMaxUsage;
            $data->maxUsagePerCustomer = $temp->maxUsagePerCustomer;
        } elseif ($data->typeId == 2) {

            $check_discount_product = DB::table('promotion_discount_products')
                ->where('promoMasterId', '=', $request->id)
                ->first();

            if ($check_discount_product) {
                $temp = DB::table('promotion_discount_products as pd')
                    ->where('pd.promoMasterId', '=', $request->id)
                    ->first();
                $temp->productOrService = 'product';
            } else {
                $temp = DB::table('promotion_discount_services as pd')
                    ->where('pd.promoMasterId', '=', $request->id)
                    ->first();
                $temp->productOrService = 'service';
            }

            // $temp = DB::table('promotionDiscounts as pd')
            //     ->where('pd.promoMasterId', '=', $request->id)
            //     ->first();

            if ($temp->productOrService == 'product') {

                $dataProd = DB::table('products as p')
                    ->select('p.fullName', 'p.category')
                    ->where('id', '=', $temp->productId)
                    ->first();

                $data->productId = $temp->productId;
                $data->productName = $dataProd->fullName;
                $data->productType = $dataProd->category;
            } elseif ($temp->productOrService == 'service') {
                $dataService = DB::table('services')
                    ->select('fullName')
                    ->where('id', '=', $temp->serviceId)
                    ->first();

                $data->serviceId = $temp->serviceId;
                $data->serviceName = $dataService->fullName;
            }

            $data->productOrService = $temp->productOrService;

            if ($temp->discountType == 'percent') {
                $data->percent = $temp->percent;
            } elseif ($temp->discountType == 'amount') {
                $data->amount = $temp->amount;
            }

            $data->discountType = $temp->discountType;
            $data->totalMaxUsage = $temp->totalMaxUsage;
            $data->maxUsagePerCustomer = $temp->maxUsagePerCustomer;
        } elseif ($data->typeId == 3) {
            $temp = DB::table('promotionBundles as pb')
                ->where('pb.promoMasterId', '=', $request->id)
                ->first();

            $customList = [];

            $temp1 = DB::table('promotion_bundle_detail_products as pbdp')
                ->where('pbdp.promoBundleId', '=', $temp->id)
                ->get();

            $temp2 = DB::table('promotion_bundle_detail_services as pbds')
                ->where('pbds.promoBundleId', '=', $temp->id)
                ->get();

            foreach ($temp1 as $value) {

                $dataProd = DB::table('products as p')
                    ->select('p.fullName', 'p.category')
                    ->where('id', '=', $value->productId)
                    ->first();

                $tempList = [
                    'productOrService' => 'product',
                    'productType' => $dataProd->category,
                    'productId' => $value->productId,
                    'productName' => $dataProd->fullName,
                    'quantity' => $value->quantity,
                ];

                $customList[] = $tempList;
            }

            foreach ($temp2 as $value) {
                $dataService = DB::table('services')
                    ->select('fullName')
                    ->where('id', '=', $value->serviceId)
                    ->first();

                $tempList = [
                    'productOrService' => 'service',
                    'serviceId' => $value->serviceId,
                    'serviceName' => $dataService->fullName,
                    'quantity' => $value->quantity,
                ];

                $customList[] = $tempList;
            }

            $data->bundles = $customList;

            $data->price = $temp->price;
            $data->totalMaxUsage = $temp->totalMaxUsage;
            $data->maxUsagePerCustomer = $temp->maxUsagePerCustomer;
        } elseif ($data->typeId == 4) {

            $temp = DB::table('promotionMasters as pm')
                ->join('promotionBasedSales as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotionTypes as pt', 'pm.type', 'pt.id')
                ->join('users as u', 'pm.userId', 'u.id')
                ->select(
                    'pm.id as id',
                    'pm.name',
                    'pm.type as typeId',
                    'pt.typeName as type',
                    DB::raw("DATE_FORMAT(pm.startDate, '%Y-%m-%d') as startDate"),
                    DB::raw("DATE_FORMAT(pm.endDate, '%Y-%m-%d') as endDate"),
                    DB::raw("CASE WHEN pm.status = 1 then 'Active' ELSE 'Inactive' END as status"),
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                    'pb.minPurchase',
                    'pb.maxPurchase',
                    'pb.percentOrAmount',
                    'pb.totalMaxUsage',
                    'pb.maxUsagePerCustomer',
                )
                ->where('pm.id', '=', $request->id)
                ->first();

            if ($temp->percentOrAmount == 'percent') {

                $temp_two = DB::table('promotionBasedSales as pb')
                    ->select(
                        'pb.percent'
                    )
                    ->where('pb.promoMasterId', '=', $request->id)
                    ->first();

                $temp->percent = $temp_two->percent;
            } elseif ($temp->percentOrAmount == 'amount') {

                $temp_two = DB::table('promotionBasedSales as pb')
                    ->select(
                        'pb.amount'
                    )
                    ->where('pb.promoMasterId', '=', $request->id)
                    ->first();

                $temp->amount = $temp_two->amount;
            }

            $data->basedSales = $temp;
        }

        return response()->json($data, 200);
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'type' => 'required|integer|in:1,2,3,4',
            'name' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'status' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $promo = PromotionMaster::find($request->id);

        if (!$promo) {
            return responseInvalid(['Promotion not found.']);
        }

        if (!$request->locations) {

            return responseInvalid(['Location cannot be empty!']);
        }

        if (is_string($request->locations)) {
            $ResultLocations = json_decode($request->locations, true);
        } elseif (is_array($request->locations)) {
            $ResultLocations = $request->locations;
        }

        if (is_string($request->customerGroups)) {
            $ResultCustGroup = json_decode($request->customerGroups, true);
        } elseif (is_array($request->customerGroups)) {
            $ResultCustGroup = $request->customerGroups;
        }

        if ($request->type == 1) {

            if (is_array($request->freeItem)) {

                $ResultFreeItem = $request->freeItem;
            } else {
                $ResultFreeItem = json_decode($request->freeItem, true);
            }

            $validateLocation = Validator::make(
                $ResultFreeItem,
                [
                    'quantityBuy' => 'required|integer',
                    'productBuyId' => 'required|integer',
                    'quantityFree' => 'required|integer',
                    'productFreeId' => 'required|integer',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'quantityBuy.required' => 'Quantity Buy Item Should be Required!',
                    'quantityBuy.integer' => 'Quantity Buy Item Should be Filled!',
                    'productBuyId.required' => 'Product Buy Id Should be Required!',
                    'productBuyId.integer' => 'Product Buy Id Should be Filled!',
                    'quantityFree.required' => 'Quantity Free Item Should be Required!',
                    'quantityFree.integer' => 'Quantity Free Item Should be Filled!',
                    'productFreeId.required' => 'Product Free Id Should be Required!',
                    'productFreeId.integer' => 'Product Free Id Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateLocation->fails()) {
                $errors = $validateLocation->errors()->first();

                return responseInvalid([$errors]);
            }

            foreach ($ResultLocations as $value) {

                $checkDataBuy = DB::table('products as p')
                    ->join('productLocations as pl', 'p.id', 'pl.productId')
                    ->select('p.id', 'p.fullName')
                    ->where('p.id', '=', $ResultFreeItem['productBuyId'])
                    ->where('pl.locationId', '=', $value)
                    ->first();

                if (!$checkDataBuy) {
                    return responseInvalid(['Product Buy available in selected location!']);
                }

                $chekDataFree = DB::table('products as p')
                    ->join('productLocations as pl', 'p.id', 'pl.productId')
                    ->select('p.id', 'p.fullName')
                    ->where('p.id', '=', $ResultFreeItem['productFreeId'])
                    ->where('pl.locationId', '=', $value)
                    ->first();

                if (!$chekDataFree) {
                    return responseInvalid(['Product Free available in selected location!']);
                }
            }
        } elseif ($request->type == 2) {

            if (is_array($request->discountProducts)) {

                $ResultDiscountProducts = $request->discountProducts;
            } else {
                $ResultDiscountProducts = json_decode($request->discountProducts, true);
            }

            if ($ResultDiscountProducts) {
                $validate = Validator::make(
                    $ResultDiscountProducts,
                    [
                        'discountType' => 'required|string',
                        'productId' => 'required|integer',
                        'amount' => 'nullable|numeric',
                        'percent' => 'nullable|numeric',
                        'totalMaxUsage' => 'required|integer',
                        'maxUsagePerCustomer' => 'required|integer',
                    ],
                    [
                        'discountType.required' => 'Percent or Amount Should be Required!',
                        'discountType.string' => 'Percent or Amount Should be Filled!',
                        'productId.required' => 'Product Id Should be Required!',
                        'productId.integer' => 'Product Id Should be Filled!',
                        'amount.numeric' => 'Amount Should be Filled!',
                        'percent.numeric' => 'Percent Should be Filled!',
                        'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                        'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                        'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                        'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                    ]
                );

                if ($validate->fails()) {
                    $errors = $validate->errors()->first();

                    return responseInvalid([$errors]);
                }
            }

            if (is_array($request->discountServices)) {

                $ResultDiscountServices = $request->discountServices;
            } else {
                $ResultDiscountServices = json_decode($request->discountServices, true);
            }

            if ($ResultDiscountServices) {
                $validate = Validator::make(
                    $ResultDiscountServices,
                    [
                        'discountType' => 'required|string',
                        'serviceId' => 'required|integer',
                        'amount' => 'nullable|numeric',
                        'percent' => 'nullable|numeric',
                        'totalMaxUsage' => 'required|integer',
                        'maxUsagePerCustomer' => 'required|integer',
                    ],
                    [

                        'discountType.required' => 'Percent or Amount Should be Required!',
                        'discountType.string' => 'Percent or Amount Should be Filled!',
                        'serviceId.required' => 'Service Id Should be Required!',
                        'serviceId.integer' => 'Service Id Should be Filled!',
                        'amount.numeric' => 'Amount Should be Filled!',
                        'percent.numeric' => 'Percent Should be Filled!',
                        'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                        'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                        'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                        'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                    ]
                );

                if ($validate->fails()) {
                    $errors = $validate->errors()->first();

                    return responseInvalid([$errors]);
                }
            }
        } elseif ($request->type == 3) {

            //$ResultBundle = $request->bundle;
            // $ResultBundle = json_decode($request->bundle, true);
            if (is_array($request->bundle)) {

                $ResultBundle = $request->bundle;
            } else {
                $ResultBundle = json_decode($request->bundle, true);
            }

            $validateBundle = Validator::make(
                $ResultBundle,
                [
                    'amount' => 'nullable|numeric',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'amount.numeric' => 'Amount Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateBundle->fails()) {
                $errors = $validateBundle->errors()->first();

                return responseInvalid([$errors]);
            }

            if (is_array($request->bundleDetailProducts)) {

                $bundleDetailProduct = $request->bundleDetailProducts;
            } else {
                $bundleDetailProduct = json_decode($request->bundleDetailProducts, true);
            }

            if (is_array($request->bundleDetailServices)) {

                $bundleDetailService = $request->bundleDetailServices;
            } else {
                $bundleDetailService = json_decode($request->bundleDetailServices, true);
            }

            if ($bundleDetailProduct) {
                $validateBundleDetailProduct = Validator::make(
                    $bundleDetailProduct,
                    [
                        '*.productId' => 'nullable|integer',
                        '*.quantity' => 'required|integer',
                    ],
                    [
                        '*.productId.integer' => 'Product Id Should be Filled!',
                        '*.quantity.required' => 'Quantity Should be Required!',
                        '*.quantity.integer' => 'Quantity Should be Filled!',
                    ]
                );

                if ($validateBundleDetailProduct->fails()) {
                    $errors = $validateBundleDetailProduct->errors()->first();

                    return responseInvalid([$errors]);
                }
            }

            if ($bundleDetailService) {

                $validateBundleDetailService = Validator::make(
                    $bundleDetailService,
                    [
                        '*.serviceId' => 'nullable|integer',
                        '*.quantity' => 'required|integer',
                    ],
                    [
                        '*.serviceId.integer' => 'Service Id Should be Filled!',
                        '*.quantity.required' => 'Quantity Should be Required!',
                        '*.quantity.integer' => 'Quantity Should be Filled!',
                    ]
                );

                if ($validateBundleDetailService->fails()) {
                    $errors = $validateBundleDetailService->errors()->first();

                    return responseInvalid([$errors]);
                }
            }
        } elseif ($request->type == 4) {

            // $ResultBasedSale = json_decode($request->basedSale, true);
            if (is_array($request->basedSale)) {

                $ResultBasedSale = $request->basedSale;
            } else {
                $ResultBasedSale = json_decode($request->basedSale, true);
            }
            // $ResultBasedSale = json_decode($request->basedSale, true);
            //$ResultBasedSale = $request->basedSale;

            $validateLocation = Validator::make(
                $ResultBasedSale,
                [
                    'minPurchase' => 'required|integer',
                    'maxPurchase' => 'required|integer',
                    'percentOrAmount' => 'required|string',
                    'amount' => 'nullable|numeric',
                    'percent' => 'nullable|numeric',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'minPurchase.required' => 'Min Purchase Should be Required!',
                    'minPurchase.integer' => 'Min Purchase Should be Filled!',
                    'maxPurchase.required' => 'Max Purchase Should be Required!',
                    'maxPurchase.integer' => 'Max Purchase Should be Filled!',
                    'percentOrAmount.required' => 'Percent or Amount Should be Required!',
                    'percentOrAmount.string' => 'Percent or Amount Should be Filled!',
                    'amount.numeric' => 'Amount Should be Filled!',
                    'percent.numeric' => 'Percent Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateLocation->fails()) {
                $errors = $validateLocation->errors()->first();

                return responseInvalid([$errors]);
            }
        }


        DB::beginTransaction();
        try {

            PromotionLocation::where('promoMasterId', $request->id)->delete();
            foreach ($ResultLocations as $value) {
                PromotionLocation::create([
                    'promoMasterId' => $request->id,
                    'locationId' => $value,
                    'userId' => $request->user()->id,
                ]);
            }

            PromotionCustomerGroup::where('promoMasterId', $request->id)->delete();
            foreach ($ResultCustGroup as $value) {
                PromotionCustomerGroup::create([
                    'promoMasterId' => $request->id,
                    'customerGroupId' => $value,
                    'userId' => $request->user()->id,
                ]);
            }

            if ($request->type == 1) {

                $dataProdBuy = DB::table('products')
                    ->select('id', 'fullName')
                    ->where('id', '=', $ResultFreeItem['productBuyId'])
                    ->first();

                $dataProdFree = DB::table('products')
                    ->select('id', 'fullName')
                    ->where('id', '=', $ResultFreeItem['productFreeId'])
                    ->first();

                PromotionFreeItem::where('promoMasterId', $request->id)->delete();
                foreach ($ResultLocations as $value) {

                    $listProdBuy = DB::table('products')
                        ->join('productLocations as pl', 'products.id', 'pl.productId')
                        ->select('products.id', 'products.fullName')
                        ->where('fullName', '=', $dataProdBuy->fullName)
                        ->where('pl.locationId', '=', $value)
                        ->first();

                    //free

                    $listProdFree = DB::table('products')
                        ->join('productLocations as pl', 'products.id', 'pl.productId')
                        ->select('products.id', 'products.fullName')
                        ->where('fullName', '=', $dataProdFree->fullName)
                        ->where('pl.locationId', '=', $value)
                        ->first();

                    PromotionFreeItem::create([
                        'promoMasterId' => $request->id,
                        'quantityBuyItem' => $ResultFreeItem['quantityBuy'],
                        'productBuyId' => $listProdBuy->id,
                        'quantityFreeItem' => $ResultFreeItem['quantityFree'],
                        'productFreeId' => $listProdFree->id,
                        'totalMaxUsage' => $ResultFreeItem['totalMaxUsage'],
                        'maxUsagePerCustomer' => $ResultFreeItem['maxUsagePerCustomer'],
                        'userId' => $request->user()->id,
                    ]);
                }
            } elseif ($request->type == 2) {

                if ($ResultDiscountProducts) {

                    Promotion_discount_product::where('promoMasterId', $request->id)->delete();
                    $dataProd = DB::table('products')
                        ->select('id', 'fullName')
                        ->where('id', '=', $ResultDiscountProducts['productId'])
                        ->where('isDeleted', '=', 0)
                        ->first();

                    foreach ($ResultLocations as $value) {

                        $listProd = DB::table('products')
                            ->join('productLocations as pl', 'products.id', 'pl.productId')
                            ->select('products.id', 'products.fullName')
                            ->where('fullName', '=', $dataProd->fullName)
                            ->where('pl.locationId', '=', $value)
                            ->first();

                        $percent = 0;
                        $amount = 0;

                        if ($ResultDiscountProducts['discountType'] == 'percent') {
                            $percent = $ResultDiscountProducts['percent'];
                        } else {
                            $amount = $ResultDiscountProducts['amount'];
                        }

                        promotion_discount_product::create([
                            'promoMasterId' => $request->id,
                            'discountType' => $ResultDiscountProducts['discountType'],
                            'productId' => $listProd->id,
                            'amount' => $amount,
                            'percent' => $percent,
                            'totalMaxUsage' => $ResultDiscountProducts['totalMaxUsage'],
                            'maxUsagePerCustomer' => $ResultDiscountProducts['maxUsagePerCustomer'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                if ($ResultDiscountServices) {

                    Promotion_discount_services::where('promoMasterId', $request->id)->delete();

                    $dataService = DB::table('services')
                        ->select('id', 'fullName')
                        ->where('id', '=', $ResultDiscountServices['serviceId'])
                        ->where('isDeleted', '=', 0)
                        ->first();

                    foreach ($ResultLocations as $value) {

                        $listService = DB::table('services as s')
                            ->join('servicesLocation as sl', 's.id', 'sl.service_id')
                            ->select('s.id', 's.fullName')
                            ->where('fullName', '=', $dataService->fullName)
                            ->where('sl.location_id', '=', $value)
                            ->first();

                        $percent = 0;
                        $amount = 0;

                        if ($ResultDiscountServices['discountType'] == 'percent') {
                            $percent = $ResultDiscountServices['percent'];
                        } else {
                            $amount = $ResultDiscountServices['amount'];
                        }

                        promotion_discount_services::create([
                            'promoMasterId' => $request->id,
                            'discountType' => $ResultDiscountServices['discountType'],
                            'serviceId' => $listService->id,
                            'amount' => $amount,
                            'percent' => $percent,
                            'totalMaxUsage' => $ResultDiscountServices['totalMaxUsage'],
                            'maxUsagePerCustomer' => $ResultDiscountServices['maxUsagePerCustomer'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }
            } elseif ($request->type == 3) {

                $bundle = PromotionBundle::where('promoMasterId', $request->id)->first();
                if ($bundle) {
                    PromotionBundle::where('promoMasterId', $request->id)->delete();
                }
                $idBundle = PromotionBundle::create([
                    'promoMasterId' => $request->id,
                    'price' => $ResultBundle['price'],
                    'totalMaxUsage' => $ResultBundle['totalMaxUsage'],
                    'maxUsagePerCustomer' => $ResultBundle['maxUsagePerCustomer'],
                    'userId' => $request->user()->id,
                ]);

                if ($bundleDetailProduct) {
                    promotion_bundle_detail_products::where('promoBundleId', $bundle->id)->delete();
                    foreach ($bundleDetailProduct as $res) {

                        promotion_bundle_detail_products::create([
                            'promoBundleId' => $idBundle->id,
                            'productId' => $res['productId'],
                            'quantity' => $res['quantity'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }

                if ($bundleDetailService) {
                    promotion_bundle_detail_services::where('promoBundleId', $bundle->id)->delete();
                    foreach ($bundleDetailService as $res) {

                        promotion_bundle_detail_services::create([
                            'promoBundleId' => $idBundle->id,
                            'serviceId' => $res['serviceId'],
                            'quantity' => $res['quantity'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }
            } elseif ($request->type == 4) {

                PromotionBasedSales::where('promoMasterId', $request->id)->delete();

                $percent = 0;
                $amount = 0;

                if ($ResultBasedSale['percentOrAmount'] == 'percent') {
                    $percent = $ResultBasedSale['percent'];
                } else {
                    $amount = $ResultBasedSale['amount'];
                }

                PromotionBasedSales::create([
                    'promoMasterId' => $request->id,
                    'minPurchase' => $ResultBasedSale['minPurchase'],
                    'maxPurchase' => $ResultBasedSale['maxPurchase'],
                    'percentOrAmount' => $ResultBasedSale['percentOrAmount'],
                    'amount' => $amount,
                    'percent' => $percent,
                    'totalMaxUsage' => $ResultBasedSale['totalMaxUsage'],
                    'maxUsagePerCustomer' => $ResultBasedSale['maxUsagePerCustomer'],
                    'userId' => $request->user()->id,
                ]);
            }

            $promo->type = $request->type;
            $promo->name = $request->name;
            $promo->startDate = $request->startDate;
            $promo->endDate = $request->endDate;
            $promo->status = $request->status;
            $promo->userUpdateId = $request->user()->id;
            $promo->updated_at = now();
            $promo->save();

            // ✅ recentActivity log
            recentActivity(
                $request->user()->id,
                'Promotion',
                'Update Promotion',
                'Updated Promotion "' . $promo->name . '" (ID: ' . $promo->id . ')'
            );

            DB::commit();
            return response()->json([
                'message' => 'Promotion updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update promotion.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        foreach ($request->id as $va) {
            $res = PromotionMaster::find($va);
            if (!$res) {
                return responseInvalid(['Promotion with ID ' . $va . ' not found.']);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->id as $va) {
                $res = PromotionMaster::find($va);
                $userId = $request->user()->id;

                if ($res->type == 1) {
                    PromotionFreeItem::where('promoMasterId', $res->id)
                        ->update([
                            'deletedBy' => $userId,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]);
                } elseif ($res->type == 2) {
                    PromotionDiscount::where('promoMasterId', $res->id)
                        ->update([
                            'deletedBy' => $userId,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]);
                } elseif ($res->type == 3) {
                    $bundle = PromotionBundle::where('promoMasterId', $res->id)->first();

                    if ($bundle) {
                        promotion_bundle_detail_products::where('promoBundleId', $bundle->id)
                            ->update([
                                'deletedBy' => $userId,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]);

                        promotion_bundle_detail_services::where('promoBundleId', $bundle->id)
                            ->update([
                                'deletedBy' => $userId,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]);

                        PromotionBundle::where('promoMasterId', $res->id)
                            ->update([
                                'deletedBy' => $userId,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]);
                    }
                } elseif ($res->type == 4) {
                    PromotionBasedSales::where('promoMasterId', $res->id)
                        ->update([
                            'deletedBy' => $userId,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]);
                }

                PromotionMaster::where('id', $res->id)
                    ->update([
                        'deletedBy' => $userId,
                        'isDeleted' => 1,
                        'deletedAt' => Carbon::now()
                    ]);

                recentActivity(
                    $userId,
                    'Promotion',
                    'Delete Promotion',
                    'Deleted Promotion: "' . $res->name . '" (Type ' . $res->type . ') with ID ' . $res->id
                );
            }

            DB::commit();
            return response()->json([
                'message' => 'Delete Data Successful',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Delete Failed',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function listType()
    {
        $data = DB::table('promotionTypes')
            ->select('id', 'typeName')
            ->get();

        return response()->json($data, 200);
    }

    function export(Request $request)
    {
        $spreadsheet = IOFactory::load(public_path() . '/template/' . 'Template_Export_Discount.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $data = DB::table('promotionMasters as pm')
            ->join('promotionTypes as pt', 'pm.type', 'pt.id')
            ->join('promotionLocations as pl', 'pl.promoMasterId', 'pm.id')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.id as id',
                'pm.name',
                'pt.typeName as type',
                DB::raw("DATE_FORMAT(pm.startDate, '%d/%m/%Y') as startDate"),
                DB::raw("DATE_FORMAT(pm.endDate, '%d/%m/%Y') as endDate"),
                DB::raw("CASE WHEN pm.status = 1 then 'Active' ELSE 'Inactive' END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.isDeleted', '=', 0);

        $locations = $request->locationId;

        if (count($locations) > 0) {
            if (!$locations[0] == null) {
                $data = $data->whereIn('pl.locationId', $locations);
            }
        }

        $types = $request->type;

        if (count($types) > 0) {
            if (!$types[0] == null) {
                $data = $data->whereIn('pm.type', $types);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->groupBy(
            'pm.id',
            'pm.name',
            'pt.typeName',
            'pm.startDate',
            'pm.endDate',
            'pm.status',
            'pm.created_at',
            'u.firstName',
        );

        $data = $data->orderBy('pm.updated_at', 'desc')->get();

        $row = 2;
        $cnt = 1;
        foreach ($data as $item) {
            // Adjust according to your data structure
            $sheet->setCellValue("A{$row}", $cnt);
            $sheet->setCellValue("B{$row}", $item->name);
            $sheet->setCellValue("C{$row}", $item->type);
            $sheet->setCellValue("D{$row}", $item->startDate);
            $sheet->setCellValue("E{$row}", $item->endDate);
            $sheet->setCellValue("F{$row}", $item->status);
            $sheet->setCellValue("G{$row}", $item->createdBy);
            $sheet->setCellValue("H{$row}", $item->createdAt);
            // Add more columns as needed
            $cnt++;
            $row++;
        }

        $fileName = "";
        $location = "";
        $type = "";

        if (count($locations) > 0) {
            if (!$locations[0] == null) {
                $dataLocation = DB::table('location as l')
                    ->select(DB::raw("GROUP_CONCAT(l.locationName SEPARATOR ', ') as location"))
                    ->whereIn('l.id', $request->locationId)
                    ->distinct()
                    ->pluck('location')
                    ->first();

                $location = " " . $dataLocation;
            }
        }

        if (count($types) > 0) {
            if (!$types[0] == null) {

                $dataType = DB::table('promotionTypes')
                    ->select(DB::raw("GROUP_CONCAT(typeName SEPARATOR ', ') as typeName"))
                    ->whereIn('id', $request->locationId)
                    ->distinct()
                    ->pluck('typeName')
                    ->first();

                $type = " " . $dataType;
            }
        }

        //buat ini karena terdapat _ di akhir filename saat didownload di server
        if ($location == "" && $type == "") {
            $fileName = "Rekap Diskon.xlsx";
        } else {
            $fileName = "Rekap Diskon" . $location . $type . ".xlsx";
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . $fileName; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    function checkPromo(Request $request)
    {
        $data = json_decode($request->transactions, true);

        $custGroup = '';
        if (!is_null($request->customerId)) {
            $cust = Customer::find($request->customerId);
            $custGroup = $cust->customerGroupId;
        }

        $tempFree = [];

        foreach ($data as $value) {

            $res = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                ->select(
                    'pm.id',
                    'pm.name',
                    DB::raw("CONCAT('Pembelian ', fi.quantityBuyItem, ' ',pbuy.fullName,' gratis ',fi.quantityFreeItem,' ',pfree.fullName) as note")
                )
                ->where('pl.locationId', '=', $value['locationId'])
                ->where('fi.productBuyId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempFree = array_merge($tempFree, $res);
        }

        $tempDiscount = [];

        foreach ($data as $value) {

            $res = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->select(
                    'pm.id',
                    'pm.name',
                    DB::raw("
                            CONCAT(
                                'Pembelian Produk ',
                                p.fullName,
                                CASE
                                    WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%')
                                    WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount)
                                    ELSE ''
                                END
                            ) as note
                        ")

                )
                ->where('pl.locationId', '=', $value['locationId'])
                ->where('pd.productId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempDiscount = array_merge($tempDiscount, $res);
        }

        //$tempDiscount = array_merge($tempDiscount, $res);
        $resultBundle = [];

        foreach ($data as $value) {
            // return $value;
            $res = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotionBundleDetails as pbd', 'pb.id', 'pbd.promoBundleId')
                ->join('products as p', 'p.id', 'pbd.productId')
                ->select(
                    'pbd.promoBundleId',
                    'pm.name',
                )
                ->where('pl.locationId', '=', $value['locationId'])
                ->where('pbd.productId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get();

            foreach ($res as $valdtl) {

                $data = DB::table('promotionBundleDetails as b')
                    ->join('products as p', 'p.id', 'b.productId')
                    ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                    ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                    ->select('pb.id', 'p.fullName', 'b.quantity', 'pb.price', 'm.name')
                    ->where('b.promoBundleId', '=', $valdtl->promoBundleId)
                    ->get();
                $kalimat = 'paket bundling produk ';

                for ($i = 0; $i < count($data); $i++) {

                    if (count($data) == 1) {
                        $kalimat .= $data[$i]->quantity . ' ' . $data[$i]->fullName;
                    } else {
                        if ($i == count($data) - 1) {
                            $kalimat .= 'dan ' . $data[$i]->quantity . ' ' . $data[$i]->fullName;
                        } else {
                            $kalimat .= $data[$i]->quantity . ' ' . $data[$i]->fullName . ', ';
                        }
                    }
                }

                $kalimat .= ' sebesar Rp ' . $data[0]->price;

                $resultBundle[] = [
                    'id' => $data[0]->id,
                    'note' => $kalimat,
                    'name' => $data[0]->name
                ];
            }
        }

        $data = json_decode($request->transactions, true);

        $resultBasedSales = [];

        $totalTransaction = 0;
        foreach ($data as $value) {
            $totalTransaction += $value['priceOverall'];
        }

        $findBasedSales = DB::table('promotionMasters as pm')
            ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
            ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
            ->join('promotionBasedSales as bs', 'pm.id', 'bs.promoMasterId')
            ->where('pl.locationId', '=', $value['locationId'])
            ->where('bs.minPurchase', '<', $totalTransaction)
            ->where('bs.maxPurchase', '>', $totalTransaction)
            ->where('pcg.customerGroupId', '=', $custGroup)
            ->where('pm.startDate', '<=', Carbon::now())
            ->where('pm.endDate', '>=', Carbon::now())
            ->where('pm.status', '=', 1)
            ->get();

        $text = "";

        foreach ($findBasedSales as $sale) {

            if ($sale->percentOrAmount == 'percent') {
                $text = 'Diskon ' . $sale->percent . ' % setiap pembelian minimal Rp ' . $sale->minPurchase;
            } elseif ($sale->percentOrAmount == 'amount') {
                $text = 'Potongan harga sebesar Rp ' . $sale->amount . ' setiap pembelian minimal Rp ' . $sale->minPurchase;
            }

            $resultBasedSales[] = [
                'id' => $sale->id,
                'note' => $text,
                'name' => $sale->name
            ];

            $text = "";
        }

        $result = [
            'freeItem' => $tempFree,
            'discount' => $tempDiscount,
            'bundles' => $resultBundle,
            'basedSales' => $resultBasedSales,
        ];

        // Jika ingin menampilkan sebagai JSON:
        return response()->json($result);
    }
}
