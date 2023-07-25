<?php

namespace App\Http\Controllers\Staff;

use App\Models\Staff\AccessControlSchedule;
use App\Http\Controllers\Controller;
use App\Models\AccessControl\MenuMasters;
use App\Models\AccessControl\MenuList;
use App\Models\AccessControl\AccessLimit;
use App\Models\AccessControl\AccessType;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Validator;
use DB;

class AccessControlSchedulesController extends Controller
{

    public function updateAccessControlSchedules(Request $request)
    {

        try {

            $validate = Validator::make($request->all(), [
                'id' => 'required|integer',
                'usersId' => 'required|integer',
                'masterId' => 'required|integer',
                'menuList' => 'required|integer',
                'accessTypeId' => 'required|integer',
                'startTime' => 'required',
                'endTime' => 'required',
            ]);


            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }


            $checkIfUsersExits = User::where([['id', '=', $request->usersId], ['isDeleted', '=', '0']])->first();

            if ($checkIfUsersExits == null) {

                return responseInvalid(['User id not found! please try different id']);
            }


            AccessControlSchedule::where('id', '=', $request->id)
                ->update(
                    [
                        'masterId' =>  $request->masterId,
                        'menuList' => $request->menuList,
                        'accessTypeId' =>  $request->accessTypeId,
                        'startTime' =>  $request->startTime,
                        'endTime' =>  $request->endTime,
                    ],
                );


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
                new exportStaff(
                    $request->orderValue,
                    $request->orderColumn,
                    $request->locationId,
                ),
                $fileName
            );
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ]);
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
                DB::raw("IFNULL ((f.totalAccessMenu),0) as totalAccessMenu"),
                'e.locationName as location',
                'e.locationId as locationId',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
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
            if ($request->search) {

                $res = $this->Search($request);

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

    private function Search(Request $request)
    {

        $data = $this->getAllData();

        if ($request->search) {
            $data = $data->where('type', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'type';
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

            $data_item = [];
            foreach ($request->id as $val) {

                $checkIfDataExits = AccessControlSchedule::where([
                    ['id', '=', $val],
                    ['isDeleted', '=', '0']
                ])
                    ->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'schedule id : ' . $val . ' not found, please try different id');
                }
            }

            if ($data_item) {
                return responseInvalid([$data_item]);
            }

            foreach ($request->id as $val) {

                AccessControlSchedule::where('id', '=', $val)
                    ->update(
                        [
                            'isDeleted' => 1,
                            'deletedBy' =>  $request->user()->id,
                            'deletedAt' =>  Carbon::now()
                        ],
                    );
            }

            DB::commit();

            return responseDelete();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }






    public function insertAccessControlSchedules(Request $request)
    {

        try {

            $validate = Validator::make($request->all(), [
                'usersId' => 'required|integer',
                'masterId' => 'required|integer',
                'menuList' => 'required|integer',
                'accessTypeId' => 'required|integer',
                'accessLimitId' => 'required|integer',
                'startTime' => 'required',
                'endTime' => 'required',
            ]);


            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }


            $checkIfUsersExits = User::where([['id', '=', $request->usersId], ['isDeleted', '=', '0']])->first();

            if ($checkIfUsersExits == null) {

                return responseInvalid(['User id not found! please try different id']);
            }

            $checkIfMasterExits = MenuMasters::where([['id', '=', $request->masterId], ['isDeleted', '=', '0']])->first();

            if ($checkIfMasterExits == null) {

                return responseInvalid(['Master id not found! please try different id']);
            }

            $checkIfMenuListExits = MenuList::where([['id', '=', $request->menuList], ['isActive', '=', '1']])->first();

            if ($checkIfMenuListExits == null) {

                return responseInvalid(['Menu list id not found! please try different id']);
            }

            $checkIfAccessTypeExists = AccessType::where([['id', '=', $request->accessTypeId]])->first();

            if ($checkIfAccessTypeExists == null) {

                return responseInvalid(['Access Type id not found! please try different id']);
            }

            $checkIfAccessLimitExists = AccessLimit::where([['id', '=', $request->accessLimitId]])->first();

            if ($checkIfAccessLimitExists == null) {

                return responseInvalid(['Access Limit id not found! please try different id']);
            }

            $start = Carbon::parse($request->startTime);
            $end = Carbon::parse($request->endTime);

            if ($end < $start) {
                return responseInvalid(['To date must higher than from date!!']);
            }

            $AccessControlSchedule = new AccessControlSchedule();
            $AccessControlSchedule->usersId = $request->usersId;
            $AccessControlSchedule->masterId = $request->masterId;
            $AccessControlSchedule->menuList = $request->menuList;
            $AccessControlSchedule->accessTypeId = $request->accessTypeId;
            $AccessControlSchedule->accessLimitId = $request->accessLimitId;
            $AccessControlSchedule->startTime = $request->startTime;
            $AccessControlSchedule->endTime = $request->endTime;
            $AccessControlSchedule->created_at = now();
            $AccessControlSchedule->updated_at = now();
            $AccessControlSchedule->save();
            DB::commit();

            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function getAccessLimit()
    {


        try {

            $menuAccessLimit = AccessLimit::select('id', 'timeLimit as timeLimit')->get();

            return responseList($menuAccessLimit);
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function getMasterMenu()
    {

        try {

            $menuMastersData = MenuMasters::select('id', 'masterName as masterName')->where([
                ['isDeleted', '=', 0],
            ])->get();

            return responseList($menuMastersData);
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
