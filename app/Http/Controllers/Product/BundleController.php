<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductBundle;
use App\Models\ProductBundleDetail;
use App\Models\ProductBundleLog;
use App\Models\ProductClinic;
use Illuminate\Http\Request;
use Validator;
use DB;
use Illuminate\Support\Carbon;

class BundleController
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productBundles as pb')
            ->join('users as u', 'pb.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'pb.locationId')
            ->join('productCategories as pc', 'pc.Id', 'pb.categoryId')
            ->select(
                'pb.id',
                'pb.name',
                'pb.locationId',
                'loc.locationName',
                'pb.categoryId',
                'pc.categoryName',
                'pb.status',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pb.isDeleted', '=', 0);

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

        $data = $data->orderBy('pb.updated_at', 'desc');

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

        $data = DB::table('productBundles as pb')
            ->select(
                'pb.name',
            )
            ->where('pb.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pb.name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pb.name';
        }


        $data = DB::table('productBundles as pb')
            ->join('users as u', 'pb.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'pb.locationId')
            ->join('productCategories as pc', 'pc.Id', 'pb.categoryId')
            ->select(
                'pc.categoryName'
            )
            ->where('pb.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pc.categoryName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pc.categoryName';
        }


        $data = DB::table('productBundles as pb')
            ->join('users as u', 'pb.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'pb.locationId')
            ->join('productCategories as pc', 'pc.Id', 'pb.categoryId')
            ->select(
                'loc.locationName'
            )
            ->where('pb.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('loc.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'loc.locationName';
        }

        return $temp_column;
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:30',
            'locationId' => 'required|integer',
            'categoryId' => 'required|integer',
            'remark' => 'required|string',
            'status' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $errorDetail = $this->ValidateDetail($request, 'create');

        if ($errorDetail != '') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errorDetail],
            ], 422);
        }

        //validate existing data
        $bundle = ProductBundle::where('locationId', '=', $request->locationId)
            ->where('name', '=', $request->name)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($bundle) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Bundle Name in this branch has already exists!'],
            ], 422);
        }

        $products = json_decode($request->products, true);

        if (count($products) > 0) {
            $products = json_decode($request->products, true);
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product List cannot be empty!'],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $prod =  ProductBundle::create([
                'name' => $request->name,
                'locationId' => $request->locationId,
                'categoryId' => $request->categoryId,
                'remark' => $request->remark,
                'status' => $request->status,
                'userId' => $request->user()->id,
            ]);

            foreach ($products as $value) {

                ProductBundleDetail::create([
                    'productBundleId' => $prod->id,
                    'productId' => $value['productId'],
                    'quantity' => $value['quantity'],
                    'total' => $value['total'],
                    'userId' => $request->user()->id,
                ]);
            }

            DB::commit();

            $this->AddLog($request, $prod->id, 'Created', '', '');

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

    private function AddLog($request, $id, $event, $status, $productName)
    {
        if ($event == 'Created') {

            ProductBundleLog::create(
                [
                    'productBundleId' => $id,
                    'event' => $event,
                    'details' => 'A draft Product Bundle has been created.',
                    'userId' => $request->user()->id,
                ]
            );
        } elseif ($event == 'Updated') {

            if ($status == 'new') {

                ProductBundleLog::create(
                    [
                        'productBundleId' => $id,
                        'event' => $event,
                        'details' => 'A Product ' . $productName .  ' has been added on bundle.',
                        'userId' => $request->user()->id,
                    ]
                );
            } elseif ($status == 'update') {
                ProductBundleLog::create(
                    [
                        'productBundleId' => $id,
                        'event' => $event,
                        'details' => 'A Product ' . $productName . ' has been updated on bundle.',
                        'userId' => $request->user()->id,
                    ]
                );
            } elseif ($status == 'delete') {
                ProductBundleLog::create(
                    [
                        'productBundleId' => $id,
                        'event' => $event,
                        'details' => 'A Product ' . $productName . ' has been deleted on bundle.',
                        'userId' => $request->user()->id,
                    ]
                );
            } elseif ($status == 'status') {

                $detail = '';
                if ($request->status == 1) {
                    $detail = 'A Product Bundle has been Activated.';
                } else if ($request->status == 0) {
                    $detail = 'A Product Bundle has been Disabled.';
                }

                ProductBundleLog::create(
                    [
                        'productBundleId' => $id,
                        'event' => 'Updated',
                        'details' => $detail,
                        'userId' => $request->user()->id,
                    ]
                );
            }
        }
    }

    private function ValidateDetail($request, $status)
    {

        $products = null;

        if ($status == 'create') {

            $products = json_decode($request->products, true);

            if (count($products) > 0) {
                // foreach ($products as $res) {
                $validateDetail = Validator::make(
                    $products,
                    [
                        '*.productId' => 'required|integer',
                        '*.quantity' => 'required|integer|min:1',
                        '*.total' => 'required|numeric',

                    ],
                    [
                        '*.productId.integer' => 'Product Id Should be Filled',
                        '*.quantity.integer' => 'Quantity Should be Filled',
                        '*.total.numeric' => 'Total Should be Filled',

                        '*.productId.required' => 'Product Id Should be Required',
                        '*.quantity.required' => 'Quantity Should be Required',
                        '*.quantity.integer' => 'Quantity Should be Filled',
                        '*.total.required' => 'Total Should be Required',

                        '*.quantity.min' => 'Quantity must be at least 1',
                    ]
                );

                if ($validateDetail->fails()) {
                    $errors = $validateDetail->errors()->first();
                    return $errors;
                    // }
                }
            }

            return '';
        } elseif ($status == 'update') {

            $products = $request->products;

            if (count($products) > 0) {

                // foreach ($products as $res) {
                $validateDetail = Validator::make(
                    $products,
                    [
                        '*.productId' => 'required|integer',
                        '*.quantity' => 'required|integer|min:1',
                        '*.total' => 'required|numeric',

                    ],
                    [
                        '*.productId.integer' => 'Product Id Should be Integer',
                        '*.quantity.integer' => 'Quantity Should be Integer',
                        '*.total.numeric' => 'Total Should be Decimal',

                        '*.productId.required' => 'Product Id Should be Required',
                        '*.quantity.required' => 'Quantity Should be Required',
                        '*.total.required' => 'Total Should be Required',

                        '*.quantity.min' => 'Quantity must be at least 1',
                    ]
                );

                if ($validateDetail->fails()) {
                    $errors = $validateDetail->errors()->first();
                    return $errors;
                }
                // }
            }

            return '';
        }
    }

    public function detail(Request $request)
    {

        $prod = ProductBundle::find($request->id);

        if (!$prod) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product Bundle Request not found!'],
            ], 422);
        }

        $prod = DB::table('productBundles as pb')
            ->join('users as u', 'pb.userId', 'u.id')
            ->join('location as loc', 'loc.Id', 'pb.locationId')
            ->join('productCategories as pc', 'pc.Id', 'pb.categoryId')
            ->select(
                'pb.id',
                'pb.name',
                'pb.locationId',
                'loc.locationName',
                'pb.categoryId',
                'pc.categoryName',
                'pb.remark',
                'pb.status',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pb.id', '=', $request->id)
            ->where('pb.isDeleted', '=', 0)
            ->first();

        $prodDetail = DB::table('productBundleDetails as pbd')
            ->join('productClinics as pc', 'pc.id', 'pbd.productId')
            ->join('users as u', 'pbd.userId', 'u.id')
            ->select(
                'pbd.id',
                'pbd.productBundleId',
                'pbd.productId',
                'pc.fullName',
                'pbd.quantity',
                DB::raw("TRIM(pc.price)+0 as price"),
                DB::raw("TRIM(pbd.total)+0 as total"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pbd.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pbd.productBundleId', '=', $request->id)
            ->where('pbd.isDeleted', '=', 0)
            ->get();

        $history = DB::table('productBundleLogs as pbl')
            ->join('productBundles as pb', 'pbl.productBundleId', 'pb.id')
            ->join('users as u', 'pbl.userId', 'u.id')
            ->select(
                'pbl.id',
                'pbl.event',
                'pbl.details',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pbl.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pbl.productBundleId', '=', $request->id)
            ->orderBy('pbl.id', 'desc')
            ->get();


        return response()->json([
            'productBundle' => $prod,
            'detailBundle' => $prodDetail,
            'history' => $history
        ], 200);
    }

    public function update(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'name' => 'required|string|min:3|max:30',
            'locationId' => 'required|integer',
            'categoryId' => 'required|integer',
            'remark' => 'required|string',
            'status' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $prodBundle = ProductBundle::find($request->id);

        if (!$prodBundle) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data not found!'],
            ], 422);
        }

        if ($prodBundle->name != $request->name || $prodBundle->locationId != $request->locationId) {
            $bundle = ProductBundle::where('locationId', '=', $request->locationId)
                ->where('name', '=', $request->name)
                ->where('isDeleted', '=', 0)
                ->first();

            if ($bundle) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Bundle Name in this branch has already exists!'],
                ], 422);
            }
        }

        $errorDetail = "";

        if ($request->products) {
            $errorDetail = $this->ValidateDetail($request, 'update');
        }

        if ($errorDetail != '') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errorDetail],
            ], 422);
        }

        if ($request->products) {
            $products = $request->products;
        }

        $prodBundle->name = $request->name;
        $prodBundle->locationId = $request->locationId;
        $prodBundle->categoryId = $request->categoryId;
        $prodBundle->remark = $request->remark;
        $prodBundle->status = $request->status;
        $prodBundle->userUpdateId = $request->user()->id;
        $prodBundle->updated_at = \Carbon\Carbon::now();
        $prodBundle->save();

        if ($request->products) {

            foreach ($products as $value) {

                if ($value['status'] == 'new') {

                    ProductBundleDetail::create([
                        'productBundleId' => $prodBundle->id,
                        'productId' => $value['productId'],
                        'quantity' => $value['quantity'],
                        'total' => $value['total'],
                        'userId' => $request->user()->id,
                    ]);
                } elseif ($value['status'] == 'delete') {

                    ProductBundleDetail::where('id', '=', $value['id'])
                        ->update(
                            [
                                'deletedBy' => $request->user()->id,
                                'isDeleted' => 1,
                                'deletedAt' => Carbon::now()
                            ]
                        );
                } elseif ($value['status'] == 'update') {

                    $p = ProductBundleDetail::find($value['id']);

                    $p->productId = $value['productId'];
                    $p->quantity = $value['quantity'];
                    $p->total = $value['total'];
                    $p->userUpdateId = $request->user()->id;
                    $p->updated_at = \Carbon\Carbon::now();
                    $p->save();
                }

                $pClinic = ProductClinic::find($value['productId']);

                $this->AddLog($request, $request->id, 'Updated', $value['status'], $pClinic->fullName);
            }
        }

        return response()->json([
            'message' => 'Update data successfull',
        ], 200);
    }

    public function changeStatus(Request $request)
    {
        $res = ProductBundle::find($request->id);

        if (!$res) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data not found!'],
            ], 422);
        }

        $res->status = $request->status;
        $res->userUpdateId = $request->user()->id;
        $res->updated_at = Carbon::now();
        $res->save();

        $this->AddLog($request, $request->id, 'Updated', 'status', '');

        return response()->json([
            'message' => 'Update Status Successful',
        ], 200);
    }

    public function delete(Request $request)
    {
        foreach ($request->id as $va) {
            $res = ProductBundle::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {

            $prodBundle = ProductBundle::find($va);

            $prodBundleDetail = ProductBundleDetail::where('productBundleId', '=', $prodBundle->id)->get();

            if ($prodBundleDetail) {

                ProductBundleDetail::where('productBundleId', '=', $prodBundle->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $prodBundle->DeletedBy = $request->user()->id;
            $prodBundle->isDeleted = true;
            $prodBundle->DeletedAt = Carbon::now();
            $prodBundle->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}
