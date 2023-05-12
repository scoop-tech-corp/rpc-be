<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\ProductCategoryImport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Models\ProductCategories;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

class CategoryController extends Controller
{
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'categoryName' => 'required|string',
            'expiredDay' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('productCategories')
            ->where('CategoryName', '=', $request->categoryName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits === null) {

            ProductCategories::create([
                'categoryName' => $request->categoryName,
                'expiredDay' => $request->expiredDay,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Category Name already exists!'],
            ], 422);
        }
    }

    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productCategories as pc')
            ->join('users as u', 'pc.userId', 'u.id')
            ->select(
                'pc.id',
                'categoryName',
                'pc.expiredDay as expiredDay',
                DB::raw("(select count(*) from productSellCategories where productCategoryId=pc.id) + (select count(*) from productClinicCategories where productCategoryId=pc.id) as totalProduct"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pc.updated_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pc.isDeleted', '=', 0);

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
            if ($request->orderColumn == 'createdAt') {
                $data = $data->orderBy('pc.updated_at', $request->orderValue);
            } else {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }
        } else {
            $data = $data->orderBy('pc.updated_at', 'desc');
        }

        if ($itemPerPage) {

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
        } else {
            $data = $data->get();
            return response()->json($data);
        }
    }

    private function Search($request)
    {
        $temp_column = null;

        $data = DB::table('productCategories as pc')
            ->select(
                'pc.categoryName'
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pc.categoryName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pc.categoryName';
        }

        //
        $data = DB::table('productCategories as pc')
            ->select(
                'pc.expiredDay as expiredDay'
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('pc.expiredDay', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pc.expiredDay';
        }

        $data = DB::table('productCategories as pc')
            ->join('users as u', 'pc.userId', 'u.id')
            ->select(
                'u.firstName'
            )
            ->where('pc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }

    public function detailSell(Request $request)
    {
        $cat = DB::table('productCategories as pc')
            ->select('pc.id', 'pc.CategoryName', 'pc.expiredDay as expiredDay')
            ->where('pc.id', '=', $request->id)
            ->first();

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productSells as ps')
            ->join('productSellCategories as pc', 'ps.id', 'pc.productSellId')
            ->join('productSellLocations as pl', 'ps.id', 'pl.productSellId')
            ->join('location as l', 'l.id', 'pl.locationId')
            ->select('ps.id', 'ps.fullName', 'ps.expiredDate', 'l.locationName')
            ->where('pc.productCategoryId', '=', $request->id)
            ->where('ps.isDeleted', '=', 0);

        if ($request->locationId) {
            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->search) {
            $data = $data->where('ps.fullName', 'like', '%' . $request->search . '%');
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('ps.updated_at', 'desc');

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
            'category' => $cat,
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ]);
    }

    public function detailClinic(Request $request)
    {
        $cat = DB::table('productCategories as pc')
            ->select('pc.id', 'pc.CategoryName', 'pc.expiredDay as expiredDay')
            ->where('pc.id', '=', $request->id)
            ->first();

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productClinics as ps')
            ->join('productClinicCategories as pc', 'ps.id', 'pc.productClinicId')
            ->join('productClinicLocations as pl', 'ps.id', 'pl.productClinicId')
            ->join('location as l', 'l.id', 'pl.locationId')
            ->select('ps.id', 'ps.fullName', 'ps.expiredDate', 'l.locationName')
            ->where('pc.productCategoryId', '=', $request->id)
            ->where('ps.isDeleted', '=', 0);

        if ($request->locationId) {
            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->search) {
            $data = $data->where('ps.fullName', 'like', '%' . $request->search . '%');
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('ps.updated_at', 'desc');

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
            'category' => $cat,
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ]);
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'categoryName' => 'required|string',
            'expiredDay' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $cat = ProductCategories::find($request->id);

        if (!$cat) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Category Name not found!'],
            ], 422);
        }

        $checkIfValueExits = DB::table('productCategories')
            ->where('CategoryName', '=', $request->categoryName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits === null) {

            ProductCategories::where('id', '=', $request->id)
                ->update(
                    [
                        'categoryName' => $request->categoryName,
                        'expiredDay' => $request->expiredDay,
                        'updated_at' => Carbon::now()
                    ]
                );

            return response()->json(
                [
                    'message' => 'Update Data Successful!',
                ],
                200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Category Name already exists!'],
            ], 422);
        }
    }

    public function export(Request $request)
    {
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

        $fileName = "Rekap Kategori Produk " . $date . ".xlsx";

        return Excel::download(
            new ProductCategoryImport(
                $request->orderValue,
                $request->orderColumn,
            ),
            $fileName
        );
    }

    public function delete(Request $request)
    {
        if (!$request->id) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is no any Data to delete!'],
            ], 422);
        }

        foreach ($request->id as $va) {
            $res = ProductCategories::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {

            $cat = ProductCategories::find($va);
            $cat->DeletedBy = $request->user()->id;
            $cat->isDeleted = true;
            $cat->DeletedAt = Carbon::now();
            $cat->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}
