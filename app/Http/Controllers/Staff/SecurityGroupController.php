<?php

namespace App\Http\Controllers\Staff;

use App\Models\SecurityGroups\SecurityGroups;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use DB;

class SecurityGroupController extends Controller
{
    public function index(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = DB::table('usersRoles as a')
            ->leftjoin(
                DB::raw('(select roleId,id from users where isDeleted=0) as b'),
                function ($join) {
                    $join->on('b.roleId', '=', 'a.id');
                }
            )
            ->select(
                'a.id',
                'a.roleName',
                DB::raw("CAST(IFNULL(count(b.roleId),0) AS INT) as totalUser"),
                DB::raw("CASE WHEN a.IsActive = 1 THEN 1 else 0 END as status"),
                'a.updated_at as updatedAt'
            )->groupBy('b.roleId', 'a.id', 'a.roleName', 'a.IsActive', 'a.updated_at');


        $data = DB::table($data)
            ->select(
                'id',
                'roleName',
                'totalUser',
                'status',
                'updatedAt'
            );

        if ($request->orderValue) {
            $defaultOrderBy = $request->orderValue;
        }

        $checkOrder = null;

        if ($request->orderColumn && $defaultOrderBy) {

            $listOrder = array(
                'id',
                'roleName',
                'totalUser',
                'status',
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

            $checkOrder = true;
        }


        if ($checkOrder) {

            $data = DB::table($data)
                ->select(
                    'id',
                    'roleName',
                    'totalUser',
                    'status',
                )
                ->orderBy($request->orderColumn, $defaultOrderBy)
                ->orderBy('id', 'asc');
        } else {

            $data = DB::table($data)
                ->select(
                    'id',
                    'roleName',
                    'totalUser',
                    'status',
                )
                ->orderBy('id', 'asc');
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


    public function dropdownUsersSecurityGroup(Request $request)
    {
        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        $subquery = DB::table('usersLocation as a')
            ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
            ->select('a.usersId', DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
            ->groupBy('a.usersId')
            ->where('a.isDeleted', '=', 0);


        $data = User::from('users as a')
            ->leftJoinSub($subquery, 'b', function ($join) {
                $join->on('b.usersId', '=', 'a.id');
            })
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as usersId',
                DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ) as customerName"),
                'c.jobName as jobName',
                'b.locationName as locationName',
            )->where([
                ['a.isDeleted', '=', '0'],
                ['c.isActive', '=', '1'],
            ])
            ->whereNull('a.roleId')
            ->get();

        return response()->json($data, 200);
    }



    public function detailSecurityGroup(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        $id = $request->id;

        $checkIfValueExits = SecurityGroups::where([
            ['id', '=', $id],
        ])->first();


        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another security group id",
            ]);
        } else {

            $param = SecurityGroups::select('id', 'roleName', 'isActive as status')
                ->where('id', '=', $id)
                ->first();

            $subquery = DB::table('usersLocation as a')
                ->leftJoin('location as b', 'b.id', '=', 'a.locationId')
                ->select('a.usersId', DB::raw("GROUP_CONCAT(b.locationName) as locationName"))
                ->groupBy('a.usersId')
                ->where('a.isDeleted', '=', 0);

            $data = User::from('users as a')
                ->leftJoinSub($subquery, 'b', function ($join) {
                    $join->on('b.usersId', '=', 'a.id');
                })
                ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
                ->select(
                    'a.id as usersId',
                    DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ) as customerName"),
                    'c.jobName as jobName',
                    'b.locationName as locationName',
                )->where([
                    ['a.roleId', '=', $id],
                    ['a.isDeleted', '=', '0'],
                    ['c.isActive', '=', '1'],
                ])
                ->get();


            $param->users = $data;

            return response()->json($param, 200);
        }
    }


    public function InsertSecurityGroup(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        $validate = Validator::make($request->all(), [
            'role' => 'required',
            'status' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        try {





            $checkIfRoleExists = SecurityGroups::where([
                ['roleName', '=', $request->role],
            ])->first();

            if ($checkIfRoleExists) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => ['Role name : ' . $request->role . ' is already exists, please try different role name'],
                ], 422);
            } else {

                $data_item = [];


                $userIdArray = json_decode($request->usersId, true);

                foreach ($userIdArray as $val) {

                    $checkIfDataExits = DB::table('users')
                        ->where([
                            ['id', '=', $val],
                            ['isDeleted', '=', '0']
                        ])
                        ->first();

                    if ($checkIfDataExits === null) {
                        array_push($data_item, 'user id: ' . $val . ' not found, please try different id');
                    }
                }

                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }

                $securityGroups = new SecurityGroups();
                $securityGroups->roleName =  $request->role;
                $securityGroups->isActive = $request->status;
                $securityGroups->save();


                if ($request->status == 1) {

                    foreach ($request->usersId as $val) {

                        DB::table('users')
                            ->where('id', '=', $val)
                            ->update(['roleId' => $securityGroups->id]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully insert Security Groups',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }


    public function updateSecurityGroup(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        try {

            if ($request->id == 1) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => ['Restricted to update Administrator, please user different id !!'],
                ], 422);
            } else {

                if (($request->status != 1 && $request->status != 0)) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => ['Status role id must active = 1, or non active = 0'],
                    ], 422);
                }


                $checkIfRoleExists = SecurityGroups::where([
                    ['id', '=', $request->id],
                ])->first();

                if ($checkIfRoleExists === null) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => ['Role id : ' . $request->id . ' is not exists, please try different role id'],
                    ], 422);
                }


                SecurityGroups::where('id', '=', $request->id)
                    ->update([
                        'isActive' => $request->status
                    ]);


                $data_item = [];

                foreach ($request->users as $val) {

                    $checkIfDataExits = DB::table('users')
                        ->where([
                            ['id', '=', $val['userId']],
                            ['isDeleted', '=', '0']
                        ])
                        ->first();

                    if ($checkIfDataExits === null) {
                        array_push($data_item, 'user id: ' . $val['userId'] . ' not found, please try different id');
                    }


                    if ($data_item) {
                        return response()->json([
                            'message' => 'Inputed data is not valid',
                            'errors' => $data_item,
                        ], 422);
                    }
                }



                foreach ($request->users as $val) {

                    if ($val['status'] == "del") { // set user to role id become non active
                        DB::table('users')
                            ->where('id', '=', $val['userId'])
                            ->update(['roleId' => ""]);
                    } else {


                        if ($request->status == 1) {
                            DB::table('users')
                                ->where('id', '=', $val['userId'])
                                ->update(['roleId' => $request->status]);
                        } else {

                            DB::table('users')
                                ->where('id', '=', $val['userId'])
                                ->update(['roleId' => ""]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => ['Successfully update Security Groups'],
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function deleteSecurityGroup(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        $validate = Validator::make($request->all(), [
            'id' => 'required',
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

            if ($request->id == 1) {

                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => ['Restricted to delete Administrator, please user different id !!'],
                ], 422);
            } else {


                $checkIfDataExits = SecurityGroups::where([
                    ['id', '=', $request->id]
                ])->first();

                if ($checkIfDataExits === null) {
                    array_push($data_item, 'Security group Id : ' . $request->id . ' not found, please try different security group');
                }

                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            }


            SecurityGroups::where([
                ['id', '=', $request->id],
                ['isActive', '=', '1']
            ])->update(['isActive' => 0, 'updated_at' => now()]);

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => ['Successfully deleted Security Groups'],
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }
}
