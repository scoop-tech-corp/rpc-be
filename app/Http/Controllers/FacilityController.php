<?php

namespace App\Http\Controllers;


use DB;
use File;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Validator;
use App\Exports\Facility\exportFacility;

class FacilityController extends Controller
{

    public function createFacility(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!adminAccess($request->user()->id)) {
                return response()->json([
                    'message' => 'The user role was invalid.',
                    'errors' => ['User Access not Authorize!'],
                ], 403);
            }

            $validate = Validator::make($request->all(), [
                'locationId' => 'required|integer',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIflocationexists = DB::table('location')
                ->where([
                    ['id', '=', $request->locationId],
                    ['isDeleted', '=', '0']
                ])
                ->first();

            if (!$checkIflocationexists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Spesific location id not exists please try different id!'],
                ], 422);
            }

            $checkIfDataExits = DB::table('facility')
                ->where([
                    ['locationId', '=', $request->input('locationId')],
                    ['isDeleted', '=', '0']
                ])
                ->first();

            if ($checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Facility with location id already Exist, please try different name !'],
                ], 422);
            }



            $intcheck = 0;
            $flag = false;

            if ($request->hasfile('images')) {

                $flag = true;

                $data_item = [];
                $filteredimage = [];

                $files[] = $request->file('images');

                $json_array_name = json_decode($request->imagesName, true);

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $file_size = $fil->getSize();

                        $file_size = $file_size / 1024;

                        $oldname = $fil->getClientOriginalName();

                        if ($file_size >= 5000) {

                            array_push($data_item, 'Photo ' . $oldname . ' size more than 5mb! Please upload less than 5mb!');
                        }

                        $filteredimage[$json_array_name[$intcheck]['name']][] = $json_array_name[$intcheck];
                        $intcheck = $intcheck + 1;
                    }
                }

                if ($data_item) {

                    return response()->json([
                        'message' => 'Inputed photo is not valid',
                        'errors' => $data_item,
                    ], 422);
                }


                $filteredimage = array_filter($filteredimage, function ($v) {
                    return count($v) > 1;
                });

                if ($filteredimage) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => ['Identical image name , please check again'],
                    ], 422);
                }
            }

            if ($flag == true) {
                if ($request->imagesName) {
                    $ResultImageDatas = json_decode($request->imagesName, true);

                    if (count($ResultImageDatas) != count($request->file('images'))) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Images name and Total image should same!'],
                        ], 422);
                    } else {

                        foreach ($ResultImageDatas as $value) {

                            if ($value['name'] == "") {

                                return response()->json([
                                    'message' => 'The given data was invalid.',
                                    'errors' => ['Image name can not be empty!'],
                                ], 422);
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Image name can not be empty!'],
                    ], 422);
                }
            }

            $inputUnitReal = [];

            if ($request->unit) {

                $arraunit = json_decode($request->unit, true);

                foreach ($arraunit as $val) {

                    if ($val['command'] != "del") {
                        array_push($inputUnitReal, $val);
                    }
                }


                $messages = [
                    'unitName.required' => 'Please input unit name, unit name is required',
                    'unitName.max' => 'Exceeded maximum character, max character for unit name is 25',
                    'notes.max' => 'Exceeded maximum character, max character for notes name is 300',
                    'status.required' => 'Please input status, status is required',
                    'status.integer' => 'Status must be integer',
                    'capacity.required' => 'Please input capacity unit, capacity is required',
                    'capacity.integer' => 'Capacity must be integer',
                    'amount.required' => 'Please input amount unit, amount is required',
                    'amount.integer' => 'Amount must be integer',
                ];

                $data_item = [];
                $filtered = [];

                foreach ($inputUnitReal as $key) {

                    $check = Validator::make($key, [
                        "unitName" => 'required|max:25',
                        "notes" => 'max:300',
                        "status" => 'required|integer',
                        "capacity" => 'required|integer',
                        "amount" => 'required|integer'
                    ], $messages);

                    if ($key['unitName'] == "") {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Unit name can not be empty!'],
                        ], 422);
                    }

                    if ($check->fails()) {

                        $errors = $check->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item))) {
                                array_push($data_item, $checkisu);
                            }
                        }
                    }

                    $filtered[$key['unitName']][] = $key;
                }

                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }

                $filtered = array_filter($filtered, function ($v) {
                    return count($v) > 1;
                });

                if ($filtered) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => ['Identical unit name , please check again'],
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Facility unit can not be empty!'],
                ], 422);
            }




            //INSERT DATA
            DB::table('facility')->insert([
                'locationId' => $request->input('locationId'),
                'introduction' => $request->input('introduction'),
                'description' => $request->input('description'),
                'isDeleted' => 0,
                'created_at' => now()
            ]);

            if ($request->unit) {

                foreach ($inputUnitReal as $val) {

                    $checkIfFacilityExits = DB::table('facility_unit')
                        ->where([
                            ['locationId', '=', $request->input('locationId')],
                            ['unitName', '=', $val['unitName']],
                            ['isDeleted', '=', '0']
                        ])
                        ->first();

                    if ($checkIfFacilityExits === null) {

                        DB::table('facility_unit')->insert([
                            'locationId' => $request->input('locationId'),
                            'unitName' => $val['unitName'],
                            'status' => $val['status'],
                            'capacity' => $val['capacity'],
                            'amount' => $val['amount'],
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);
                    } else {

                        return response()->json([
                            'result' => 'Failed',
                            'message' => "Unit name with spesific location already exists, please try different name",
                        ]);
                    }
                }
            }

            if ($request->hasfile('images')) {

                $json_array = json_decode($request->imagesName, true);

                $int = 0;

                if (count($files) != 0) {

                    foreach ($files as $file) {

                        foreach ($file as $fil) {

                            if ($json_array[$int]['status'] != "del") {

                                $name = $fil->hashName();
                                $fil->move(public_path() . '/FacilityImages/', $name);

                                $fileName = "/FacilityImages/" . $name;

                                DB::table('facility_images')
                                    ->insert([
                                        'locationId' => $request->input('locationId'),
                                        'labelName' => $json_array[$int]['name'],
                                        'realImageName' => $fil->getClientOriginalName(),
                                        'imageName' => $name,
                                        'imagePath' => $fileName,
                                        'isDeleted' => 0,
                                        'created_at' => now(),
                                    ]);
                            }

                            $int = $int + 1;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'Success',
                'message' => 'Successfully inserted new facility',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function deleteFacility(Request $request)
    {


        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }


        DB::beginTransaction();

        $validate = Validator::make($request->all(), [
            'locationId' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        try {

            $data_item = [];

            foreach ($request->locationId as $val) {

                $checkIfDataExits = DB::table('facility')
                    ->where([
                        ['locationId', '=', $val],
                        ['isDeleted', '=', '0']
                    ])
                    ->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'locationId : ' . $val . ' not found, please try different locationId');
                }
            }

            if ($data_item) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => $data_item,
                ], 422);
            }

            foreach ($request->locationId as $val) {

                DB::table('facility')
                    ->where([
                        ['locationId', '=', $val],
                        ['isDeleted', '=', '0']
                    ])
                    ->update(['isDeleted' => 1, 'updated_at' => now()]);

                DB::table('facility_unit')
                    ->where([
                        ['locationId', '=', $val],
                        ['isDeleted', '=', '0']
                    ])
                    ->update(['isDeleted' => 1, 'updated_at' => now()]);

                DB::table('facility_images')
                    ->where([
                        ['locationId', '=', $val],
                        ['isDeleted', '=', '0']
                    ])
                    ->update(['isDeleted' => 1, 'updated_at' => now()]);

                DB::commit();
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted facility',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function facilityDetail(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'locationId' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $locationId = $request->input('locationId');

        $checkIfValueExits = DB::table('facility')
            ->where([
                ['facility.locationId', '=', $locationId],
                ['facility.isDeleted', '=', '0']
            ])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another location id",
            ]);
        } else {

            $facility = DB::table('facility')
                ->join('location', 'location.id', '=', 'facility.locationId')
                ->select(
                    'facility.locationId as locationId',
                    'location.locationName as locationName',
                    'facility.introduction as introduction',
                    'facility.description as description',
                )
                ->where([
                    ['facility.locationId', '=', $locationId],
                    ['facility.isDeleted', '=', '0']
                ])
                ->first();

            $fasilitas_unit = DB::table('facility_unit')
                ->select(
                    'facility_unit.id as id',
                    'facility_unit.locationId as locationId',
                    'facility_unit.unitName as unitName',
                    'facility_unit.status as status',
                    'facility_unit.capacity as capacity',
                    'facility_unit.amount as amount',
                    'facility_unit.notes as notes',
                )
                ->where([
                    ['facility_unit.locationId', '=', $locationId],
                    ['facility_unit.isDeleted', '=', '0']
                ])
                ->get();

            $facility->unit = $fasilitas_unit;

            $fasilitas_images = DB::table('facility_images')
                ->select(
                    'facility_images.id as id',
                    'facility_images.locationId as locationId',
                    'facility_images.labelName as labelName',
                    'facility_images.imagePath as imagePath',
                )
                ->where([
                    ['facility_images.locationId', '=', $locationId],
                    ['facility_images.isDeleted', '=', '0']
                ])
                ->get();

            $facility->images = $fasilitas_images;

            return response()->json($facility, 200);
        }
    }

    public function searchImageFacility(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'locationId' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('facility_images')
            ->where([
                ['facility_images.locationId', '=', $request->input('locationId')],
                ['facility_images.isDeleted', '=', '0']
            ])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists please try different id",
            ]);
        } else {

            $images = DB::table('facility_images')
                ->select(
                    'facility_images.id as id',
                    'facility_images.locationId as locationId',
                    'facility_images.labelName as labelName',
                    'facility_images.imagePath as imagePath',
                )
                ->where([
                    ['facility_images.locationId', '=', $request->input('locationId')],
                    ['facility_images.isDeleted', '=', '0']
                ]);

            if ($request->name) {
                $res = $this->SearchImages($request);

                if ($res) {
                    $images = $images->where($res, 'like', '%' . $request->name . '%');
                } else {
                    $images = [];
                    return response()->json($images, 200);
                }
            }
            $images = $images->orderBy('facility_images.created_at', 'desc');
            $images = $images->get();
            return response()->json(['images' => $images], 200);
        }
    }

    private function SearchImages($request)
    {

        $data = DB::table('facility_images')
            ->select(
                'facility_images.id as id',
                'facility_images.locationId as locationId',
                'facility_images.labelName as labelName',
                'facility_images.imagePath as imagePath',
            )
            ->where([
                ['facility_images.locationId', '=', $request->locationId],
                ['facility_images.isDeleted', '=', '0']
            ]);

        if ($request->name) {
            $data = $data->where('facility_images.labelName', 'like', '%' . $request->name . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'facility_images.labelName';
            return $temp_column;
        }
    }

    public function updateFacility(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make($request->all(), [
                'locationId' => 'required|integer',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIflocationexists = DB::table('location')
                ->where([
                    ['id', '=', $request->locationId],
                    ['isDeleted', '=', '0']
                ])
                ->first();

            if (!$checkIflocationexists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Spesific location id not exists please try different id!'],
                ], 422);
            }


            $data_item = [];
            $inputUnitReal = [];
            $filtered = [];

            if ($request->unit) {

                if (count($request->unit) != 0) {

                    foreach ($request->unit as $val) {

                        if ($val['command'] != "del" || ($val['command'] == "del" && $val['id'] != "")) {
                            array_push($inputUnitReal, $val);
                        }
                    }

                    $messages = [
                        'unitName.required' => 'Please input unit name, unit name is required',
                        'unitName.max' => 'Exceeded maximum character, max character for unit name is 25',
                        'notes.max' => 'Exceeded maximum character, max character for notes name is 300',
                        'status.required' => 'Please input status, status is required',
                        'status.integer' => 'Status must be integer',
                        'capacity.required' => 'Please input capacity unit, capacity is required',
                        'capacity.integer' => 'Capacity must be integer',
                        'amount.required' => 'Please input amount unit, amount is required',
                        'amount.integer' => 'Amount must be integer',
                    ];

                    foreach ($inputUnitReal as $key) {

                        $check = Validator::make($key, [
                            "unitName" => 'required|max:25',
                            "notes" => 'max:300',
                            "status" => 'required|integer',
                            "capacity" => 'required|integer',
                            "amount" => 'required|integer'

                        ], $messages);


                        if ($key['unitName'] == "") {

                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Unit name can not be empty!'],
                            ], 422);
                        }



                        if ($check->fails()) {

                            $errors = $check->errors()->all();

                            foreach ($errors as $checkisu) {

                                if (!(in_array($checkisu, $data_item))) {
                                    array_push($data_item, $checkisu);
                                }
                            }
                        }

                        $filtered[$key['unitName']][] = $key;
                    }


                    if ($data_item) {
                        return response()->json([
                            'message' => 'Inputed data is not valid',
                            'errors' => $data_item,
                        ], 422);
                    }


                    $filtered = array_filter($filtered, function ($v) {
                        return count($v) > 1;
                    });

                    if ($filtered) {
                        return response()->json([
                            'message' => 'Inputed data is not valid',
                            'errors' => ['Identical unit name, please check again'],
                        ], 422);
                    }
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Facility unit can not be empty!'],
                ], 422);
            }



            //UPDATE
            DB::table('facility')
                ->where('locationId', '=', $request->locationId)
                ->update([
                    'introduction' => $request->introduction,
                    'description' => $request->description,
                    'updated_at' => now(),
                ]);


            foreach ($inputUnitReal as $val) {

                if ($val['id'] == "") {

                    DB::table('facility_unit')
                        ->insert([
                            'locationId' => $request->input('locationId'),
                            'unitName' => $val['unitName'],
                            'status' => $val['status'],
                            'capacity' => $val['capacity'],
                            'amount' => $val['amount'],
                            'notes' => $val['notes'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);
                } else {

                    if ($val['command'] == "del") {

                        DB::table('facility_unit')
                            ->where([
                                ['locationId', '=', $request->input('locationId')],
                                ['id', '=', $val['id']],
                                ['isDeleted', '=', '0']
                            ])
                            ->update([
                                'unitName' => $val['unitName'],
                                'isDeleted' => 1,
                                'updated_at' => now(),
                            ]);
                    } else {

                        DB::table('facility_unit')
                            ->where([
                                ['locationId', '=', $request->input('locationId')],
                                ['id', '=', $val['id']],
                            ])
                            ->update([
                                'unitName' => $val['unitName'],
                                'capacity' => $val['capacity'],
                                'amount' => $val['amount'],
                                'status' => $val['status'],
                                'notes' => $val['notes'],
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'successfuly update facility',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function uploadImageFacility(Request $request)
    {


        try {

            $json_array = json_decode($request->imagesName, true);
            $files[] = $request->file('images');
            $index = 0;

            foreach ($json_array as $val) {

                if (($val['id'] == "" || $val['id'] == 0)  && ($val['status'] == "")) { //create new

                    $name = $files[0][$index]->hashName();

                    $files[0][$index]->move(public_path() . '/FacilityImages/', $name);

                    $fileName = "/FacilityImages/" . $name;

                    DB::table('facility_images')
                        ->insert([
                            'locationId' => $request->input('locationId'),
                            'labelName' =>  $val['name'],
                            'realImageName' => $files[0][$index]->getClientOriginalName(),
                            'imageName' => $name,
                            'imagePath' => $fileName,
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);

                    $index = $index + 1;
                } elseif (($val['id'] != "" && $val['id'] != 0)  && ($val['status'] == "del")) { // delete

                    $find_image = DB::table('facility_images')
                        ->select(
                            'facility_images.imageName',
                            'facility_images.imagePath'
                        )
                        ->where('id', '=', $val['id'])
                        ->first();

                    if ($find_image) {

                        if (file_exists(public_path() . $find_image->imagePath)) {

                            File::delete(public_path() . $find_image->imagePath);

                            DB::table('facility_images')->where([['id', '=', $val['id']]])->delete();
                        }
                    }
                } elseif (($val['id'] != "" || $val['id'] != 0)  && ($val['status'] == "")) { // update

                    $find_image = DB::table('facility_images')
                        ->select(
                            'facility_images.imageName',
                            'facility_images.imagePath'
                        )
                        ->where('id', '=', $val['id'])
                        ->first();

                    if ($find_image) {


                        DB::table('facility_images')
                            ->where([['id', '=', $val['id']]])
                            ->update([
                                'labelName' => $val['name'],
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'successfuly update image facility',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function facilityMenuHeader(Request $request)
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($request->search || $request->search == 0) {

            $res = $this->Search($request);

            if (str_contains($res, "location.id")) {

                $data = $data->where($res, '=', $request->search);
            } else if (str_contains($res, "location.locationName")) {

                $data = $data->having($res, 'like', '%' . $request->search . '%');
            } else if (str_contains($res, "facility_unit.capacity")) {

                $data = $data->having(DB::raw('IFNULL(SUM(facility_unit.capacity),0)'), '=', $request->search);
            } else if (str_contains($res, "facility_unit.unitName")) {

                $data = $data->having(DB::raw('IFNULL(count(facility_unit.unitName),0)'), '=', $request->search);
            } else if (str_contains($res, "facility.locationId")) {

                $data = $data->having(DB::raw('IFNULL(count(DISTINCT(facility.locationId)),0)'), '=', $request->search);
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

        if ($request->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'location.id',
                'location.locationName',
                'facility_unit.capacity',
                'facility.locationId',
                'facility_unit.unitName',
            );

            if (!in_array($request->orderColumn, $listOrder)) {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please try different Order Column',
                    'orderColumn' => $listOrder,
                ]);
            }

            if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'order value must Ascending: ASC or Descending: DESC ',
                ]);
            }

            $data = $data->orderBy($request->orderColumn, $defaultOrderBy);
        }

        $data = $data->orderBy('facility.created_at', 'desc');

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
    }

    private function Search($request)
    {

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');
        if ($request->search || $request->search == 0) {
            $data = $data->where('location.id', '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {

            $temp_column = 'location.id';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->where('location.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location.locationName';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->having(DB::raw('IFNULL (SUM(facility_unit.capacity),0)'), '=', $request->search);
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(SUM(facility_unit.capacity),0)';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->having(DB::raw('IFNULL(count(DISTINCT(facility.locationId)),0)'), '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(count(DISTINCT(facility.locationId)),0)';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(
                DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                }
            )
            ->leftjoin(
                DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                }
            )
            ->select(
                'location.id as locationId',
                'location.locationName as locationName',
                'facility.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal")
            )
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'facility.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->having(DB::raw('IFNULL(count(facility_unit.unitName),0)'), '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(count(facility_unit.unitName),0)';
            return $temp_column;
        }
    }

    public function facilityExport(Request $request)
    {

        try {


            $tmp = "";
            $fileName = "";
            $date = Carbon::now()->format('d-m-y');

            if ($request->locationId) {

                $location = DB::table('location')
                    ->select('locationName')
                    ->whereIn('id', $request->locationId)
                    ->get();

                if ($location) {

                    foreach ($location as $key) {
                        $tmp = $tmp . (string) $key->locationName . ",";
                    }
                }
                $tmp = rtrim($tmp, ", ");
            }

            if ($tmp == "") {
                $fileName = "Rekap Fasilitas " . $date . ".xlsx";
            } else {
                $fileName = "Rekap Fasilitas " . $tmp . " " . $date . ".xlsx";
            }

            return Excel::download(
                new exportFacility(
                    $request->orderValue,
                    $request->orderColumn,
                    $request->search,
                    $request->locationId,
                ),
                $fileName
            );

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }



    public function facilityLocation(Request $request)
    {

        try {

            $getLocationFasilitas = DB::table('location')
                ->leftjoin(
                    DB::raw('(select locationId,isDeleted from facility  where isDeleted=0 ) as facility'),
                    function ($join) {
                        $join->on('facility.locationId', '=', 'location.id');
                    }
                )
                ->select(
                    'location.id as id',
                    'location.locationName as locationName',
                )
                ->where([['location.isDeleted', '=', '0']])
                ->whereNull('facility.locationId')
                ->orderBy('location.created_at', 'desc')
                ->get();

            return response()->json($getLocationFasilitas, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }
}
