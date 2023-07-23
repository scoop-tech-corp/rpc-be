<?php

namespace App\Http\Controllers\Staff;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Staff\DataStaticStaff;
use App\Models\Staff\JobTitle;
use App\Models\Staff\TypeId;
use App\Models\Staff\PayPeriod;
use Validator;
use DB;

class DataStaticStaffController extends Controller
{

    public function getDataStaticStaff()
    {
        try {

            $param_customer = [];

            $data_static_telepon = DataStaticStaff::select(
                'id',
                'value as value',
                'name as name',
            )->where(
                [
                    ['value', '=', 'Telephone'],
                    ['isDeleted', '=', '0']
                ]
            )->get();


            $data_static_messenger = DataStaticStaff::select(
                'id',
                'value as value',
                'name as name',
            )->where(
                [
                    ['value', '=', 'Messenger'],
                    ['isDeleted', '=', '0']
                ]
            )->get();

            $dataStaticUsage = DataStaticStaff::select(
                'id',
                'value as value',
                'name as name',
            )->where(
                [
                    ['value', '=', 'Usage'],
                    ['isDeleted', '=', '0']
                ]
            )->get();


            $dataTypeId = TypeId::select(
                'id',
                DB::raw("'Type id' as value"),
                'typeName as name',
            )->where('isActive', '=', 1)
                ->get();


            $dataPayPeriod = PayPeriod::select(
                'id',
                DB::raw("'Pay Period' as value"),
                'periodName as name',
            )->where('isActive', '=', 1)
                ->get();

            $dataJobTitle = JobTitle::select(
                'id',
                DB::raw("'Job Title' as value"),
                'jobName as name',
            )->where('isActive', '=', 1)
                ->get();

            $param_customer = array('dataStaticTelephone' => $data_static_telepon);
            $param_customer['dataStaticMessenger'] = $data_static_messenger;
            $param_customer['dataStaticUsage'] = $dataStaticUsage;
            $param_customer['dataStaticTypeId'] = $dataTypeId;
            $param_customer['dataStaticPayPeriod'] = $dataPayPeriod;
            $param_customer['dataStaticJobTitle'] = $dataJobTitle;

            return response()->json($param_customer, 200);
        } catch (Exception $e) {

            return responseInvalid([$e]);
        }
    }


    public function deleteDataStaticStaff(Request $request)
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
                    'job title',
                    'type id',
                    'pay period',
                    'usage',
                    'telephone',
                    'messenger'
                );


                if (!in_array(strtolower($val['type']), $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different keyword',
                        'type' => $listOrder,
                    ],400 );
    
                }


                if (strtolower($val['type']) == "messenger" || strtolower($val['type']) == "telephone"  || strtolower($val['type']) == "usage") {

                    $checkDataExists =  DataStaticStaff::where([
                        ['value', '=', strtolower($val['type'])],
                        ['id', '=', $val['id']],
                        ['isDeleted', '=', '0']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Data Static ' . $val['type'] . ' is not exists! Please try different id !']);
                    }
                } else if (strtolower($val['type']) == "job title") {

                    $checkDataExists =  JobTitle::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']

                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Job Title id is not exists! Please try different id !']);
                    }
                } else if (strtolower($val['type']) == "type id") {

                    $checkDataExists =  TypeId::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Type id is not exists! Please try different id !']);
                    }
                } else if (strtolower($val['type']) == "pay period") {

                    $checkDataExists =  PayPeriod::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Pay period id is not exists! Please try different id !']);
                    }
                }
            }


            foreach ($request->datas as $val) {


                if (strtolower($val['type']) == "messenger" || strtolower($val['type']) == "telephone"  || strtolower($val['type']) == "usage") {

                    DataStaticStaff::where([
                        ['value', '=', strtolower($val['type'])],
                        ['id', '=', $val['id']]
                    ])->update(['isDeleted' => 1, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "job title") {

                    JobTitle::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "type id") {

                    TypeId::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "pay period") {

                    PayPeriod::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);
                }
            }

            DB::commit();

            return responseDelete();

        } catch (Exception $e) {

            return responseInvalid([$e]);
        }
    }


    public function insertDataStaticStaff(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:255|string',
        ]);

        DB::beginTransaction();

        try {


            $listOrder = array(
                'job title',
                'type id',
                'pay period',
                'usage',
                'telephone',
                'messenger'
            );


            if (!in_array(strtolower($request->input('keyword')), $listOrder)) {

                return response()->json([
                    'message' => 'failed',
                    'errors' => 'Please try different keyword',
                    'type' => $listOrder,
                ],400 );

            }


            if (strtolower($request->input('keyword')) == "messenger" || strtolower($request->input('keyword')) == "telephone"  || strtolower($request->input('keyword')) == "usage") {

                $checkIfValueExits = DataStaticStaff::where([
                    ['value', '=', $request->input('keyword')],
                    ['name', '=', $request->input('name')]
                ])->first();

                if ($checkIfValueExits != null) {

                    return response()->json([
                        'result' => 'Failed',
                        'message' => 'Data static staff already exists! Please choose another keyword and name !',
                    ]);
                } else {

                    $DataStatic = new DataStaticStaff();
                    $DataStatic->value = $request->input('keyword');
                    $DataStatic->name = $request->input('name');
                    $DataStatic->isDeleted = 0;
                    $DataStatic->created_at = now();
                    $DataStatic->updated_at = now();
                    $DataStatic->save();
                }
            } else if (strtolower($request->input('keyword')) == "job title") {

                $checkDataExists =  JobTitle::where([
                    ['jobName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Job Title already exists! Please try different value!']);
                } else {

                    $JobTitle = new JobTitle();
                    $JobTitle->jobName = $request->input('name');
                    $JobTitle->isActive = 1;
                    $JobTitle->created_at = now();
                    $JobTitle->updated_at = now();
                    $JobTitle->save();
                    
                }
            } else if (strtolower($request->input('keyword')) == "type id") {

                $checkDataExists =  TypeId::where([
                    ['typeName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Type id already exists! Please try different value!']);
                } else {

                    $TypeId = new TypeId();
                    $TypeId->typeName = $request->input('name');
                    $TypeId->isActive = 1;
                    $TypeId->created_at = now();
                    $TypeId->updated_at = now();
                    $TypeId->save();
                }
            } else if (strtolower($request->input('keyword')) == "pay period") {

                $checkDataExists =  PayPeriod::where([
                    ['periodName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Pay period already exists! Please try different value!']);
                } else {

                    $payPeriod = new PayPeriod();
                    $payPeriod->periodName = $request->input('name');
                    $payPeriod->isActive = 1;
                    $payPeriod->created_at = now();
                    $payPeriod->updated_at = now();
                    $payPeriod->save();
                }
            }

            DB::commit();


            return responseCreate();

        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }




    public function getAllStatic()
    {

        $dataJobTitle = JobTitle::select(
            'id',
            DB::raw("'Job Title' as type"),
            'jobName as typeName',
        )->where('isActive', '=', 1);


        $dataTypeId = TypeId::select(
            'id',
            DB::raw("'Type id' as type"),
            'typeName as typeName',
        )->where('isActive', '=', 1);

        $dataPayPeroid = PayPeriod::select(
            'id',
            DB::raw("'Pay Period' as type"),
            'periodName as typeName',
        )->where('isActive', '=', 1);

        $dataStaticUsage = DataStaticStaff::select(
            'id',
            DB::raw("'Usage' as type"),
            'name as typeName',
        )->where([
            ['isDeleted', '=', '0'],
            ['value', '=', 'Usage']
        ]);

        $dataStaticTelephone = DataStaticStaff::select(
            'id',
            DB::raw("'Telephone' as type"),
            'name as typeName',
        )
            ->where([
                ['isDeleted', '=', '0'],
                ['value', '=', 'Telephone']
            ]);

        $dataStaticMessenger = DataStaticStaff::select(
            'id',
            DB::raw("'Messenger' as type"),
            'name as typeName',
        )
            ->where([
                ['isDeleted', '=', '0'],
                ['value', '=', 'Messenger']
            ]);

        $dataTypeId = $dataTypeId
            ->union($dataJobTitle)
            ->union($dataPayPeroid)
            ->union($dataStaticUsage)
            ->union($dataStaticTelephone)
            ->union($dataStaticMessenger);

        $data = DB::query()->fromSub($dataTypeId, 'a')
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


    public function indexDataStaticStaff(Request $request)
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
                    ],400);
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
