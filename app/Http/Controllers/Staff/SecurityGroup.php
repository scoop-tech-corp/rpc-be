<?php

namespace App\Http\Controllers\Staff;

use App\Models\SecurityGroups\SecurityGroups;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use DB;

class SecurityGroup extends Controller
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
                'a.id as securityGroupId',
                'a.roleName',
                DB::raw("IFNULL (count(b.roleId),0) as totalUser"),
                DB::raw("CASE WHEN a.IsActive = 1 THEN 'Aktif' else 'Tidak Aktif' END as status"),
                'a.updated_at as updatedAt'
            )->groupBy('b.roleId', 'a.id', 'a.roleName', 'a.IsActive', 'a.updated_at');


        $data = DB::table($data)
            ->select(
                'securityGroupId',
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
                'securityGroupId',
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
                    'securityGroupId',
                    'roleName',
                    'totalUser',
                    'status',
                )
                ->orderBy($request->orderColumn, $defaultOrderBy)
                ->orderBy('updatedAt', 'desc');
        } else {

            $data = DB::table($data)
                ->select(
                    'securityGroupId',
                    'roleName',
                    'totalUser',
                    'status',
                )
                ->orderBy('updatedAt', 'desc');
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



    public function detailSecurityGroup(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'securityGroupId' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $securityGroupId = $request->input('securityGroupId');

        $checkIfValueExits = SecurityGroups::where([
            ['id', '=', $securityGroupId],
        ])->first();


        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another security group id",
            ]);
        } else {

            $data = User::from('users as a')
                ->leftjoin('location as b', 'b.id', '=', 'a.locationId')
                ->leftjoin('jobtitle as c', 'c.id', '=', 'a.jobTitleId')
                ->select(
                    'a.id as usersId',
                    DB::raw("CONCAT(IFNULL(a.firstName,'') ,' ', IFNULL(a.middleName,'') ,' ', IFNULL(a.lastName,'') ) as customerName"),
                    'c.jobName as jobName',
                    'b.locationName as locationName',
                )->where([
                    ['a.roleId', '=', $securityGroupId],
                    ['a.isDeleted', '=', '0'],
                    ['b.isDeleted', '=', '0'],
                    ['c.isActive', '=', '1'],
                ])
                ->get();

            return response()->json($data, 200);
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
            'roleName' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        try {

            $checkIfDataExits = SecurityGroups::where([
                ['roleName', '=', $request->roleName],
            ])->first();

            if ($checkIfDataExits) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => 'Role name : ' .  $request->roleName . ' already exists in user roles, please try different role name',
                ], 422);
            }

            $securityGroup = new SecurityGroups();
            $securityGroup->roleName = $request->roleName;
            $securityGroup->isActive = 1;
            $securityGroup->save();

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
            'securityGroupId' => 'required',
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

            if ($request->securityGroupId == 1) {

                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => 'Restricted to delete id, please check your security group id',
                ], 422);
            } else {


                $checkIfDataExits = SecurityGroups::where([
                    ['id', '=', $request->securityGroupId],
                    ['isActive', '=', '1']
                ])->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'Security group id : ' . $request->securityGroupId . ' not found, please try different security group');
                }

                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            }


            SecurityGroups::where([
                ['id', '=', $request->securityGroupId],
                ['isActive', '=', '1']
            ])->update(['isActive' => 0, 'updated_at' => now()]);

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted Security Groups',
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
