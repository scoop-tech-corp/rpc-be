<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\exportValue;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Location;
use Validator;
use DB;
use File;
use PDF;


class StaffLeaveController extends Controller
{


    public function insertLeaveStaff(Request $request)
    {
        DB::beginTransaction();

        try {

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
                // $workingDays = implode(', ', $request->workingDays);

                DB::table('leaverequest')->insert([
                    'usersId' => $request->usersId,
                    'requesterName' => $userName,
                    'jobtitle' => $request->user()->jobTitleId,
                    'locationId' => $request->user()->locationId,
                    'leaveType' => $request->leaveType,
                    'fromDate' => $request->fromDate,
                    'toDate' => $request->toDate,
                    'duration' => $request->duration,
                    'workingdays' => $request->workingDays,
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

    public function setStatusLeaveRequest(Request $request)
    {

        try {

            DB::beginTransaction();

            $request->validate([
                'id' => 'required|max:25',
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


            $checkIfDataExits = DB::table('leaverequest')
                ->where([
                    ['usersId', '=', $request->id],
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
                    ['id', '=', $request->id],
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

            DB::table('leaverequest')
                ->where('id', '=', $request->id)
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
                        ->where('id', '=', $request->id)
                        ->update([
                            'annualSickAllowanceRemaining' => $totalsisaCuti,
                        ]);
                } else {

                    DB::table('users')
                        ->where('id', '=', $request->id)
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
                'firstName',
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
                'id' => 'required|max:25',
            ]);

            DB::beginTransaction();

            $checkIfDataExits = DB::table('users')
                ->where([
                    ['id', '=', $request->id],
                ])
                ->first();


            if ($checkIfDataExits == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User id not found, please try different id',
                ]);
            } else {

                $getAllowance = DB::table('users')
                    ->select(
                        DB::raw("CONCAT('Annual Leave' ,' ', IFNULL(annualLeaveAllowance,'0') ,' ', 'days remaining') as annualLeaveAllowance"),
                        DB::raw("CONCAT('Sick Leave' ,' ', IFNULL(annualSickAllowance,'0') ,' ', 'days remaining') as annualSickAllowance"),
                    )
                    ->where([
                        ['id', '=', $request->id],
                        ['isDeleted', '=', '0']
                    ])
                    ->get();
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


    public function getLeaveType(Request $request)
    {

        try {

            $request->validate([
                'id' => 'required|max:25',
            ]);

            DB::beginTransaction();

            $checkIfDataExits = DB::table('users')
                ->where([
                    ['id', '=', $request->id],
                    ['isDeleted', '=', '0']
                ])
                ->first();


            if ($checkIfDataExits == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User id not found, please try different id',
                ]);
            } else {

                $getAllowance = DB::table('users')
                    ->select(
                        DB::raw("CONCAT('Annual Leave' ,' ', IFNULL(annualLeaveAllowance,'0') ,' ', 'days remaining') as annualLeaveAllowance"),
                        DB::raw("CONCAT(IFNULL(annualSickAllowance,'0') ,' ', 'days remaining') as annualSickAllowance"),
                    )
                    ->where([
                        ['id', '=', $request->id],
                        ['isDeleted', '=', '0']
                    ])
                    ->get();
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
}
