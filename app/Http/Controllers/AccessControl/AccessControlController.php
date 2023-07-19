<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessControl\AccessControlHistory;
use App\Models\AccessControl\AccessControl;
use App\Models\AccessControl\MenuList;
use App\Models\AccessControl\AccessLimit;
use App\Models\AccessControl\AccessType;
use App\Models\AccessControl\MenuMasters;
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

            return responseInvalid([$e]);
        }
    }


    public function insertMenuMaster(Request $Request)
    {

        DB::beginTransaction();

        try {

            $validate = Validator::make($Request->all(), [
                'masterName' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }


            $checkMasterMenu = MenuMasters::where([
                ['masterName', '=', $Request->masterName],
            ])->first();

            if ($checkMasterMenu) {

                return responseInvalid(['Menu master with spesific name already! Please try another name!']);
            } else {

                $MenuMasters = new MenuMasters();
                $MenuMasters->masterName = $Request->masterName;
                $MenuMasters->isDeleted = 0;
                $MenuMasters->created_at = now();
                $MenuMasters->updated_at = now();
                $MenuMasters->save();
                DB::commit();
            }

            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function insertMenutList(Request $Request)
    {
        DB::beginTransaction();

        try {

            $validate = Validator::make($Request->all(), [
                'masterId' => 'required|integer',
                'menuName' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }


            $checkMasterMenu = MenuMasters::where([
                ['id', '=', $Request->masterId],
            ])->first();

            if (!$checkMasterMenu) {

                return responseInvalid(['Menu master with spesific id not exists! Please try another id!']);
            }


            $checkMenuList = MenuList::where([
                ['menuName', '=', $Request->menuName],
            ])->first();

            if ($checkMenuList) {

                return responseInvalid(['Menu list with spesific name already exists! Please try another menu list id!']);
            } else {


                $MenuList = new MenuList();
                $MenuList->masterId = $Request->masterId;
                $MenuList->menuName = $Request->menuName;
                $MenuList->isActive = 1;
                $MenuList->created_at = now();
                $MenuList->updated_at = now();
                $MenuList->save();
                $newMenuListId = $MenuList->id;

                $getAllUserRoles = UsersRoles::select('id')
                    ->where([
                        ['isActive', '=', 1],
                    ])->get();

                $getAccessType = AccessType::select('id')
                    ->where([
                        ['AccessType', '=', 'None']
                    ])->first();

                $getAccessLimit = accesslimit::select('id')
                    ->where([
                        ['id', '=', 1],
                    ])->first();

                foreach ($getAllUserRoles as $roles) {

                    $AccessControl = new AccessControl();
                    $AccessControl->menuListId = $newMenuListId;
                    $AccessControl->roleId = $roles->id;
                    $AccessControl->accessTypeId = $getAccessType->id;
                    $AccessControl->accessLimitId = $getAccessLimit->id;
                    $AccessControl->isDeleted = 0;
                    $AccessControl->created_at = now();
                    $AccessControl->updated_at = now();
                    $AccessControl->save();
                }


                DB::commit();
            }


            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function dropdownMenuList()
    {

        try {

            $menuListsData = DB::table('menuList as a')
                ->leftJoin('menuMaster as b', 'a.masterId', '=', 'b.id')
                ->select(
                    'a.id',
                    'b.id as masterId',
                    'b.masterName as masterName',
                    'a.menuName as menuName'
                )->where([
                    ['a.isActive', '=', 1]
                ])->get();

            return responseList($menuListsData);
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }



    public function dropdownMenuMaster()
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


    public function dropdownAccessType()
    {

        try {

            $accessType = AccessType::select('id', 'accessType')->get();
            return responseList($accessType);
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function indexAccessControlDashboard()
    {

        try {

            $menus = [];

            $roles = UsersRoles::select(DB::raw('LOWER(roleName) as roleName'))->pluck('roleName')->toArray();
            $jsonData = json_encode(['roles' => $roles]);

            $data = json_decode($jsonData, true);

            $menuMastersData = MenuMasters::select('id', 'masterName as module')->where([
                ['isDeleted', '=', 0],
            ])->get();

            $menuMastersDataNew = $menuMastersData->map(function ($item) {
                return collect($item)->forget('id');
            });

            $tolo = ['menu'];
            $menuMastersDataNew = $menuMastersDataNew
                ->union($tolo);

            foreach ($menuMastersData as $menu) {

                $menuListsData = MenuList::select('id', 'menuName as menuName')->where([
                    ['isActive', '=', 1],
                    ['masterId', '=', $menu->id],
                ])->get();

                $menus = [];
                if ($menuListsData) {

                    foreach ($menuListsData as $datamenulist) {

                        $accessControls = DB::table('accessControl')
                            ->join('menuList', 'accessControl.menuListId', '=', 'menuList.id')
                            ->join('usersRoles', 'accessControl.roleID', '=', 'usersRoles.id')
                            ->select(
                                'menuList.id as menuId',
                                'menuList.menuName as menuName',
                                DB::raw('LOWER(usersRoles.roleName) as roleName'),
                                'accessControl.accessTypeId'
                            )
                            ->where([
                                ['menuListId', '=', $datamenulist->id],
                            ])->get();

                        foreach ($accessControls as $accessControl) {

                            $menuId = $accessControl->menuId;
                            $menuName = $accessControl->menuName;
                            $roleName = $accessControl->roleName;
                            $accessType = $accessControl->accessTypeId;

                            $menuIndex = array_search($menuId, array_column($menus, 'menuId'));

                            if ($menuIndex === false) {

                                $menus[] = [
                                    'menuId' => $menuId,
                                    'menuName' => $menuName,
                                    $roleName => $accessType,
                                ];
                            } else {

                                $menus[$menuIndex][$roleName] = $accessType;
                            }
                        }
                    }
                }


                $data['lists'][] = [
                    'module' => $menu->module,
                    'menus' =>  $menus
                ];
            }

            return response()->json($data, 200);
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function deleteAccessControlMenu(Request $Request)
    {
        DB::beginTransaction();
        try {

            $validate = Validator::make($Request->all(), [
                'menuId' => 'required|integer',
                'roleId' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfDataMenuExists = DB::table('menuList')
                ->where([
                    ['id', '=', $Request->menuId]
                ])
                ->first();

            if (!$checkIfDataMenuExists) {
                return responseInvalid(['Menu list id not exists! Please try different id!']);
            }

            $checkIfUserRoleExists = DB::table('usersRoles')
                ->where([
                    ['id', '=', $Request->roleId],
                    ['isActive', '=', 1],
                ])
                ->first();

            if (!$checkIfUserRoleExists) {
                return responseInvalid(['User role id not exists! Please try different id!']);
            }

            $checkIfMenuExistsInAccessControl = DB::table('accessControl')
                ->where([
                    ['menuListId', '=', $Request->menuId]
                ])
                ->first();

            if (!$checkIfMenuExistsInAccessControl) {
                return responseInvalid(['Menu list id not exists in Access Control!']);
            }

            $checkIfRoleIdExistsInAccessControl = DB::table('accessControl')
                ->where([
                    ['roleId', '=', $Request->roleId],
                ])
                ->first();

            if (!$checkIfRoleIdExistsInAccessControl) {
                return responseInvalid(['Role id not exists in Access Control!']);
            }

            DB::table('accessControl')
                ->where([
                    ['roleId', '=', $Request->roleId],
                    ['menuListId', '=', $Request->menuId]
                ])
                ->update(['isDeleted' => 1,]);

            $remark_value =   "Menu " . $checkIfDataMenuExists->menuName . " is Deleted By " . $Request->user()->firstName;

            DB::table('accessControlHistory')
                ->insert([
                    'menuId' => $Request->menuId,
                    'roleId' => $Request->roleId,
                    'remark' => $remark_value,
                    'updatedBy' => $Request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return responseDelete();
        } catch (Exception $e) {

            DB::rollback();
            return responseInvalid([$e]);
        }
    }

    public function updateMenuMaster(Request $Request)
    {
        DB::beginTransaction();

        try {

            $validate = Validator::make($Request->all(), [
                'id' => 'required|integer',
                'masterName' => 'required|string',
            ]);

            if ($validate->fails()) {

                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $checkIfDataExits = MenuMasters::where([['id', '=', $Request->id]])->first();

            if (!$checkIfDataExits) {
                return responseInvalid(['Menu master with spesific id not exists! Please try different id!']);
            } else {

                if ($checkIfDataExits->masterName == $Request->masterName) {
                    return responseInvalid(['Master name same with previous name! Please try different name!']);
                }

                $checkIfMasterName = MenuMasters::where([
                    ['masterName', '=', $Request->masterName]
                ])->first();

                if ($checkIfMasterName) {

                    return responseInvalid(['Master name already exists! Please try different name!']);
                } else {
                    MenuMasters::where([
                        ['id', '=', $Request->id]
                    ])->update(['masterName' => $Request->masterName]);
                }
            }

            DB::commit();

            return responseUpdate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function updateMenuList(Request $Request)
    {

        DB::beginTransaction();

        try {

            $validate = Validator::make($Request->all(), [
                'id' => 'required|integer',
                'masterId' => 'integer',
                'menuName' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }


            $checkIfDataExits = MenuList::where([
                ['id', '=', $Request->id]
            ])->first();


            if (!$checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Menu list with spesific id not exists! Please try different id!'],
                ], 422);
            } else {

                if ($checkIfDataExits->menuName == $Request->menuName) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Menu name same with previous name! Please try different name!'],
                    ], 422);
                }

                $checkIfMenuExits = MenuList::where([
                    ['menuName', '=', $Request->menuName]
                ])->first();


                if ($checkIfMenuExits) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Menu name already exists! Please try different name!'],
                    ], 422);
                } else {
                    MenuList::where([
                        ['id', '=', $Request->id]
                    ])->update(['menuName' => $Request->menuName]);
                }
            }

            DB::commit();

            return responseUpdate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }



    public function updateAccessControlMenu(Request $Request)
    {

        DB::beginTransaction();

        try {

            $validate = Validator::make($Request->all(), [
                'menuId' => 'required|integer',
                'roleName' => 'required|string',
                'type' => 'required|integer'
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }


            $checkIfDataExits = AccessType::where([
                ['id', '=', $Request->type]
            ])->first();


            if (!$checkIfDataExits) {

                return responseInvalid(['Access type id not exists! Please try different id!']);
            }


            $checkIfDataMenuExists = MenuList::where('id', '=', $Request->menuId)->first();

            if (!$checkIfDataMenuExists) {

                return responseInvalid(['Menu list id not exists! Please try different id!']);
            }

            $checkIfUserRoleExists = UsersRoles::where([['roleName', 'like',  '%' . $Request->roleName . '%'], ['isActive', '=', 1],])->first();

            if (!$checkIfUserRoleExists) {

                return responseInvalid(['User role name not exists! Please try different id!']);
            }

            $roleIdAccessControl = $checkIfUserRoleExists->id;


            $checkIfMenuExistsInAccessControl = AccessControl::where([['menuListId', '=', $Request->menuId]])->first();

            if (!$checkIfMenuExistsInAccessControl) {

                return responseInvalid(['Menu list id not exists in Access Control!']);
            }


            $checkIfRoleIdExistsInAccessControl = AccessControl::where([['roleId', '=', $roleIdAccessControl]])->first();

            if (!$checkIfRoleIdExistsInAccessControl) {

                return responseInvalid(['Role id not exists in Access Control!']);
            }

            $getFinal = AccessControl::where([
                ['menuListId', '=', $Request->menuId],
                ['roleId', '=', $roleIdAccessControl]
            ])->first();

            if (($getFinal->accessTypeId != $Request->type)) {

                $valeuremark = "Access Type " . $checkIfDataMenuExists->menuName . " is change to " . $checkIfDataExits->accessType . " by " . $Request->user()->firstName;

                AccessControl::where([
                    ['menuListId', '=', $Request->menuId],
                    ['roleId', '=', $roleIdAccessControl]
                ])->update(['accessTypeId' => $Request->type]);

                $AccessControlHistory = new AccessControlHistory();
                $AccessControlHistory->menuId = $Request->menuId;
                $AccessControlHistory->roleId = $roleIdAccessControl;
                $AccessControlHistory->remark = $valeuremark;
                $AccessControlHistory->updatedBy = $Request->user()->id;
                $AccessControlHistory->created_at = now();
                $AccessControlHistory->updated_at = now();
                $AccessControlHistory->save();
            } else {

                return responseInvalid(['Access type id already same! Please try different id']);
            }

            DB::commit();

            return responseUpdate();

        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
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
