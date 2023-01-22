<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductClinic;
use App\Models\ProductClinicLocation;
use App\Models\ProductInventory;
use App\Models\ProductInventoryList;
use App\Models\ProductInventoryListImages;
use App\Models\ProductSell;
use App\Models\ProductSellLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProductInventoryController
{
    public function index(Request $request)
    {

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productInventories as p')
            ->join('users as u', 'p.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'p.locationId')
            ->select(
                'p.id',
                'p.requirementName',
                'p.locationId',
                'loc.locationName as locationName',
                'p.totalItem',
                // 'p.isApprovedOffice',
                // DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                // DB::raw("IFNULL(DATE_FORMAT(p.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                // DB::raw("IFNULL(p.reasonOffice,'') as reasonOffice"),

                // 'p.isApprovedAdmin',
                // DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                // DB::raw("IFNULL(DATE_FORMAT(p.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                // DB::raw("IFNULL(p.reasonAdmin,'') as reasonAdmin"),

                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            );

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

        $data = $data->orderBy('p.id', 'desc');

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

    public function Search($request)
    {
        $temp_column = '';

        $data = DB::table('productInventories as p')
            ->select(
                'p.requirementName as requirementName'
            )
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('p.requirementName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'p.requirementName';
            return $temp_column;
        }
    }

    public function indexHistory(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        if (role($request->user()->id) == 'Administrator') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pil', 'pil.productInventoryId', 'p.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                )->distinct()
                ->whereIn('pil.isApprovedAdmin', array(1, 2));
        } elseif (role($request->user()->id) == 'Office') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pil', 'pil.productInventoryId', 'p.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                )->distinct()
                ->whereIn('pil.isApprovedOffice', array(1, 2));
        }

        if ($request->search) {
            $res = $this->SearchHistory($request);
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

        $data = $data->orderBy('p.id', 'desc');

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

    public function SearchHistory($request)
    {
        $temp_column = null;

        $data = DB::table('productInventories as p')
            ->select('p.requirementName')
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('p.requirementName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'p.requirementName';
        }

        $data = DB::table('productInventories as p')
            ->join('users as u', 'p.userId', 'u.id')
            ->select('u.firstName')
            ->where('p.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }
    }

    public function indexApproval(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        if (role($request->user()->id) == 'Administrator') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    // 'p.isApprovedOffice',
                    // 'uOff.firstName as officeApprovedBy',
                    // 'p.isApprovedAdmin',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )->distinct()
                ->where('p.isApprovalAdmin', '=', 1)
                ->where('pl.isApprovedOffice', '=', 0);
        } elseif (role($request->user()->id) == 'Office') {
            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->join('productInventoryLists as pl', 'p.id', 'pl.productInventoryId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    // 'p.isApprovedOffice',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )->distinct()
                ->where('p.isApprovalOffice', '=', 1)
                ->where('pl.isApprovedOffice', '=', 0);
        }

        if ($request->search) {
            $res = $this->SearchApproval($request);
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

        $data = $data->orderBy('p.id', 'desc');

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

    public function SearchApproval($request)
    {
        # code...
    }

    public function detail(Request $request)
    {
        $prod = ProductInventory::find($request->id);

        if (!$prod) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product Inventory Request not found!'],
            ], 422);
        }

        $prodDetail = ProductInventoryList::where('productInventoryId', '=', $prod->id)->get();

        $result[] = null;

        foreach ($prodDetail as $value) {

            if ($value['productType'] = 'productSell') {

                $prodDetail = DB::table('productInventoryLists as pi')
                    ->join('productSells as p', 'p.id', 'pi.productId')
                    ->join('usages as u', 'u.id', 'pi.usageId')
                    ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
                    ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
                    ->leftJoin('productInventoryListImages as pimg', 'pi.id', 'pimg.productInventoryListId')
                    ->select(
                        'pi.id',
                        'pi.productType',
                        'pi.productId',
                        'p.fullName as productName',
                        'pi.usageId',
                        'u.usage',
                        'pi.quantity',

                        'pi.isApprovedOffice',
                        DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

                        'pi.isApprovedAdmin',
                        DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                        DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

                        DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
                        DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),

                        DB::raw("IFNULL(pimg.imagePath,'') as imagePath"),
                        DB::raw("IFNULL(pimg.realImageName,'') as realImageName"),
                    )
                    ->where('pi.productInventoryId', '=', $request->id)
                    ->orderBy('pi.id', 'desc')
                    ->get();

                $result = $prodDetail;
            } else {

                $prodDetail = DB::table('productInventoryLists as pi')
                    ->join('productClinics as p', 'p.id', 'pi.productId')
                    ->join('usages as u', 'u.id', 'pi.usageId')
                    ->leftJoin('users as uOff', 'pi.userApproveOfficeId', 'uOff.id')
                    ->leftJoin('users as uAdm', 'pi.userApproveAdminId', 'uAdm.id')
                    ->leftJoin('productInventoryListImages as pimg', 'pi.id', 'pimg.productInventoryListId')
                    ->select(
                        'pi.id',
                        'pi.productType',
                        'pi.productId',
                        'p.fullName as productName',
                        'pi.usageId',
                        'u.usage',
                        'pi.quantity',

                        'pi.isApprovedOffice',
                        DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                        DB::raw("IFNULL(pi.reasonOffice,'') as reasonOffice"),

                        'pi.isApprovedAdmin',
                        DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                        DB::raw("IFNULL(DATE_FORMAT(pi.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                        DB::raw("IFNULL(pi.reasonAdmin,'') as reasonAdmin"),

                        DB::raw("IFNULL(DATE_FORMAT(pi.dateCondition, '%d/%m/%Y'),'') as dateCondition"),
                        DB::raw("IFNULL(pi.itemCondition,'') as itemCondition"),
                        DB::raw("IFNULL(pimg.imagePath,'') as imagePath"),
                        DB::raw("IFNULL(pimg.realImageName,'') as realImageName"),
                    )
                    ->where('pi.productInventoryId', '=', $request->id)
                    ->orderBy('pi.id', 'desc')
                    ->get();

                $result = $prodDetail;
            }
        }

        return response()->json($result, 200);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'requirementName' => 'required|string|max:30',
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $ResultProducts = null;

        if ($request->listProducts) {
            $ResultProducts = json_decode($request->listProducts, true);
        }

        $validateProducts = Validator::make(
            $ResultProducts,
            [
                '*.productType' => 'required|string',
                '*.productId' => 'required|integer',
                '*.usageId' => 'required|integer',
                '*.quantity' => 'required|integer',
                '*.dateCondition' => 'required|date',
                '*.itemCondition' => 'required|string',
            ],
            [
                '*.productType.string' => 'Product Type Should be String!',
                '*.productId.integer' => 'Product Id Should be Integer',
                '*.usage.integer' => 'Usage Should be Integer',
                '*.quantity.integer' => 'Quantity Should be Integer',
                '*.dateCondition.date' => 'Quantity Should be Date',
                '*.itemCondition.string' => 'Quantity Should be Integer'
            ]
        );

        if ($validateProducts->fails()) {
            $errors = $validateProducts->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $approvalAdmin = 0;
        $approvalOffice = 0;

        foreach ($ResultProducts as $value) {

            if ($value['productType'] == 'productSell') {

                $findProduct = ProductSell::find($value['productId']);

                if ($findProduct->isAdminApproval == 1) {
                    $approvalAdmin = 1;
                }

                if ($findProduct->isOfficeApproval == 1) {
                    $approvalOffice = 1;
                }
            } elseif ($value['productType'] == 'productClinic') {

                $findProduct = ProductClinic::find($value['productId']);

                if ($findProduct->isAdminApproval == 1) {
                    $approvalAdmin = 1;
                }

                if ($findProduct->isOfficeApproval == 1) {
                    $approvalOffice = 1;
                }
            }
        }

        DB::beginTransaction();
        try {

            $prod =  ProductInventory::create([
                'requirementName' => $request->requirementName,
                'locationId' => $request->locationId,
                'totalItem' => count($ResultProducts),
                'isApprovalAdmin' => $approvalAdmin,
                'isApprovalOffice' => $approvalOffice,
                'userId' => $request->user()->id,
            ]);

            $count = 0;

            $files[] = $request->file('images');
            $tmpImages = [];

            if ($request->hasfile('images')) {
                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $name = $fil->hashName();

                        $fil->move(public_path() . '/ProductClinicImages/', $name);

                        $fileName = "/ProductInventoryImages/" . $name;

                        $file = new ProductInventoryListImages();
                        $file->productInventoryListId = 1;
                        $file->realImageName = $fil->getClientOriginalName();
                        $file->imagePath = $fileName;
                        $file->userId = $request->user()->id;

                        array_push($tmpImages, $file);
                    }
                }
            }

            foreach ($ResultProducts as $value) {

                $prodList = ProductInventoryList::create([
                    'productInventoryId' => $prod->id,
                    'productType' => $value['productType'],
                    'productId' => $value['productId'],
                    'usageId' => $value['usageId'],
                    'quantity' => $value['quantity'],
                    'dateCondition' => $value['dateCondition'],
                    'itemCondition' => $value['itemCondition'],
                    'isAnyImage' => $value['isAnyImage'],
                    'userId' => $request->user()->id,
                ]);

                if ($value['isAnyImage'] == 1) {

                    ProductInventoryListImages::create([
                        'productInventoryListId' => $prodList->id,
                        'realImageName' => $tmpImages[$count]['realImageName'],
                        'imagePath' => $tmpImages[$count]['imagePath'],
                        'userId' => $request->user()->id,
                    ]);

                    $count += 1;
                }
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } catch (Throwable $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Insert Failed',
                'errors' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request)
    {
    }

    public function updateApproval(Request $request)
    {
        $prod = ProductInventoryList::find($request->id);

        if (!$prod) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['The Product Request not found!'],
            ], 422);
        }

        // if (role($request->user()->id) == 'Office' && $prod->isApprovedOffice != 0) {
        //     return response()->json([
        //         'message' => 'The given data was invalid.',
        //         'errors' => ['Data has already signed by Office!'],
        //     ], 422);
        // }

        // if (role($request->user()->id) == 'Administrator' && $prod->isApprovedAdmin != 0) {
        //     return response()->json([
        //         'message' => 'The given data was invalid.',
        //         'errors' => ['Data has already signed by Administrator!'],
        //     ], 422);
        // }

        if ($request->status == 2 && $request->reason == "") {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Reason should be filled when to set reject!'],
            ], 422);
        } elseif ($request->status == 1 && $request->reason != "") {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Reason should be empty when to set approve!'],
            ], 422);
        }

        if (role($request->user()->id) == 'Office') {
            ProductInventoryList::where('id', '=', $request->id)
                ->update(
                    [
                        'userApproveOfficeId' => $request->user()->id,
                        'isApprovedOffice' => $request->status,
                        'reasonOffice' => $request->reason,
                        'userApproveOfficeAt' => Carbon::now()
                    ]
                );
        } elseif (role($request->user()->id) == 'Administrator') {
            ProductInventoryList::where('id', '=', $request->id)
                ->update(
                    [
                        'userApproveAdminId' => $request->user()->id,
                        'isApprovedAdmin' => $request->status,
                        'reasonAdmin' => $request->reason,
                        'userApproveAdminAt' => Carbon::now()
                    ]
                );
        }

        if ($prod->isApprovedAdmin == 1 && $prod->isApprovedOffice == 1) {
            // update untuk mengurangi stok barang
            $prodInventory = ProductInventory::find($prod->productInventoryId);

            $prodList = ProductInventoryList::where('id', '=', $request->id)->get();

            //check validation update
            $msg = $this->ValidateUpdate($prodList, $prodInventory);
            if ($msg != "") {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [$msg],
                ], 422);
            }

            foreach ($prodList as $value) {

                if ($value['productType'] == 'productSell') {

                    $product = ProductSellLocation::where('productSellId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->first();

                    ProductSellLocation::where('productSellId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->update([
                            'inStock' => $product->inStock - $value['quantity'],
                            'userUpdateId' => $request->user()->id,
                            'updated_at' => Carbon::now()
                        ]);
                } elseif ($value['productType'] == 'productClinic') {
                    $product = ProductClinicLocation::where('productClinicId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->first();

                    ProductClinicLocation::where('productClinicId', '=', $value['productId'])
                        ->where('locationId', '=', $prodInventory->locationId)
                        ->update([
                            'inStock' => $product->inStock - $value['quantity'],
                            'userUpdateId' => $request->user()->id,
                            'updated_at' => Carbon::now()
                        ]);
                }
            }
        }

        return response()->json(
            [
                'message' => 'Update Status Successful!',
            ],
            200
        );
    }

    private function ValidateUpdate($prodList, $prodInventory)
    {
        $msg = '';
        foreach ($prodList as $value) {

            if ($value['productType'] == 'productSell') {

                $product = ProductSellLocation::where('productSellId', '=', $value['productId'])
                    ->where('locationId', '=', $prodInventory->locationId)
                    ->first();

                if (!$product) {
                    $msg = 'Data not exist';
                }
            } elseif ($value['productType'] == 'productClinic') {
                $product = ProductClinicLocation::where('productClinicId', '=', $value['productId'])
                    ->where('locationId', '=', $prodInventory->locationId)
                    ->first();

                if (!$product) {
                    $msg = 'Data not exist';
                }
            }
        }

        return $msg;
    }

    private function LogProductSell()
    {
    }

    private function LogProductClinic()
    {
    }

    public function delete(Request $request)
    {

        foreach ($request->id as $va) {

            $prod = ProductInventory::find($va);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product Inventory Request not found!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {

            $prod = ProductInventory::find($va);

            $prodList = ProductInventoryList::where('ProductInventoryId', '=', $prod->id)->get();

            if ($prodList) {

                ProductInventoryList::where('ProductSellId', '=', $prodList->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $prodList->DeletedBy = $request->user()->id;
            $prodList->isDeleted = true;
            $prodList->DeletedAt = Carbon::now();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}
