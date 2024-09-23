<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\DataStaticCustomers;
use App\Models\Customer\CustomerOccupation;
use App\Models\Customer\TitleCustomer;
use App\Models\Customer\ReferenceCustomer;
use App\Models\Customer\PetCategory;
use App\Models\Customer\SourceCustomer;
use App\Models\Customer\TypeIdCustomer;
use App\Models\CustomerGroups;
use Illuminate\Http\Request;
use Validator;
use DB;


class DataStaticCustomerController extends Controller
{
    public function getDataStaticCustomer()
    {
        try {

            $param_customer = [];

            $data_static_telepon = DataStaticCustomers::select(
                'id',
                'value as value',
                'name as name',
            )->where(
                [
                    ['value', '=', 'Telephone'],
                    ['isDeleted', '=', '0']
                ]
            )->get();

            $data_static_messenger = DataStaticCustomers::select(
                'id',
                'value as value',
                'name as name',
            )->where(
                [
                    ['value', '=', 'messenger'],
                    ['isDeleted', '=', '0']
                ]
            )->get();

            $dataStaticUsage = DataStaticCustomers::select(
                'id',
                'value as value',
                'name as name',
            )->where(
                [
                    ['value', '=', 'Usage'],
                    ['isDeleted', '=', '0']
                ]
            )->get();

            $dataTitleCustomer = TitleCustomer::select(
                'id',
                DB::raw("'Title Customer' as value"),
                'titleName as name',
            )->where('isActive', '=', 1)->get();



            $dataCustomerGroup = CustomerGroups::select(
                'id',
                DB::raw("'Customer Group' as value"),
                'customerGroup as name',
            )->where('isDeleted', '=', 0)->get();


            $dataCustomerOccupation = CustomerOccupation::select(
                'id',
                DB::raw("'Occupation Customer' as value"),
                'occupationName as name',
            )->where('isActive', '=', 1)->get();

            $dataTypeIdGroup = TypeIdCustomer::select(
                'id',
                DB::raw("'Type Id' as value"),
                'typeName as name',
            )->where('isActive', '=', 1)->get();

            $dataCustomerReference = ReferenceCustomer::select(
                'id',
                DB::raw("'Reference Customer' as value"),
                'referenceName as name',
            )->where('isActive', '=', 1)->get();

            $dataPetCategory = PetCategory::select(
                'id',
                DB::raw("'Pet Category' as value"),
                'petCategoryName as name',
            )->where('isActive', '=', 1)->get();


            $dataSourceCustomer = SourceCustomer::select(
                'id',
                DB::raw("'Source Reminder' as value"),
                'sourceName as name',
            )->where('isActive', '=', 1)->get();

            $param_customer = array('dataStaticTelephone' => $data_static_telepon);
            $param_customer['dataStaticMessenger'] = $data_static_messenger;
            $param_customer['dataStaticUsage'] = $dataStaticUsage;
            $param_customer['dataStaticTitleCustomer'] = $dataTitleCustomer;
            $param_customer['dataStaticCustomerOccupation'] = $dataCustomerOccupation;
            $param_customer['dataStaticCustomerReference'] = $dataCustomerReference;
            $param_customer['dataStaticPetCategory'] = $dataPetCategory;
            $param_customer['dataStaticSourceCustomer'] = $dataSourceCustomer;
            $param_customer['dataStaticCustomerGroup'] = $dataCustomerGroup;
            $param_customer['dataStaticCustomerTypeId'] = $dataTypeIdGroup;

            return response()->json($param_customer, 200);
        } catch (Exception $e) {

            return responseInvalid([$e]);
        }
    }



    public function insertDataStaticCustomer(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:255',
        ]);

        DB::beginTransaction();

        try {


            $listOrder = array(
                'title customer',
                'occupation customer',
                'reference customer',
                'pet category',
                'source reminder',
                'customer group',
                'type id',
                'usage',
                'telephone',
                'messenger'
            );


            if (!in_array(strtolower($request->input('keyword')), $listOrder)) {

                return response()->json([
                    'message' => 'failed',
                    'errors' => 'Please try different keyword',
                    'type' => $listOrder,
                ], 400);
            }


            if (strtolower($request->input('keyword')) == "messenger" || strtolower($request->input('keyword')) == "telephone"  || strtolower($request->input('keyword')) == "usage") {

                $checkIfValueExits = DataStaticCustomers::where([
                    ['value', '=', $request->input('keyword')],
                    ['name', '=', $request->input('name')],
                    ['isDeleted', '=', '0']
                ])->first();

                if ($checkIfValueExits != null) {

                    return responseInvalid(['Data static customer already exists! Please choose another name !']);
                } else {

                    $DataStatic = new DataStaticCustomers();
                    $DataStatic->value = $request->input('keyword');
                    $DataStatic->name = $request->input('name');
                    $DataStatic->isDeleted = 0;
                    $DataStatic->created_at = now();
                    $DataStatic->updated_at = now();
                    $DataStatic->save();
                }
            } else if (strtolower($request->input('keyword')) == "title customer") {

                $checkIfValueExits = TitleCustomer::where([
                    ['titleName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkIfValueExits != null) {

                    return responseInvalid(['Title Customer already exists! Please choose another name !']);
                } else {

                    $TitleCustomer = new TitleCustomer();
                    $TitleCustomer->titleName = $request->input('name');
                    $TitleCustomer->isActive = 1;
                    $TitleCustomer->created_at = now();
                    $TitleCustomer->updated_at = now();
                    $TitleCustomer->save();
                }
            } else if (strtolower($request->input('keyword')) == "occupation customer") {


                $checkDataExists =  CustomerOccupation::where([
                    ['occupationName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Occupation customer already exists! Please try different value!']);
                } else {

                    $CustomerOccupation = new CustomerOccupation();
                    $CustomerOccupation->occupationName = $request->input('name');
                    $CustomerOccupation->isActive = 1;
                    $CustomerOccupation->created_at = now();
                    $CustomerOccupation->updated_at = now();
                    $CustomerOccupation->save();
                }
            } else if (strtolower($request->input('keyword')) == "reference customer") {

                $checkDataExists =  ReferenceCustomer::where([
                    ['referenceName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Reference Customer already exists! Please try different value!']);
                } else {

                    $ReferenceCustomer = new ReferenceCustomer();
                    $ReferenceCustomer->referenceName = $request->input('name');
                    $ReferenceCustomer->isActive = 1;
                    $ReferenceCustomer->created_at = now();
                    $ReferenceCustomer->updated_at = now();
                    $ReferenceCustomer->save();
                }
            } else if (strtolower($request->input('keyword')) == "source reminder") {

                $checkDataExists =  SourceCustomer::where([
                    ['sourceName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Source reminder already exists! Please try different value!']);
                } else {

                    $SourceCustomer = new SourceCustomer();
                    $SourceCustomer->sourceName = $request->input('name');
                    $SourceCustomer->isActive = 1;
                    $SourceCustomer->created_at = now();
                    $SourceCustomer->updated_at = now();
                    $SourceCustomer->save();
                }
            } else if (strtolower($request->input('keyword')) == "customer group") {

                $checkDataExists =  CustomerGroups::where([
                    ['customerGroup', '=', $request->input('name')],
                    ['isDeleted', '=', '0']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Customer group name already exists! Please try different value!']);
                } else {

                    $CustomerGroups = new CustomerGroups();
                    $CustomerGroups->customerGroup = $request->input('name');
                    $CustomerGroups->isDeleted = 0;
                    $CustomerGroups->userId = $request->user()->id;
                    $CustomerGroups->created_at = now();
                    $CustomerGroups->updated_at = now();
                    $CustomerGroups->save();
                }
            } else if (strtolower($request->input('keyword')) == "type id") {

                $checkDataExists =  TypeIdCustomer::where([
                    ['typeName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Type Id customer already exists! Please try different value!']);
                } else {

                    $TypeIdCustomer = new TypeIdCustomer();
                    $TypeIdCustomer->typeName = $request->input('name');
                    $TypeIdCustomer->isActive = 1;
                    $TypeIdCustomer->created_at = now();
                    $TypeIdCustomer->updated_at = now();
                    $TypeIdCustomer->save();
                }
            } else if (strtolower($request->input('keyword')) == "pet category") {


                $checkDataExists =  PetCategory::where([
                    ['petCategoryName', '=', $request->input('name')],
                    ['isActive', '=', '1']
                ])->first();

                if ($checkDataExists) {

                    return responseInvalid(['Pet Category already exists! Please try different value!']);
                } else {

                    $PetCategory = new PetCategory();
                    $PetCategory->petCategoryName = $request->input('name');
                    $PetCategory->isActive = 1;
                    $PetCategory->created_at = now();
                    $PetCategory->updated_at = now();
                    $PetCategory->save();
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
            DB::raw("'Source Reminder' as type"),
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
        )->where([
            ['isDeleted', '=', '0'],
            ['value', '=', 'Messenger']
        ]);

        $dataStaticTypeId = TypeIdCustomer::select(
            'id',
            DB::raw("'ID Type' as type"),
            'typeName as typeName',
        )->where('isActive', '=', 1);

        $dataTitleCustomer = $dataTitleCustomer
            ->union($dataCustomerOccupation)
            ->union($dataCustomerReference)
            ->union($dataPetCategory)
            ->union($dataSourceCustomer)
            ->union($dataCustomerGroup)
            ->union($dataStaticTypeId)
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

                    return responseInvalid(['order value must Ascending: ASC or Descending: DESC']);
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

                        return responseInvalid([$data_item]);
                    }
                }



                $listOrder = array(
                    'title customer',
                    'occupation customer',
                    'reference customer',
                    'pet category',
                    'source reminder',
                    'customer group',
                    'type id',
                    'usage',
                    'telephone',
                    'messenger'
                );


                if (!in_array(strtolower($val['type']), $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different type',
                        'type' => $listOrder,
                    ], 400);
                }


                if (strtolower($val['type']) == "messenger" || strtolower($val['type']) == "telephone"  || strtolower($val['type']) == "usage") {

                    $checkDataExists =  DataStaticCustomers::where([
                        ['value', '=', strtolower($val['type'])],
                        ['id', '=', $val['id']],
                        ['isDeleted', '=', '0']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Data Static ' . $val['type'] . ' is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "title customer") {

                    $checkDataExists =  TitleCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']

                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Title customer id is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "occupation customer") {

                    $checkDataExists =  CustomerOccupation::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Customer occupation id is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "reference customer") {

                    $checkDataExists =  ReferenceCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Reference customer is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "source reminder") {

                    $checkDataExists =  SourceCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']

                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Source reminder is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "customer group") {

                    $checkDataExists =  CustomerGroups::where([
                        ['id', '=', $val['id']],
                        ['isDeleted', '=', '0']

                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Customer group is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "type id") {

                    $checkDataExists =  TypeIdCustomer::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Type Id is not exists , please try different id !']);
                    }
                } else if (strtolower($val['type']) == "pet category") {

                    $checkDataExists =  PetCategory::where([
                        ['id', '=', $val['id']],
                        ['isActive', '=', '1']
                    ])->first();

                    if (!$checkDataExists) {

                        return responseInvalid(['Pet Category is not exists , please try different id !']);
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
                } else if (strtolower($val['type']) == "source reminder") {

                    SourceCustomer::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "customer group") {

                    CustomerGroups::where([
                        ['id', '=', $val['id']]
                    ])->update(['isDeleted' => 1, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "type id") {

                    TypeIdCustomer::where([
                        ['id', '=', $val['id']]
                    ])->update(['isActive' => 0, 'updated_at' => now()]);
                } else if (strtolower($val['type']) == "pet category") {

                    PetCategory::where([
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
}
