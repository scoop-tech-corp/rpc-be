<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductInventory;
use App\Models\ProductInventoryList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Validator;
use Illuminate\Support\Carbon;

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
                DB::raw("IFNULL(uOff.name,'') as officeApprovedBy"),
                'p.isApprovedAdmin',
                DB::raw("IFNULL(uAdm.name,'') as adminApprovedBy"),
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:00') as createdAt")
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

    public function Search(Request $request)
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

        $data = DB::table('productInventories as p')
            ->join('users as u', 'p.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'p.locationId')
            ->select(
                'p.id',
                'p.requirementName',
                'p.locationId',
                'loc.locationName as locationName',
                'p.isApprovedOffice',
                'p.isApprovedAdmin',
                DB::raw("IFNULL(p.reasonOffice,'') as Reason"),
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:00') as createdAt")
            );

        if ($request->user()->role == 'Admin') {
            $data = $data->where('p.isApprovedOffice', '=', 1)
                ->whereIn('p.isApprovedAdmin', array(1, 2));
        } elseif ($request->user()->role == 'Office') {
            $data = $data->whereIn('p.isApprovedOffice', array(1, 2));
        }

        if ($request->search) {
            $res = $this->SearchOffice($request);
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

    public function SearchOffice(Request $request)
    {
    }

    public function indexApproval(Request $request)
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
                'p.isApprovedOffice',
                'p.isApprovedAdmin',
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:00') as createdAt")
            );

        if ($request->user()->role == 'Admin') {
            $data = $data->where('p.isApprovedOffice', '=', 1)
                ->where('p.isApprovedAdmin', '=', 0);
        } elseif ($request->user()->role == 'Office') {
            $data = $data->where('p.isApprovedOffice', '=', 0);
        }



        if ($request->search) {
            $res = $this->SearchAdmin($request);
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

    public function indexOffice(Request $request)
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
                'p.isApprovedOffice',
                'p.isApprovedAdmin',
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:00') as createdAt")
            )
            ->where('p.isApprovedOffice', '=', 0);

        if ($request->search) {
            $res = $this->searchOffice($request);
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

    public function SearchAdmin(Request $request)
    {
    }

    public function indexHistoryAdmin(Request $request)
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
                'p.isApprovedOffice',
                'p.isApprovedAdmin',
                DB::raw("IFNULL(p.reasonAdmin,'') as Reason"),
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:00') as createdAt")
            )
            ->where('p.isApprovedOffice', '=', 1)
            ->whereIn('p.isApprovedAdmin', array(1, 2));

        if ($request->search) {
            $res = $this->SearchAdmin($request);
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

        if ($request->status == 2 && $request->reason == "") {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Reason should be filled when to set reject!'],
            ], 422);
        }

        if ($request->user()->role == 'Office') {
            ProductInventory::where('id', '=', $request->id)
                ->update(
                    [
                        'userApproveOfficeId' => $request->user()->id,
                        'isApprovedOffice' => $request->status,
                        'reasonOffice' => $request->reason,
                        'userApproveOfficeAt' => Carbon::now()
                    ]
                );
        } elseif ($request->user()->role == 'Admin') {
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

        return response()->json(
            [
                'message' => 'Update Status Successful!',
            ],
            200
        );
    }

    public function delete(Request $request)
    {
    }
}
