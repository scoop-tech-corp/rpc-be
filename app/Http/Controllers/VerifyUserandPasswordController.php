<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Validator;

class VerifyUserandPasswordController extends Controller
{
    public function index($id)
    {

        DB::beginTransaction();

        try {

            $checkIfUsersExists = DB::table('users')
                ->where([
                    ['isDeleted', '=', '0'],
                    ['id', '=', $id]
                ])
                ->first();

            if ($checkIfUsersExists != null) { //users exists

                $checkIfValueExits = DB::table('usersEmails')
                    ->where([
                        ['usersEmails.usage', '=', 'Utama'],
                        ['usersEmails.email_verified_at', '=', null],
                        ['usersEmails.usersId', '=', $id]
                    ])
                    ->first();

                if ($checkIfValueExits != null) {

                    return view('posts.setpassword', [
                        'id' => $id,
                    ]);
                } else {

                    return view('posts.accountverified');
                }
            } else {

                return view('posts.accountnotexists');
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // $request->validate([
            //     'new_password' => 'required|string|min:3|same:confirm_password',
            //     'confirm_password' => 'required',
            // ]);
            $validate = Validator::make(
                $request->all(),
                [
                    'new_password' => [
                        'required',
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                    ],
                    'confirm_password' => 'required|same:new_password',
                ],
                [
                    'new_password.regex' => 'The password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character!',
                    'confirm_password.same' => 'The confirm password must match the password!',
                ]
            );

            if ($validate->fails()) {

                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }

            DB::table('usersEmails')
                ->where('usersId', '=', $request->hiddenId)
                ->update(['email_verified_at' => now(),]);

            DB::table('users')
                ->where('id', '=', $request->hiddenId)
                ->update(['password' => bcrypt($request->confirm_password),]);

            DB::commit();

            return view('posts.accountverified');
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }
}
