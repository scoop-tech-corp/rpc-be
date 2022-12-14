<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductClinic;
use App\Models\ProductClinicLocation;
use App\Models\ProductInventory;
use App\Models\ProductInventoryList;
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
            ->leftJoin('users as uOff', 'p.userApproveOfficeId', 'uOff.id')
            ->leftJoin('users as uAdm', 'p.userApproveAdminId', 'uAdm.id')
            ->select(
                'p.id',
                'p.requirementName',
                'p.locationId',
                'p.totalItem',
                'loc.locationName as locationName',

                'p.isApprovedOffice',
                DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                DB::raw("IFNULL(DATE_FORMAT(p.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                DB::raw("IFNULL(p.reasonOffice,'') as reasonOffice"),

                'p.isApprovedAdmin',
                DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),
                DB::raw("IFNULL(DATE_FORMAT(p.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),
                DB::raw("IFNULL(p.reasonAdmin,'') as reasonAdmin"),

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
                ->leftJoin('users as uOff', 'p.userApproveOfficeId', 'uOff.id')
                ->leftJoin('users as uAdm', 'p.userApproveAdminId', 'uAdm.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'p.isApprovedOffice',
                    'p.isApprovedAdmin',

                    DB::raw("IFNULL(uOff.firstName,'') as officeApprovedBy"),
                    DB::raw("IFNULL(uAdm.firstName,'') as adminApprovedBy"),

                    DB::raw("IFNULL(DATE_FORMAT(p.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s'),'') as officeApprovedAt"),
                    DB::raw("IFNULL(DATE_FORMAT(p.userApproveAdminAt, '%d/%m/%Y %H:%i:%s'),'') as adminApprovedAt"),

                    DB::raw("IFNULL(p.reasonOffice,'') as reasonOffice"),
                    DB::raw("IFNULL(p.reasonAdmin,'') as reasonAdmin"),

                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                );


            $data = $data->where('p.isApprovedOffice', '=', 1)
                ->whereIn('p.isApprovedAdmin', array(1, 2));
        } elseif (role($request->user()->id) == 'Office') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->leftJoin('users as uOff', 'p.userApproveOfficeId', 'uOff.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'p.isApprovedOffice',
                    DB::raw("IFNULL(p.reasonOffice,'') as reasonOffice"),
                    'uOff.firstName as officeApprovedBy',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt"),
                    DB::raw("DATE_FORMAT(p.userApproveOfficeAt, '%d/%m/%Y %H:%i:%s') as userApprovedOfficeAt")
                )
                ->whereIn('p.isApprovedOffice', array(1, 2));
        }

        if ($request->search) {
            $res = $this->SearchHistory($request);
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

    public function SearchHistory($request)
    {
    }

    public function indexApproval(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        if (role($request->user()->id) == 'Administrator') {

            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->leftJoin('users as uOff', 'p.userApproveOfficeId', 'uOff.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'p.isApprovedOffice',
                    'uOff.firstName as officeApprovedBy',
                    'p.isApprovedAdmin',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )
                ->where('p.isApprovedOffice', '=', 1)
                ->where('p.isApprovedAdmin', '=', 0);
        } elseif (role($request->user()->id) == 'Office') {
            $data = DB::table('productInventories as p')
                ->join('users as u', 'p.userId', 'u.id')
                ->join('location as loc', 'loc.Id', 'p.locationId')
                ->select(
                    'p.id',
                    'p.requirementName',
                    'p.locationId',
                    'loc.locationName as locationName',
                    'p.isApprovedOffice',
                    'u.firstName as createdBy',
                    DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                )
                ->where('p.isApprovedOffice', '=', 0);
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
                    ->select(
                        'pi.id',
                        'pi.productType',
                        'pi.productId',
                        'p.fullName as productName',
                        'pi.usageId',
                        'u.usage',
                        'pi.quantity'
                    )
                    ->where('pi.productInventoryId', '=', $request->id)
                    ->get();

                $result = $prodDetail;
            } else {

                $prodDetail = DB::table('productInventoryLists as pi')
                    ->join('productClinics as p', 'p.id', 'pi.productId')
                    ->join('usages as u', 'u.id', 'pi.usageId')
                    ->select(
                        'pi.id',
                        'pi.productType',
                        'pi.productId',
                        'p.fullName as productName',
                        'pi.usageId',
                        'u.usage',
                        'pi.quantity'
                    )
                    ->where('pi.productInventoryId', '=', $request->id)
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
            ],
            [
                '*.productType.string' => 'Product Type Should be String!',
                '*.productId.integer' => 'Product Id Should be Integer',
                '*.usage.integer' => 'Usage Should be Integer',
                '*.quantity.integer' => 'Quantity Should be Integer'
            ]
        );

        if ($validateProducts->fails()) {
            $errors = $validateProducts->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        DB::beginTransaction();
        try {

            $prod =  ProductInventory::create([
                'requirementName' => $request->requirementName,
                'totalItem' => count($ResultProducts),
                'locationId' => $request->locationId,
                'userId' => $request->user()->id,
            ]);

            foreach ($ResultProducts as $value) {

                ProductInventoryList::create([
                    'productInventoryId' => $prod->id,
                    'productType' => $value['productType'],
                    'productId' => $value['productId'],
                    'usageId' => $value['usageId'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
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
                'errors' => $e,
            ]);
        }
    }

    public function update(Request $request)
    {
    }

    public function updateApproval(Request $request)
    {
        $prod = ProductInventory::find($request->id);

        if (!$prod) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product Inventory Request not found!'],
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
            ProductInventory::where('id', '=', $request->id)
                ->update(
                    [
                        'userApproveOfficeId' => $request->user()->id,
                        'isApprovedOffice' => $request->status,
                        'reasonOffice' => $request->reason,
                        'userApproveOfficeAt' => Carbon::now()
                    ]
                );
        } elseif (role($request->user()->id) == 'Administrator') {
            ProductInventory::where('id', '=', $request->id)
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
            $prodInventory = ProductInventory::find($request->id);

            $prodList = ProductInventoryList::where('ProductInventoryId', '=', $request->id)->get();

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
