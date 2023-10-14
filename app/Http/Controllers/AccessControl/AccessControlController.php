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
    public function indexMenuMaster(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('menuMaster as mm')
            ->join('users as u', 'mm.userId', 'u.id')
            ->select(
                'mm.id',
                'mm.masterName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {
            $res = $this->searchMasterMenu($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('mm.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;


        return responseIndex(ceil($totalPaging), $data);
    }

    public function indexMenuList(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('menuList as ml')
            ->join('menuMaster as mm', 'mm.id', 'ml.masterId')
            ->join('users as u', 'ml.userId', 'u.id')
            ->select(
                'ml.id',
                'mm.masterName',
                'ml.menuName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {

            $res = $this->searchListMenu($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('mm.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;


        return responseIndex(ceil($totalPaging), $data);
    }

    private function searchListMenu($request)
    {
        $temp_column = null;

        $data = DB::table('menuList as ml')
            ->join('menuMaster as mm', 'mm.id', 'ml.masterId')
            ->join('users as u', 'ml.userId', 'u.id')
            ->select(
                'ml.id',
                'mm.masterName',
                'ml.menuName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('mm.masterName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'mm.masterName';
        }

        $data = DB::table('menuList as ml')
            ->join('menuMaster as mm', 'mm.id', 'ml.masterId')
            ->join('users as u', 'ml.userId', 'u.id')
            ->select(
                'ml.id',
                'mm.masterName',
                'ml.menuName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('ml.menuName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ml.menuName';
        }

        $data = DB::table('menuList as ml')
            ->join('menuMaster as mm', 'mm.id', 'ml.masterId')
            ->join('users as u', 'ml.userId', 'u.id')
            ->select(
                'ml.id',
                'mm.masterName',
                'ml.menuName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }


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
                    DB::raw("
                        REPLACE(
                            TRIM(
                                REPLACE(
                                    CONCAT(
                                        IFNULL(a.firstName, ''),
                                        IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
                                        IFNULL(CONCAT(' ', a.lastName), ''),
                                        IFNULL(CONCAT(' (', a.nickName, ')'), '')
                                    ),
                                    '  (',
                                    '('
                                )
                            ),
                            ' (',
                            '('
                        ) AS name
                        "),
                    'b.roleName as roleName',
                    'c.jobName as jobName',
                    'a.createdBy as createdBy',
                    DB::raw("DATE_FORMAT(a.created_at, '%d/%m/%Y') as createdAt"),
                    //DB::raw('a.created_at as createdAt'),
                    DB::raw("DATE_FORMAT(a.updated_at, '%d/%m/%Y') as updated_at")
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

                    return responseInvalid(['order value must Ascending: ASC or Descending: DESC ']);
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

    private function searchMasterMenu($request)
    {
        $temp_column = null;

        $data = DB::table('menuMaster as mm')
            ->join('users as u', 'mm.userId', 'u.id')
            ->select(
                'mm.id',
                'mm.masterName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('mm.masterName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'mm.masterName';
        }

        $data = DB::table('menuMaster as mm')
            ->join('users as u', 'mm.userId', 'u.id')
            ->select(
                'mm.id',
                'mm.masterName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mm.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('mm.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
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
                $MenuMasters->userId = $Request->user()->id;;
                $MenuMasters->isDeleted = 0;
                $MenuMasters->created_at = now();
                $MenuMasters->updated_at = now();
                $MenuMasters->save();
                $newMenuMasterId = $MenuMasters->id;

                $remark_value =   "Menu Master" . $Request->masterName . " is created By " . $Request->user()->firstName;

                $AccessControlHistory = new AccessControlHistory();
                $AccessControlHistory->menuId = $newMenuMasterId;
                $AccessControlHistory->roleId = $Request->user()->roleId;
                $AccessControlHistory->remark = $remark_value;
                $AccessControlHistory->updatedBy = $Request->user()->id;
                $AccessControlHistory->created_at = now();
                $AccessControlHistory->updated_at = now();
                $AccessControlHistory->save();

                DB::commit();
            }

            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }



    public function insertAccessControlMenu(Request $Request)
    {
        DB::beginTransaction();

        try {


            $validate = Validator::make($Request->all(), [
                'menuId' => 'required|integer',
                'roleId' => 'required|integer',
                'accessTypeId' => 'required|integer',
                'accessLimitId' => 'required|integer',
            ]);


            if ($validate->fails()) {

                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }



            $checkMenuList = MenuList::where([
                ['id', '=', $Request->menuListId],
            ])->first();

            if (!$checkMenuList) {

                return responseInvalid(['Menu list with spesific id not exists, please try another menu list id']);
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
                $MenuList->userId = $Request->user()->id;
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

                foreach ($getAllUserRoles as $roles) {

                    $AccessControl = new AccessControl();
                    $AccessControl->menuListId = $newMenuListId;
                    $AccessControl->roleId = $roles->id;
                    $AccessControl->accessTypeId = $getAccessType->id;
                    $AccessControl->isDeleted = 0;
                    $AccessControl->created_at = now();
                    $AccessControl->updated_at = now();
                    $AccessControl->save();
                }

                $remark_value =   "Menu " . $Request->menuName . " is created By " . $Request->user()->firstName;

                $AccessControlHistory = new AccessControlHistory();
                $AccessControlHistory->menuId = $newMenuListId;
                $AccessControlHistory->roleId = $Request->user()->roleId;
                $AccessControlHistory->remark = $remark_value;
                $AccessControlHistory->updatedBy = $Request->user()->id;
                $AccessControlHistory->created_at = now();
                $AccessControlHistory->updated_at = now();
                $AccessControlHistory->save();

                DB::commit();
            }


            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function dropdownMenuList(Request $request)
    {
        try {

            if ($request->masterId) {

                $menuListsData = DB::table('menuList as a')
                    ->leftJoin('menuMaster as b', 'a.masterId', '=', 'b.id')
                    ->select(
                        'a.id',
                        'b.id as masterId',
                        'b.masterName as masterName',
                        'a.menuName as menuName'
                    )->where([
                        ['a.isActive', '=', 1], ['a.masterId', '=', $request->masterId]
                    ])->get();
            } else {

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
            }

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

                if (!$menuListsData->isEmpty()) {

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

                    $data['lists'][] = [
                        'module' => $menu->module,
                        'menus' =>  $menus
                    ];
                }
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

                return responseInvalid($errors);
            }


            $checkIfDataExits = MenuList::where([
                ['id', '=', $Request->id]
            ])->first();


            if (!$checkIfDataExits) {

                return responseInvalid(['Menu list with spesific id not exists! Please try different id!']);
            } else {

                if ($checkIfDataExits->menuName == $Request->menuName) {

                    return responseInvalid(['Menu name same with previous name! Please try different name!']);
                }

                $checkIfMenuExits = MenuList::where([
                    ['menuName', '=', $Request->menuName]
                ])->first();


                if ($checkIfMenuExits) {

                    return responseInvalid(['Menu name already exists! Please try different name!']);
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
                        ) AS createdBy
                        "),
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

                    return responseInvalid(['order value must Ascending: ASC or Descending: DESC ']);
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

            return responseInvalid([$e]);
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
                        ) AS createdBy
                        "),
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
                        ) AS createdBy
                        "),
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
                        ) AS createdBy
                        "),
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
                        ) AS createdBy
                        "),
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
                        ) AS createdBy
                        "),
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
                DB::raw("
		REPLACE(
			TRIM(
				REPLACE(
					CONCAT(
						IFNULL(a.firstName, ''),
						IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
						IFNULL(CONCAT(' ', a.lastName), ''),
						IFNULL(CONCAT(' (', a.nickName, ')'), '')
					),
					'  (',
					'('
				)
			),
			' (',
			'('
		) AS name
		"),
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
                DB::raw("
		REPLACE(
			TRIM(
				REPLACE(
					CONCAT(
						IFNULL(a.firstName, ''),
						IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
						IFNULL(CONCAT(' ', a.lastName), ''),
						IFNULL(CONCAT(' (', a.nickName, ')'), '')
					),
					'  (',
					'('
				)
			),
			' (',
			'('
		) AS name
		"),
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
                DB::raw("
		REPLACE(
			TRIM(
				REPLACE(
					CONCAT(
						IFNULL(a.firstName, ''),
						IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
						IFNULL(CONCAT(' ', a.lastName), ''),
						IFNULL(CONCAT(' (', a.nickName, ')'), '')
					),
					'  (',
					'('
				)
			),
			' (',
			'('
		) AS name
		"),
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
                DB::raw("
		REPLACE(
			TRIM(
				REPLACE(
					CONCAT(
						IFNULL(a.firstName, ''),
						IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
						IFNULL(CONCAT(' ', a.lastName), ''),
						IFNULL(CONCAT(' (', a.nickName, ')'), '')
					),
					'  (',
					'('
				)
			),
			' (',
			'('
		) AS name
		"),
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
                DB::raw("
		REPLACE(
			TRIM(
				REPLACE(
					CONCAT(
						IFNULL(a.firstName, ''),
						IF(a.middleName IS NOT NULL AND a.middleName != '', CONCAT(' ', a.middleName), ''),
						IFNULL(CONCAT(' ', a.lastName), ''),
						IFNULL(CONCAT(' (', a.nickName, ')'), '')
					),
					'  (',
					'('
				)
			),
			' (',
			'('
		) AS name
		"),
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
