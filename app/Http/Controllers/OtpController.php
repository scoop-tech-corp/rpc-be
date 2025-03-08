<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\SendOtpMail;
use App\Models\User;
use DB;
use Validator;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $checkEmail = DB::table('usersEmails')
            ->where('email', '=', $request->email)
            ->where('usage', '=', 'Utama')
            ->first();

        if (!$checkEmail) {
            return responseInvalid(['Email is not exists!']);
        }

        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        Otp::updateOrCreate(
            ['email' => $request->email],
            ['otp' => $otp, 'is_used' => false, 'expires_at' => $expiresAt, 'userId' => 1]
        );

        Mail::to($request->email)->send(new SendOtpMail($otp));

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        $otpRecord = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $otpRecord->update(['is_used' => true]);

        return response()->json(['message' => 'OTP verified successfully']);
    }

    public function changePassword(Request $request)
    {
        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => [
                        'required',
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                    ]
                ],
                [
                    'password.regex' => 'The new password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character!',
                ]
            );

            if ($validate->fails()) {

                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }

            $otpRecord = Otp::where('email', $request->email)
                ->where('is_used', true)
                ->first();

            if (!$otpRecord) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $checkEmail = DB::table('usersEmails')
                ->where('email', '=', $request->email)
                ->where('usage', '=', 'Utama')
                ->first();

            if (!$checkEmail) {
                return responseInvalid(['Email is not exists!']);
            }

            $users =  User::where('id', '=', $checkEmail->usersId)->where('isDeleted', '=', '0')->first();

            if ($users == null) {
                return responseInvalid(['User id not found, please try different id']);
            }

            User::where('id', '=', $checkEmail->usersId)
                ->update([
                    'password' => bcrypt($request->password),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return responseUpdate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }
}
