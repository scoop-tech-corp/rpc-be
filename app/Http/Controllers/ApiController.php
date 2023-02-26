<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

class ApiController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/register",
     * operationId="Register",
     * tags={"Register"},
     * summary="User Register",
     * description="User Register here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="User Register",
     *        example = "User Register",
     *       value = {
     *           "name":"DW",
     *           "email":"testingvalue@gmail.com",
     *           "password":"111111"
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","email","password"},
     *               @OA\Property(property="name", type="text"),
     *               @OA\Property(property="email", type="text"),
     *               @OA\Property(property="password", type="password"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function register(Request $request)
    {
        //Validate data
        $data = $request->only('name', 'email', 'password', 'role');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50',
            'role' => 'required|string',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'isDeleted' => 0,
        ]);

        //User created, return success response
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * operationId="Login Username",
     * tags={"Login Username"},
     * summary="Login",
     * description="Login RPC here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Login User",
     *        example = "Login User",
     *       value = {
     *           "email":"yolo@gmail.com",
     *           "password":"111111"
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "password"},
     *               @OA\Property(property="email", type="text"),
     *               @OA\Property(property="password", type="password")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        //Send failed response if request is not valid
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

                //Request is validated
                //Create token

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
                        'users.roleId',
                        'usersRoles.roleName',
                        'jobTitle.jobName as jobName',
                        DB::raw("CONCAT(IFNULL(users.firstName,'') ,' ', IFNULL(users.lastName,'')) as name"),
                    )
                    ->where([
                        ['users.id', '=', $userId],
                        ['users.isDeleted', '=', '0'],
                        ['jobTitle.isActive', '=', '1']
                    ])
                    ->first();

                $data = DB::table('tableAccess as a')
                    ->join('menuList as b', 'b.id', '=', 'a.menuListId')
                    ->join('tableRoleAccess as c', 'c.id', '=', 'a.roleAccessId')
                    ->select(
                        'b.menuName',
                        'c.accessType',
                    )
                    ->where([['a.roleId', '=', $users->roleId],])
                    ->get();

                $accessLimit = DB::table('tableAccess as a')
                    ->join('menuList as b', 'b.id', '=', 'a.menulistId')
                    ->join('accessLimit as c', 'c.id', '=', 'a.accessLimitId')
                    ->join('tableRoleAccess as d', 'd.id', '=', 'a.roleAccessId')
                    ->select(
                        'b.menuName',
                        'd.accessType',
                        'c.timeLimit',
                    )
                    ->where([['a.roleId', '=', $users->roleId],])
                    ->get();


                return response()->json([
                    'success' => true,
                    'token' => $token,
                    'usersId' => $userId,
                    "userName" => $users->name,
                    "emailAddress" => $emailaddress,
                    "jobName" => $users->jobName,
                    "role" => $users->roleName,
                    "menuLevel" => $data,
                    "accessLimit" => $accessLimit,
                ]);
            }
        } else {

            return response()->json([
                'result' => 'Failed',
                'message' => 'Email login not found, please try different email',
            ]);
        }
    }

    public function logout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'required',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is validated, do logout
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
}
