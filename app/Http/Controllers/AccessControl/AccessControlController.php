<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessControl\AccessControlHistory;
use App\Models\AccessControl\AccessControl;
use App\Models\AccessControl\MenuList;
use App\Models\AccessControl\AccessLimit;
use App\Models\AccessControl\AccessType;
use App\Models\Staff\UsersRoles;

use Validator;
use DB;

class AccessControlController extends Controller
{
    public function index(Request $request)
    {

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            $data = DB::table('users as a')
                ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
                ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
                ->select(
                    'a.id as id',
                    DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                    'b.roleName as roleName',
                    'c.jobName as jobName',
                    'a.createdBy as createdBy',
                    DB::raw('a.created_at as createdAt'),
                    'a.updated_at'
                )
                ->where([
                    ['a.isDeleted', '=', '0'],
                    ['b.isActive', '=', '1'],
                    ['c.isActive', '=', '1'],
                ]);


            $data = DB::table($data)
                ->select(
                    'id',
                    'name',
                    'roleName',
                    'jobName',
                    'createdBy',
                    'createdAt',
                    'updated_at'
                );

            if ($request->search) {

                $res = $this->Search($request);

                if ($res) {

                    if ($res == "name") {

                        $data = $data->where('name', 'like', '%' . $request->search . '%');
                    } else if ($res == "roleName") {

                        $data = $data->where('roleName', 'like', '%' . $request->search . '%');
                    } else if ($res == "jobName") {

                        $data = $data->where('jobName', 'like', '%' . $request->search . '%');
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
            }


            if ($request->orderValue) {

                $defaultOrderBy = $request->orderValue;
            }


            $checkOrder = null;
            if ($request->orderColumn && $defaultOrderBy) {

                $listOrder = array(
                    'id',
                    'name',
                    'roleName',
                    'jobName',
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
                        'id',
                        'name',
                        'roleName',
                        'jobName',
                        'createdBy',
                        'createdAt'
                    )
                    ->orderBy($request->orderColumn, $defaultOrderBy)
                    ->orderBy('updated_at', 'desc');
            } else {


                $data = DB::table($data)
                    ->select(
                        'id',
                        'name',
                        'roleName',
                        'jobName',
                        'createdBy',
                        'createdAt'
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

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }




    public function insertAccessControlMenut(Request $Request)
    {
        DB::beginTransaction();

        try {


            $validate = Validator::make($Request->all(), [
                'menuListId' => 'required|integer',
                'roleId' => 'required|integer',
                'accessTypeId' => 'required|integer',
                'accessLimitId' => 'required|integer',
            ]);


            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }



            $checkMenuList = MenuList::where([
                ['id', '=', $Request->menuListId],
            ])->first();

            if (!$checkMenuList) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => "Menu list with spesific id not exists, please try another menu list id",
                ]);
            }



            $checkMenuList = MenuList::where([['id', '=', $Request->menuListId],])->first();

            if (!$checkMenuList) {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list with spesific id not exists, please try another menu list id!'],
                ], 422);
            }


            $checkIfUserRoleExists = UsersRoles::where([['id', '=', $Request->roleId], ['isActive', '=', 1],])->first();

            if (!$checkIfUserRoleExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['User role id not exists please try different id!'],
                ], 422);
            }

            $checkIfDataExits = AccessType::where([['id', '=', $Request->accessTypeId]])->first();

            if (!$checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Access type id not exists please try different id!'],
                ], 422);
            }


            $checkIfAccessLimitExists = AccessLimit::where([['id', '=', $Request->accessLimitId]])->first();

            if (!$checkIfAccessLimitExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Access Limit id not exists please try different id!'],
                ], 422);
            }


            $getFinal = AccessControl::where([
                ['menuListId', '=', $Request->menuListId],
                ['roleId', '=', $Request->roleId]
            ])->first();

            if ($getFinal) {
                return response()->json([
                    'result' => 'Failed',
                    'message' => "Menu list id and role id already exists, please try another menu id and role id",
                ]);
            }


            $valeuremark = "Menu List " . $checkMenuList->menuName . " with role name " .  $checkIfUserRoleExists->roleName . " has been added by " . $Request->user()->firstName;

            $AccessControl = new AccessControl();
            $AccessControl->menuListId = $Request->menuListId;
            $AccessControl->roleId =  $Request->roleId;
            $AccessControl->accessTypeId = $Request->accessTypeId;
            $AccessControl->accessLimitId = $Request->accessLimitId;
            $AccessControl->isDeleted = 0;
            $AccessControl->created_at = now();
            $AccessControl->updated_at = now();
            $AccessControl->save();

            $AccessControlHistory = new AccessControlHistory();
            $AccessControlHistory->menuId = $Request->menuListId;
            $AccessControlHistory->roleId = $Request->roleId;
            $AccessControlHistory->remark = $valeuremark;
            $AccessControlHistory->updatedBy = $Request->user()->id;
            $AccessControlHistory->created_at = now();
            $AccessControlHistory->updated_at = now();
            $AccessControlHistory->save();

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'successfuly insert menu access control',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' =>  $e,
            ]);
        }
    }

    public function insertMenutList(Request $Request)
    {
        DB::beginTransaction();

        try {

            $validate = Validator::make($Request->all(), [
                'menuName' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }


            $checkMenuList = MenuList::where([
                ['menuName', '=', $Request->menuName],
            ])->first();

            if ($checkMenuList) {


                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list with spesific name already exists, please try another menu list id!'],
                ], 422);
            } else {


                $MenuList = new MenuList();
                $MenuList->menuName = $Request->menuName;
                $MenuList->isActive = 1;
                $MenuList->created_at = now();
                $MenuList->updated_at = now();
                $MenuList->save();
                DB::commit();
            }



            return response()->json([
                'result' => 'success',
                'message' => 'successfuly insert menu list',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' =>  $e,
            ]);
        }
    }





    public function deleteAccessControlMenu(Request $Request)
    {
        DB::beginTransaction();
        try {

            $validate = Validator::make($Request->all(), [
                'menuListId' => 'required|integer',
                'roleId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIfDataMenuExists = DB::table('menuList')
                ->where([
                    ['id', '=', $Request->menuListId]
                ])
                ->first();

            if (!$checkIfDataMenuExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list id not exists please try different id!'],
                ], 422);
            }


            $checkIfUserRoleExists = DB::table('usersRoles')
                ->where([
                    ['id', '=', $Request->roleId],
                    ['isActive', '=', 1],
                ])
                ->first();

            if (!$checkIfUserRoleExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['User role id not exists please try different id!'],
                ], 422);
            }




            $checkIfMenuExistsInAccessControl = DB::table('accessControl')
                ->where([
                    ['menuListId', '=', $Request->menuListId]
                ])
                ->first();

            if (!$checkIfMenuExistsInAccessControl) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list id not exists in Access Control'],
                ], 422);
            }


            $checkIfRoleIdExistsInAccessControl = DB::table('accessControl')
                ->where([
                    ['roleId', '=', $Request->roleId],
                    // ['isDeleted', '=', 0],
                ])
                ->first();

            if (!$checkIfRoleIdExistsInAccessControl) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Role id not exists in Access Control'],
                ], 422);
            }



            DB::table('accessControl')
                ->where([
                    ['roleId', '=', $Request->roleId],
                    ['menuListId', '=', $Request->menuListId]
                ])
                ->update(['isDeleted' => 1,]);

            $remark_value =   "Menu " . $checkIfDataMenuExists->menuName . " is Deleted By " . $Request->user()->firstName;

            DB::table('accesscontrolhistory')
                ->insert([
                    'menuId' => $Request->menuListId,
                    'roleId' => $Request->roleId,
                    'remark' => $remark_value,
                    'updatedBy' => $Request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();


            return response()->json([
                'result' => 'success',
                'message' => 'Successfully delete access control menu'
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' =>  $e,
            ]);
        }
    }


    public function updateAccessControlMenu(Request $Request)
    {

        DB::beginTransaction();
        try {


            $validate = Validator::make($Request->all(), [
                'menuListId' => 'required|integer',
                'accessTypeId' => 'required|integer',
                'roleId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }


            $checkIfDataExits = AccessType::where([
                ['id', '=', $Request->accessTypeId]
            ])
                ->first();

            if (!$checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Access type id not exists please try different id!'],
                ], 422);
            }



            $checkIfDataMenuExists = MenuList::where('id', '=', $Request->menuListId)->first();

            if (!$checkIfDataMenuExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list id not exists please try different id!'],
                ], 422);
            }


            $checkIfUserRoleExists = UsersRoles::where([['id', '=', $Request->roleId], ['isActive', '=', 1],])->first();

            if (!$checkIfUserRoleExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['User role id not exists please try different id!'],
                ], 422);
            }



            $checkIfMenuExistsInAccessControl = AccessControl::where([['menuListId', '=', $Request->menuListId]])->first();

            if (!$checkIfMenuExistsInAccessControl) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list id not exists in Access Control'],
                ], 422);
            }


            $checkIfRoleIdExistsInAccessControl = AccessControl::where([['roleId', '=', $Request->roleId]])->first();

            if (!$checkIfRoleIdExistsInAccessControl) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Role id not exists in Access Control'],
                ], 422);
            }



            //kalau ada access limit 
            if ($Request->accessLimitId) {

                $checkIfAccessLimitExists = AccessLimit::where([['id', '=', $Request->accessLimitId]])->first();

                if (!$checkIfAccessLimitExists) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Access Limit id not exists please try different id!'],
                    ], 422);
                }

                $getFinal = AccessControl::where([
                    ['menuListId', '=', $Request->menuListId],
                    ['roleId', '=', $Request->roleId]
                ])->first();

                //3 condition 
                // kalau role id sama tapi limit beda 
                // kalau role id beda tapi limit sama
                // kalau 2 2 nya berbeda

                if (($getFinal->accessTypeId == $Request->accessTypeId) && ($getFinal->accessLimitId != $Request->accessLimitId)) {

                    $valeuremark = "Access Limit  " . $checkIfDataMenuExists->menuName . " is change to " .  $checkIfAccessLimitExists->timeLimit . " by " . $Request->user()->firstName;

                    AccessControl::where([
                        ['menuListId', '=', $Request->menuListId],
                        ['roleId', '=', $Request->roleId]
                    ])->update(['accessLimitId' => $Request->accessLimitId]);


                    $AccessControlHistory = new AccessControlHistory();
                    $AccessControlHistory->menuId = $Request->menuListId;
                    $AccessControlHistory->roleId = $Request->roleId;
                    $AccessControlHistory->remark = $valeuremark;
                    $AccessControlHistory->updatedBy = $Request->user()->id;
                    $AccessControlHistory->created_at = now();
                    $AccessControlHistory->updated_at = now();
                    $AccessControlHistory->save();
                } elseif (($getFinal->accessTypeId != $Request->accessTypeId) && ($getFinal->accessLimitId == $Request->accessLimitId)) {

                    $valeuremark = "Access Type " . $checkIfDataMenuExists->menuName . " is change to " . $checkIfDataExits->accessType . " by " . $Request->user()->firstName;

                    AccessControl::where([
                        ['menuListId', '=', $Request->menuListId],
                        ['roleId', '=', $Request->roleId]
                    ])->update(['accessTypeId' => $Request->accessTypeId]);


                    $AccessControlHistory = new AccessControlHistory();
                    $AccessControlHistory->menuId = $Request->menuListId;
                    $AccessControlHistory->roleId = $Request->roleId;
                    $AccessControlHistory->remark = $valeuremark;
                    $AccessControlHistory->updatedBy = $Request->user()->id;
                    $AccessControlHistory->created_at = now();
                    $AccessControlHistory->updated_at = now();
                    $AccessControlHistory->save();
                } elseif (($getFinal->accessTypeId != $Request->accessTypeId) &&  ($getFinal->accessLimitId != $Request->accessLimitId)) {

                    $valeuremark = "Access type menu " . $checkIfDataMenuExists->menuName . " is change to " . $checkIfDataExits->accessType . " & Access Limit change to " . $checkIfAccessLimitExists->timeLimit . " by " . $Request->user()->firstName;

                    AccessControl::where([
                        ['menuListId', '=', $Request->menuListId],
                        ['roleId', '=', $Request->roleId]
                    ])->update(['accessTypeId' => $Request->accessTypeId, 'accessLimitId' => $Request->accessLimitId]);


                    $AccessControlHistory = new AccessControlHistory();
                    $AccessControlHistory->menuId = $Request->menuListId;
                    $AccessControlHistory->roleId = $Request->roleId;
                    $AccessControlHistory->remark = $valeuremark;
                    $AccessControlHistory->updatedBy = $Request->user()->id;
                    $AccessControlHistory->created_at = now();
                    $AccessControlHistory->updated_at = now();
                    $AccessControlHistory->save();
                } else {

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Access type id and access limit already same, please try different id!'],
                    ], 422);
                }
            } else {

                //kalau tidak ada access limit

                $getFinal = AccessControl::where([
                    ['menuListId', '=', $Request->menuListId],
                    ['roleId', '=', $Request->roleId]
                ])->first();

                if (($getFinal->accessTypeId != $Request->accessTypeId)) {

                    $valeuremark = "Access Type " . $checkIfDataMenuExists->menuName . " is change to " . $checkIfDataExits->accessType . " by " . $Request->user()->firstName;

                    AccessControl::where([
                        ['menuListId', '=', $Request->menuListId],
                        ['roleId', '=', $Request->roleId]
                    ])->update(['accessTypeId' => $Request->accessTypeId]);


                    $AccessControlHistory = new AccessControlHistory();
                    $AccessControlHistory->menuId = $Request->menuListId;
                    $AccessControlHistory->roleId = $Request->roleId;
                    $AccessControlHistory->remark = $valeuremark;
                    $AccessControlHistory->updatedBy = $Request->user()->id;
                    $AccessControlHistory->created_at = now();
                    $AccessControlHistory->updated_at = now();
                    $AccessControlHistory->save();
                } else {

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Access type id already same, please try different id!'],
                    ], 422);
                }
            }


            DB::commit();


            return response()->json([
                'result' => 'success',
                'message' => 'Successfully updated access control menu'
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' =>  $e,
            ]);
        }
    }



    public function indexHistory(Request $request)
    {

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            $data = DB::table('accessControlHistory as a')
                ->leftjoin('users as b', 'b.id', '=', 'a.updatedBy')
                ->leftjoin('usersRoles as d', 'd.id', '=', 'a.roleId')
                ->leftjoin('menuList as c', 'c.id', '=', 'a.menuId')
                ->select(
                    'a.id as id',
                    'c.menuName as menuName',
                    'd.roleName as roleName',
                    'a.remark as action',
                    DB::raw("CONCAT(IFNULL(b.firstName,''), case when b.middleName is null then '' else ' ' end , IFNULL(b.middleName,'') ,case when b.lastName is null then '' else ' ' end, case when b.lastName is null then '' else b.lastName end ) as createdBy"),
                    DB::raw('a.created_at as createdAt'),
                    'a.updated_at'
                )
                ->where([
                    ['b.isDeleted', '=', '0'],
                    ['c.isActive', '=', '1'],
                    ['d.isActive', '=', '1'],
                ]);


            $data = DB::table($data)
                ->select(
                    'id',
                    'menuName',
                    'roleName',
                    'action',
                    'createdBy',
                    'createdAt',
                    'updated_at'
                );




            if ($request->search) {

                $res = $this->SearchHistory($request);

                if ($res) {

                    if ($res == "menuName") {

                        $data = $data->where('menuName', 'like', '%' . $request->search . '%');
                    } else if ($res == "roleName") {

                        $data = $data->where('roleName', 'like', '%' . $request->search . '%');
                    } else if ($res == "action") {

                        $data = $data->where('action', 'like', '%' . $request->search . '%');
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
            }


            if ($request->orderValue) {

                $defaultOrderBy = $request->orderValue;
            }



            $checkOrder = null;
            if ($request->orderColumn && $defaultOrderBy) {

                $listOrder = array(
                    'id',
                    'menuName',
                    'roleName',
                    'action',
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
                        'id',
                        'menuName',
                        'roleName',
                        'action',
                        'createdBy',
                        'createdAt'
                    )
                    ->orderBy($request->orderColumn, $defaultOrderBy)
                    ->orderBy('updated_at', 'desc');
            } else {


                $data = DB::table($data)
                    ->select(
                        'id',
                        'menuName',
                        'roleName',
                        'action',
                        'createdBy',
                        'createdAt'
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

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }






    private function SearchHistory($request)
    {

        $data = DB::table('accessControlHistory as a')
            ->leftjoin('users as b', 'b.id', '=', 'a.updatedBy')
            ->leftjoin('usersRoles as d', 'd.id', '=', 'a.roleId')
            ->leftjoin('menuList as c', 'c.id', '=', 'a.menuId')
            ->select(
                'a.id as id',
                'c.menuName as menuName',
                'd.roleName as roleName',
                'a.remark as action',
                DB::raw("CONCAT(IFNULL(b.firstName,''), case when b.middleName is null then '' else ' ' end , IFNULL(b.middleName,'') ,case when b.lastName is null then '' else ' ' end, case when b.lastName is null then '' else b.lastName end ) as createdBy"),
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['b.isDeleted', '=', '0'],
                ['c.isActive', '=', '1'],
                ['d.isActive', '=', '1'],
            ]);


        $data = DB::table($data)
            ->select(
                'id',
                'menuName',
                'roleName',
                'action',
                'createdBy',
                'createdAt',
                'updated_at'
            );


        if ($request->search) {

            $data = $data->where('menuName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'menuName';
            return $temp_column;
        }


        $data = DB::table('accessControlHistory as a')
            ->leftjoin('users as b', 'b.id', '=', 'a.updatedBy')
            ->leftjoin('usersRoles as d', 'd.id', '=', 'a.roleId')
            ->leftjoin('menuList as c', 'c.id', '=', 'a.menuId')
            ->select(
                'a.id as id',
                'c.menuName as menuName',
                'd.roleName as roleName',
                'a.remark as action',
                DB::raw("CONCAT(IFNULL(b.firstName,''), case when b.middleName is null then '' else ' ' end , IFNULL(b.middleName,'') ,case when b.lastName is null then '' else ' ' end, case when b.lastName is null then '' else b.lastName end ) as createdBy"),
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['b.isDeleted', '=', '0'],
                ['c.isActive', '=', '1'],
                ['d.isActive', '=', '1'],
            ]);


        $data = DB::table($data)
            ->select(
                'id',
                'menuName',
                'roleName',
                'action',
                'createdBy',
                'createdAt',
                'updated_at'
            );


        if ($request->search) {

            $data = $data->where('roleName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'roleName';
            return $temp_column;
        }



        $data = DB::table('accessControlHistory as a')
            ->leftjoin('users as b', 'b.id', '=', 'a.updatedBy')
            ->leftjoin('usersRoles as d', 'd.id', '=', 'a.roleId')
            ->leftjoin('menuList as c', 'c.id', '=', 'a.menuId')
            ->select(
                'a.id as id',
                'c.menuName as menuName',
                'd.roleName as roleName',
                'a.remark as action',
                DB::raw("CONCAT(IFNULL(b.firstName,''), case when b.middleName is null then '' else ' ' end , IFNULL(b.middleName,'') ,case when b.lastName is null then '' else ' ' end, case when b.lastName is null then '' else b.lastName end ) as createdBy"),
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['b.isDeleted', '=', '0'],
                ['c.isActive', '=', '1'],
                ['d.isActive', '=', '1'],
            ]);


        $data = DB::table($data)
            ->select(
                'id',
                'menuName',
                'roleName',
                'action',
                'createdBy',
                'createdAt',
                'updated_at'
            );


        if ($request->search) {

            $data = $data->where('action', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'action';
            return $temp_column;
        }

        $data = DB::table('accessControlHistory as a')
            ->leftjoin('users as b', 'b.id', '=', 'a.updatedBy')
            ->leftjoin('usersRoles as d', 'd.id', '=', 'a.roleId')
            ->leftjoin('menuList as c', 'c.id', '=', 'a.menuId')
            ->select(
                'a.id as id',
                'c.menuName as menuName',
                'd.roleName as roleName',
                'a.remark as action',
                DB::raw("CONCAT(IFNULL(b.firstName,''), case when b.middleName is null then '' else ' ' end , IFNULL(b.middleName,'') ,case when b.lastName is null then '' else ' ' end, case when b.lastName is null then '' else b.lastName end ) as createdBy"),
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['b.isDeleted', '=', '0'],
                ['c.isActive', '=', '1'],
                ['d.isActive', '=', '1'],
            ]);


        $data = DB::table($data)
            ->select(
                'id',
                'menuName',
                'roleName',
                'action',
                'createdBy',
                'createdAt',
                'updated_at'
            );


        if ($request->search) {

            $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdBy';
            return $temp_column;
        }



        $data = DB::table('accessControlHistory as a')
            ->leftjoin('users as b', 'b.id', '=', 'a.updatedBy')
            ->leftjoin('usersRoles as d', 'd.id', '=', 'a.roleId')
            ->leftjoin('menuList as c', 'c.id', '=', 'a.menuId')
            ->select(
                'a.id as id',
                'c.menuName as menuName',
                'd.roleName as roleName',
                'a.remark as action',
                DB::raw("CONCAT(IFNULL(b.firstName,''), case when b.middleName is null then '' else ' ' end , IFNULL(b.middleName,'') ,case when b.lastName is null then '' else ' ' end, case when b.lastName is null then '' else b.lastName end ) as createdBy"),
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['b.isDeleted', '=', '0'],
                ['c.isActive', '=', '1'],
                ['d.isActive', '=', '1'],
            ]);


        $data = DB::table($data)
            ->select(
                'id',
                'menuName',
                'roleName',
                'action',
                'createdBy',
                'createdAt',
                'updated_at'
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


    private function Search($request)
    {

        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'name';
            return $temp_column;
        }




        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('roleName', 'like', '%' . $request->search . '%');
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'roleName';
            return $temp_column;
        }



        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('jobName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'jobName';
            return $temp_column;
        }


        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdBy';
            return $temp_column;
        }


        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
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
}
