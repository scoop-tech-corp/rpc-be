<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductBundle;
use App\Models\ProductBundleDetail;
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
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pb.isDeleted', '=', 0);

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

        $data = $data->orderBy('pb.id', 'desc');

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
                'errors' => $errorDetail,
            ], 422);
        }

        if ($request->products) {

            $products = json_decode($request->products, true);
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

    private function ValidateDetail($request, $status)
    {

        $products = null;

        if ($status == 'create') {

            if ($request->products) {

                $products = json_decode($request->products, true);
            }

            $validateDetail = Validator::make(
                $products,
                [
                    '*.productId' => 'required|integer',
                    '*.quantity' => 'required|integer',
                    '*.total' => 'required|numeric',

                ],
                [
                    '*.productId.integer' => 'Product Id Should be Integer',
                    '*.quantity.integer' => 'Quantity Should be Integer',
                    '*.total.numeric' => 'Total Should be Decimal',
                ]
            );

            if ($validateDetail->fails()) {
                $errors = $validateDetail->errors()->all();

                return $errors;
            }

            return '';
        } elseif ($status == 'update') {

            if ($request->products) {

                $products = $request->products;
            }

            $validateDetail = Validator::make(
                $products,
                [
                    '*.productId' => 'required|integer',
                    '*.quantity' => 'required|integer',
                    '*.total' => 'required|numeric',

                ],
                [
                    '*.productId.integer' => 'Product Id Should be Integer',
                    '*.quantity.integer' => 'Quantity Should be Integer',
                    '*.total.numeric' => 'Total Should be Decimal',
                ]
            );

            if ($validateDetail->fails()) {
                $errors = $validateDetail->errors()->all();

                return $errors;
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
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pb.id', '=', $request->id)
            ->get();

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
                'u.name as createdBy',
                DB::raw("DATE_FORMAT(pbd.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pbd.productBundleId', '=', $request->id)
            ->get();


        return response()->json([
            'productBundle' => $prod,
            'detailBundle' => $prodDetail
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

        $errorDetail = $this->ValidateDetail($request, 'update');

        if ($errorDetail != '') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errorDetail,
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
        }

        return response()->json([
            'message' => 'Update data successfull',
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
