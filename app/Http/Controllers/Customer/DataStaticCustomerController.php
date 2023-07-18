<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\DataStaticCustomers;
use App\Models\Customer\CustomerOccupation;
use App\Models\Customer\TitleCustomer;
use App\Models\Customer\ReferenceCustomer;
use App\Models\Customer\PetCategory;
use App\Models\Customer\SourceCustomer;
use App\Models\CustomerGroups;


use Illuminate\Http\Request;
use Validator;
use DB;


class DataStaticCustomerController extends Controller
{
    public function getDataStaticCustomer(Request $request)
    {
        try {

            $param_customer = [];

            $data_static_telepon = DataStaticCustomers::select(
                'value as value',
                'name as name',
            )->where('value', '=', 'Telephone')
                ->get();

            $data_static_messenger = DataStaticCustomers::select(
                'value as value',
                'name as name',
            )->where('value', '=', 'messenger')
                ->get();

            $dataStaticUsage = DataStaticCustomers::select(
                'value as value',
                'name as name',
            )->where('value', '=', 'Usage')
                ->get();

            $param_customer = array('dataStaticTelephone' => $data_static_telepon);
            $param_customer['dataStaticMessenger'] = $data_static_messenger;
            $param_customer['dataStaticUsage'] = $dataStaticUsage;

            return response()->json($param_customer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }



    public function insertDataStaticCustomer(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:255',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DataStaticCustomers::where([
                ['value', '=', $request->input('keyword')],
                ['name', '=', $request->input('name')]
            ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Data static customer already exists, please choose another keyword and name',
                ]);
            } else {

                $DataStatic = new DataStaticCustomers();
                $DataStatic->value = $request->input('keyword');
                $DataStatic->name = $request->input('name');
                $DataStatic->isDeleted = 0;
                $DataStatic->created_at = now();
                $DataStatic->updated_at = now();
                $DataStatic->save();

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted data static customer',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }



    public function getAllStatic()
    {


        $dataTitleCustomer = TitleCustomer::select(
            'id',
            DB::raw("'Title Customer' as type"),
            'titleName as typeName',
        )->where('isActive', '=', 1);

        $dataCustomerOccupation = CustomerOccupation::select(
            'id',
            DB::raw("'Occupation Customer' as type"),
            'occupationName as typeName',
        )->where('isActive', '=', 1);


        $dataCustomerReference = ReferenceCustomer::select(
            'id',
            DB::raw("'Reference Customer' as type"),
            'referenceName as typeName',
        )->where('isActive', '=', 1);

        $dataPetCategory = PetCategory::select(
            'id',
            DB::raw("'Pet Category' as type"),
            'petCategoryName as typeName',
        )->where('isActive', '=', 1);


        $dataSourceCustomer = SourceCustomer::select(
            'id',
            DB::raw("'Source Customer' as type"),
            'sourceName as typeName',
        )->where('isActive', '=', 1);

        $dataCustomerGroup = CustomerGroups::select(
            'id',
            DB::raw("'Customer Group' as type"),
            'customerGroup as typeName',
        )->where('isDeleted', '=', 0);


        $dataStaticUsage = DataStaticCustomers::select(
            'id',
            DB::raw("'Usage' as type"),
            'name as typeName',
        )
            ->where([
                ['isDeleted', '=', '0'],
                ['value', '=', 'Usage']
            ]);


        $dataStaticTelephone = DataStaticCustomers::select(
            'id',
            DB::raw("'Telephone' as type"),
            'name as typeName',
        )
            ->where([
                ['isDeleted', '=', '0'],
                ['value', '=', 'Telephone']
            ]);


        $dataStaticCustomer = DataStaticCustomers::select(
            'id',
            DB::raw("'Messenger' as type"),
            'name as typeName',
        )
            ->where([
                ['isDeleted', '=', '0'],
                ['value', '=', 'Messenger']
            ]);


        $dataTitleCustomer = $dataTitleCustomer
            ->union($dataCustomerOccupation)
            ->union($dataCustomerReference)
            ->union($dataPetCategory)
            ->union($dataSourceCustomer)
            ->union($dataCustomerGroup)
            ->union($dataStaticUsage)
            ->union($dataStaticTelephone)
            ->union($dataStaticCustomer);


        $data = DB::query()->fromSub($dataTitleCustomer, 'a')
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

    public function indexDataStaticCustomer(Request $request)
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
                    ]);
                }


                if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'order value must Ascending: ASC or Descending: DESC ',
                    ]);
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

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }


    public function deleteDataStaticCustomer(Request $request)
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

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }


            foreach ($request->datas as $val) {

                $data_item = [];

                $message = [
                    'id.required' => 'id on data is required',
                    'type.required' => 't   ype on data is required'
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

                        return response()->json([
                            'message' =>  'Inputed data is not valid',
                            'errors' => $data_item,
                        ], 422);
                    }
                }



                $listOrder = array(
                    'title customer',
                    'occupation customer',
                    'reference customer',
                    'pet category',
                    'source customer',
                    'customer group',
                    'usage',
                    'telephone',
                    'messenger'
                );


                if (!in_array(strtolower($val['type']), $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different type',
                        'type' => $listOrder,
                    ]);
                }


                if (strtolower($val['type']) == "messenger" || strtolower($val['type']) == "telephone"  || strtolower($val['type']) == "usage") {

                    $checkDataExists =  DataStaticCustomers::where([
                        ['value', '=', strtolower($val['type'])],
                        ['id', '=', $val['id']],
                        ['isDeleted', '=', '0']
                    ])->first();

                    if (!$checkDataExists) {

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Data Static ' . $val['type'] . ' is not exists , please try different id !'],
                        ], 422);
                    }
                } else if (strtolower($val['type']) == "title customer") {

                    $checkDataExists =  TitleCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']

                    ])->first();

                    if (!$checkDataExists) {

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Title customer id is not exists , please try different id !'],
                        ], 422);
                    }
                } else if (strtolower($val['type']) == "occupation customer") {

                    $checkDataExists =  CustomerOccupation::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Customer occupation id is not exists , please try different id !'],
                        ], 422);
                    }
                } else if (strtolower($val['type']) == "reference customer") {

                    $checkDataExists =  ReferenceCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Reference customer is not exists , please try different id !'],
                        ], 422);
                    }
                } else if (strtolower($val['type']) == "source customer") {

                    $checkDataExists =  SourceCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']

                    ])->first();

                    if (!$checkDataExists) {

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Source customer is not exists , please try different id !'],
                        ], 422);
                    }
                } else if (strtolower($val['type']) == "customer group") {

                    $checkDataExists =  CustomerGroups::where([
                        ['id', '=', $val['id']],
                        ['isDeleted', '=', '0']

                    ])->first();

                    if (!$checkDataExists) {

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Customer group is not exists , please try different id !'],
                        ], 422);
                    }
                }
            }


            foreach ($request->datas as $val) {


                if (strtolower($val['type']) == "messenger" || strtolower($val['type']) == "telephone"  || strtolower($val['type']) == "usage") {

                    DataStaticCustomers::where([
                        ['value', '=', strtolower($val['type'])],
                        ['id', '=', $val['id']]
                    ])->update(['isDeleted' => 1, 'updated_at' => now()]); 
                } else if (strtolower($val['type']) == "title customer") {

                    TitleCustomer::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]); 

                } else if (strtolower($val['type']) == "occupation customer") { 

                    CustomerOccupation::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);

                } else if (strtolower($val['type']) == "reference customer") { 

                    ReferenceCustomer::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "source customer") { 

                    SourceCustomer::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]); 
                } else if (strtolower($val['type']) == "customer group") {

                    CustomerGroups::where([
                        ['id', '=', $val['id']]
                    ])->update(['isDeleted' => 1, 'updated_at' => now()]);
                }
            }

            DB::commit();


            return response()->json(
                [
                    'result' => 'success',
                    'message' => 'Deleted Data Static Customer Successful!',
                ],
                200
            );
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }
}
