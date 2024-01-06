<?php

namespace App\Http\Controllers\Service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Diagnose;
use App\Models\Task;
use Validator;
use DB;

class DataStaticServiceController extends Controller
{

    public function delete(Request $request)
    {
        if (adminAccess($request->user()->id) != 1) {

            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => 'User Access not Authorize!',
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make($request->all(), [
                'datas' => 'required',
            ]);

            if ($validate->fails()) {

                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }


            foreach ($request->datas as $val) {

                $data_item = [];

                $message = [
                    'id.required' => 'id on data is required',
                    'type.required' => 'type on data is required'
                ];


                $validateDetail = Validator::make(
                    $val,
                    [
                        'id' => 'required',
                        'type' => 'required',
                    ],
                    $message
                );

                if ($validateDetail->fails()) {

                    $errors = $validateDetail->errors()->all();

                    foreach ($errors as $checkisu) {

                        if (!(in_array($checkisu, $data_item))) {
                            array_push($data_item, $checkisu);
                        }
                    }

                    if ($data_item) {

                        return responseInvalid($data_item);
                    }
                }



                $listOrder = array(
                    'diagnose',
                    'task',
                );


                if (!in_array(strtolower($val['type']), $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different keyword',
                        'type' => $listOrder,
                    ], 400);
                }
            }


            foreach ($request->datas as $val) {
                if (strtolower($val['type']) == "diagnose") {
                    Diagnose::where([
                        ['id', '=', $val['id']]
                    ])->update(['isDeleted' => true, 'deletedBy' =>Auth()->user()->id, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "task") {
                    Task::where([
                        ['id', '=', $val['id']]
                    ])->update(['isDeleted' => true, 'deletedBy' =>Auth()->user()->id, 'updated_at' => now()]);
                }
            }

            DB::commit();

            return responseDelete();
        } catch (Exception $e) {
            return responseInvalid([$e]);
        }
    }
    public function getAllStatic()
    {

        $dataDiagnose = Diagnose::select(
            'id',
            DB::raw("'diagnose' as type"),
            'name as typeName',
        )->where('isDeleted', '=', false);



        $dataTask = Task::select(
            'id',
            DB::raw("'task' as type"),
            'name as typeName',
        )->where('isDeleted', '=', false);

        $dataDiagnose = $dataDiagnose
            ->union($dataTask);

        $data = DB::query()->fromSub($dataDiagnose, 'a')
            ->select('id', 'type', 'typeName');

        return $data;
    }


    private function SearchDataStatic(Request $request)
    {

        $data = $this->getAllStatic();

        if ($request->search) {
            $data = $data->where('type', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'type';
            return $temp_column;
        }


        $data = $this->getAllStatic();

        if ($request->search) {
            $data = $data->where('typeName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'typeName';
            return $temp_column;
        }
    }


    public function index(Request $request)
    {

        if (adminAccess($request->user()->id) != 1) {

            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => 'User Access not Authorize!',
            ], 403);
        }

        DB::beginTransaction();

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            $data = $this->getAllStatic();


            if ($request->search) {

                $res = $this->SearchDataStatic($request);

                if ($res == "type") {

                    $data = $data->where('type', 'like', '%' . $request->search . '%');
                } else if ($res == "typeName") {

                    $data = $data->where('typeName', 'like', '%' . $request->search . '%');
                } else {

                    $data = [];
                    return response()->json([
                        'totalPagination' => 0,
                        'data' => $data
                    ], 200);
                }
            }


            if ($request->orderValue) {

                $defaultOrderBy = $request->orderValue;
            }

            $checkOrder = null;
            if ($request->orderColumn && $defaultOrderBy) {

                $listOrder = array(
                    'id',
                    'type',
                    'typeName'
                );

                if (!in_array($request->orderColumn, $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different order column',
                        'orderColumn' => $listOrder,
                    ], 400);
                }


                if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {

                    return responseInvalid(['order value must Ascending: ASC or Descending: DESC ']);
                }

                $checkOrder = true;
            }

            if ($checkOrder) {

                $data = DB::table($data)
                    ->select(
                        'id',
                        'type',
                        'typeName'
                    )
                    ->orderBy($request->orderColumn, $defaultOrderBy);
            }

            if ($request->rowPerPage > 0) {
                $defaultRowPerPage = $request->rowPerPage;
            }

            $goToPage = $request->goToPage;

            $offset = ($goToPage - 1) * $defaultRowPerPage;

            $count_data = $data->count();
            $count_result = $count_data - $offset;

            if ($count_result < 0) {

                $data = $data->offset(0)->limit($defaultRowPerPage)->get();
            } else {
                $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
            }

            $total_paging = $count_data / $defaultRowPerPage;

            return response()->json(['totalPagination' => ceil($total_paging), 'data' => $data], 200);
        } catch (Exception $e) {

            return responseInvalid([$e]);
        }
    }
}
