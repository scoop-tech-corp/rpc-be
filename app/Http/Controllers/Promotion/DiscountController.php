<?php

namespace App\Http\Controllers\Promotion;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\Promotion\PromoReport;
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

            responseInvalid($errors);
        }

        $ResultLocations = $request->locations;
        //json_decode($request->locations, true);

        if (!$ResultLocations) {

            responseInvalid(['Location cannot be empty!']);
        }

        $ResultCustGroup = json_decode($request->customerGroups, true);

        if ($request->type == 1) {

            $ResultFreeItem = json_decode($request->freeItem, true);

            $validateLocation = Validator::make(
                $ResultFreeItem,
                [
                    'quantityBuyItem' => 'required|integer',
                    'productBuyType' => 'required|string',
                    'productBuyName' => 'required|string',
                    'quantityFreeItem' => 'required|integer',
                    'productFreeType' => 'required|string',
                    'productFreeName' => 'required|string',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'quantityBuyItem.required' => 'Quantity Buy Item Should be Required!',
                    'quantityBuyItem.integer' => 'Quantity Buy Item Should be Filled!',
                    'productBuyType.required' => 'Product Buy Type Should be Required!',
                    'productBuyType.string' => 'Product Buy Type Should be Filled!',
                    'productBuyName.required' => 'Product Buy Name Should be Required!',
                    'productBuyName.string' => 'Product Buy Name Should be Filled!',
                    'quantityFreeItem.required' => 'Quantity Free Item Should be Required!',
                    'quantityFreeItem.integer' => 'Quantity Free Item Should be Filled!',
                    'productFreeType.required' => 'Product Free Type Should be Required!',
                    'productFreeType.string' => 'Product Free Type Should be Filled!',
                    'productFreeName.required' => 'Product Free Name Should be Required!',
                    'productFreeName.string' => 'Product Free Name Should be Filled!',
                    'totalMaxUsage.required' => 'Total Max Usage Should be Required!',
                    'totalMaxUsage.integer' => 'Total Max Usage Should be Filled!',
                    'maxUsagePerCustomer.required' => 'Max Usage per Customer Should be Required!',
                    'maxUsagePerCustomer.integer' => 'Max Usage per Customer Should be Filled!',
                ]
            );

            if ($validateLocation->fails()) {
                $errors = $validateLocation->errors()->first();

                responseInvalid([$errors]);
            }
        } elseif ($request->type == 2) {

            $ResultDiscount = json_decode($request->discount, true);

            $validateLocation = Validator::make(
                $ResultDiscount,
                [
                    'productOrService' => 'required|string',
                    'percentOrAmount' => 'required|string',
                    'productType' => 'required|string',
                    'productName' => 'required|string',
                    'amount' => 'nullable|numeric',
                    'percent' => 'nullable|numeric',
                    'totalMaxUsage' => 'required|integer',
                    'maxUsagePerCustomer' => 'required|integer',
                ],
                [
                    'productOrService.required' => 'Product or Service Should be Required!',
                    'productOrService.string' => 'Product or Service Should be Filled!',
                    'percentOrAmount.required' => 'Percent or Amount Should be Required!',
                    'percentOrAmount.string' => 'Percent or Amount Should be Filled!',
                    'productType.required' => 'Product Type Should be Required!',
                    'productType.string' => 'Product Type Should be Filled!',
                    'productName.required' => 'Product Name Should be Required!',
                    'productName.string' => 'Product Name Should be Filled!',
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

                responseInvalid([$errors]);
            }
        } elseif ($request->type == 3) {

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

                responseInvalid([$errors]);
            }

            $ResultBundleDetails = json_decode($request->bundleDetails, true);

            $validateBundleDetails = Validator::make(
                $ResultBundleDetails,
                [
                    '*.productOrService' => 'required|string',
                    '*.productType' => 'required|string',
                    '*.productId' => 'nullable|integer',
                    '*.serviceId' => 'nullable|integer',
                    '*.quantity' => 'required|integer',
                ],
                [
                    '*.productOrService.required' => 'Product or Service Should be Required!',
                    '*.productOrService.string' => 'Product or Service Should be Filled!',
                    '*.productType.required' => 'Product Type Should be Required!',
                    '*.productType.string' => 'Product Type Should be Filled!',
                    '*.productId.integer' => 'Product Id Should be Filled!',
                    '*.serviceId.integer' => 'Service Id Should be Filled!',
                    '*.quantity.required' => 'Quantity Should be Required!',
                    '*.quantity.integer' => 'Quantity Should be Filled!',
                ]
            );

            if ($validateBundleDetails->fails()) {
                $errors = $validateBundleDetails->errors()->first();

                responseInvalid([$errors]);
            }
        } elseif ($request->type == 4) {

            $ResultBasedSale = json_decode($request->basedSale, true);

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

                responseInvalid([$errors]);
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

                foreach ($ResultLocations as $value) {

                    // if ($ResultFreeItem['productBuyType'] == 'Sell') {
                    // } elseif ($ResultFreeItem['productBuyType'] == 'Clinic') {
                    //     $dataProdBuyId = DB::table('productClinics as ps')
                    //         ->join('productClinicLocations as psl', 'ps.id', 'psl.productClinicId')
                    //         ->select('ps.id')
                    //         ->where('ps.fullName', '=', $ResultFreeItem['productBuyName'])
                    //         ->where('psl.locationId', '=', $value)
                    //         ->first();
                    // }

                    $dataProdBuyId = DB::table('products as ps')
                        ->join('productLocations as psl', 'ps.id', 'psl.productId')
                        ->select('ps.id')
                        ->where('ps.fullName', '=', $ResultFreeItem['productBuyName'])
                        ->where('psl.locationId', '=', $value)
                        ->first();

                    $dataProdFreeId = DB::table('products as ps')
                        ->join('productLocations as psl', 'ps.id', 'psl.productId')
                        ->select('ps.id')
                        ->where('ps.fullName', '=', $ResultFreeItem['productFreeName'])
                        ->where('psl.locationId', '=', $value)
                        ->first();

                    // if ($ResultFreeItem['productFreeType'] == 'Sell') {

                    // } elseif ($ResultFreeItem['productFreeType'] == 'Clinic') {
                    //     $dataProdFreeId = DB::table('productClinics as ps')
                    //         ->join('productClinicLocations as psl', 'ps.id', 'psl.productClinicId')
                    //         ->select('ps.id')
                    //         ->where('ps.fullName', '=', $ResultFreeItem['productFreeName'])
                    //         ->where('psl.locationId', '=', $value)
                    //         ->first();
                    // }

                    PromotionFreeItem::create([
                        'promoMasterId' => $idPromo->id,
                        'quantityBuyItem' => $ResultFreeItem['quantityBuyItem'],
                        'productBuyType' => $ResultFreeItem['productBuyType'],
                        'productBuyId' => $dataProdBuyId->id,
                        'quantityFreeItem' => $ResultFreeItem['quantityFreeItem'],
                        'productFreeType' => $ResultFreeItem['productFreeType'],
                        'productFreeId' => $dataProdFreeId->id,
                        'totalMaxUsage' => $ResultFreeItem['totalMaxUsage'],
                        'maxUsagePerCustomer' => $ResultFreeItem['maxUsagePerCustomer'],
                        'userId' => $request->user()->id,
                    ]);
                }
            } elseif ($request->type == 2) {

                foreach ($ResultLocations as $value) {

                    $dataProdId = DB::table('products as ps')
                        ->join('productLocations as psl', 'ps.id', 'psl.productId')
                        ->select('ps.id')
                        ->where('ps.fullName', '=', $ResultDiscount['productName'])
                        ->where('psl.locationId', '=', $value)
                        ->first();

                    // if ($ResultDiscount['productType'] == 'Sell') {

                    // } elseif ($ResultDiscount['productType'] == 'Clinic') {
                    //     $dataProdId = DB::table('productClinics as ps')
                    //         ->join('productClinicLocations as psl', 'ps.id', 'psl.productClinicId')
                    //         ->select('ps.id')
                    //         ->where('ps.fullName', '=', $ResultDiscount['productName'])
                    //         ->where('psl.locationId', '=', $value)
                    //         ->first();
                    // }

                    PromotionDiscount::create([
                        'promoMasterId' => $idPromo->id,
                        'productOrService' => $ResultDiscount['productOrService'],
                        'percentOrAmount' => $ResultDiscount['percentOrAmount'],
                        'productType' => $ResultDiscount['productType'],
                        'productId' => $dataProdId->id,
                        'serviceId' => $ResultDiscount['serviceId'],
                        'amount' => $ResultDiscount['amount'],
                        'percent' => $ResultDiscount['percent'],
                        'totalMaxUsage' => $ResultDiscount['totalMaxUsage'],
                        'maxUsagePerCustomer' => $ResultDiscount['maxUsagePerCustomer'],
                        'userId' => $request->user()->id,
                    ]);
                }
            } elseif ($request->type == 3) {

                $idBundle = PromotionBundle::create([
                    'promoMasterId' => $idPromo->id,
                    'price' => $ResultBundle['price'],
                    'totalMaxUsage' => $ResultBundle['totalMaxUsage'],
                    'maxUsagePerCustomer' => $ResultBundle['maxUsagePerCustomer'],
                    'userId' => $request->user()->id,
                ]);

                foreach ($ResultBundleDetails as $res) {

                    foreach ($ResultLocations as $value) {

                        $dataProdId = DB::table('products as ps')
                            ->join('productLocations as psl', 'ps.id', 'psl.productId')
                            ->select('ps.id')
                            ->where('ps.fullName', '=', $res['productName'])
                            ->where('psl.locationId', '=', $value)
                            ->first();

                        // if ($res['productType'] == 'Sell') {

                        // } elseif ($res['productType'] == 'Clinic') {
                        //     $dataProdId = DB::table('productClinics as ps')
                        //         ->join('productClinicLocations as psl', 'ps.id', 'psl.productClinicId')
                        //         ->select('ps.id')
                        //         ->where('ps.fullName', '=', $res['productName'])
                        //         ->where('psl.locationId', '=', $value)
                        //         ->first();
                        // }

                        PromotionBundleDetail::create([
                            'promoBundleId' => $idBundle->id,
                            'productOrService' => $res['productOrService'],
                            'productType' => $res['productType'],
                            'productId' => $dataProdId->id,
                            'serviceId' => $res['serviceId'],
                            'quantity' => $res['quantity'],
                            'userId' => $request->user()->id,
                        ]);
                    }
                }
            } elseif ($request->type == 4) {
                PromotionBasedSales::create([
                    'promoMasterId' => $idPromo->id,
                    'minPurchase' => $ResultBasedSale['minPurchase'],
                    'maxPurchase' => $ResultBasedSale['maxPurchase'],
                    'percentOrAmount' => $ResultBasedSale['percentOrAmount'],
                    'amount' => $ResultBasedSale['amount'],
                    'percent' => $ResultBasedSale['percent'],
                    'totalMaxUsage' => $ResultBasedSale['totalMaxUsage'],
                    'maxUsagePerCustomer' => $ResultBasedSale['maxUsagePerCustomer'],
                    'userId' => $request->user()->id,
                ]);
            }

            DB::commit();
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
                DB::raw("DATE_FORMAT(pm.startDate, '%d/%m/%Y') as startDate"),
                DB::raw("DATE_FORMAT(pm.endDate, '%d/%m/%Y') as endDate"),
                DB::raw("CASE WHEN pm.status = 1 then 'Active' ELSE 'Inactive' END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.id', '=', $request->id)
            ->first();

        $dataLoc = DB::table('promotionLocations as pl')
            ->join('location as l', 'pl.locationId', 'l.id')
            ->select(DB::raw("GROUP_CONCAT(l.locationName SEPARATOR ', ') as location"))
            ->where('pl.promoMasterId', '=', $request->id)
            ->distinct()
            ->pluck('location')
            ->first();

        $dataCust = DB::table('promotionCustomerGroups as pc')
            ->join('customerGroups as cg', 'pc.customerGroupId', 'cg.id')
            ->select(DB::raw("GROUP_CONCAT(cg.customerGroup SEPARATOR ', ') as customerGroup"))
            ->where('pc.promoMasterId', '=', $request->id)
            ->distinct()
            ->pluck('customerGroup')
            ->first();

        $data->location = $dataLoc;
        $data->customerGroup = $dataCust;

        if ($data->typeId == 1) {

            $temp = DB::table('promotionFreeItems as pf')
                ->where('pf.promoMasterId', '=', $request->id)
                ->first();

            $dataProdBuy = DB::table('products as p')
                ->select('p.fullName')
                ->where('id', '=', $temp->productBuyId)
                ->first();

            // if ($temp->productBuyType == 'Sell') {

            // } elseif ($temp->productBuyType == 'Clinic') {

            //     $dataProdBuy = DB::table('productClinics as p')
            //         ->select('p.fullName')
            //         ->where('id', '=', $temp->productBuyId)
            //         ->first();
            // }



            $dataProdFree = DB::table('products as p')
                ->select('p.fullName')
                ->where('id', '=', $temp->productFreeId)
                ->first();
            // if ($temp->productFreeType == 'Sell') {

            // } elseif ($temp->productFreeType == 'Clinic') {

            //     $dataProdFree = DB::table('productClinics as p')
            //         ->select('p.fullName')
            //         ->where('id', '=', $temp->productFreeId)
            //         ->first();
            // }

            $data->quantityBuyItem = $temp->quantityBuyItem;
            $data->productBuyType = $temp->productBuyType;
            $data->productBuyId = $temp->productBuyId;
            $data->productBuyName = $dataProdBuy->fullName;

            $data->quantityFreeItem = $temp->quantityFreeItem;
            $data->productFreeType = $temp->productFreeType;
            $data->productFreeId = $temp->productFreeId;
            $data->productFreeName = $dataProdFree->fullName;

            $data->totalMaxUsage = $temp->totalMaxUsage;
            $data->maxUsagePerCustomer = $temp->maxUsagePerCustomer;
        } elseif ($data->typeId == 2) {
            $temp = DB::table('promotionDiscounts as pd')
                ->where('pd.promoMasterId', '=', $request->id)
                ->first();

            if ($temp->productOrService == 'product') {

                $dataProd = DB::table('products as p')
                    ->select('p.fullName')
                    ->where('id', '=', $temp->productId)
                    ->first();

                // if ($temp->productType == 'Sell') {

                // } elseif ($temp->productType == 'Clinic') {

                //     $dataProd = DB::table('productClinics as p')
                //         ->select('p.fullName')
                //         ->where('id', '=', $temp->productId)
                //         ->first();
                // }

                $data->productId = $temp->productId;
                $data->productName = $dataProd->fullName;
            } elseif ($temp->productOrService == 'service') {
                $dataService = DB::table('services')
                    ->select('fullName')
                    ->where('id', '=', $temp->serviceId)
                    ->first();

                $data->serviceId = $temp->serviceId;
                $data->serviceName = $dataService->fullName;
            }

            $data->productOrService = $temp->productOrService;
            $data->percentOrAmount = $temp->percentOrAmount;
            $data->productType = $temp->productType;

            if ($temp->percentOrAmount == 'percent') {
                $data->percent = $temp->percent;
            } elseif ($temp->percentOrAmount == 'amount') {
                $data->amount = $temp->amount;
            }

            $data->totalMaxUsage = $temp->totalMaxUsage;
            $data->maxUsagePerCustomer = $temp->maxUsagePerCustomer;
        } elseif ($data->typeId == 3) {
            $temp = DB::table('promotionBundles as pb')
                ->where('pb.promoMasterId', '=', $request->id)
                ->first();

            $customList = [];

            $temp2 = DB::table('promotionBundleDetails as pb')
                ->where('pb.promoBundleId', '=', $temp->id)
                ->get();

            foreach ($temp2 as $value) {

                if ($value->productOrService == 'product') {

                    $dataProd = DB::table('products as p')
                        ->select('p.fullName')
                        ->where('id', '=', $value->productId)
                        ->first();

                    // if ($value->productType == 'Sell') {

                    // } elseif ($value->productType == 'Clinic') {
                    //     $dataProd = DB::table('productClinics as p')
                    //         ->select('p.fullName')
                    //         ->where('id', '=', $value->productId)
                    //         ->first();
                    // }

                    $tempList = [
                        'productOrService' => $value->productOrService,
                        'productType' => $value->productType,
                        'productId' => $value->productId,
                        'productName' => $dataProd->fullName,
                        'quantity' => $value->quantity,
                    ];

                    $customList[] = $tempList;
                } elseif ($value['productOrService'] == 'service') {
                    $dataService = DB::table('services')
                        ->select('fullName')
                        ->where('id', '=', $value['serviceId'])
                        ->first();

                    $tempList = [
                        'productOrService' => $value->productOrService,
                        'serviceId' => $dataService->fullName,
                        'quantity' => $value->quantity,
                    ];

                    $customList[] = $tempList;
                }
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
                    DB::raw("DATE_FORMAT(pm.startDate, '%d/%m/%Y') as startDate"),
                    DB::raw("DATE_FORMAT(pm.endDate, '%d/%m/%Y') as endDate"),
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

            $data = $temp;
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
            responseInvalid($errors);
        }
    }

    public function delete(Request $request)
    {
        foreach ($request->id as $va) {
            $res = PromotionMaster::find($va);
            if (!$res) {
                responseInvalid(['There is any Data not found!']);
            }
        }

        foreach ($request->id as $va) {

            $res = PromotionMaster::find($va);

            if ($res->type == 1) {
                PromotionFreeItem::where('promoMasterId', '=', $res->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } elseif ($res->type == 2) {
                PromotionDiscount::where('promoMasterId', '=', $res->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } elseif ($res->type == 3) {

                $bundle = PromotionBundle::where('promoMasterId', '=', $res->id)
                    ->first();

                PromotionBundleDetail::where('promoBundleId', '=', $bundle->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );

                PromotionBundle::where('promoMasterId', '=', $res->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } elseif ($res->type == 4) {
                PromotionBasedSales::where('promoMasterId', '=', $res->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            PromotionMaster::where('id', '=', $res->id)
                ->update(
                    [
                        'deletedBy' => $request->user()->id,
                        'isDeleted' => 1,
                        'deletedAt' => Carbon::now()
                    ]
                );

            return response()->json([
                'message' => 'Delete Data Successful',
            ], 200);
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

        $orderValues = $request->orderValue;

        if (count($orderValues) > 0) {
            if (!$orderValues[0] == null) {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }
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
}
