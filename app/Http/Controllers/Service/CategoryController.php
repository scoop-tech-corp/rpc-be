<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;
use App\Models\ServiceCategories;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Service\ServiceCategoryImport;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        function buildQuery(Request $request)
        {
            $data = DB::table('serviceCategory as sc')->where('sc.isDeleted', '=', 0)->join('users', 'sc.userId', '=', 'users.id');

            if ($request->search) {
                $data = $data->where('sc.categoryName', 'like', '%' . $request->search . '%')->orWhere('users.firstName', 'like', '%' . $request->search . '%');
            }

            if ($request->orderValue && $request->orderColumn != 'totalProduct') {
                $orderByColumn = $request->orderColumn == 'createdAt' ? 'sc.updated_at' : $request->orderColumn;
                $data = $data->orderBy($orderByColumn, $request->orderValue);
            } else {
                $data = $data->orderBy('sc.updated_at', 'desc');
            }

            return $data->select('sc.id', 'sc.categoryName', 'sc.created_at', 'sc.updated_at', DB::raw("DATE_FORMAT(sc.updated_at, '%d/%m/%Y') as createdAt"),'users.firstName as createdBy');
        }

        $data = buildQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'categoryName' => 'required|string',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        $checkIfValueExits = DB::table('serviceCategory')
            ->where('CategoryName', '=', $request->categoryName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits) {
            return responseErrorValidation('Category Name already exists!', ['Category Name already exists!']);
        }

        $result = ServiceCategories::create([
            'categoryName' => $request->categoryName,
            'userId' => $request->user()->id,
        ]);
        return responseSuccess($result);
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'categoryName' => 'required|string',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        $cat = ServiceCategories::find($request->id);

        if (!$cat) {
            return responseErrorValidation('Category Name not found!');
        }

        $checkIfValueExits = DB::table('serviceCategory')
            ->where('CategoryName', '=', $request->categoryName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits) {
            return responseErrorValidation(['Category Name already exists!']);
        }

        ServiceCategories::where('id', '=', $request->id)->update([
            'categoryName' => $request->categoryName,
            'updated_at' => Carbon::now(),
            'userUpdateId' => $request->user()->id,
        ]);

        $result = ServiceCategories::find($request->id);

        return responseSuccess($result, 'Update Data Successful!');
    }
    public function export(Request $request)
    {
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

        $fileName = "Rekap Kategori Service " . $date . ".xlsx";

        return Excel::download(
            new ServiceCategoryImport(
                $request->orderValue,
                $request->orderColumn,
            ),
            $fileName
        );
    }

    public function delete(Request $request)
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to delete!']);
        }

        foreach ($request->id as $va) {
            $res = ServiceCategories::find($va);

            if (!$res) {
                return responseErrorValidation(['data with id ' . $va .  ' not found!']);
            }
        }

        foreach ($request->id as $va) {

            $cat = ServiceCategories::find($va);
            $cat->DeletedBy = $request->user()->id;
            $cat->isDeleted = true;
            $cat->DeletedAt = Carbon::now();
            $cat->save();
        }

       return responseSuccess($request->id, 'Delete Data Successful!');
    }
}
