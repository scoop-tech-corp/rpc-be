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
use App\Models\location;
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

            AccessControlSchedule::where([
                ['endTime', '<=', $currentDateTime],
                ['isDeleted', '=', '0'],
                ['status', '=', 2]
            ])->update(['status' => 3]);

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
                ->select(DB::raw("
                            a.usersId,
                            REPLACE(
                                TRIM(
                                    REPLACE(
                                        CONCAT(
                                            IFNULL(b.firstName, ''),
                                            IF(b.middleName IS NOT NULL AND b.middleName != '', CONCAT(' ', b.middleName), ''),
                                            IFNULL(CONCAT(' ', b.lastName), ''),
                                            IFNULL(CONCAT(' (', b.nickName, ')'), '')
                                        ),
                                        '  (',
                                        '('
                                    )
                                ),
                                ' (',
                                '('
                            ) AS name
                        "))
                ->where([['locationId', '=', $request->locationId], ['a.isDeleted', '=', '0']])->get();





            foreach ($data as &$result) {

                $result->name = str_replace('  (', '(', $result->name);
                $result->name = str_replace(' (', '(', $result->name);
            }



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


            $validate = Validator::make($request->all(), [
                'locationId' => 'required|integer',
                'usersId' => 'required|integer',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid([$errors]);
            }

            $checkIfUsersExits = User::where([['id', '=', $request->usersId], ['isDeleted', '=', '0']])->first();

            if ($checkIfUsersExits == null) {
                return responseInvalid(['User id not found! please try different id']);
            }

            $checkIfLocationExits = Location::where([['id', '=',  $request->locationId], ['isDeleted', '=', '0']])->first();

            if ($checkIfLocationExits == null) {
                return responseInvalid(['Location id not found! please try different id']);
            }

            $input_real  = [];
            $data_item = [];

            if ($request->details) {

                $messageSchedules = [
                    'masterMenuId.required' => 'Master menu id on tab Schedules is required!',
                    'listMenuId.required' => 'Menu list id on tab Schedules is required!',
                    'accessTypeId.required' => 'Access type id on tab Schedules is required!',
                    'giveAccessNow.required' => 'Give access now on tab Schedules is required!',
                    'integer' => 'The :attribute must be an integer.',
                ];


                foreach ($request->details as $val) {

                    if (array_key_exists('command', $val)) {

                        if ($val['command'] != "del" || ($val['command'] == "del" && $val['id'] != "")) {
                            array_push($input_real, $val);
                        }
                    } else {
                        array_push($input_real, $val);
                    }
                }



                foreach ($input_real as $key) {

                    $validateSchedules = Validator::make(
                        $key,
                        [
                            'masterMenuId' => 'required|integer',
                            'listMenuId' => 'required|integer',
                            'accessTypeId' => 'required|integer',
                            'giveAccessNow' => 'required|boolean',
                            'startTime' => $key['giveAccessNow'] ? 'required|date_format:d/m/Y H:i:s' : '',
                            'endTime' => $key['giveAccessNow'] ? 'required|date_format:d/m/Y H:i:s|after:startTime' : '',
                            'duration' => $key['giveAccessNow'] ? 'required_if:giveAccessNow,1' : '',
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

                    $checkIfMasterExits = MenuMasters::where([['id', '=', $key['masterMenuId']], ['isDeleted', '=', '0']])->first();

                    if ($checkIfMasterExits == null) {

                        return responseInvalid(['Master id not found! please try different id']);
                    }

                    $checkIfMenuListExits = MenuList::where([['id', '=', $key['listMenuId']], ['isActive', '=', '1']])->first();

                    if ($checkIfMenuListExits == null) {

                        return responseInvalid(['Menu list id not found! please try different id']);
                    }

                    $checkIfAccessTypeExists = AccessType::where([['id', '=', $key['accessTypeId']]])->first();

                    if ($checkIfAccessTypeExists == null) {

                        return responseInvalid(['Access Type id not found! please try different id']);
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




            foreach ($input_real  as $key) {

                if ($key['id'] == "") {

                    if ($key['giveAccessNow'] == 1) {

                        $format = 'd/m/Y H:i:s';
                        $start = DateTime::createFromFormat($format, $key['startTime']);
                        $end = DateTime::createFromFormat($format, $key['endTime']);

                        if ($end < $start) {
                            return responseInvalid(['To date must higher than from date!!']);
                        }


                        $existingRecord = AccessControlSchedule::where([
                            'locationId' => $request->locationId,
                            'usersId' =>  $request->usersId,
                            'masterMenuId' => $key['masterMenuId'],
                            'listMenuId' => $key['listMenuId'],
                            'startTime' => $start,
                            'endTime' => $end,
                            'isDeleted' => 0
                        ])->first();

                        if ($existingRecord) {
                            return responseInvalid(['This schedule already exists!']);
                        }


                        $AccessControlSchedule = new AccessControlSchedule();
                        $AccessControlSchedule->locationId = $request->locationId;
                        $AccessControlSchedule->usersId = $request->usersId;
                        $AccessControlSchedule->masterMenuId = $key['masterMenuId'];
                        $AccessControlSchedule->listMenuId = $key['listMenuId'];
                        $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
                        $AccessControlSchedule->giveAccessNow = $key['giveAccessNow'];
                        $AccessControlSchedule->startTime = $start;
                        $AccessControlSchedule->endTime = $end;
                        $AccessControlSchedule->status = 2;
                        $AccessControlSchedule->duration =  $key['duration'];
                        $AccessControlSchedule->createdBy = $request->user()->id;
                        $AccessControlSchedule->created_at = now();
                        $AccessControlSchedule->updated_at = now();
                        $AccessControlSchedule->save();
                    } else {

                        $AccessControlSchedule = new AccessControlSchedule();
                        $AccessControlSchedule->locationId = $request->locationId;
                        $AccessControlSchedule->usersId = $request->usersId;
                        $AccessControlSchedule->masterMenuId = $key['masterMenuId'];
                        $AccessControlSchedule->listMenuId = $key['listMenuId'];
                        $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
                        $AccessControlSchedule->giveAccessNow = $key['giveAccessNow'];
                        $AccessControlSchedule->createdBy = $request->user()->id;
                        $AccessControlSchedule->status = 1;
                        $AccessControlSchedule->created_at = now();
                        $AccessControlSchedule->updated_at = now();
                        $AccessControlSchedule->save();
                    }
                } else {

                    if (array_key_exists('command', $key)) {

                        if ($key['command'] == "del") {
                            AccessControlSchedule::where([
                                ['id', '=', $key['id']]
                            ])->update([
                                'isDeleted' => 1,
                                'deletedBy' => $request->user()->id,
                                'deletedAt' => now()
                            ]);
                        } else {

                            if ($key['giveAccessNow'] == 1) {

                                $format = 'd/m/Y H:i:s';
                                $start = DateTime::createFromFormat($format, $key['startTime']);
                                $end = DateTime::createFromFormat($format, $key['endTime']);

                                if ($end < $start) {
                                    return responseInvalid(['To date must higher than from date!!']);
                                }

                                AccessControlSchedule::where([
                                    ['id', '=', $key['id']]
                                ])->update([
                                    'masterMenuId' => $key['masterMenuId'],
                                    'listMenuId' => $key['listMenuId'],
                                    'accessTypeId' => $key['accessTypeId'],
                                    'giveAccessNow' => $key['giveAccessNow'],
                                    'startTime' => $start,
                                    'endTime' => $end,
                                    'status' => 2,
                                    'duration' => $key['duration'],
                                    'userUpdateId' => $request->user()->id,
                                    'updated_at' => now()
                                ]);
                            } else {

                                AccessControlSchedule::where([
                                    ['id', '=', $key['id']]
                                ])->update([
                                    'masterMenuId' => $key['masterMenuId'],
                                    'listMenuId' => $key['listMenuId'],
                                    'accessTypeId' => $key['accessTypeId'],
                                    'giveAccessNow' => $key['giveAccessNow'],
                                    'status' => 1,
                                    'userUpdateId' => $request->user()->id,
                                    'updated_at' => now()
                                ]);
                            }
                        }
                    } else {

                        if ($key['giveAccessNow'] == 1) {

                            $format = 'd/m/Y H:i:s';
                            $start = DateTime::createFromFormat($format, $key['startTime']);
                            $end = DateTime::createFromFormat($format, $key['endTime']);

                            if ($end < $start) {
                                return responseInvalid(['To date must higher than from date!!']);
                            }

                            AccessControlSchedule::where([
                                ['id', '=', $key['id']]
                            ])->update([
                                'masterMenuId' => $key['masterMenuId'],
                                'listMenuId' => $key['listMenuId'],
                                'accessTypeId' => $key['accessTypeId'],
                                'giveAccessNow' => $key['giveAccessNow'],
                                'startTime' => $start,
                                'endTime' => $end,
                                'status' => 2,
                                'duration' => $key['duration'],
                                'userUpdateId' => $request->user()->id,
                                'updated_at' => now()
                            ]);
                        } else {

                            AccessControlSchedule::where([
                                ['id', '=', $key['id']]
                            ])->update([
                                'masterMenuId' => $key['masterMenuId'],
                                'listMenuId' => $key['listMenuId'],
                                'accessTypeId' => $key['accessTypeId'],
                                'status' => 1,
                                'userUpdateId' => $request->user()->id,
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return responseUpdate();
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


        $group =  DB::table('accessControlSchedules as a')
            ->select(
                'locationId',
                'usersId',
                DB::raw('COUNT(listMenuId) as totalAccessMenu'),
                DB::raw('CAST(MAX(createdBy) AS SIGNED) as createdBy'),
                DB::raw('MAX(created_at) as created_at')
            )->where([
                ['isDeleted', '=', 0]
            ])
            ->groupBy('locationId', 'usersId')
            ->orderByDesc('created_at');

        $data = DB::table(DB::raw("({$group->toSql()}) as a"))
            ->mergeBindings($group)
            ->leftJoin('users as b', function ($join) {
                $join->on('a.usersId', '=', 'b.id');
            })
            ->leftJoin('users as x', function ($join) {
                $join->on('a.createdBy', '=', 'x.id');
            })
            ->leftJoin('location as c', 'c.id', '=', 'a.locationId')
            ->leftjoin('jobTitle as d', 'd.id', '=', 'b.jobTitleId')
            ->select(
                'a.usersId',
                DB::raw("
                REPLACE(
                    TRIM(
                        REPLACE(
                            CONCAT(
                                IFNULL(b.firstName, ''),
                                IF(b.middleName IS NOT NULL AND b.middleName != '', CONCAT(' ', b.middleName), ''),
                                IFNULL(CONCAT(' ', b.lastName), ''),
                                IFNULL(CONCAT(' (', b.nickName, ')'), '')
                            ),
                            '  (',
                            '('
                        )
                    ),
                    ' (',
                    '('
                ) AS name"),
                'd.jobName as jobTitle',
                'c.locationName as location',
                'a.locationId as locationId',
                DB::raw('IFNULL(a.totalAccessMenu, 0) as totalAccessMenu'),
                'x.firstName as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d/%m/%Y %H:%i:%s") as createdAt'),
            )->where([
                ['b.isDeleted', '=', '0'],
                ['x.isDeleted', '=', '0']
            ]);

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

                if ($res == "usersId") {

                    $data = $data->where('usersId', 'like', '%' . $request->search . '%');
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
                    'usersId',
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
                        'usersId',
                        'name',
                        'jobTitle',
                        'location',
                        'totalAccessMenu',
                        'createdBy',
                        'createdAt',
                    )
                    ->orderBy($request->orderColumn, $defaultOrderBy);
            } else {


                $data = DB::table($data)
                    ->select(
                        'usersId',
                        'name',
                        'jobTitle',
                        'location',
                        'totalAccessMenu',
                        'createdBy',
                        'createdAt',
                    );
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
                'usersId',
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
                'usersId',
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
                'usersId',
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
                'usersId',
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
                'usersId',
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
                'usersId',
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
                'id' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }


            foreach ($request->id as $val) {

                $checkIfDataExits = AccessControlSchedule::where([
                    ['id', '=', $val],
                    ['isDeleted', '=', '1'],
                ])->first();

                if ($checkIfDataExits) {
                    return responseInvalid(['Data Schedules with id ' . $val . ' already deleted! try different ID']);
                }


                $checkStatusInProgress = AccessControlSchedule::where([
                    ['id', '=', $val],
                    ['status', '<>', '1']
                ])->first();

                if ($checkStatusInProgress) {
                    return responseInvalid(['Data Schedules with id ' . $val . ' already On Going or Finished! try different ID']);
                }
            }

            foreach ($request->id as $val) {

                AccessControlSchedule::where([
                    ['id', '=', $val]
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
                'id' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfValueExits = AccessControlSchedule::where([
                ['id', '=', $request->id],
                ['isDeleted', '=', '0'],
                ['status', '=', '1']
            ])->first();

            if ($checkIfValueExits === null) {

                return responseInvalid(['Schedule with spesific id already on going or finished!']);
            } else {

                $param_schedules = AccessControlSchedule::select(
                    'usersId as usersId',
                    'locationId as locationId',
                )->where([
                    ['id', '=', $request->id],
                    ['isDeleted', '=', '0'],
                    ['status', '=', '1']
                ])->first();


                $shedules = AccessControlSchedule::from('accessControlSchedules as a')
                    ->leftJoin('menuMaster as b', 'b.id', '=', 'a.masterMenuId')
                    ->leftJoin('menuList as c', 'c.id', '=', 'a.listMenuId')
                    ->leftJoin('accessType as d', 'd.id', '=', 'a.accessTypeId')
                    ->leftJoin('statusSchedules as e', 'e.id', '=', 'a.status')
                    ->select(
                        'a.id',
                        'a.masterMenuId',
                        'b.masterName',
                        'a.listMenuId',
                        'c.menuName',
                        'a.accessTypeId',
                        'd.accessType',
                        'a.giveAccessNow',
                        DB::raw('DATE_FORMAT(a.startTime, "%d/%m/%Y %H:%i:%s") as startTime'),
                        DB::raw('DATE_FORMAT(a.endTime, "%d/%m/%Y %H:%i:%s") as endTime'),
                        'a.duration',
                        DB::raw('(CASE WHEN e.status = 1 THEN 1 ELSE 0 END) as isNotRunning'),
                    )->where([
                        ['a.isDeleted', '=', 0],
                        ['a.id', '=', $request->id],
                        ['a.status', '=', 1],
                    ])->get();

                $param_schedules->details = $shedules;

                return response()->json($param_schedules, 200);
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




                $param_schedules = AccessControlSchedule::select(
                    'usersId as usersId',
                    'locationId as locationId',
                )
                    ->where([
                        ['usersId', '=', $request->usersId],
                        ['isDeleted', '=', '0']
                    ])->first();

                $shedules = AccessControlSchedule::from('accessControlSchedules as a')
                    ->leftJoin('menuMaster as b', 'b.id', '=', 'a.masterMenuId')
                    ->leftJoin('menuList as c', 'c.id', '=', 'a.listMenuId')
                    ->leftJoin('accessType as d', 'd.id', '=', 'a.accessTypeId')
                    ->leftJoin('statusSchedules as e', 'e.id', '=', 'a.status')
                    ->select(
                        'a.id',
                        'b.masterName',
                        'c.menuName',
                        'd.accessType',
                        DB::raw('DATE_FORMAT(a.startTime, "%d/%m/%Y %H:%i:%s") as startTime'),
                        DB::raw('DATE_FORMAT(a.endTime, "%d/%m/%Y %H:%i:%s") as endTime'),
                        'a.duration',
                        'e.status',
                        DB::raw('(CASE WHEN a.status = 1 THEN 1 ELSE 0 END) as isNotRunning'),
                    )->where([
                        ['a.isDeleted', '=', 0]
                    ])->get();

                $param_schedules->details = $shedules;

                return response()->json($param_schedules, 200);
            }


            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function insertAccessControlSchedules(Request $request)
    {


        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer',
            'usersId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $checkIfUsersExits = User::where([['id', '=', $request->usersId], ['isDeleted', '=', '0']])->first();

        if ($checkIfUsersExits == null) {
            return responseInvalid(['User id not found! please try different id']);
        }

        $checkIfLocationExits = Location::where([['id', '=',  $request->locationId], ['isDeleted', '=', '0']])->first();

        if ($checkIfLocationExits == null) {
            return responseInvalid(['Location id not found! please try different id']);
        }


        try {

            $data_item = [];
            $input_real = [];

            if ($request->details) {

                $arraySchedules = json_decode($request->details, true);

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

                    'masterMenuId.required' => 'Master id on tab Schedules is required!',
                    'menuListId.required' => 'Menu list id on tab Schedules is required!',
                    'accessTypeId.required' => 'Access type id on tab Schedules is required!',
                    'giveAccessNow.required' => 'Give access now on tab Schedules is required!',
                    'integer' => 'The :attribute must be an integer.',
                ];



                foreach ($input_real as $key) {

                    $validateSchedules = Validator::make(
                        $key,
                        [

                            'masterMenuId' => 'required|integer',
                            'listMenuId' => 'required|integer',
                            'accessTypeId' => 'required|integer',
                            'giveAccessNow' => 'required|boolean',
                            'startTime' => $key['giveAccessNow'] ? 'required|date_format:d/m/Y H:i:s' : '',
                            'endTime' => $key['giveAccessNow'] ? 'required|date_format:d/m/Y H:i:s|after:startTime' : '',
                            'duration' => $key['giveAccessNow'] ? 'required_if:giveAccessNow,1' : '',
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

                    $checkIfMasterExits = MenuMasters::where([['id', '=', $key['masterMenuId']], ['isDeleted', '=', '0']])->first();

                    if ($checkIfMasterExits == null) {

                        return responseInvalid(['Master id not found! please try different id']);
                    }

                    $checkIfMenuListExits = MenuList::where([['id', '=', $key['listMenuId']], ['isActive', '=', '1']])->first();

                    if ($checkIfMenuListExits == null) {

                        return responseInvalid(['Menu list id not found! please try different id']);
                    }

                    $checkIfAccessTypeExists = AccessType::where([['id', '=', $key['accessTypeId']]])->first();

                    if ($checkIfAccessTypeExists == null) {

                        return responseInvalid(['Access Type id not found! please try different id']);
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


            if ($request->details) {

                foreach ($input_real  as $key) {

                    if ($key['giveAccessNow'] == 1) {

                        $format = 'd/m/Y H:i:s';
                        $start = DateTime::createFromFormat($format, $key['startTime']);
                        $end = DateTime::createFromFormat($format, $key['endTime']);

                        if ($end < $start) {
                            return responseInvalid(['To date must higher than from date!!']);
                        }

                        $existingRecord = AccessControlSchedule::where([
                            'locationId' =>  $request->locationId,
                            'usersId' =>  $request->usersId,
                            'masterMenuId' => $key['masterMenuId'],
                            'listMenuId' => $key['listMenuId'],
                            'startTime' => $start,
                            'endTime' => $end,
                            'isDeleted' => 0
                        ])->first();

                        if ($existingRecord) {
                            return responseInvalid(['This schedule already exists!']);
                        }

                        $AccessControlSchedule = new AccessControlSchedule();
                        $AccessControlSchedule->locationId = $request->locationId;
                        $AccessControlSchedule->usersId = $request->usersId;
                        $AccessControlSchedule->masterMenuId = $key['masterMenuId'];
                        $AccessControlSchedule->listMenuId = $key['listMenuId'];
                        $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
                        $AccessControlSchedule->giveAccessNow = $key['giveAccessNow'];
                        $AccessControlSchedule->startTime = $start;
                        $AccessControlSchedule->endTime = $end;
                        $AccessControlSchedule->status = 2;
                        $AccessControlSchedule->duration =  $key['duration'];
                        $AccessControlSchedule->createdBy = $request->user()->id;
                        $AccessControlSchedule->created_at = now();
                        $AccessControlSchedule->updated_at = now();
                        $AccessControlSchedule->save();
                    } else {

                        $AccessControlSchedule = new AccessControlSchedule();
                        $AccessControlSchedule->locationId = $request->locationId;
                        $AccessControlSchedule->usersId = $request->usersId;
                        $AccessControlSchedule->masterMenuId = $key['masterMenuId'];
                        $AccessControlSchedule->listMenuId = $key['listMenuId'];
                        $AccessControlSchedule->accessTypeId = $key['accessTypeId'];
                        $AccessControlSchedule->createdBy = $request->user()->id;
                        $AccessControlSchedule->status = 1;
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
