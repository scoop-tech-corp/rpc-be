<?php

namespace App\Http\Controllers\Staff;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\Staff\ExportAccessControlSchedule;
use Maatwebsite\Excel\Facades\Excel;
use Validator;
use DB;
use Carbon\Carbon;
use DateTime;
use App\Models\User;
use App\Models\Location;
use App\Models\AccessControl\AccessType;
use App\Models\AccessControl\MenuList;
use App\Models\AccessControl\MenuMasters;
use App\Models\Staff\AccessControlSchedule;

class AccessControlSchedulesController extends Controller
{

    public function setSchedulerProgress()
    {

        try {

            $currentDateTime = Carbon::now();

            AccessControlSchedule::where([['endTime', '<=', $currentDateTime], ['isDeleted', '=', '0']])
                ->update(['status' => 'Finished']);

            DB::commit();
        } catch (Exception $e) {

            return responseInvalid($e);
        }
    }



    public function getUsersFromLocationId(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'locationId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfLocationExits = Location::where([['id', '=', $request->locationId], ['isDeleted', '=', '0']])->first();

            if ($checkIfLocationExits == null) {
                return responseInvalid(['Location id not found! please try different id']);
            }

            $data = DB::table('usersLocation as a')
                ->leftJoin('users as b', 'b.id', '=', 'a.usersId')
                ->select(
                    'a.usersId',
                    DB::raw("CONCAT(IFNULL(b.firstName,'') ,' ', IFNULL(b.middleName,'') ,' ', IFNULL(b.lastName,'') ,'(', IFNULL(b.nickName,'') ,')'  ) as name"),
                )
                ->where([['locationId', '=', $request->locationId], ['a.isDeleted', '=', '0']])->get();

            return response()->json($data, 200);

            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function updateAccessControlSchedules(Request $request)
    {

        try {


            $data_item = [];

            if ($request->schedules) {

                $messageSchedules = [
                    'locationId.required' => 'Location id on tab Schedules is required!',
                    'usersId.required' => 'User id on tab Schedules is required!',
                    'masterId.required' => 'Master id on tab Schedules is required!',
                    'menuListId.required' => 'Menu list id on tab Schedules is required!',
                    'accessTypeId.required' => 'Access type id on tab Schedules is required!',
                    'giveAccessNow.required' => 'Give access now on tab Schedules is required!',
                    'integer' => 'The :attribute must be an integer.',
                ];


                foreach ($request->schedules as $val) {

                    if (array_key_exists('command', $val)) {

                        if ($val['command'] != "del") {
                            array_push($input_real, $val);
                        }
                    } else {
                        if (array_key_exists('id', $val)) {
                            // array_push($input_real, $val);


                        }
                    }
                }
            } else {

                return responseInvalid(['Schedules can not be empty!']);
            }










            // $validate = Validator::make($request->all(), [
            //     'locationId' => 'required|integer',
            //     'usersId' => 'required|integer'
            // ]);

            // if ($validate->fails()) {
            //     $errors = $validate->errors()->all();
            //     return responseInvalid($errors);
            // }


            // $checkIfUsersExits = User::where([['id', '=', $request->usersId], ['isDeleted', '=', '0']])->first();

            // if ($checkIfUsersExits == null) {
            //     return responseInvalid(['User id not found! please try different id']);
            // }


            // $checkIfLocationExits = Location::where([['id', '=', $request->locationId], ['isDeleted', '=', '0']])->first();

            // if ($checkIfLocationExits == null) {
            //     return responseInvalid(['Location id not found! please try different id']);
            // }


            // $data_item = [];

            // if ($request->schedules) {

            //     $messageSchedules = [
            //         'masterId.required' => 'Master id on tab Schedules is required!',
            //         'menuListId.required' => 'Menu list id on tab Schedules is required!',
            //         'accessTypeId.required' => 'Access type id on tab Schedules is required!',
            //         'accessLimitId.required' => 'Access limit id on tab Schedules is required!',
            //         'startTime.required' => 'Start time on tab Schedules is required!',
            //         'endTime.required' => 'End time on tab Schedules is required!',
            //     ];

            //     foreach ($request->schedules as $key) {

            //         $validateSchedules = Validator::make(
            //             $key,
            //             [
            //                 'masterId' => 'required',
            //                 'menuListId' => 'required',
            //                 'accessTypeId' => 'required',
            //                 'accessLimitId' => 'required',
            //                 'startTime' => 'required',
            //                 'endTime' => 'required',
            //             ],
            //             $messageSchedules
            //         );

            //         if ($validateSchedules->fails()) {

            //             $errors = $validateSchedules->errors()->all();

            //             foreach ($errors as $checkisu) {

            //                 if (!(in_array($checkisu, $data_item))) {
            //                     array_push($data_item, $checkisu);
            //                 }
            //             }
            //         }

            //         $checkIfMasterExits = MenuMasters::where([['id', '=', $key['masterId']], ['isDeleted', '=', '0']])->first();

            //         if ($checkIfMasterExits == null) {

            //             return responseInvalid(['Master id not found! please try different id']);
            //         }

            //         $checkIfMenuListExits = MenuList::where([['id', '=', $key['menuListId']], ['isActive', '=', '1']])->first();

            //         if ($checkIfMenuListExits == null) {

            //             return responseInvalid(['Menu list id not found! please try different id']);
            //         }

            //         $checkIfAccessTypeExists = AccessType::where([['id', '=', $key['accessTypeId']]])->first();

            //         if ($checkIfAccessTypeExists == null) {

            //             return responseInvalid(['Access Type id not found! please try different id']);
            //         }

            //         $checkIfAccessLimitExists = AccessLimit::where([['id', '=', $key['accessLimitId']]])->first();

            //         if ($checkIfAccessLimitExists == null) {

            //             return responseInvalid(['Access Limit id not found! please try different id']);
            //         }


            //         $format = 'd/m/Y H:i:s';
            //         $start = DateTime::createFromFormat($format, $key['startTime']);
            //         $end = DateTime::createFromFormat($format, $key['endTime']);

            //         if ($end < $start) {
            //             return responseInvalid(['To date must higher than from date!!']);
            //         }
            //     }

            //     if ($data_item) {

            //         return responseInvalid([$data_item]);

            //     }

            // } else {

            //     return responseInvalid(['Schedules can not be empty!']);
            // }




            // if ($request->schedules) {

            //     // AccessControlSchedule::where('codeLocation', '=', $request->usersId)->delete();

            //     foreach ($request->schedules  as $key) {

            //         // $format = 'd/m/Y H:i:s';
            //         // $start = DateTime::createFromFormat($format, $key['startTime']);
            //         // $end = DateTime::createFromFormat($format, $key['endTime']);

            //         // $AccessControlSchedule = new AccessControlSchedule();
            //         // $AccessControlSchedule->locationId = $request->locationId;
            //         // $AccessControlSchedule->usersId = $request->usersId;
            //         // $AccessControlSchedule->masterId = $key['masterId'];
            //         // $AccessControlSchedule->menuListId = $key['menuListId'];
            //         // $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
            //         // $AccessControlSchedule->accessLimitId = $key['accessLimitId'];
            //         // $AccessControlSchedule->startTime = $start;
            //         // $AccessControlSchedule->endTime = $end;
            //         // $AccessControlSchedule->created_at = now();
            //         // $AccessControlSchedule->updated_at = now();
            //         // $AccessControlSchedule->save();
            //     }
            // }

            // DB::commit();

            // return responseUpdate();

        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }






    public function export(Request $request)
    {

        try {

            $tmp = "";
            $fileName = "";
            $date = Carbon::now()->format('d-m-Y');

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
                $fileName = "Access Control Schedule " . $date . ".xlsx";
            } else {
                $fileName = "Access Control Schedule " . $tmp . " " . $date . ".xlsx";
            }

            return Excel::download(
                new ExportAccessControlSchedule(
                    $request->orderValue,
                    $request->orderColumn,
                    $request->locationId,
                ),
                $fileName
            );
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function getAllData()
    {

        $groupedAccessSchedules = AccessControlSchedule::select('usersId', DB::raw('COUNT(*) as totalAccessMenu'))
            ->groupBy('usersId');

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->leftJoinSub($groupedAccessSchedules, 'f', function ($join) {
                $join->on('f.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,a.firstName) ,')'  ) as name"),
                'b.jobName as jobTitle',
                'e.locationName as location',
                'e.locationId as locationId',
                DB::raw('IFNULL(f.totalAccessMenu, 0) as totalAccessMenu'),
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d/%m/%Y %H:%i:%s") as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0']
            ]);

        $data = DB::table($subquery, 'a');

        return $data;
    }

    public function index(Request $request)
    {

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            $data = $this->getAllData();

            if ($request->locationId) {

                $test = $request->locationId;

                $data = $data->where(function ($query) use ($test) {
                    foreach ($test as $id) {
                        $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                    }
                });
            }

            if ($request->search) {

                $res = $this->Search($request);

                if ($res == "id") {

                    $data = $data->where('id', 'like', '%' . $request->search . '%');
                } else if ($res == "name") {

                    $data = $data->where('name', 'like', '%' . $request->search . '%');
                } else if ($res == "jobTitle") {

                    $data = $data->where('jobTitle', 'like', '%' . $request->search . '%');
                } else if ($res == "location") {

                    $data = $data->where('location', 'like', '%' . $request->search . '%');
                } else if ($res == "totalAccessMenu") {

                    $data = $data->where('totalAccessMenu', 'like', '%' . $request->search . '%');
                } else if ($res == "createdBy") {

                    $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
                } else if ($res == "createdAt") {

                    $data = $data->where('createdAt', 'like', '%' . $request->search . '%');
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
                    'name',
                    'jobTitle',
                    'location',
                    'totalAccessMenu',
                    'createdBy',
                    'createdAt',
                );

                if (!in_array($request->orderColumn, $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different order column',
                        'orderColumn' => $listOrder,
                    ]);
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
                        'name',
                        'jobTitle',
                        'location',
                        'totalAccessMenu',
                        'createdBy',
                        'createdAt',
                    )
                    ->orderBy($request->orderColumn, $defaultOrderBy)
                    ->orderBy('updated_at', 'desc');
            } else {


                $data = DB::table($data)
                    ->select(
                        'id',
                        'name',
                        'jobTitle',
                        'location',
                        'totalAccessMenu',
                        'createdBy',
                        'createdAt',
                    )
                    ->orderBy('updated_at', 'desc');
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

    private function Search(Request $request)
    {

        $data = $this->getAllData();


        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

        if ($request->search) {
            $data = $data->where('name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'name';
            return $temp_column;
        }


        $data = $this->getAllData();


        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

        if ($request->search) {
            $data = $data->where('jobTitle', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'jobTitle';
            return $temp_column;
        }



        $data = $this->getAllData();


        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

        if ($request->search) {
            $data = $data->where('location', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location';
            return $temp_column;
        }


        $data = $this->getAllData();


        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

        if ($request->search) {
            $data = $data->where('totalAccessMenu', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'totalAccessMenu';
            return $temp_column;
        }



        $data = $this->getAllData();


        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

        if ($request->search) {
            $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdBy';
            return $temp_column;
        }



        $data = $this->getAllData();


        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'jobTitle',
                'location',
                'totalAccessMenu',
                'createdBy',
                'createdAt',
            );

        if ($request->search) {
            $data = $data->where('createdAt', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdAt';
            return $temp_column;
        }
    }


    public function deleteAccessControlSchedules(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'usersId' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }


            $data_item = [];

            foreach ($request->usersId as $val) {

                $checkIfUsersExits = User::where([['id', '=', $val], ['isDeleted', '=', '0']])->first();

                if ($checkIfUsersExits == null) {
                    return responseInvalid(['User id not found! please try different id']);
                }

                $checkIfDataExits = AccessControlSchedule::where([
                    ['usersId', '=', $val],
                    ['isDeleted', '=', '0']
                ])->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'Schedules with user id : ' . $val . ' not found, please try different id');
                }
            }

            if ($data_item) {
                return responseInvalid([$data_item]);
            }

            foreach ($request->usersId as $val) {

                AccessControlSchedule::where([
                    ['usersId', '=', $val]
                ])->update([
                    'isDeleted' => 1,
                    'deletedBy' =>  $request->user()->id,
                    'deletedAt' =>  Carbon::now()
                ],);
            }

            DB::commit();

            return responseDelete();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function detailSchedules(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'usersId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfValueExits = AccessControlSchedule::where([
                ['usersId', '=', $request->usersId],
                ['isDeleted', '=', '0']
            ])->first();

            if ($checkIfValueExits === null) {

                return responseInvalid(['Users with spesific id not found!']);
            } else {

                $shedules = AccessControlSchedule::from('accessControlSchedules as a')
                    ->leftJoin('menuMaster as b', 'b.id', '=', 'a.masterId')
                    ->leftJoin('menuList as c', 'c.id', '=', 'a.menuListId')
                    ->leftJoin('accessType as d', 'd.id', '=', 'a.accessTypeId')
                    ->select(
                        'a.id',
                        'a.locationId',
                        'a.masterId',
                        'a.usersId',
                        'b.masterName',
                        'c.menuName',
                        'd.accessType',
                        'a.status',
                        'a.duration',
                        'a.status',
                    )->where([
                        ['a.isDeleted', '=', 0]
                    ])->get();

                return response()->json($shedules, 200);
            }


            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function detailAllSchedules(Request $request)
    {

        try {

            $validate = Validator::make($request->all(), [
                'usersId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfValueExits = AccessControlSchedule::where([
                ['usersId', '=', $request->usersId],
                ['isDeleted', '=', '0']
            ])->first();

            if ($checkIfValueExits === null) {

                return responseInvalid(['Users with spesific id not found!']);
            } else {


                // $shedules = AccessControlSchedule::from('accessControlSchedules as a')
                // ->leftJoin('menuMaster as b', 'b.id', '=', 'a.masterId')
                // ->leftJoin('menuList as c', 'c.id', '=', 'a.menuListId')
                // ->leftJoin('accessType as d', 'd.id', '=', 'a.accessTypeId')
                // ->select(
                //     'a.id',
                //     'a.locationId',
                //     'a.masterId',
                //     'a.usersId',
                //     'b.masterName',
                //     'c.menuName',
                //     'd.accessType',
                //     //DB::raw('CONCAT(DATE_FORMAT(a.startTime, "%d/%m/%Y"), " ", TIME_FORMAT(a.startTime, "%H:%i:%s")) as startTime'),
                //     // DB::raw('DATE_FORMAT(a.endTime, "%d/%m/%Y %H:%i:%s") as endTime'),
                //     'a.status',
                //     'a.duration',
                //     'a.status',
                // )->where([
                //     ['a.isDeleted', '=', 0]
                // ])->get();

                $shedules = AccessControlSchedule::from('accessControlSchedules as a')
                    ->leftJoin('menuMaster as b', 'b.id', '=', 'a.masterId')
                    ->leftJoin('menuList as c', 'c.id', '=', 'a.menuListId')
                    ->leftJoin('accessType as d', 'd.id', '=', 'a.accessTypeId')
                    ->select(
                        'a.id',
                        'a.locationId',
                        'a.masterId',
                        'a.usersId',
                        'b.masterName',
                        'c.menuName',
                        'd.accessType',
                        //DB::raw('CONCAT(DATE_FORMAT(a.startTime, "%d/%m/%Y"), " ", TIME_FORMAT(a.startTime, "%H:%i:%s")) as startTime'),
                        // DB::raw('DATE_FORMAT(a.endTime, "%d/%m/%Y %H:%i:%s") as endTime'),
                        'a.status',
                        'a.duration',
                        DB::raw('(CASE WHEN a.status = "not running" THEN 1 ELSE 0 END) as isNotRunning'),
                        'a.status',
                    )->where([
                        ['a.isDeleted', '=', 0]
                    ])->get();

                return response()->json($shedules, 200);
            }


            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function insertAccessControlSchedules(Request $request)
    {

        try {

            $data_item = [];
            $input_real = [];

            if ($request->schedules) {

                $arraySchedules = json_decode($request->schedules, true);

                foreach ($arraySchedules as $val) {

                    if (array_key_exists('command', $val)) {

                        if ($val['command'] != "del") {
                            array_push($input_real, $val);
                        }
                    } else {

                        array_push($input_real, $val);
                    }
                }

                $messageSchedules = [
                    'locationId.required' => 'Location id on tab Schedules is required!',
                    'usersId.required' => 'User id on tab Schedules is required!',
                    'masterId.required' => 'Master id on tab Schedules is required!',
                    'menuListId.required' => 'Menu list id on tab Schedules is required!',
                    'accessTypeId.required' => 'Access type id on tab Schedules is required!',
                    'giveAccessNow.required' => 'Give access now on tab Schedules is required!',
                    'integer' => 'The :attribute must be an integer.',
                ];



                foreach ($input_real as $key) {

                    $validateSchedules = Validator::make(
                        $key,
                        [
                            'locationId' => 'required|integer',
                            'usersId' => 'required|integer',
                            'masterId' => 'required|integer',
                            'menuListId' => 'required|integer',
                            'accessTypeId' => 'required|integer',
                            'giveAccessNow' => 'required|boolean',
                            'startTime' => $key['giveAccessNow'] ? 'required|date_format:d/m/Y H:i:s' : '',
                            'endTime' => $key['giveAccessNow'] ? 'required|date_format:d/m/Y H:i:s|after:startTime' : '',
                            'duration' => $key['giveAccessNow'] ? 'required_if:giveAccessNow,1|string' : '',
                        ],
                        $messageSchedules
                    );

                    if ($validateSchedules->fails()) {

                        $errors = $validateSchedules->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item))) {
                                array_push($data_item, $checkisu);
                            }
                        }
                    }

                    $checkIfMasterExits = MenuMasters::where([['id', '=', $key['masterId']], ['isDeleted', '=', '0']])->first();

                    if ($checkIfMasterExits == null) {

                        return responseInvalid(['Master id not found! please try different id']);
                    }

                    $checkIfMenuListExits = MenuList::where([['id', '=', $key['menuListId']], ['isActive', '=', '1']])->first();

                    if ($checkIfMenuListExits == null) {

                        return responseInvalid(['Menu list id not found! please try different id']);
                    }

                    $checkIfAccessTypeExists = AccessType::where([['id', '=', $key['accessTypeId']]])->first();

                    if ($checkIfAccessTypeExists == null) {

                        return responseInvalid(['Access Type id not found! please try different id']);
                    }

                    $checkIfUsersExits = User::where([['id', '=', $key['usersId']], ['isDeleted', '=', '0']])->first();

                    if ($checkIfUsersExits == null) {
                        return responseInvalid(['User id not found! please try different id']);
                    }

                    $checkIfLocationExits = Location::where([['id', '=', $key['locationId']], ['isDeleted', '=', '0']])->first();

                    if ($checkIfLocationExits == null) {
                        return responseInvalid(['Location id not found! please try different id']);
                    }


                    if ($key['giveAccessNow'] == 1) {
                        $format = 'd/m/Y H:i:s';
                        $start = DateTime::createFromFormat($format, $key['startTime']);
                        $end = DateTime::createFromFormat($format, $key['endTime']);

                        if ($end < $start) {
                            return responseInvalid(['To date must higher than from date!!']);
                        }
                    }
                }

                if ($data_item) {

                    return responseInvalid($data_item);
                }
            } else {

                return responseInvalid(['Schedules can not be empty!']);
            }



            if ($request->schedules) {

                foreach ($input_real  as $key) {


                    if ($key['giveAccessNow'] == 1) {
                        $format = 'd/m/Y H:i:s';
                        $start = DateTime::createFromFormat($format, $key['startTime']);
                        $end = DateTime::createFromFormat($format, $key['endTime']);

                        if ($end < $start) {
                            return responseInvalid(['To date must higher than from date!!']);
                        }


                        $format = 'd/m/Y H:i:s';
                        $start = DateTime::createFromFormat($format, $key['startTime']);
                        $end = DateTime::createFromFormat($format, $key['endTime']);

                        $AccessControlSchedule = new AccessControlSchedule();
                        $AccessControlSchedule->locationId = $key['locationId'];
                        $AccessControlSchedule->usersId = $key['usersId'];
                        $AccessControlSchedule->masterId = $key['masterId'];
                        $AccessControlSchedule->menuListId = $key['menuListId'];
                        $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
                        $AccessControlSchedule->giveAccessNow = $key['giveAccessNow'];
                        $AccessControlSchedule->startTime = $start;
                        $AccessControlSchedule->endTime = $end;
                        $AccessControlSchedule->status = "In Progress";
                        $AccessControlSchedule->duration =  $key['duration'];
                        $AccessControlSchedule->createdBy = $request->user()->firstName;
                        $AccessControlSchedule->created_at = now();
                        $AccessControlSchedule->updated_at = now();
                        $AccessControlSchedule->save();
                    } else {

                        $AccessControlSchedule = new AccessControlSchedule();
                        $AccessControlSchedule->locationId = $key['locationId'];
                        $AccessControlSchedule->usersId = $key['usersId'];
                        $AccessControlSchedule->masterId = $key['masterId'];
                        $AccessControlSchedule->menuListId = $key['menuListId'];
                        $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
                        $AccessControlSchedule->createdBy = $request->user()->firstName;
                        $AccessControlSchedule->created_at = now();
                        $AccessControlSchedule->updated_at = now();
                        $AccessControlSchedule->save();
                    }
                }
            }

            DB::commit();

            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function getMenuList(Request $request)
    {

        try {

            $validate = Validator::make($request->all(), [
                'masterId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfMasterExists = MenuMasters::where([
                ['isDeleted', '=', 0],
                ['id', '=', $request->masterId],
            ])->first();

            if (!$checkIfMasterExists) {

                return responseInvalid(['Menu master with spesific id not exists! Please try another id!']);
            } else {


                $dataMenuList = MenuList::select(
                    'id',
                    'menuName as menuName'
                )->where([
                    ['isActive', '=', 1],
                    ['masterId', '=', $request->masterId]
                ])->get();

                return responseList($dataMenuList);
            }
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }
}
