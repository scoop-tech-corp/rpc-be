<?php

namespace App\Http\Controllers\Staff;

use App\Exports\StaffLeave\exportStaffLeave;
use App\Exports\StaffLeave\exportBalance;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use DB;

class StaffLeaveController extends Controller
{
    private $client;
    private $api_key;
    private $country;

    public function insertLeaveStaff(Request $request)
    {
        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'usersId' => 'required|integer',
                    'leaveType' => 'required|string',
                    'fromDate' => 'required|date_format:Y-m-d',
                    'toDate' => 'required|date_format:Y-m-d',
                    'duration' => 'required|integer',
                    // 'workingDays' => 'required|array',
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
                            'result' =>  'The given data was invalid.',
                            'message' => 'Working days value must same within the array',
                            'workingDays' => $listOrderUpper,
                        ]);
                    }

                    if ($valueDays == null) {

                        $valueDays =  $val['name'];

                    } else {

                        $valueDays = $valueDays . ',' . $val['name'];
                    }
                }
            }



            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end <= $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }

            $checkIfDataExits = DB::table('users')
                ->where([
                    ['id', '=', $request->usersId],
                    ['isDeleted', '=', '0']
                ])
                ->first();


            if ($checkIfDataExits == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User id not found, please try different id',
                ], 422);
            } else {


                $userName =  $request->user()->firstName . " " . $request->user()->middleName . " " . $request->user()->lastName . "(" . $request->user()->nickName . ")";

                DB::table('leaveRequest')->insert([
                    'usersId' => $request->usersId,
                    'requesterName' => $userName,
                    'jobtitle' => $request->user()->jobTitleId,
                    'locationId' => $request->user()->locationId,
                    'leaveType' => $request->leaveType,
                    'fromDate' => $request->fromDate,
                    'toDate' => $request->toDate,
                    'duration' => $request->duration,
                    'workingdays' => $valueDays,
                    'status' => "pending",
                    'remark' => $request->remark,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully input request leave',
                ], 200);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }


    public function getWorkingDays(Request $request)
    {


        try {

            $start = Carbon::parse($request->fromDate);
            $end = Carbon::parse($request->toDate);

            if ($end <= $start) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['To date must higher than from date!!'],
                ], 422);
            }

            $results = DB::table('holidays')
                ->whereBetween('date', [$start, $end])
                ->get();

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
                    'workDays' => $nameDays,
                    'totalDays' => $totalDays,
                ],
                200
            );
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }


    public function setStatusLeaveRequest(Request $request)
    {

        try {

            DB::beginTransaction();

            $request->validate([
                'userId' => 'required|max:25',
                'status' => 'required|max:25',
            ]);

            if (strtolower($request->status) != "approve" && strtolower($request->status) != "reject") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'Status must Approve or Reject',
                ], 422);
            }


            if (strtolower($request->status) == "reject" && strtolower($request->reason) == "") {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please input reason if status is reject',
                ], 422);
            }


            $checkIfDataExits = DB::table('leaveRequest')
                ->where([
                    ['usersId', '=', $request->userId],
                    ['status', '=', 'pending'],
                ])
                ->first();

            if ($checkIfDataExits == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Leave request not found, please try different id',
                ], 422);
            }

            $checkdataUsers = DB::table('users')
                ->where([
                    ['id', '=', $request->userId],
                    ['isDeleted', '=', '0'],
                ])
                ->first();

            if ($checkdataUsers == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Users not found, please try different id',
                ], 422);
            }

            $reason = null;

            if (strtolower($request->status) == "reject") {
                $reason = $request->reason;
            }

            $userName =  $request->user()->firstName . " " . $request->user()->middleName . " " . $request->user()->lastName . "(" . $request->user()->nickName . ")";

            DB::table('leaveRequest')
                ->where('id', '=', $request->userId)
                ->update([
                    'status' => $request->status,
                    'rejectedReason' => $reason,
                    'approveOrRejectedBy' =>  $userName,
                    'approveOrRejectedDate' =>  now(),
                ]);

            if (strtolower($request->status) == "approve") {

                $totalsisaCuti = $checkdataUsers->annualLeaveAllowanceRemaining - $checkIfDataExits->duration;

                if (str_contains($checkIfDataExits->leaveType, "sick")) {

                    DB::table('users')
                        ->where('id', '=', $request->userId)
                        ->update([
                            'annualSickAllowanceRemaining' => $totalsisaCuti,
                        ]);
                } else {

                    DB::table('users')
                        ->where('id', '=', $request->userId)
                        ->update([
                            'annualLeaveAllowanceRemaining' => $totalsisaCuti,
                        ]);
                }
            }

            $statusMessage = strtolower($request->status);

            DB::commit();

            return response()->json([
                'result' => 'Success',
                'message' => 'Successfully ' . $statusMessage . ' leave request',
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,

            ], 422);
        }
    }

    public function getIndexStaffBalance(Request $request)
    {
        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = DB::table('users as a')
            ->select(
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as Vacation Allowance',
                'a.annualLeaveAllowanceRemaining as Vacation Balance',
                'a.annualSickAllowance as Sick Allowance',
                'a.annualSickAllowanceRemaining as Sick Balance',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $res = $this->SearchStaffBalance($request);

            if ($res) {

                $data = $data->where($res, 'like', '%' . $request->search . '%');
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
                'name',
                'annualLeaveAllowance',
                'annualLeaveAllowanceRemaining',
                'annualSickAllowance',
                'annualSickAllowanceRemaining',
            );

            if (!in_array($request->orderColumn, $listOrder)) {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please try different order column',
                    'orderColumn' => $listOrder,
                ]);
            }

            if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'order value must Ascending: ASC or Descending: DESC ',
                ]);
            }

            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('a.updated_at', 'desc');

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






    private function SearchStaffBalance($request)
    {

        $data = DB::table('users as a')
            ->select(
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as Vacation Allowance',
                'a.annualLeaveAllowanceRemaining as Vacation Balance',
                'a.annualSickAllowance as Sick Allowance',
                'a.annualSickAllowanceRemaining as Sick Balance',
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


        $data = DB::table('users as a')
            ->select(
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as Vacation Allowance',
                'a.annualLeaveAllowanceRemaining as Vacation Balance',
                'a.annualSickAllowance as Sick Allowance',
                'a.annualSickAllowanceRemaining as Sick Balance',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualLeaveAllowance', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowance';
            return $temp_column;
        }


        $data = DB::table('users as a')
            ->select(
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as Vacation Allowance',
                'a.annualLeaveAllowanceRemaining as Vacation Balance',
                'a.annualSickAllowance as Sick Allowance',
                'a.annualSickAllowanceRemaining as Sick Balance',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualLeaveAllowanceRemaining', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowanceRemaining';
            return $temp_column;
        }

        $data = DB::table('users as a')
            ->select(
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as Vacation Allowance',
                'a.annualLeaveAllowanceRemaining as Vacation Balance',
                'a.annualSickAllowance as Sick Allowance',
                'a.annualSickAllowanceRemaining as Sick Balance',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualSickAllowance', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowance';
            return $temp_column;
        }

        $data = DB::table('users as a')
            ->select(
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
                'a.annualLeaveAllowance as Vacation Allowance',
                'a.annualLeaveAllowanceRemaining as Vacation Balance',
                'a.annualSickAllowance as Sick Allowance',
                'a.annualSickAllowanceRemaining as Sick Balance',
            )
            ->where([
                ['a.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('a.annualSickAllowanceRemaining', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowanceRemaining';
            return $temp_column;
        }
    }

    public function exportLeaveRequest(Request $request)
    {

        try {

            $request->validate([
                'status' => 'required'
            ]);

            if (strtolower($request->status) != "approve" && strtolower($request->status) != "reject" and strtolower($request->status) != "pending") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'Status must Pending, Approve or Reject',
                ], 422);
            }

            $tmp = "";
            $fileName = "";
            $date = Carbon::now()->format('d-m-Y');

            // if ($request->locationId) {

            //     $location = DB::table('location')
            //         ->select('locationName')
            //         ->whereIn('id', $request->locationId)
            //         ->get();

            //     if ($location) {

            //         foreach ($location as $key) {
            //             $tmp = $tmp . (string) $key->locationName . ",";
            //         }
            //     }
            //     $tmp = rtrim($tmp, ", ");
            // }

            // if ($tmp == "") {
            $fileName = "Leave Request " . $date . ".xlsx";
            // } else {
            //     $fileName = "Staff " . $tmp . " " . $date . ".xlsx";
            // }



            return Excel::download(
                new exportStaffLeave(
                    $request->orderValue,
                    $request->orderColumn,
                    $request->status,
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

    public function exportBalance(Request $request)
    {

        try {

            $tmp = "";
            $fileName = "";
            $date = Carbon::now()->format('d-m-Y');


            $fileName = "Leave Balace " . $date . ".xlsx";



            return Excel::download(
                new exportBalance(
                    $request->orderValue,
                    $request->orderColumn,
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



    public function getIndexRequestLeave(Request $request)
    {


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
                    'result' => 'failed',
                    'message' => 'Value status must Pending, Approve or Reject',
                ], 422);
            } else {

                $defaultRowPerPage = 5;
                $defaultOrderBy = "asc";

                $data = DB::table('leaveRequest as a')
                    ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
                    ->select(
                        'a.requesterName as requester',
                        'b.jobName as jobName',
                        'a.leaveType as leave type',
                        'a.fromDate as date',
                        'a.duration as days',
                        'a.remark as remark',
                        'a.created_at as created at',
                    )
                    ->where([
                        ['a.status', '=', $request->status],
                    ]);

                if ($request->search) {

                    $res = $this->SearchRequestLeave($request);

                    if ($res) {
                        $data = $data->where($res, 'like', '%' . $request->search . '%');
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
                        'requesterName',
                        'jobName',
                        'leaveType',
                        'fromDate',
                        'duration',
                        'remark',
                        'created_at',
                    );

                    if (!in_array($request->orderColumn, $listOrder)) {

                        return response()->json([
                            'result' => 'failed',
                            'message' => 'Please try different order column',
                            'orderColumn' => $listOrder,
                        ]);
                    }

                    if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                        return response()->json([
                            'result' => 'failed',
                            'message' => 'order value must Ascending: ASC or Descending: DESC ',
                        ]);
                    }

                    $data = $data->orderBy($request->orderColumn, $request->orderValue);
                }

                $data = $data->orderBy('a.created_at', 'desc');


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

    private function SearchRequestLeave($request)
    {

        $data = DB::table('leaverequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.requesterName';
            return $temp_column;
        }


        $data = DB::table('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('b.jobName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'b.jobName';
            return $temp_column;
        }

        $data = DB::table('leaverequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.leaveType';
            return $temp_column;
        }

        $data = DB::table('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.fromDate';
            return $temp_column;
        }


        $data = DB::table('leaverequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('a.duration', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.duration';
            return $temp_column;
        }


        $data = DB::table('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.remark';
            return $temp_column;
        }


        $data = DB::table('leaverequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->select(
                'a.requesterName as requester',
                'b.jobName as jobName',
                'a.leaveType as leave type',
                'a.fromDate as date',
                'a.duration as days',
                'a.remark as remark',
                'a.created_at as created at',
            )
            ->where([
                ['a.status', '=', 'pending'],
            ]);

        if ($request->search) {
            $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.created_at';
            return $temp_column;
        }
    }

    public function getLeaveRequest(Request $request)
    {
        try {

            $request->validate([
                'userId' => 'required|max:25',
            ]);

            DB::beginTransaction();

            $checkIfDataExits = DB::table('users')
                ->where([
                    ['id', '=', $request->userId],
                ])
                ->first();


            if ($checkIfDataExits == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User id not found, please try different id',
                ]);
            } else {

                $annualLeaveAllowance = DB::table('users')
                    ->select(
                        DB::raw("1  as id"),
                        DB::raw("'Leave Allowance'  as leaveType"),
                        DB::raw("CONCAT('Annual Leave' ,' ', IFNULL(annualLeaveAllowance,'0') ,' ', 'days remaining') as value"),
                    )
                    ->where([
                        ['id', '=', $request->userId],
                        ['isDeleted', '=', '0']
                    ]);

                $annualSickAllowance = DB::table('users')
                    ->select(
                        DB::raw("2  as id"),
                        DB::raw("'Sick Allowance'  as leaveType"),
                        DB::raw("CONCAT('Sick Leave' ,' ', IFNULL(annualSickAllowance,'0') ,' ', 'days remaining') as value"),
                    )
                    ->where([
                        ['id', '=', $request->userId],
                        ['isDeleted', '=', '0']
                    ]);
                $getAllowance = $annualLeaveAllowance->union($annualSickAllowance)->get();
            }

            DB::commit();

            return response()->json($getAllowance, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
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

                    if (DB::table('holidays')
                        ->where('type', $val->type[0])
                        ->where('year', $valYear)
                        ->where('date', $val->date->iso)
                        ->exists()
                    ) {

                        DB::table('holidays')
                            ->where('type', $val->type[0])
                            ->where('date', $val->date->iso)
                            ->where('year', $valYear)
                            ->update([
                                'date' => $val->date->iso,
                                'type' => $val->type[0],
                                'description' => $val->name,
                                'year' => $valYear,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    } else {

                        DB::table('holidays')
                            ->insert([
                                'date' => $val->date->iso,
                                'type' => $val->type[0],
                                'year' => $valYear,
                                'description' => $val->name,
                                'created_at' => now(),
                                'updated_at' => now(),
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
                'result' => 'Failed',
                'message' => $e,
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
