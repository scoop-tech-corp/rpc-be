<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\AccessControl\MenuList;
use App\Models\AccessControl\MenuMasters;
use App\Models\StaffAbsents;
use App\Models\menuGroup;
use Carbon\Carbon;
use Carbon\Doctrine\CarbonDoctrineType;
use Illuminate\Support\Facades\DB as FacadesDB;

class ApiController extends Controller
{


    public function register(Request $request)
    {

        $data = $request->only('name', 'email', 'password', 'role');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50',
            'role' => 'required|string',
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'isDeleted' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], Response::HTTP_OK);
    }



    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $checkIfValueExits = DB::table('usersEmails')
            ->select(
                'usersEmails.usersId',
                'usersEmails.email'
            )
            ->where([
                ['usersEmails.email', '=', $request->email],
                ['usersEmails.usage', '=', 'Utama'],
                ['usersEmails.isDeleted', '=', 0]
            ])
            ->first();

        if ($checkIfValueExits != null) {

            $users = DB::table('users')
                ->select(
                    'firstName',
                    'password',
                )
                ->where([
                    ['email', '=', $request->email],
                    ['isDeleted', '=', 0]
                ])
                ->first();

            if ($users->password == null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Email address is not verified, Please check your email to verify your account and set the password',
                ], 400);
            } else {

                try {

                    if (!$token = JWTAuth::attempt($credentials)) {

                        return response()->json([
                            'success' => false,
                            'message' => "Password unmatch, please check again",
                        ], 400);
                    }
                } catch (JWTException $e) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Could not create token.',
                    ], 500);
                }

                $userId = $checkIfValueExits->usersId;
                $emailaddress = $checkIfValueExits->email;

                $users = DB::table('users')
                    ->leftjoin('jobTitle', 'jobTitle.id', '=', 'users.jobTitleId')
                    ->leftjoin('usersRoles', 'usersRoles.id', '=', 'users.roleId')
                    ->select(
                        'users.id',
                        'users.imagePath',
                        'users.roleId',
                        DB::raw("IF(usersRoles.roleName IS NULL, '', usersRoles.roleName) as roleName"),
                        DB::raw("IF(jobTitle.jobName IS NULL,'', jobTitle.jobName) as jobName"),
                        DB::raw("CONCAT(IFNULL(users.firstName,'') ,' ', IFNULL(users.lastName,'')) as name"),
                    )
                    ->where([
                        ['users.id', '=', $userId],
                        ['users.isDeleted', '=', '0']
                    ])
                    ->first();

                $data = DB::table('accessControl as a')
                    ->join('menuList as b', 'b.id', '=', 'a.menuListId')
                    ->join('accessType as c', 'c.id', '=', 'a.accessTypeId')
                    ->select(
                        'b.menuName',
                        'c.accessType',
                    )
                    ->where([['a.roleId', '=', $users->roleId],])
                    ->get();

                $locations = DB::table('usersLocation as ul')
                    ->join('location as l', 'ul.locationId', 'l.id')
                    ->select('l.id', 'l.locationName')
                    ->where('ul.usersId', '=', $userId)
                    ->get();

                $menuMastersData = MenuMasters::select('id', 'masterName as module')->where([
                    ['isDeleted', '=', 0],
                ])->get();


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
                                    'menuList.id as uid',
                                    'menuList.menuName as menuName',
                                    DB::raw('LOWER(usersRoles.roleName) as roleName'),
                                )
                                ->where([
                                    ['menuListId', '=', $datamenulist->id],
                                ])->get();


                            foreach ($accessControls as $accessControl) {

                                $menuId = $accessControl->uid;
                                $menuName = $accessControl->menuName;

                                $menuIndex = array_search($menuId, array_column($menus, 'uid'));

                                if ($menuIndex === false) {

                                    $menus[] = [
                                        'uid' => $menuId,
                                        'childName' => $menuName,

                                    ];
                                }
                            }
                        }


                        $data[] = [
                            'menuName' => $menu->module,
                            'children' =>  $menus
                        ];
                    }
                }


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
                                ->join('accessType', 'accessType.id', '=', 'accessControl.accessTypeId')
                                ->select(
                                    'menuList.id as uid',
                                    'menuList.menuName as menuName',
                                    'accessType.id as accessTypeId',
                                    'accessType.accessType as accessTypeName',
                                    DB::raw('LOWER(usersRoles.roleName) as roleName'),
                                )
                                ->where([
                                    ['menuListId', '=', $datamenulist->id],
                                ])->get();


                            foreach ($accessControls as $accessControl) {

                                $menuId = $accessControl->uid;
                                $menuName = $accessControl->menuName;
                                $accessType = $accessControl->accessTypeName;
                                $accessTypeId = $accessControl->accessTypeId;

                                $menuIndex = array_search($menuId, array_column($menus, 'uid'));

                                if ($menuIndex === false) {

                                    $menus[] = [
                                        'uid' => $menuId,
                                        'childName' => $menuName,
                                        'accessTypeId' => $accessTypeId,
                                        'accessType' => $accessType,
                                    ];
                                }
                            }
                        }


                        $accessTypeMenu[] = [
                            'menuName' => $menu->module,
                            'children' =>  $menus
                        ];
                    }
                }

                $curTime = Carbon::now();
                $compareTime = Carbon::createFromFormat('H:i:s', '07:00:00');

                $diffTime = $compareTime->diffInSeconds($curTime, false);

                if ($diffTime < 0) {
                    $isAbsent = 1;
                } else {

                    $absent = StaffAbsents::where('userId', '=', $userId)
                        ->whereDate('created_at', Carbon::today())
                        ->first();
                    $isAbsent = 1;
                    if (!$absent) {
                        $isAbsent = 0;
                    }
                }

                $masterMenu = (object)[];
                $resChild = [];
                $valueRes = [];
                $finalRes = [];
                $valueResSingle = [];

                $groups = DB::table('menuGroups')
                    ->select('id as idNum', 'groupName as id', DB::raw('"group" as type'))
                    ->where('isDeleted', '=', 0)
                    ->orderBy('orderMenu', 'asc')
                    ->get();

                foreach ($groups as $value) {

                    $tempChildren = DB::table('childrenMenuGroups')
                        ->select('id as idNum', 'identify as id', 'title', 'type', 'icon')
                        ->where('groupId', '=', $value->idNum)
                        ->where('isDeleted', '=', 0)
                        ->get();

                    if (count($tempChildren) == 1) {

                        $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                            ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                            ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                            ->select('gcm.orderMenu', 'gcm.identify as id', 'gcm.title', 'gcm.type', 'gcm.url', 'gcm.icon', 'accessTypeId as accessType')
                            ->where('gcm.childrenId', '=', $tempChildren[0]->idNum)
                            ->where('ac.roleId', '=', $users->roleId)
                            ->where('gcm.isDeleted', '=', 0)
                            ->orderBy('gcm.orderMenu', 'asc')
                            ->get();

                        if (count($grandChilds) == 1) {

                            $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                                ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                                ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                                ->select('gcm.identify as id', 'gcm.title', 'gcm.type', 'gcm.url', 'gcm.icon', 'accessTypeId as accessType')
                                ->where('gcm.childrenId', '=', $tempChildren[0]->idNum)
                                ->where('ac.roleId', '=', $users->roleId)
                                ->where('gcm.isDeleted', '=', 0)
                                ->first();

                            array_push($valueResSingle, $grandChilds);
                        } else {
                            $resChild[] = array(
                                'id' => $tempChildren[0]->id,
                                'title' => $tempChildren[0]->title,
                                'type' => $tempChildren[0]->type,
                                'icon' => $tempChildren[0]->icon,
                                'children' => $grandChilds
                            );
                            $valueRes = $resChild;
                            $resChild = [];
                        }
                    } else {
                        $childrens = DB::table('childrenMenuGroups')
                            ->select('id as idNum', 'identify as id', 'title', 'type', 'icon')
                            ->where('groupId', '=', $value->idNum)
                            ->where('isDeleted', '=', 0)
                            ->orderBy('orderMenu', 'asc')
                            ->get();

                        foreach ($childrens as $valueChild) {
                            $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                                ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                                ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                                ->select('gcm.id as idNum', 'gcm.identify as id', 'gcm.title', 'gcm.type', 'gcm.url', 'cm.icon', 'ac.accessTypeId as accessType')
                                ->where('gcm.childrenId', '=', $valueChild->idNum)
                                ->where('ac.roleId', '=', $users->roleId)
                                ->where('gcm.isDeleted', '=', 0)
                                ->orderBy('gcm.orderMenu', 'asc')
                                ->get();

                            if (count($grandChilds) == 1) {

                                $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                                    ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                                    ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                                    ->select('gcm.id as idNum', 'gcm.identify as id', 'gcm.title', 'gcm.type', 'gcm.url', 'gcm.icon', 'ac.accessTypeId as accessType')
                                    ->where('gcm.childrenId', '=', $valueChild->idNum)
                                    ->where('ac.roleId', '=', $users->roleId)
                                    ->where('gcm.isDeleted', '=', 0)
                                    ->first();

                                array_push($valueResSingle, $grandChilds);
                            } else {
                                $grandChildsNew = DB::table('grandChildrenMenuGroups as gcm')
                                    ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                                    ->select('gcm.identify as id', 'gcm.title', 'gcm.type', 'gcm.url', 'ac.accessTypeId as accessType')
                                    ->where('ac.roleId', '=', $users->roleId)
                                    ->where('gcm.childrenId', '=', $valueChild->idNum)
                                    ->where('gcm.isDeleted', '=', 0)
                                    ->orderBy('gcm.orderMenu', 'asc')
                                    ->get();

                                $resChild[] = array(
                                    'id' => $grandChilds[0]->id,
                                    'title' => $grandChilds[0]->title,
                                    'type' => $grandChilds[0]->type,
                                    'url' => $grandChilds[0]->url,
                                    'children' => $grandChildsNew,
                                );
                                $valueRes = $resChild;
                                $resChild = [];
                            }
                        }
                    }

                    if (count($valueRes) == 0) {

                        $finalRes = $valueResSingle;
                    } else {
                        $finalRes = $valueRes;
                    }

                    $masterMenu->items[] = array(
                        'id' => $value->id,
                        'type' => $value->type,
                        'children' => $finalRes
                    );

                    $valueRes = [];
                    $valueResSingle = [];
                }

                $profileMenu = (object)[];

                $profileMenu->items = DB::table('menuProfiles')
                    ->select('title', 'url', 'icon')
                    ->where('isDeleted', '=', 0)
                    ->get();

                $settingMenu = (object)[];

                if ($users->roleName == 'Administrator') {

                    $settingMenu->items = DB::table('menuSettings')
                        ->select('title', 'url', 'icon')
                        ->where('isDeleted', '=', 0)
                        ->get();
                }

                $reportMenu = (object)[];

                $reportMenu->items = DB::table('accessReportMenus')
                    ->select('groupName', 'menuName', 'url', 'roleId', 'accessTypeId')
                    ->where('roleId', '=', $users->roleId)
                    ->where('isDeleted', '=', 0)
                    ->get();

                // broadcast(new \App\Events\UserLoggedIn($userId));
                return response()->json([
                    'id' => $userId,
                    'success' => true,
                    'token' => $token,
                    'usersId' => $userId,
                    "userName" => $users->name,
                    "emailAddress" => $emailaddress,
                    "jobName" => $users->jobName,
                    "role" => $users->roleName,
                    'imagePath' => $users->imagePath,
                    "isAbsent" => $isAbsent,
                    "masterMenu" => $masterMenu,
                    'profileMenu' => $profileMenu,
                    'settingMenu' => $settingMenu,
                    'reportMenu' => $reportMenu,
                    // "locations" => $locations,
                    // "menuLevel" => $data,
                    // "accessType" => $accessTypeMenu,
                ]);
            }
        } else {

            return response()->json([
                'result' => 'Failed',
                'message' => 'Email login not found, please try different email',
            ], 422);
        }
    }

    public function logout(Request $request)
    {

        $validator = Validator::make($request->only('token'), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User has been logged out',
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_user(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }

    public function online($id)
    {
        return $id;
    }
}
