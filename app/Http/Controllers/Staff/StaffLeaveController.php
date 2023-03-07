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

            if (User::where('id', '=', $request->usersId)->where('isDeleted', '=', '0')->doesntExist()) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User id not found, please try different id',
                ], 422);
            } else {


                $userName =  $request->user()->firstName . " " . $request->user()->middleName . " " . $request->user()->lastName . "(" . $request->user()->nickName . ")";
                $staffLeave = new LeaveRequest();
                $staffLeave->usersId = $request->usersId;
                $staffLeave->requesterName = $userName;
                $staffLeave->jobtitle = $request->user()->jobTitleId;
                $staffLeave->locationId =  $request->user()->locationId;
                $staffLeave->leaveType = $request->leaveType;
                $staffLeave->fromDate = $request->fromDate;
                $staffLeave->toDate = $request->toDate;
                $staffLeave->duration = $request->duration;
                $staffLeave->workingdays = $valueDays;
                $staffLeave->status = "pending";
                $staffLeave->remark =  $request->remark;
                $staffLeave->created_at = now();
                $staffLeave->updated_at = now();
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
                'result' => 'Failed',
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
                'usersId' => 'required|max:25',
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


            $leaveRequest = leaveRequest::where('usersId', '=', $request->usersId)
                ->where('status', '=', 'pending')
                ->first();

            if ($leaveRequest == null) {
                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Leave request not found, please try different id',
                ], 422);
            }

            $users = User::where([
                ['id', '=', $request->usersId],
                ['isDeleted', '=', '0'],
            ])->first();

            if ($users == null) {

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

            $leaveRequest->status = $request->status;
            $leaveRequest->rejectedReason = $reason;
            $leaveRequest->approveOrRejectedBy = $userName;
            $leaveRequest->approveOrRejectedDate = now();

            if (strtolower($request->status) == "approve") {

                $totalsisaCuti = $users->annualLeaveAllowanceRemaining - $leaveRequest->duration;

                if (str_contains($leaveRequest->leaveType, "sick")) {
                    $users->annualSickAllowanceRemaining =  $totalsisaCuti;
                } else {
                    $users->annualLeaveAllowanceRemaining =  $totalsisaCuti;
                }
            }
            $leaveRequest->save();
            $users->save();
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

        $rolesIndex = roleStaffLeave($request->user()->id);


        if ($rolesIndex == 1) {

            $data = $this->indexBalanceLeaveAdminandOffice($request);
        } else {

            $data = $this->indexBalanceLeaveDoctorandStaff($request);
        }

        $data = DB::table($data)
            ->select(
                'name',
                'annualLeaveAllowance',
                'annualLeaveAllowanceRemaining',
                'annualSickAllowance',
                'annualSickAllowanceRemaining'
            )
            ->orderBy('updated_at', 'desc');


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


        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualLeaveAllowance', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowance';
            return $temp_column;
        }


        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualLeaveAllowanceRemaining', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowanceRemaining';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualSickAllowance', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowance';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualSickAllowanceRemaining', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowanceRemaining';
            return $temp_column;
        }
    }



    private function SearchBalanceDoctorandStaff($request)
    {

        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualLeaveAllowance', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowance';
            return $temp_column;
        }


        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualLeaveAllowanceRemaining', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualLeaveAllowanceRemaining';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
            $data = $data->where('a.annualSickAllowance', '=', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.annualSickAllowance';
            return $temp_column;
        }

        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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



    public function indexBalanceLeaveAdminandOffice(Request $request)
    {
        $defaultOrderBy = "asc";

        // $data = DB::table('users as a')
        //     ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
        //     ->select(
        //         DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
        //         'a.annualLeaveAllowance as annualLeaveAllowance',
        //         'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
        //         'a.annualSickAllowance as annualSickAllowance',
        //         'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
        //         'a.updated_at as updated_at',
        //     )
        //     ->where([
        //         ['a.isDeleted', '=', '0'],
        //     ])->get();

        // info($data);


        $data = User::from('Users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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
                $data = [];
                return $data;
            }
        }

        return $data;
    }

    public function indexBalanceLeaveDoctorandStaff(Request $request)
    {
        $defaultOrderBy = "asc";

        // $data = DB::table('users as a')
        //     ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
        //     ->select(
        //         DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ,'(', IFNULL(a.nickName,'') ,')'  ) as name"),
        //         'a.annualLeaveAllowance as annualLeaveAllowance',
        //         'a.annualLeaveAllowanceRemaining as annualLeaveAllowanceRemaining',
        //         'a.annualSickAllowance as annualSickAllowance',
        //         'a.annualSickAllowanceRemaining as annualSickAllowanceRemaining',
        //         'a.updated_at as updated_at',
        //     )
        //     ->where([
        //         ['a.isDeleted', '=', '0'],
        //         ['a.id', '=', $request->user()->id],
        //     ]);

        $data = User::from('users as a')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
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

            $res = $this->SearchBalanceDoctorandStaff($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                $data = [];
                return $data;
            }
        }

        return $data;
    }


    public function indexLeaveAdminandOffice(Request $request)
    {
        $defaultOrderBy = "asc";

        // $data = DB::table('leaveRequest as a')
        //     ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
        //     ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
        //     ->select(
        //         'a.requesterName as requesterName',
        //         'b.jobName as jobName',
        //         'c.locationName as locationName',
        //         'a.locationId as locationId',
        //         'a.leaveType as leaveType',
        //         'a.fromDate as fromDate',
        //         'a.duration as duration',
        //         'a.remark as remark',
        //         'a.created_at as createdAt',
        //         'a.updated_at as updatedAt',
        //     )
        //     ->where([
        //         ['a.status', '=', $request->status],
        //     ]);


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

            $val = [];

            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationId', $request->locationId);
            }
        }


        if ($request->search) {

            $res = $this->SearchRequestLeaveAdminOffice($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                $data = [];
                return $data;
            }
        }

        return $data;
    }




    public function indexLeaveDoctorandStaff(Request $request)
    {

        $defaultOrderBy = "asc";

        // $data = DB::table('leaveRequest as a')
        //     ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
        //     ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
        //     ->select(
        //         'a.requesterName as requesterName',
        //         'b.jobName as jobName',
        //         'c.locationName as locationName',
        //         'a.locationId as locationId',
        //         'a.leaveType as leaveType',
        //         'a.fromDate as fromDate',
        //         'a.duration as duration',
        //         'a.remark as remark',
        //         'a.created_at as createdAt',
        //         'a.updated_at as updatedAt',
        //     )
        //     ->where([
        //         ['a.status', '=', $request->status],
        //         ['a.usersId', '=', $request->user()->id],
        //     ]);

        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

            $val = [];

            foreach ($request->locationId as $temp) {
                $val = $temp;
            }

            if ($val) {
                $data = $data->whereIn('a.locationId', $request->locationId);
            }
        }


        if ($request->search) {

            $res = $this->SearchRequestLeaveStaffDoctor($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                $data = [];
                return $data;
            }
        }

        $data = DB::table($data)
            ->select(
                'requesterName',
                'jobName',
                'locationName',
                'leaveType',
                'fromDate',
                'duration',
                'remark',
                'createdAt',
            );

        return $data;
    }


    public function getIndexRequestLeave(Request $request)
    {

        $defaultRowPerPage = 5;

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
                    'result' => 'failed',
                    'message' => 'Value status must Pending, Approve or Reject',
                ], 422);
            } else {


                $listOrder = null;

                if ($rolesIndex == 1) {

                    $data = $this->indexLeaveAdminandOffice($request);
                } else {
                    $data = $this->indexLeaveDoctorandStaff($request);
                }



                $data = DB::table($data)
                    ->select(
                        'requesterName',
                        'jobName',
                        'locationName',
                        'leaveType',
                        'fromDate',
                        'duration',
                        'remark',
                        'createdAt',
                    )
                    ->orderBy('updatedAt', 'desc');

                if ($request->orderValue) {
                    $defaultOrderBy = $request->orderValue;
                }

                if ($request->orderColumn && $defaultOrderBy) {

                    $listOrder = array(
                        'requesterName',
                        'jobName',
                        'locationName',
                        'leaveType',
                        'fromDate',
                        'duration',
                        'remark',
                        'createdAt',
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

                $data = $data->orderBy('createdAt', 'desc');

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


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.requesterName';
            return $temp_column;
        }



        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.jobName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.jobName';
            return $temp_column;
        }




        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('c.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'c.locationName';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.leaveType';
            return $temp_column;
        }



        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.fromDate';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.duration', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.duration';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.remark';
            return $temp_column;
        }

        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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


        if ($request->search) {
            $data = $data->where('a.created_at', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.created_at';
            return $temp_column;
        }
    }

    private function SearchRequestLeaveAdminOffice($request)
    {

        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('a.requesterName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.requesterName';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('a.jobName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.jobName';
            return $temp_column;
        }

        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('c.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'c.locationName';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('a.leaveType', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.leaveType';
            return $temp_column;
        }

        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('a.fromDate', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.fromDate';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('a.duration', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.duration';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

        if ($request->search) {
            $data = $data->where('a.remark', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'a.remark';
            return $temp_column;
        }


        $data = leaveRequest::from('leaveRequest as a')
            ->leftjoin('jobtitle as b', 'a.jobtitle', '=', 'b.id')
            ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
            ->select(
                'a.requesterName as requesterName',
                'b.jobName as jobName',
                'c.locationName as locationName',
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

            $checkIfDataExits = User::where([['id', '=', $request->userId]])->first();

            if ($checkIfDataExits == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User id not found, please try different id',
                ]);
            } else {

                $annualLeaveAllowance = User::select(
                    DB::raw("1  as id"),
                    DB::raw("'Leave Allowance'  as leaveType"),
                    DB::raw("CONCAT('Annual Leave' ,' ', IFNULL(annualLeaveAllowance,'0') ,' ', 'days remaining') as value"),
                )
                    ->where([
                        ['id', '=', $request->userId],
                        ['isDeleted', '=', '0']
                    ]);

                $annualSickAllowance = User::select(
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

                    if (holidays::where('type', $val->type[0])
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

                        holidays::insert([
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
