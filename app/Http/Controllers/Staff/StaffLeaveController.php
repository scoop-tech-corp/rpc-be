<?php

namespace App\Http\Controllers\Staff;

use App\Exports\StaffLeave\exportStaffLeave;
use App\Exports\StaffLeave\exportBalance;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\Staff\LeaveRequest;
use App\Models\Staff\Holidays;
use App\Models\User;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use DB;

class StaffLeaveController extends Controller
{
    private $client;
    private $api_key;
    private $country;

    public function getUsersId(Request $request)
    {
        try {

            $getUser = User::select(
                'id as usersId',
                DB::raw("CONCAT(IFNULL(firstName,'') ,' ', IFNULL(middleName,'') ,' ', IFNULL(lastName,'') ,'(', IFNULL(nickName,'') ,')'  ) as name"),
            )->where('id', $request->user()->id)
                ->where('isDeleted', '0')
                ->get();

            return response()->json($getUser, 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }

    public function getAllStaffActive(Request $request)
    {

        try {

            $getUser = User::select(
                'id as usersId',
                DB::raw("CONCAT(IFNULL(firstName,'') ,' ', IFNULL(middleName,'') ,' ', IFNULL(lastName,'') ,'(', IFNULL(nickName,'') ,')'  ) as name"),
            )
                ->where('isDeleted', '0')
                ->get();

            return response()->json($getUser, 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }



    public function adjustBalance(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {


            $validate = Validator::make(
                $request->all(),
                [
                    'usersId' => 'required|integer',
                    'balanceTypeId' => 'required|integer',
                    'amount' => 'required|integer',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }


            $User =  User::where('id', '=', $request->usersId)->where('isDeleted', '=', '0')->first();

            if ($User == null)

                return response()->json([
                    'message' => 'Failed',
                    'errors' => 'User id not found, please try different id',
                ], 422);



            $listOrder = array(
                1, 2, 3, 4
            );

            if (!in_array($request->balanceTypeId, $listOrder)) {

                return response()->json([
                    'message' =>  'The given data was invalid.',
                    'errors' => 'Balance type id must same within the array',
                    'balanceTypeId' => $listOrder,
                ]);
            }


            if ($request->balanceTypeId == 1) {



                User::where('id', '=', $request->usersId)
                    ->update(
                        [
                            'annualLeaveAllowance' => $request->amount,
                        ],
                    );
            } else if ($request->balanceTypeId == 2) {

                User::where('id', '=', $request->usersId)
                    ->update(
                        [
                            'annualLeaveAllowanceRemaining' => $request->amount,
                        ],
                    );
            } else if ($request->balanceTypeId == 3) {

                User::where('id', '=', $request->usersId)
                    ->update(
                        [
                            'annualSickAllowance' => $request->amount,
                        ],
                    );
            } else if ($request->balanceTypeId == 4) {

                User::where('id', '=', $request->usersId)
                    ->update(
                        [
                            'annualSickAllowanceRemaining' => $request->amount,
                        ],
                    );
            }

            DB::commit();

            return response()->json([
                'result' => 'Success',
                'message' => 'Successfully update balance user',
            ], 200);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }


    public function getDropdownBalanceType(Request $request)
    {

        try {

            $array1 = array(
                'balanceTypeId' => '1',
                'balanceType' => 'Annual Leave Allowance'
            );

            $array2 = array(
                'balanceTypeId' => '2',
                'balanceType' => 'Annual Leave Allowance Remaining'
            );

            $array3 = array(
                'balanceTypeId' => '3',
                'balanceType' => 'Annual Sick Allowance'
            );

            $array4 = array(
                'balanceTypeId' => '4',
                'balanceType' => 'Annual Sick Allowance Remaining'
            );

            $combinedArray = array($array1, $array2, $array3, $array4);

            return response()->json($combinedArray, 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ]);
        }
    }


    public function adjustLeaveRequest(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'usersId' => 'required|integer',
                    'leaveType' => 'required|string',
                    'fromDate' => 'required|date_format:Y-m-d',
                    'toDate' => 'required|date_format:Y-m-d',
                    'totalDays' => 'required|integer',
                    'workingDays' => 'required',
                    'remark' => 'required|string',

                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $valueDays = null;
            $json_array_name = json_decode($request->workingDays, true);


            $hitungNameDays  = 0;

            foreach ($json_array_name as $val) {


                if (preg_match('/\d+/', $val['name'])) {

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => 'Working days contain number, please check again'
                    ], 422);
                } else {


                    $listOrder = array(
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                    );

                    $listOrderUpper = array(
                        'Monday',
                        'Tuesday',
                        'Wednesday',
                        'Thursday',
                        'Friday',
                    );

                    if (!in_array(strtolower($val['name']), $listOrder)) {

                        return response()->json([
                            'message' =>  'The given data was invalid.',
                            'errors' => 'Working days value must same within the array',
                            'workingDays' => $listOrderUpper,
                        ]);
                    }



                    if ($valueDays == null) {

                        $valueDays =  $val['name'];
                    } else {

                        $valueDays = $valueDays . ',' . $val['name'];
                    }
                    $hitungNameDays =  $hitungNameDays + 1;
                }
            }

            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end < $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }

            $countDays = 0;

            $results = Holidays::whereBetween('date', [$start, $end])->get();

            while ($start <= $end) {

                if ($start->isWeekday()) {

                    if (!$results->contains('date', $start->toDateString())) {

                        $countDays = $countDays + 1;
                    }
                }

                $start->addDay();
            }


            // if ($countDays != $request->totalDays) {

            //     return response()->json([
            //         'result' => 'Failed',
            //         'message' => 'Wrong duration, please check again. Your duration day must be ' . $countDays,
            //     ], 422);
            // }


            // if ($request->totalDays != $hitungNameDays) {

            //     return response()->json([
            //         'result' => 'Failed',
            //         'message' => 'Wrong working days, please check again. Your working days must be ' .  $request->totalDays . ' days '
            //     ], 422);
            // }


            if (User::where('id', '=', $request->usersId)->where('isDeleted', '=', '0')->doesntExist()) {

                return response()->json([
                    'message' => 'Failed',
                    'errors' => 'User id not found, please try different id',
                ], 422);
            } else {

                $listOrder = array(
                    'leave allowance',
                    'sick allowance',
                );

                if (!in_array(strtolower($request->leaveType), $listOrder)) {

                    return response()->json([
                        'message' => 'Failed',
                        'errors' => 'Only leave allowance or sick allowance is allowed',
                    ], 422);
                }


                $sickallowance =  $request->user()->annualSickAllowanceRemaining;
                $leaveallowance =  $request->user()->annualLeaveAllowanceRemaining;

                if (str_contains(strtolower($request->leaveType), "sick")) {

                    if ($sickallowance == 0) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'You dont have any sick allowance left : ' .  $sickallowance,
                        ], 422);
                    }


                    if ($request->totalDays > $sickallowance) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'Cannot request higher than your remaining sick allowance, your remaining sick allowance : ' .  $sickallowance,
                        ], 422);
                    }
                } else {

                    if ($leaveallowance == 0) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'You dont have any leave allowance left : ' .  $leaveallowance,
                        ], 422);
                    }


                    if ($request->totalDays > $leaveallowance) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'Cannot request higher than your remaining leave allowance, your remaining leave allowance : ' .  $leaveallowance,
                        ], 422);
                    }
                }

                $from_date = $request->fromDate;
                $to_date = $request->toDate;

                $resultCheckExists =  LeaveRequest::where('usersId', $request->usersId)
                    ->where('status', 'pending')
                    ->where('fromDate', '<=', $to_date)
                    ->where('toDate', '>=', $from_date)
                    ->exists();

                if ($resultCheckExists) {

                    return response()->json([
                        'message' => 'Failed',
                        'errors' => 'You already had request leave on the spesific date, please check again.'
                    ], 422);
                }


                // $dataUserLocation = DB::table('usersLocation as a')
                //     ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
                //     ->select(
                //         'a.usersId',
                //         DB::raw('MIN(b.id) as locationId')
                //     )
                //     ->groupBy('a.usersId')
                //     ->where('a.isDeleted', '=', 0);

                $dataUserLocation = DB::table('usersLocation as a')
                    ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
                    ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
                    ->groupBy('a.usersId')
                    ->where('a.isDeleted', '=', 0);

                $userName =  User::from('users as a')
                    ->leftJoinSub($dataUserLocation, 'b', function ($join) {
                        $join->on('b.usersId', '=', 'id');
                    })
                    ->select(
                        'jobTitleId',
                        'b.locationId as locationId',
                        'b.locationName as locationName',
                        DB::raw("CONCAT(IFNULL(firstName,'') ,' ', IFNULL(middleName,'') ,' ', IFNULL(lastName,'') ,'(', IFNULL(nickName,'') ,')'  ) as name")
                    )
                    ->where('id', '=', $request->usersId)
                    ->where('isDeleted', '=', '0')
                    ->first();

                $staffLeave = new LeaveRequest();
                $staffLeave->usersId = $request->usersId;
                $staffLeave->requesterName = $userName->name;
                $staffLeave->jobTitle = $userName->jobTitleId;
                $staffLeave->locationId =  $userName->locationId;
                $staffLeave->locationName =  $userName->locationName;
                $staffLeave->leaveType = $request->leaveType;
                $staffLeave->fromDate = $request->fromDate;
                $staffLeave->toDate = $request->toDate;
                $staffLeave->duration = $request->totalDays;
                $staffLeave->workingDays = $valueDays;
                $staffLeave->status = "pending";
                $staffLeave->remark =  $request->remark;
                $staffLeave->save();

                DB::commit();

                return response()->json([
                    'result' => 'Success',
                    'message' => 'Successfully input request leave',
                ], 200);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }

    public function insertLeaveStaff(Request $request)
    {
        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'usersId' => 'required|integer',
                    'leaveType' => 'required|string',
                    'fromDate' => 'required|date_format:Y-m-d|after_or_equal:today',
                    'toDate' => 'required|date_format:Y-m-d',
                    'totalDays' => 'required|integer',
                    'workingDays' => 'required',
                    'remark' => 'required|string',

                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $valueDays = null;
            $json_array_name = json_decode($request->workingDays, true);

            $hitungNameDays = 0;

            foreach ($json_array_name as $val) {


                if (preg_match('/\d+/', $val['name'])) {

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => 'Working days contain number, please check again'
                    ], 422);
                } else {


                    $listOrder = array(
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                    );

                    $listOrderUpper = array(
                        'Monday',
                        'Tuesday',
                        'Wednesday',
                        'Thursday',
                        'Friday',
                    );

                    if (!in_array(strtolower($val['name']), $listOrder)) {

                        return response()->json([
                            'message' =>  'The given data was invalid.',
                            'errors' => 'Working days value must same within the array',
                            'workingDays' => $listOrderUpper,
                        ]);
                    }

                    if ($valueDays == null) {

                        $valueDays =  $val['name'];
                    } else {

                        $valueDays = $valueDays . ',' . $val['name'];
                    }

                    $hitungNameDays = $hitungNameDays + 1;
                }
            }

            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end < $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }


            $countDays = 0;

            $results = Holidays::whereBetween('date', [$start, $end])->get();

            while ($start <= $end) {

                if ($start->isWeekday()) {

                    if (!$results->contains('date', $start->toDateString())) {

                        $countDays = $countDays + 1;
                    }
                }

                $start->addDay();
            }

            if (User::where('id', '=', $request->usersId)->where('isDeleted', '=', '0')->doesntExist()) {

                return response()->json([
                    'message' => 'Failed',
                    'errors' => 'User id not found, please try different id',
                ], 422);
            } else {

                $listOrder = array(
                    'leave allowance',
                    'sick allowance',
                );

                if (!in_array(strtolower($request->leaveType), $listOrder)) {

                    return response()->json([
                        'message' => 'Failed',
                        'errors' => 'Only leave allowance or sick allowance is allowed',
                    ], 422);
                }


                $sickallowance =  $request->user()->annualSickAllowanceRemaining;
                $leaveallowance =  $request->user()->annualLeaveAllowanceRemaining;


                if (str_contains(strtolower($request->leaveType), "sick")) {

                    if ($sickallowance == 0) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'You dont have any sick allowance left : ' .  $sickallowance,
                        ], 422);
                    }


                    if ($request->totalDays > $sickallowance) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'Cannot request higher than your remaining sick allowance, your remaining sick allowance : ' .  $sickallowance,
                        ], 422);
                    }
                } else {

                    if ($leaveallowance == 0) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'You dont have any leave allowance left : ' .  $leaveallowance,
                        ], 422);
                    }


                    if ($request->totalDays > $leaveallowance) {

                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'Cannot request higher than your remaining leave allowance, your remaining leave allowance : ' .  $leaveallowance,
                        ], 422);
                    }
                }

                $from_date = $request->fromDate;
                $to_date = $request->toDate;

                $resultCheckExists =  LeaveRequest::where('usersId', $request->usersId)
                    ->where('status', 'pending')
                    ->where('fromDate', '<=', $to_date)
                    ->where('toDate', '>=', $from_date)
                    ->exists();

                if ($resultCheckExists) {

                    return response()->json([
                        'message' => 'Failed',
                        'errors' => 'You already had request leave on the spesific date, please check again.'
                    ], 422);
                }


                $dataUserLocation = DB::table('usersLocation as a')
                    ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
                    ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
                    ->groupBy('a.usersId')
                    ->where('a.isDeleted', '=', 0);


                // $dataUserLocation = DB::table('usersLocation as a')
                //     ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
                //     ->select(
                //         'a.usersId',
                //         DB::raw('MIN(b.id) as locationId')
                //     )
                //     ->groupBy('a.usersId')
                //     ->where('a.isDeleted', '=', 0);


                $userName =  User::from('users as a')
                    ->leftJoinSub($dataUserLocation, 'b', function ($join) {
                        $join->on('b.usersId', '=', 'id');
                    })
                    ->select(
                        'jobTitleId',
                        'b.locationName as locationName',
                        'b.locationId as locationId',
                        DB::raw("CONCAT(IFNULL(firstName,'') ,' ', IFNULL(middleName,'') ,' ', IFNULL(lastName,'') ,'(', IFNULL(nickName,'') ,')'  ) as name")
                    )
                    ->where('id', '=', $request->usersId)
                    ->where('isDeleted', '=', '0')
                    ->first();

                $staffLeave = new LeaveRequest();
                $staffLeave->usersId = $request->usersId;
                $staffLeave->requesterName = $userName->name;
                $staffLeave->jobTitle = $userName->jobTitleId;
                $staffLeave->locationId =  $userName->locationId;
                $staffLeave->locationName =  $userName->locationName;
                $staffLeave->leaveType = $request->leaveType;
                $staffLeave->fromDate = $request->fromDate;
                $staffLeave->toDate = $request->toDate;
                $staffLeave->duration = $request->totalDays;
                $staffLeave->workingDays = $valueDays;
                $staffLeave->status = "pending";
                $staffLeave->remark =  $request->remark;
                $staffLeave->save();

                DB::commit();

                return response()->json([
                    'result' => 'Success',
                    'message' => 'Successfully input request leave',
                ], 200);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }

    public function getWorkingDays(Request $request)
    {

        try {

            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end < $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }

            $results = Holidays::whereBetween('date', [$start, $end])->get();

            $nameDays = [];
            $totalDays = 0;

            while ($start <= $end) {

                if ($start->isWeekday()) {

                    if (!$results->contains('date', $start->toDateString())) {
                        $nameDays[] = [
                            'date' => $start->format('Y-m-d'),
                            'name' => $start->format('l')
                        ];

                        $totalDays = $totalDays + 1;
                    }
                }

                $start->addDay();
            }

            return response()->json(
                [
                    'workingDays' => $nameDays,
                    'totalDays' => $totalDays,
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


    public function setStatusLeaveRequest(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        try {

            DB::beginTransaction();

            $request->validate([
                'leaveRequestId' => 'required|max:25',
                'status' => 'required|max:25',
            ]);

            if (strtolower($request->status) != "approve" && strtolower($request->status) != "reject") {
                return response()->json([
                    'message' => 'failed',
                    'errors' => 'Status must Approve or Reject',
                ], 422);
            }


            if (strtolower($request->status) == "reject" && strtolower($request->reason) == "") {

                return response()->json([
                    'message' => 'failed',
                    'errors' => 'Please input reason if status is reject',
                ], 422);
            }


            $leaveRequest = LeaveRequest::where('id', '=', $request->leaveRequestId)
                ->where('status', '=', 'pending')
                ->first();

            if ($leaveRequest == null) {
                return response()->json([
                    'message' => 'Failed',
                    'errors' => 'Leave request not found, please try different id',
                ], 422);
            }

            $users = User::where([
                ['id', '=', $leaveRequest->usersId],
                ['isDeleted', '=', '0'],
            ])->first();

            if ($users == null) {

                return response()->json([
                    'message' => 'Failed',
                    'errors' => 'Users not found, please try different id',
                ], 422);
            }

            $reason = null;

            if (strtolower($request->status) == "reject") {
                $reason = $request->reason;
            }

            $userName =  $request->user()->firstName . " " . $request->user()->middleName . " " . $request->user()->lastName . "(" . $request->user()->nickName . ")";

            $leaveRequest->status = $request->status;
            $leaveRequest->rejectedReason = $reason;
            $leaveRequest->approveOrRejectedBy = $userName;
            $leaveRequest->approveOrRejectedDate = now();

            if (strtolower($request->status) == "approve") {

                if (str_contains($leaveRequest->leaveType, "sick")) {

                    if (($leaveRequest->duration) > ($users->annualSickAllowanceRemaining)) {
                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'Invalid input, your sick leave remaining ' . $users->annualSickAllowanceRemaining,
                        ], 422);
                    } else {
                        $users->annualSickAllowanceRemaining =  $users->annualSickAllowanceRemaining - $leaveRequest->duration;
                    }
                } else {

                    if (($leaveRequest->duration) > ($users->annualLeaveAllowanceRemaining)) {
                        return response()->json([
                            'message' => 'Failed',
                            'errors' => 'Invalid input, your leave allowance remaining ' . $users->annualLeaveAllowanceRemaining,
                        ], 422);
                    } else {
                        $users->annualLeaveAllowanceRemaining =  $users->annualLeaveAllowanceRemaining - $leaveRequest->duration;
                    }
                }
            }
            $leaveRequest->save();
            $users->save();
            $statusMessage = strtolower($request->status);

            DB::commit();

            return response()->json([
                'message' => 'Success',
                'errors' => 'Successfully ' . $statusMessage . ' leave request',
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,

            ], 422);
        }
    }

    public function getIndexStaffBalance(Request $request)
    {
        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $rolesIndex = roleStaffLeave($request->user()->id);


        if ($rolesIndex == 1) {

            $data = $this->indexBalanceLeaveAdminandOffice($request);
        } else {

            $data = $this->indexBalanceLeaveDoctorandStaff($request);
        }

        if ($data == null) {
            return response()->json(['totalPagination' => 0, 'data' => []], 200);
        }

        if ($request->orderValue) {
            $defaultOrderBy = $request->orderValue;
        }

        $checkOrder = null;

        if ($request->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'name',
                'annualLeaveAllowance',
                'annualLeaveAllowanceRemaining',
                'annualSickAllowance',
                'annualSickAllowanceRemaining',
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
                    'usersId',
                    'name',
                    'annualLeaveAllowance',
                    'annualLeaveAllowanceRemaining',
                    'annualSickAllowance',
                    'annualSickAllowanceRemaining'
                )
                ->orderBy($request->orderColumn, $defaultOrderBy)
                ->orderBy('updated_at', 'desc');
        } else {

            $data = DB::table($data)
                ->select(
                    'usersId',
                    'name',
                    'annualLeaveAllowance',
                    'annualLeaveAllowanceRemaining',
                    'annualSickAllowance',
                    'annualSickAllowanceRemaining'
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
    }


    private function SearchBalanceAdminandOffice($request)
    {

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.firstName';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);


        if ($request->search) {
            $data = $data->where('a.annualLeaveAllowance', '=', $request->search);
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowance';
            return $temp_column;
        }


        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualLeaveAllowanceRemaining', '=',  $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowanceRemaining';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualSickAllowance', '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowance';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualSickAllowanceRemaining', '=',  $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowanceRemaining';
            return $temp_column;
        }
    }



    private function SearchBalanceDoctorandStaff($request)
    {

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);


        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['a.id', '=', $request->user()->id],
            ]);

        if ($request->search) {
            $data = $data->where('a.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.firstName';
            return $temp_column;
        }


        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['a.id', '=', $request->user()->id],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualLeaveAllowance', '=',  $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowance';
            return $temp_column;
        }



        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['a.id', '=', $request->user()->id],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualLeaveAllowanceRemaining', '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowanceRemaining';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['a.id', '=', $request->user()->id],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualSickAllowance', '=',  $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowance';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['a.id', '=', $request->user()->id],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualSickAllowanceRemaining', '=', $request->search);
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowanceRemaining';
            return $temp_column;
        }
    }

    public function exportLeaveRequest(Request $request)
    {

        $rolesIndex = roleStaffLeave($request->user()->id);

        try {

            $request->validate([
                'status' => 'required'
            ]);

            if (strtolower($request->status) != "approve" && strtolower($request->status) != "reject" and strtolower($request->status) != "pending") {
                return response()->json([
                    'message' => 'failed',
                    'errors' => 'Status must Pending, Approve or Reject',
                ], 422);
            }

            $tmp = "";
            $fileName = "";
            $date = Carbon::now()->format('d-m-Y');

            if ($rolesIndex == 1) {

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
                    $fileName = "Leave Request " . ucfirst($request->status) . ' ' . $date . ".xlsx";
                } else {
                    $fileName = "Leave Request " .  ucfirst($request->status) . ' ' . $tmp . " " . $date . ".xlsx";
                }
            } else {

                $fileName = "Leave Request " . ucfirst($request->status) . ' ' . $date  . ".xlsx";
            }


            return Excel::download(
                new exportStaffLeave(
                    $request->orderValue,
                    $request->orderColumn,
                    $request->status,
                    $rolesIndex,
                    $request->fromDate,
                    $request->toDate,
                    $request->user()->id,
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

    public function exportBalance(Request $request)
    {

        try {

            $rolesIndex = roleStaffLeave($request->user()->id);

            $tmp = "";
            $fileName = "";
            $date = Carbon::now()->format('d-m-Y');

            if ($rolesIndex == 1) {

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
                    $fileName = "Balance Allowance" . $date . ".xlsx";
                } else {
                    $fileName = "Balance Allowance " . $tmp . " " . $date . ".xlsx";
                }
            } else {

                $fileName = "Balance Allowance " . $date . ".xlsx";
            }

            return Excel::download(
                new exportBalance(
                    $request->orderValue,
                    $request->orderColumn,
                    $rolesIndex,
                    $request->user()->id,
                    $request->locationId,
                ),
                $fileName
            );
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }

    public function indexBalanceLeaveAdminandOffice(Request $request)
    {

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);


        if ($request->locationId) {

            $val = [];

            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationId', $request->locationId);
            }
        }


        if ($request->search) {

            $res = $this->SearchBalanceAdminandOffice($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                return null;
            }
        }

        return $data;
    }

    public function indexBalanceLeaveDoctorandStaff(Request $request)
    {
        $defaultOrderBy = "asc";

        $dataUserLocation = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.id) as locationId"), DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);

        $data = User::from('users as a')
            ->leftJoinSub($dataUserLocation, 'e', function ($join) {
                $join->on('e.usersId', '=', 'a.id');
            })
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as annualLeaveAllowance',
                'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
                'a.annualSickAllowance as annualSickAllowance',
                'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
                'a.updated_at as updated_at',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['a.id', '=', $request->user()->id],
            ]);

        if ($request->search) {

            $res = $this->SearchBalanceDoctorandStaff($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                return null;
            }
        }

        return $data;
    }


    public function indexLeaveAdminandOffice(Request $request)
    {

        if (strtolower($request->status) == "pending") {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $request->status],
                ]);
        } elseif (strtolower($request->status) == "approve") {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.approveOrRejectedBy as  approvedBy',
                    'a.approveOrRejectedDate as approvedAt',
                    'a.updated_at as updatedAt',
                )->where([
                    ['a.status', '=', $request->status],
                ]);
        } else {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.approveOrRejectedBy as  rejectedBy',
                    'a.rejectedReason as  rejectedReason',
                    'a.approveOrRejectedDate as rejectedAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $request->status],
                ]);
        }

        //YOLO
        if ($request->locationId) {

            $test = $request->locationId;

            $data = $data->where(function ($query) use ($test) {
                foreach ($test as $id) {
                    $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                }
            });
        }
        //YOLO

        if (strtotime($request->fromDate) !== false && strtotime($request->toDate) !== false) {

            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end < $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }

            $data = $data->whereBetween('fromDate', [$request->fromDate, $request->toDate]);
        }


        if ($request->search) {

            $res = $this->SearchRequestLeaveAdminOffice($request);

            if ($res) {

                if (is_numeric($request->search)) {
                    $data = $data->where($res, '=', $request->search);
                } else {
                    $data = $data->where($res, 'like', '%' . $request->search . '%');
                }
            } else {
                return null;
            }
        }

        return $data;
    }

    public function indexLeaveDoctorandStaff(Request $request)
    {

        if (strtolower($request->status) == "pending") {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $request->status],
                    ['a.usersId', '=', $request->user()->id],
                ]);

        } elseif (strtolower($request->status) == "approve") {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.approveOrRejectedBy as  approvedBy',
                    'a.approveOrRejectedDate as approvedAt',
                    'a.updated_at as updatedAt',
                )->where([
                    ['a.status', '=', $request->status],
                    ['a.usersId', '=', $request->user()->id],
                ]);
        } else {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.approveOrRejectedBy as  rejectedBy',
                    'a.rejectedReason as  rejectedReason',
                    'a.approveOrRejectedDate as rejectedAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $request->status],
                    ['a.usersId', '=', $request->user()->id],
                ]);
        }


        if (strtotime($request->fromDate) !== false && strtotime($request->toDate) !== false) {

            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end < $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }

            $data = $data->whereBetween('fromDate', [$request->fromDate, $request->toDate]);
        }

        if ($request->search) {

            $res = $this->SearchRequestLeaveStaffDoctor($request);

            if ($res) {

                if (is_numeric($request->search)) {
                    $data = $data->where($res, '=', $request->search);
                } else {
                    $data = $data->where($res, 'like', '%' . $request->search . '%');
                }
            } else {
                return null;
            }
        }

        return $data;
    }


    public function approveAll(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        try {

            $validate = Validator::make($request->all(), [
                'leaveRequestId' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            DB::beginTransaction();

            $data_item = [];
            foreach ($request->leaveRequestId as $val) {

                $checkIfDataExits = LeaveRequest::where([
                    ['id', '=', $val],
                    ['status', '=', 'pending']
                ])->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'leave request id: ' . $val . ' not found, please try different id');
                }
            }


            if ($data_item) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => $data_item,
                ], 422);
            }

            $userName =  $request->user()->firstName . " " . $request->user()->middleName . " " . $request->user()->lastName . "(" . $request->user()->nickName . ")";

            foreach ($request->leaveRequestId as $val) {

                $leaveRequest = LeaveRequest::where('id', '=', $val)
                    ->where('status', '=', 'pending')
                    ->first();

                if ($leaveRequest == null) {
                    return response()->json([
                        'message' => 'Failed',
                        'errors' => 'Leave request not found, please try different id',
                    ], 422);
                }

                $users = User::where([
                    ['id', '=', $leaveRequest->usersId],
                    ['isDeleted', '=', '0'],
                ])->first();

                if ($users == null) {

                    return response()->json([
                        'message' => 'Failed',
                        'errors' => 'Users not found, please try different id',
                    ], 422);
                }

                if (str_contains($leaveRequest->leaveType, "sick")) {

                    if (($leaveRequest->duration) > ($users->annualSickAllowanceRemaining)) {

                        return response()->json([
                            'message' => 'Inputed data is not valid',
                            'errors' => 'User Id ' . $leaveRequest->usersId . ' , with request sick leave id ' .  $val . ', the request allowance is higher, than remaining allowance : ' . $users->annualLeaveAllowanceRemaining . ' remaining'
                        ], 422);
                    } else {
                        $users->annualSickAllowanceRemaining = $users->annualSickAllowanceRemaining  - $leaveRequest->duration;
                    }
                } else {

                    if (($leaveRequest->duration) > ($users->annualLeaveAllowanceRemaining)) {

                        return response()->json([
                            'message' => 'Inputed data is not valid',
                            'errors' => 'User Id ' . $leaveRequest->usersId . ' , with request leave id ' . $val . ', the request allowance is higher, than remaining allowance : ' . $users->annualLeaveAllowanceRemaining . ' remaining'
                        ], 422);
                    } else {
                        $users->annualLeaveAllowanceRemaining = $users->annualLeaveAllowanceRemaining  - $leaveRequest->duration;
                    }
                }

                LeaveRequest::where('id', '=', $val)
                    ->update(
                        [
                            'status' => 'approve',
                            'approveOrRejectedBy' => $userName,
                            'approveOrRejectedDate' => now()
                        ],
                    );
                $users->save();
                DB::commit();
            }

            return response()->json([
                'result' => 'Success',
                'message' => 'Successfully approve all leave request',
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }



    public function rejectAll(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        try {

            $validate = Validator::make($request->all(), [
                'leaveRequestId' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            DB::beginTransaction();

            $data_item = [];
            foreach ($request->leaveRequestId as $val) {

                $checkIfDataExits = LeaveRequest::where([
                    ['id', '=', $val],
                    ['status', '=', 'pending']
                ])->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'leave request id: ' . $val . ' not found, please try different id');
                }
            }


            if ($data_item) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => $data_item,
                ], 422);
            }

            $userName =  $request->user()->firstName . " " . $request->user()->middleName . " " . $request->user()->lastName . "(" . $request->user()->nickName . ")";

            foreach ($request->leaveRequestId as $val) {

                LeaveRequest::where('id', '=', $val)
                    ->update(
                        [
                            'status' => 'reject',
                            'approveOrRejectedBy' => $userName,
                            'approveOrRejectedDate' => now(),
                            'rejectedReason' => 'Rejected by admin'
                        ],
                    );

                DB::commit();
            }
            return response()->json([
                'result' => 'Success',
                'message' => 'Successfully reject all leave request',
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }


    public function getIndexRequestLeave(Request $request)
    {


        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $rolesIndex = roleStaffLeave($request->user()->id);

        $validate = Validator::make($request->all(), [
            'status' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        } else {

            if (strtolower($request->status) != "approve" && strtolower($request->status) != "reject" && strtolower($request->status) != "pending") {

                return response()->json([
                    'message' => 'failed',
                    'errors' => 'Value status must Pending, Approve or Reject',
                ], 422);
            } else {

                $listOrder = [];

                if ($rolesIndex == 1) {

                    $data = $this->indexLeaveAdminandOffice($request);
                } else {

                    $data = $this->indexLeaveDoctorandStaff($request);
                }


                if ($data == null) {
                    return response()->json(['totalPagination' => 0, 'data' => []], 200);
                }


                if ($request->orderValue) {

                    $defaultOrderBy = $request->orderValue;
                }

                $checkOrder = null;

                if ($request->orderColumn && $defaultOrderBy) {

                    if (strtolower($request->status) == "pending") {
                        $listOrder = array('requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt');
                    } elseif (strtolower($request->status) == "approve") {
                        $listOrder = array('requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'approvedBy', 'approvedAt');
                    } else {
                        $listOrder = array('requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'rejectedBy', 'rejectedReason', 'rejectedAt');
                    }

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

                    if (strtolower($request->status) == "pending") {
                        $data = DB::table($data)->select('leaveRequestId', 'requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt')->orderBy($request->orderColumn, $defaultOrderBy)->orderBy('updatedAt', 'desc');
                    } elseif (strtolower($request->status) == "approve") {
                        $data = DB::table($data)->select('leaveRequestId', 'requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'approvedBy', 'approvedAt')->orderBy($request->orderColumn, $defaultOrderBy)->orderBy('updatedAt', 'desc');
                    } else {
                        $data = DB::table($data)->select('leaveRequestId', 'requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'rejectedBy', 'rejectedReason', 'rejectedAt')->orderBy($request->orderColumn, $defaultOrderBy)->orderBy('updatedAt', 'desc');
                    }
                } else {

                    if (strtolower($request->status) == "pending") {
                        $data = DB::table($data)->select('leaveRequestId', 'requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt')->orderBy('updatedAt', 'desc');
                    } elseif (strtolower($request->status) == "approve") {
                        $data = DB::table($data)->select('leaveRequestId', 'requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'approvedBy', 'approvedAt')->orderBy('updatedAt', 'desc');
                    } else {
                        $data = DB::table($data)->select('leaveRequestId', 'requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'rejectedBy', 'rejectedReason', 'rejectedAt')->orderBy('updatedAt', 'desc');
                    }
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
            }
        }
    }


    private function SearchRequestLeaveStaffDoctor($request)
    {

        if (is_numeric($request->search)) {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $request->status],
                    ['a.usersId', '=', $request->user()->id],
                ]);


            if ($request->locationId) {

                $test = $request->locationId;

                $data = $data->where(function ($query) use ($test) {
                    foreach ($test as $id) {
                        $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                    }
                });
            }


            if ($request->search) {
                $data = $data->where('a.duration', '=', $request->search);
            }

            $data = $data->get();

            if (count($data)) {
                $temp_column = 'a.duration';
                return $temp_column;
            }
        } else {

            if (strtolower($request->status) == "pending") {

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.requesterName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'b.jobName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.locationName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.locationName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.leaveType';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.fromDate';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.remark';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }
            } elseif (strtolower($request->status) == "approve") {

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.requesterName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'b.jobName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.locationName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.locationName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.leaveType';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.fromDate';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);



                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.remark';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationId as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedBy', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedBy';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedDate';
                    return $temp_column;
                }
            } else {

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.requesterName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->search) {
                    $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'b.jobName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.locationName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.locationName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')

                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.leaveType';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.fromDate';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.remark';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedBy', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedBy';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.rejectedReason', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.rejectedReason';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                        ['a.usersId', '=', $request->user()->id],
                    ]);

                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedDate';
                    return $temp_column;
                }
            }
        }
    }

    private function SearchRequestLeaveAdminOffice($request)
    {

        if (is_numeric($request->search)) {

            $data = LeaveRequest::from('leaveRequest as a')
                ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                ->select(
                    'a.id as leaveRequestId',
                    'a.requesterName as requesterName',
                    'b.jobName as jobName',
                    'a.locationName as locationName',
                    'a.locationId as locationId',
                    'a.leaveType as leaveType',
                    'a.fromDate as fromDate',
                    'a.duration as duration',
                    'a.remark as remark',
                    'a.created_at as createdAt',
                    'a.updated_at as updatedAt',
                )
                ->where([
                    ['a.status', '=', $request->status],
                ]);


            if ($request->locationId) {

                $test = $request->locationId;

                $data = $data->where(function ($query) use ($test) {
                    foreach ($test as $id) {
                        $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                    }
                });
            }


            if ($request->search) {
                $data = $data->where('a.duration', '=', $request->search);
            }

            $data = $data->get();

            if (count($data)) {
                $temp_column = 'a.duration';
                return $temp_column;
            }
        } else {

            if (strtolower($request->status) == "pending") {

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.requesterName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'b.jobName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.locationName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.locationName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.leaveType';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.fromDate';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.remark';
                    return $temp_column;
                }



                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }
            } elseif (strtolower($request->status) == "approve") {

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.requesterName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'b.jobName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);


                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.locationName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.locationName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.leaveType';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.fromDate';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.remark';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedBy', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedBy';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  approvedBy',
                        'a.approveOrRejectedDate as approvedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedDate';
                    return $temp_column;
                }
            } else {

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.requesterName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'b.jobName';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.locationName', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'c.locationName';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.leaveType';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }

                if ($request->search) {
                    $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.fromDate';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.remark';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.created_at';
                    return $temp_column;
                }

                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedBy', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedBy';
                    return $temp_column;
                }




                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.rejectedReason', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.rejectedReason';
                    return $temp_column;
                }


                $data = LeaveRequest::from('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select(
                        'a.id as leaveRequestId',
                        'a.requesterName as requesterName',
                        'b.jobName as jobName',
                        'a.locationName as locationName',
                        'a.locationId as locationId',
                        'a.leaveType as leaveType',
                        'a.fromDate as fromDate',
                        'a.duration as duration',
                        'a.remark as remark',
                        'a.created_at as createdAt',
                        'a.approveOrRejectedBy as  rejectedBy',
                        'a.rejectedReason as  rejectedReason',
                        'a.approveOrRejectedDate as rejectedAt',
                        'a.updated_at as updatedAt',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->locationId) {

                    $test = $request->locationId;

                    $data = $data->where(function ($query) use ($test) {
                        foreach ($test as $id) {
                            $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                        }
                    });
                }


                if ($request->search) {
                    $data = $data->where('a.approveOrRejectedDate', 'like', '%' . $request->search . '%');
                }

                $data = $data->get();

                if (count($data)) {
                    $temp_column = 'a.approveOrRejectedDate';
                    return $temp_column;
                }
            }
        }
    }

    public function getLeaveRequest(Request $request)
    {
        try {

            $request->validate([
                'usersId' => 'required|max:25',
            ]);

            DB::beginTransaction();

            $checkIfDataExits = User::where([['id', '=', $request->usersId]])->first();

            if ($checkIfDataExits == null) {

                return response()->json([
                    'message' => 'Failed',
                    'errors' => 'User id not found, please try different id',
                ]);
            } else {

                $annualLeaveAllowance = User::select(
                    DB::raw("1  as id"),
                    DB::raw("'Leave Allowance'  as leaveType"),
                    DB::raw("CONCAT('Annual Leave' ,' ', IFNULL(annualLeaveAllowanceRemaining,'0') ,' ', 'days remaining') as value"),
                )
                    ->where([
                        ['id', '=', $request->usersId],
                        ['isDeleted', '=', '0']
                    ]);

                $annualSickAllowance = User::select(
                    DB::raw("2  as id"),
                    DB::raw("'Sick Allowance'  as leaveType"),
                    DB::raw("CONCAT('Sick Leave' ,' ', IFNULL(annualSickAllowanceRemaining,'0') ,' ', 'days remaining') as value"),
                )
                    ->where([
                        ['id', '=', $request->usersId],
                        ['isDeleted', '=', '0']
                    ]);

                $getAllowance = $annualLeaveAllowance->union($annualSickAllowance)->get();
            }

            DB::commit();

            return response()->json($getAllowance, 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ]);
        }
    }



    public function getAllHolidaysDate(Request $request)
    {

        try {

            $valYear = null;

            if ($request->year) {
                $valYear = $request->year;
            } else {
                $valYear = date('Y');
            }


            $response = $this->client->request('GET', 'holidays', [
                'query' => [
                    'api_key' => $this->api_key,
                    'country' => $this->country,
                    'year' => $valYear,
                ],
            ]);

            $holidays = json_decode($response->getBody())->response->holidays;

            foreach ($holidays as $val) {

                if ($val->type[0] == "National holiday") {

                    if (Holidays::where('type', $val->type[0])
                        ->where('year', $valYear)
                        ->where('date', $val->date->iso)
                        ->exists()
                    ) {

                        Holidays::where('type', $val->type[0])
                            ->where('date', $val->date->iso)
                            ->where('year', $valYear)
                            ->update([
                                'date' => $val->date->iso,
                                'type' => $val->type[0],
                                'description' => $val->name,
                                'year' => $valYear,
                            ]);
                    } else {

                        Holidays::insert([
                            'date' => $val->date->iso,
                            'type' => $val->type[0],
                            'year' => $valYear,
                            'description' => $val->name,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'Success',
                'message' => "Successfully input date holidays",
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://calendarific.com/api/v2/',
        ]);
        $this->api_key = '40a18b1a57c593a8ba3e949ce44420e52b610171';
        $this->country = 'ID';
    }
}
