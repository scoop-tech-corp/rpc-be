<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\StaffAbsents;
use App\Models\Staff\StaffLogin;
use App\Models\Staff\UsersLocation;
use App\Models\Timekeeper;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->only('name', 'email', 'password', 'role');

        $validator = Validator::make($data, [
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50',
            'role'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'password'  => bcrypt($request->password),
            'isDeleted' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data'    => $user,
        ], Response::HTTP_OK);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        // ── Cek email terdaftar ───────────────────────────────────────────────
        $checkIfValueExits = DB::table('usersEmails')
            ->select('usersEmails.usersId', 'usersEmails.email')
            ->where([
                ['usersEmails.email',     '=', $request->email],
                ['usersEmails.usage',     '=', 'Utama'],
                ['usersEmails.isDeleted', '=', 0],
            ])
            ->first();

        if ($checkIfValueExits == null) {
            return response()->json([
                'result'  => 'Failed',
                'message' => 'Email login not found, please try different email',
            ], 422);
        }

        // ── Cek user aktif ───────────────────────────────────────────────────
        $userCheck = DB::table('users')
            ->select('firstName', 'password', 'status')
            ->where([['email', '=', $request->email], ['isDeleted', '=', 0]])
            ->first();

        if ($userCheck == null) {
            return response()->json([
                'success' => false,
                'message' => 'Email address is not found or has already deleted in our system, Please try different email address to Login!',
            ], 400);
        }

        // ── Cek status aktif ─────────────────────────────────────────────────
        if ((int) $userCheck->status !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact your administrator.',
            ], 400);
        }

        // ── Generate JWT token ───────────────────────────────────────────────
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password unmatch, please check again',
                ], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
            ], 500);
        }

        $userId       = $checkIfValueExits->usersId;
        $emailaddress = $checkIfValueExits->email;

        // ── Data user lengkap ────────────────────────────────────────────────
        $users = DB::table('users')
            ->leftJoin('jobTitle',    'jobTitle.id',    '=', 'users.jobTitleId')
            ->leftJoin('usersRoles',  'usersRoles.id',  '=', 'users.roleId')
            ->select(
                'users.id',
                'users.imagePath',
                'users.roleId',
                'users.jobTitleId',
                DB::raw("IF(usersRoles.roleName IS NULL, '', usersRoles.roleName) as roleName"),
                DB::raw("IF(jobTitle.jobName IS NULL,'', jobTitle.jobName) as jobName"),
                DB::raw("CONCAT(IFNULL(users.firstName,'') ,' ', IFNULL(users.lastName,'')) as name"),
            )
            ->where([['users.id', '=', $userId], ['users.isDeleted', '=', '0']])
            ->first();

        // ── Lokasi user ──────────────────────────────────────────────────────
        $locations = DB::table('usersLocation as ul')
            ->join('location as l', 'l.id', '=', 'ul.locationId')
            ->select('ul.locationId as id', 'l.locationName')
            ->where('ul.usersId', $userId)
            ->where('ul.isDeleted', 0)
            ->where('l.isDeleted', 0)
            ->get();

        // ── Status absen ─────────────────────────────────────────────────────
        $curTime     = Carbon::now();
        $compareTime = Carbon::createFromFormat('H:i:s', '07:00:00');
        $diffTime    = $compareTime->diffInSeconds($curTime, false);

        if ($diffTime < 0) {
            $isAbsent = 1;
        } else {
            $absent   = StaffAbsents::where('userId', '=', $userId)
                ->whereDate('created_at', Carbon::today())
                ->first();
            $isAbsent = $absent ? 1 : 0;
        }

        // ── Build master menu (sidebar navigation) ───────────────────────────
        // Menggunakan sistem baru: menuGroups → childrenMenuGroups → grandChildrenMenuGroups
        // difilter oleh accessControl.roleId = user.roleId
        $masterMenu     = (object)[];
        $resChild       = [];
        $valueRes       = [];
        $finalRes       = [];
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
                    ->select(
                        'gcm.orderMenu',
                        'gcm.identify as id',
                        'gcm.title',
                        'gcm.type',
                        'gcm.url',
                        'gcm.icon',
                        'ac.accessTypeId as accessType',
                    )
                    ->where('gcm.childrenId', '=', $tempChildren[0]->idNum)
                    ->where('ac.roleId',      '=', $users->roleId)
                    ->where('gcm.isDeleted',  '=', 0)
                    ->orderBy('gcm.orderMenu', 'asc')
                    ->get();

                if (count($grandChilds) == 1) {

                    $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                        ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                        ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                        ->select(
                            'gcm.identify as id',
                            'gcm.title',
                            'gcm.type',
                            'gcm.url',
                            'gcm.icon',
                            'ac.accessTypeId as accessType',
                        )
                        ->where('gcm.childrenId', '=', $tempChildren[0]->idNum)
                        ->where('ac.roleId',      '=', $users->roleId)
                        ->where('gcm.isDeleted',  '=', 0)
                        ->first();

                    array_push($valueResSingle, $grandChilds);

                } else {

                    $resChild[] = [
                        'id'       => $tempChildren[0]->id,
                        'title'    => $tempChildren[0]->title,
                        'type'     => $tempChildren[0]->type,
                        'icon'     => $tempChildren[0]->icon,
                        'children' => $grandChilds,
                    ];
                    $valueRes = $resChild;
                    $resChild = [];
                }

            } else {

                $childrens = DB::table('childrenMenuGroups')
                    ->select('id as idNum', 'identify as id', 'title', 'type', 'icon')
                    ->where('groupId',   '=', $value->idNum)
                    ->where('isDeleted', '=', 0)
                    ->orderBy('orderMenu', 'asc')
                    ->get();

                foreach ($childrens as $valueChild) {

                    $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                        ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                        ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                        ->select(
                            'gcm.id as idNum',
                            'gcm.identify as id',
                            'gcm.title',
                            'gcm.type',
                            'gcm.url',
                            'cm.icon',
                            'ac.accessTypeId as accessType',
                        )
                        ->where('gcm.childrenId', '=', $valueChild->idNum)
                        ->where('ac.roleId',      '=', $users->roleId)
                        ->where('gcm.isDeleted',  '=', 0)
                        ->orderBy('gcm.orderMenu', 'asc')
                        ->get();

                    if (count($grandChilds) == 1) {

                        $grandChilds = DB::table('grandChildrenMenuGroups as gcm')
                            ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                            ->join('childrenMenuGroups as cm', 'gcm.childrenId', 'cm.id')
                            ->select(
                                'gcm.id as idNum',
                                'gcm.identify as id',
                                'gcm.title',
                                'gcm.type',
                                'gcm.url',
                                'gcm.icon',
                                'ac.accessTypeId as accessType',
                            )
                            ->where('gcm.childrenId', '=', $valueChild->idNum)
                            ->where('ac.roleId',      '=', $users->roleId)
                            ->where('gcm.isDeleted',  '=', 0)
                            ->first();

                        array_push($valueResSingle, $grandChilds);

                    } else {

                        $grandChildsNew = DB::table('grandChildrenMenuGroups as gcm')
                            ->join('accessControl as ac', 'ac.menuListId', 'gcm.id')
                            ->select(
                                'gcm.identify as id',
                                'gcm.title',
                                'gcm.type',
                                'gcm.url',
                                'ac.accessTypeId as accessType',
                            )
                            ->where('ac.roleId',      '=', $users->roleId)
                            ->where('gcm.childrenId', '=', $valueChild->idNum)
                            ->where('gcm.isDeleted',  '=', 0)
                            ->orderBy('gcm.orderMenu', 'asc')
                            ->get();

                        $resChild[] = [
                            'id'       => $grandChilds[0]->id,
                            'title'    => $grandChilds[0]->title,
                            'type'     => $grandChilds[0]->type,
                            'url'      => $grandChilds[0]->url,
                            'children' => $grandChildsNew,
                        ];
                        $valueRes = $resChild;
                        $resChild = [];
                    }
                }
            }

            $finalRes = count($valueRes) === 0 ? $valueResSingle : $valueRes;

            $masterMenu->items[] = [
                'id'       => $value->id,
                'type'     => $value->type,
                'children' => $finalRes,
            ];

            $valueRes       = [];
            $valueResSingle = [];
        }

        // ── Profile, Setting, Report menus ───────────────────────────────────
        $profileMenu        = (object)[];
        $profileMenu->items = DB::table('menuProfiles')
            ->select('title', 'url', 'icon')
            ->where('isDeleted', '=', 0)
            ->get();

        $settingMenu = (object)[];
        if ($users->roleName === 'Administrator') {
            $settingMenu->items = DB::table('menuSettings')
                ->select('title', 'url', 'icon')
                ->where('isDeleted', '=', 0)
                ->get();
        }

        $reportMenu        = (object)[];
        $reportMenu->items = DB::table('accessReportMenus')
            ->select('groupName', 'menuName', 'url', 'roleId', 'accessTypeId')
            ->where('roleId',    '=', $users->roleId)
            ->where('isDeleted', '=', 0)
            ->get();

        // ── Level 3: Schedule Access Control ─────────────────────────────────
        // Auto-expire: tandai Completed semua jadwal yang sudah lewat endTime
        DB::table('accessControlSchedulesDetail')
            ->where('status',    2)
            ->where('isDeleted', 0)
            ->where('endTime',   '<', Carbon::now())
            ->update(['status' => 3, 'updated_at' => Carbon::now()]);

        // Ambil jadwal akses yang aktif untuk user ini saat login
        $scheduleAccess = DB::table('accessControlSchedulesDetail as acsd')
            ->join('accessControlSchedulesMaster as acsm', 'acsm.id',  '=', 'acsd.scheduleMasterId')
            ->join('menuMaster as mm',                     'mm.id',    '=', 'acsd.masterMenuId')
            ->join('menuList as ml',                       'ml.id',    '=', 'acsd.listMenuId')
            ->join('accessType as at',                     'at.id',    '=', 'acsd.accessTypeId')
            ->join('location as l',                        'l.id',     '=', 'acsm.locationId')
            ->select(
                'mm.masterName  as menuMaster',
                'ml.menuName    as menuItem',
                'at.id          as accessTypeId',
                'at.accessType  as accessTypeName',
                'l.locationName',
                'acsd.startTime',
                'acsd.endTime',
            )
            ->where('acsm.usersId',   '=', $userId)
            ->where('acsm.isDeleted', '=', 0)
            ->where('acsd.isDeleted', '=', 0)
            ->where('acsd.status',    '=', 2)
            ->where('acsd.startTime', '<=', Carbon::now())
            ->where('acsd.endTime',   '>=', Carbon::now())
            ->get();

        // ── Log login staff ───────────────────────────────────────────────────
        StaffLogin::create([
            'staffId'   => $userId,
            'date'      => Carbon::now(),
            'ipAddress' => $request->ip(),
            'device'    => $request->header('User-Agent'),
        ]);

        $shift   = Timekeeper::where('jobtitleId', '=', $users->jobTitleId)->get();
        $isShift = count($shift) > 1 ? 1 : 0;

        return response()->json([
            'id'             => $userId,
            'success'        => true,
            'token'          => $token,
            'usersId'        => $userId,
            'userName'       => $users->name,
            'emailAddress'   => $emailaddress,
            'jobName'        => $users->jobName,
            'roleId'         => $users->roleId,
            'role'           => $users->roleName,
            'locations'      => $locations,
            'imagePath'      => $users->imagePath,
            'isAbsent'       => $isAbsent,
            'isShift'        => $isShift,
            'masterMenu'     => $masterMenu,
            'profileMenu'    => $profileMenu,
            'settingMenu'    => $settingMenu,
            'reportMenu'     => $reportMenu,
            'scheduleAccess' => $scheduleAccess,
        ]);
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

    public function getReportMenu(Request $request)
    {
        try {
            $user   = JWTAuth::parseToken()->authenticate();
            $roleId = $user->roleId ?? $user->role_id ?? null;

            $items = DB::table('accessReportMenus')
                ->select('groupName', 'menuName', 'url', 'roleId', 'accessTypeId')
                ->where('roleId',    $roleId)
                ->where('isDeleted', 0)
                ->get();

            return response()->json(['items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['items' => []], 200);
        }
    }

    public function online($id)
    {
        return $id;
    }
}
