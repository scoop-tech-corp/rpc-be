<?php

namespace App\Http\Controllers;

use App\Exports\exportFacility;
use DB;
use File;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class FacilityController extends Controller
{

    public function createFacility(Request $request)
    {
        DB::beginTransaction();

        try
        {

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
                ->where([['id', '=', $request->locationId],
                    ['isDeleted', '=', '0']])
                ->first();

            if (!$checkIflocationexists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Spesific location id not exists please try different id!'],
                ], 422);
            }

            $checkIfDataExits = DB::table('facility')
                ->where([['locationId', '=', $request->input('locationId')],
                    ['isDeleted', '=', '0']])
                ->first();

            if ($checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Facility with location id already Exist, please try different name !'],
                ], 422);
            }

            $flag = false;

            if ($request->hasfile('images')) {

                $flag = true;

                $data_item = [];

                $files[] = $request->file('images');

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $file_size = $fil->getSize();

                        $file_size = $file_size / 1024;

                        $oldname = $fil->getClientOriginalName();

                        if ($file_size >= 5000) {

                            array_push($data_item, 'Photo ' . $oldname . ' size more than 5mb! Please upload less than 5mb!');
                        }
                    }
                }

                if ($data_item) {

                    return response()->json([
                        'message' => 'Inputed photo is not valid',
                        'errors' => $data_item,
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

            if ($request->unit) {

                $arraunit = json_decode($request->unit, true);

                $check = Validator::make($arraunit, [
                    "*.unitName" => 'required|max:25',
                    "*.notes" => 'required|max:300',
                    "*.status" => 'required|integer',
                    "*.capacity" => 'required|integer',
                    "*.amount" => 'required|integer',
                ]);

                if ($check->fails()) {
                    $errors = $check->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errors,
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
                'created_at' => now()]);

            if ($request->unit) {

                foreach ($arraunit as $val) {

                    $checkIfFacilityExits = DB::table('facility_unit')
                        ->where([['locationId', '=', $request->input('locationId')],
                            ['unitName', '=', $val['unitName']],
                            ['isDeleted', '=', '0']])
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

        try
        {

            $data_item = [];

            foreach ($request->locationId as $val) {

                $checkIfDataExits = DB::table('facility')
                    ->where([['locationId', '=', $val],
                        ['isDeleted', '=', '0']])
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
                    ->where([['locationId', '=', $val],
                        ['isDeleted', '=', '0']])
                    ->update(['isDeleted' => 1, 'updated_at' => now()]);

                DB::table('facility_unit')
                    ->where([['locationId', '=', $val],
                        ['isDeleted', '=', '0']])
                    ->update(['isDeleted' => 1, 'updated_at' => now()]);

                DB::table('facility_images')
                    ->where([['locationId', '=', $val],
                        ['isDeleted', '=', '0']])
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
            ->where([['facility.locationId', '=', $locationId],
                ['facility.isDeleted', '=', '0']])
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
                    'facility.description as description', )
                ->where([['facility.locationId', '=', $locationId],
                    ['facility.isDeleted', '=', '0']])
                ->first();

            $fasilitas_unit = DB::table('facility_unit')
                ->select(
                    'facility_unit.id as id',
                    'facility_unit.locationId as locationId',
                    'facility_unit.unitName as unitName',
                    'facility_unit.status as status',
                    'facility_unit.capacity as capacity',
                    'facility_unit.amount as amount',
                    'facility_unit.notes as notes', )
                ->where([['facility_unit.locationId', '=', $locationId],
                    ['facility_unit.isDeleted', '=', '0']])
                ->get();

            $facility->unit = $fasilitas_unit;

            $fasilitas_images = DB::table('facility_images')
                ->select('facility_images.id as id',
                    'facility_images.locationId as locationId',
                    'facility_images.labelName as labelName',
                    'facility_images.imagePath as imagePath', )
                ->where([['facility_images.locationId', '=', $locationId],
                    ['facility_images.isDeleted', '=', '0']])
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
            ->where([['facility_images.locationId', '=', $request->input('locationId')],
                ['facility_images.isDeleted', '=', '0']])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists please try different id",
            ]);

        } else {

            $images = DB::table('facility_images')
                ->select('facility_images.id as id',
                    'facility_images.locationId as locationId',
                    'facility_images.labelName as labelName',
                    'facility_images.imagePath as imagePath', )
                ->where([['facility_images.locationId', '=', $request->input('locationId')],
                    ['facility_images.isDeleted', '=', '0']]);

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
            ->select('facility_images.id as id',
                'facility_images.locationId as locationId',
                'facility_images.labelName as labelName',
                'facility_images.imagePath as imagePath', )
            ->where([['facility_images.locationId', '=', $request->locationId],
                ['facility_images.isDeleted', '=', '0']]);

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
        DB::beginTransaction();

        try
        {

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
                ->where([['id', '=', $request->locationId],
                    ['isDeleted', '=', '0']])
                ->first();

            if (!$checkIflocationexists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Spesific location id not exists please try different id!'],
                ], 422);
            }

            if ($request->unit) {

                if (count($request->unit) != 0) {

                    $check = Validator::make($request->unit, [

                        "*.unitName" => 'required|max:25',
                        "*.notes" => 'required|max:300',
                        "*.status" => 'required|integer',
                        "*.capacity" => 'required|integer',
                        "*.amount" => 'required|integer',
                    ]);

                    if ($check->fails()) {
                        $errors = $check->errors()->all();

                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => $errors,
                        ], 422);
                    }

                    //UPDATE
                    DB::table('facility')
                        ->where('locationId', '=', $request->locationId)
                        ->update(['introduction' => $request->introduction,
                            'description' => $request->description,
                            'updated_at' => now(),
                        ]);

                    foreach ($request->unit as $val) {

                        if (isset($val['id'])) {

                            if (isset($val['command'])) {

                                DB::table('facility_unit')
                                    ->where([['locationId', '=', $request->input('locationId')],
                                        ['id', '=', $val['id']],
                                        ['isDeleted', '=', '0']])
                                    ->update(['unitName' => $val['unitName'],
                                        'isDeleted' => 1,
                                        'updated_at' => now(),
                                    ]);

                                DB::table('facility_images')
                                    ->where([['locationId', '=', $request->input('locationId')],
                                        ['id', '=', $val['id']],
                                        ['isDeleted', '=', '0']])
                                    ->update(['isDeleted' => 1,
                                        'updated_at' => now()]);

                            } else {

                                DB::table('facility_unit')
                                    ->where([['locationId', '=', $request->input('locationId')],
                                        ['id', '=', $val['id']],
                                    ])
                                    ->update(['unitName' => $val['unitName'],
                                        'capacity' => $val['capacity'],
                                        'amount' => $val['amount'],
                                        'status' => $val['status'],
                                        'notes' => $val['notes'],
                                        'updated_at' => now(),
                                    ]);
                            }

                        } else {

                            $checkIfDataExits = DB::table('facility_unit')
                                ->where([['locationId', '=', $request->input('locationId')],
                                    ['unitName', '=', $val['unitName']],
                                    ['isDeleted', '=', '0']])
                                ->first();

                            if ($checkIfDataExits != null) {

                                return response()->json([
                                    'result' => 'Failed',
                                    'message' => 'Unit name : ' . $val['unitName'] . ', already exists, please try different unit name',
                                ]);

                            } else {

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
                            }

                        }

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

            $data_item = [];

            $files[] = $request->file('images');

            foreach ($files as $file) {

                foreach ($file as $fil) {

                    $file_size = $fil->getSize();

                    $file_size = $file_size / 1024;

                    $oldname = $fil->getClientOriginalName();

                    if ($file_size >= 5000) {

                        array_push($data_item, 'Photo ' . $oldname . ' size more than 5mb! Please upload less than 5mb!');
                    }
                }
            }

            if ($data_item) {

                return response()->json([
                    'message' => 'Inputed photo is not valid',
                    'errors' => $data_item,
                ], 422);

            }

            $json_array = json_decode($request->imagesName, true);
            $int = 0;

            if (count($json_array) != 0) {

                foreach ($json_array as $val) {

                    if ($val['id'] != "") {

                        if ($val['status'] == "del") {

                            $find_image = DB::table('facility_images')
                                ->select('facility_images.imageName',
                                    'facility_images.imagePath')
                                ->where('id', '=', $val['id'])
                                ->first();

                            if ($find_image) {

                                if (file_exists(public_path() . $find_image->imagePath)) {

                                    File::delete(public_path() . $find_image->imagePath);

                                    DB::table('facility_images')->where([['id', '=', $val['id']]])->delete();
                                }

                            }

                        } else {

                            $find_image = DB::table('facility_images')
                                ->select('facility_images.imageName',
                                    'facility_images.imagePath')
                                ->where('id', '=', $val['id'])
                                ->first();

                            if ($find_image) {

                                DB::table('facility_images')
                                    ->where([['id', '=', $val['id']]])
                                    ->update(['labelName' => $val['name'],
                                        'updated_at' => now(),
                                    ]);

                            }

                        }

                    } else {

                        foreach ($files as $file) {

                            foreach ($file as $fil) {

                                $name = $fil->hashName();
                                $fil->move(public_path() . '/FacilityImages/', $name);

                                $fileName = "/FacilityImages/" . $name;

                                DB::table('facility_images')
                                    ->insert([
                                        'locationId' => $request->input('locationId'),
                                        'labelName' => $val['name'],
                                        'realImageName' => $fil->getClientOriginalName(),
                                        'imageName' => $name,
                                        'imagePath' => $fileName,
                                        'isDeleted' => 0,
                                        'created_at' => now(),
                                    ]);
                            }

                        }
                    }

                }

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'successfuly update image facility',
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

    public function facilityMenuHeader(Request $request)
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = DB::table('location')
            ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as locationId',
                'location.locationName as locationName',
                'location.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->groupBy('location.locationName', 'location.id', 'location.created_at');
            
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
                return response()->json(['totalPagination' => 0,
                    'data' => $data], 200);
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

        $data = $data->orderBy('location.created_at', 'desc');

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
            ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as locationId',
                'location.locationName as locationName',
                'location.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'location.created_at');
 
        if ($request->search || $request->search == 0) {
            $data = $data->where('location.id', '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
         
            $temp_column = 'location.id';
            return $temp_column;
        }
    
        $data = DB::table('location')
            ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as locationId',
                'location.locationName as locationName',
                'location.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'location.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->where('location.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location.locationName';
            return $temp_column;
        }
      
        $data = DB::table('location')
            ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as locationId',
                'location.locationName as locationName',
                'location.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'location.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->having(DB::raw('IFNULL (SUM(facility_unit.capacity),0)'), '=', $request->search);
        }

    
        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(SUM(facility_unit.capacity),0)';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as locationId',
                'location.locationName as locationName',
                'location.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'location.created_at');

        if ($request->search || $request->search == 0) {
            $data = $data->having(DB::raw('IFNULL(count(DISTINCT(facility.locationId)),0)'), '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'IFNULL(count(DISTINCT(facility.locationId)),0)';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin(DB::raw('(select * from facility where isDeleted=0) as facility'),
                function ($join) {
                    $join->on('facility.locationId', '=', 'location.id');
                })
            ->leftjoin(DB::raw('(select * from facility_unit where isDeleted=0) as facility_unit'),
                function ($join) {
                    $join->on('facility_unit.locationId', '=', 'facility.locationId');
                })
            ->select('location.id as locationId',
                'location.locationName as locationName',
                'location.created_at as createdAt',
                DB::raw("IFNULL (SUM(facility_unit.capacity),0) as capacityUsage"),
                DB::raw("IFNULL (count(DISTINCT(facility.locationId)),0) as facilityVariation"),
                DB::raw("IFNULL (count(facility_unit.unitName),0) as unitTotal"))
            ->where([['location.isDeleted', '=', '0']])
            ->groupBy('location.locationName', 'location.id', 'location.created_at');

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

        try
        {
            return Excel::download(new exportFacility, 'Facility.xlsx');

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

        try
        {

            $getLocationFasilitas = DB::table('location')
                ->leftjoin(DB::raw('(select locationId,isDeleted from facility  where isDeleted=0 ) as facility'),
                    function ($join) {
                        $join->on('facility.locationId', '=', 'location.id');
                    })
                ->select('location.id as id',
                    'location.locationName as locationName', )
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
