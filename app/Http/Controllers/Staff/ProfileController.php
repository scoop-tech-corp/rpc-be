<?php

namespace App\Http\Controllers\Staff;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Staff\UsersTelephones;
use App\Models\Staff\UsersMessengers;
use App\Models\Staff\UsersEmails;
use App\Models\Staff\UsersDetailAddresses;
use File;


class ProfileController extends Controller
{



    public function getPhoneLatest()
    {

        $whatsappSubquery = DB::table('usersTelephones')
            ->select('id as phoneNumberId', 'usersId', 'phoneNumber', 'type')
            ->where('type', 'Whatshapp')
            ->where('isDeleted', 0)
            ->orderBy('id', 'asc');

        $latestSubquery = DB::table('usersTelephones')
            ->select('id as phoneNumberId', 'usersId', 'phoneNumber', 'type')
            ->where('isDeleted', 0)
            ->whereNotIn('type', ['Whatshapp'])
            ->orderBy('id', 'asc');

        $subquery = DB::query()
            ->select('*')
            ->fromSub($whatsappSubquery, 'whatsapp_data')
            ->unionAll($latestSubquery);

        return $subquery;
    }



    public function getMessengerLatest()
    {

        $messengerSubquery = DB::table('usersMessengers')
            ->select('id as messengerId', 'usersId', 'messengerNumber', 'type')
            ->where('usage', 'Utama')
            ->where('isDeleted', 0)
            ->orderBy('id', 'asc');

        $latestSubquery = DB::table('usersMessengers')
            ->select('id as messengerId', 'usersId', 'messengerNumber', 'type')
            ->where('isDeleted', 0)
            ->whereNotIn('usage', ['Utama'])
            ->orderBy('id', 'asc');


        $subquery = DB::query()
            ->select('*')
            ->fromSub($messengerSubquery, 'messenger_data')
            ->unionAll($latestSubquery);

        return $subquery;
    }


    public function getDetailAddressLatest()
    {

        $messengerSubquery = DB::table('usersDetailAddresses')
            ->select('id as detailAddressId', 'usersId', 'addressName')
            ->where('isPrimary', '1')
            ->where('isDeleted', 0)
            ->orderBy('id', 'asc');



        return $messengerSubquery;
    }


    public function getEmailLatest()
    {

        $messengerSubquery = DB::table('usersEmails')
            ->select('id as emailId', 'usersId', 'email', 'usage')
            ->where('usage', 'Utama')
            ->where('isDeleted', 0)
            ->orderBy('id', 'asc');

        $latestSubquery = DB::table('usersEmails')
            ->select('id as emailId', 'usersId', 'email', 'usage')
            ->where('isDeleted', 0)
            ->whereNotIn('usage', ['Utama'])
            ->orderBy('id', 'asc');


        $subquery = DB::query()
            ->select('*')
            ->fromSub($messengerSubquery, 'email_data')
            ->unionAll($latestSubquery);

        return $subquery;
    }



    public function updateProfile(Request $request)
    {
        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required',
                    'firstName' => 'max:20|min:3|nullable',
                    'middleName' => 'max:20|min:3|nullable',
                    'lastName' => 'max:20|min:3|nullable',
                    'nickName' => 'max:20|min:3|nullable',
                    'gender' => 'string|nullable',
                    'phoneNumberId' => 'required',
                    'phoneNumber' => 'integer|nullable',
                    'emailId' => 'required',
                    'email' => 'string|nullable',
                    'messengerNumberId' => 'required',
                    'messengerNumber' => 'integer|nullable',
                    'detailAddressId' => 'required',
                    'addressName' => 'string|nullable',
                    'userName' => 'string|nullable',
                ]
            );


            if ($validate->fails()) {

                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }


            $users =  User::where('id', '=', $request->id)->where('isDeleted', '=', '0')->first();

            if ($users == null) {
                return responseInvalid(['User id not found, please try different id']);
            }

            $checkTelephone =  UsersTelephones::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->phoneNumberId],
            ])->first();


            if ($checkTelephone == null) {
                return responseInvalid(['User id telephone not found, please try different id']);
            }


            $checkMessengers =  UsersMessengers::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->messengerNumberId],
            ])->first();


            if ($checkMessengers == null) {
                return responseInvalid(['User id messenger not found, please try different id']);
            }

            $checDetailAddress =  UsersDetailAddresses::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->detailAddressId],
            ])->first();


            if ($checDetailAddress == null) {
                return responseInvalid(['User id detail address not found, please try different id']);
            }

            $checEmail =  UsersEmails::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->emailId],
            ])->first();

            if ($checEmail == null) {
                return responseInvalid(['User id email not found, please try different id']);
            }



            $latestPhoneNumber = $this->getPhoneLatest();
            $latestMessenger = $this->getMessengerLatest();
            $latestEmail = $this->getEmailLatest();
            $latestAddress = $this->getDetailAddressLatest();


            $checkDataUser = User::from('users as a')
                ->leftJoinSub($latestPhoneNumber, 'e', function ($join) {
                    $join->on('e.usersId', '=', 'a.id');
                })->leftJoinSub($latestMessenger, 'f', function ($join) {
                    $join->on('f.usersId', '=', 'a.id');
                })->leftJoinSub($latestEmail, 'g', function ($join) {
                    $join->on('g.usersId', '=', 'a.id');
                })->leftJoinSub($latestAddress, 'h', function ($join) {
                    $join->on('h.usersId', '=', 'a.id');
                })->select(
                    'a.id',
                    'e.phoneNumberId as phoneNumberId',
                    'f.messengerId as messengerNumberId',
                    'g.emailId as emailId',
                    'h.detailAddressId as detailAddressId',
                )
                ->where([
                    ['a.id', '=', $request->id],
                    ['a.isDeleted', '=', '0'],
                ])
                ->first();


            if ($checkDataUser->phoneNumberId != $request->phoneNumberId) {
                return responseInvalid(['Phone number id is not primary for update, please use id ' . $checkDataUser->phoneNumberId  . ' as phoneNumberId']);
            }


            if ($checkDataUser->messengerNumberId != $request->messengerNumberId) {
                return responseInvalid(['Messenger id is not primary for update, please use id ' . $checkDataUser->messengerNumberId  . ' as messengerNumberId']);
            }


            if ($checkDataUser->emailId != $request->emailId) {
                return responseInvalid(['Email id is not primary for update, please use id ' . $checkDataUser->detailAddressId  . ' as emailId']);
            }



            if ($checkDataUser->detailAddressId != $request->detailAddressId) {
                return responseInvalid(['Detail address id is not primary for update, please use id ' . $checkDataUser->detailAddressId  . ' as detailAddressId']);
            }

            User::where('id', '=', $request->id)
                ->update([
                    'firstName' => $request->firstName,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'nickName' => $request->nickName,
                    'gender' => $request->gender,
                    'userName' => $request->userName,
                    'email' => $request->email,
                    'updated_at' => now(),
                ]);


            UsersTelephones::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->phoneNumberId],
            ])->update([
                'phoneNumber' => $request->phoneNumber,
                'updated_at' => now(),
            ]);


            UsersMessengers::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->messengerNumberId],
            ])->update([
                'messengerNumber' => $request->messengerNumber,
                'updated_at' => now(),
            ]);


            UsersDetailAddresses::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->detailAddressId],
            ])->update([
                'addressName' => $request->addressName,
                'updated_at' => now(),
            ]);

            UsersEmails::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->emailId],
            ])->update([
                'email' => $request->email,
                'updated_at' => now(),
            ]);


            DB::commit();

            return responseUpdate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }



    public function updatePassword(Request $request)
    {
        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required',
                    'oldPassword' => 'required',
                    'newPassword' => [
                        'required',
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                    ],
                    'confirmPassword' => 'required',
                ],
                [
                    'newPassword.regex' => 'The new password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character (@$!%*?&).',
                ]
            );


            if ($validate->fails()) {

                $errors = $validate->errors()->all();

                return responseInvalid($errors);
            }

            $users =  User::where('id', '=', $request->id)->where('isDeleted', '=', '0')->first();

            if ($users == null) {
                return responseInvalid(['User id not found, please try different id']);
            }

            if (!(Hash::check($request->oldPassword, $users->password))) {
                return responseInvalid(['Old Password Not Match! Please Check Your Password Again!']);
            }

            if (($request->confirmPassword != $request->newPassword)) {
                return responseInvalid(['Confirm Password Not Match! Please Check Your Password Again!']);
            }



            User::where('id', '=', $request->id)
                ->update([
                    'password' => bcrypt($request->confirmPassword),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return responseUpdate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function uploadImageProfile(Request $request)
    {

        try {


            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                return responseInvalid([$errors]);
            }

            $users = User::find($request->id);

            if (!$users) {
                return responseInvalid(['Data not found!']);
            }

            $json_array = json_decode($request->imagesName, true);
            $files[] = $request->file('images');
            $index = 0;

            foreach ($json_array as $val) {

                if (($val['id'] == "" || $val['id'] == 0)  && ($val['status'] == "")) {

                    $name = $files[0][$index]->hashName();

                    $files[0][$index]->move(public_path() . '/UsersProfiles/', $name);

                    $fileName = "/UsersProfiles/" . $name;

                    User::where('id', '=', $request->id)->where('isDeleted', '=', '0')->update([
                        'imageName' => $name,
                        'imagePath' => $fileName,
                        'updated_at' => now(),
                    ]);

                    $index = $index + 1;
                } elseif (($val['id'] != "" && $val['id'] != 0)  && ($val['status'] == "del")) {

                    $find_image = User::select(
                        'imageName',
                        'imagePath'
                    )->where('id', '=', $val['id'])
                        ->first();

                    if ($find_image) {

                        if (file_exists(public_path() . $find_image->imagePath)) {

                            File::delete(public_path() . $find_image->imagePath);

                            User::where([['id', '=', $val['id']]])->update([
                                'imageName' => null,
                                'imagePath' => null,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                } elseif (($val['id'] != "" || $val['id'] != 0)  && ($val['status'] == "")) {

                    User::where([['id', '=', $val['id']]])->update([
                        'imageName' => null,
                        'imagePath' => null,
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return responseCreate();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }


    public function detailProfile(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'id' => 'required|integer',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            $users =  User::where('id', '=', $request->id)->where('isDeleted', '=', '0')->first();

            if ($users == null) {
                return responseInvalid(['User id not found, please try different id']);
            }

            $type = '';
            if ($request->has('type')) {

                if (strtolower($request->type) != "edit") {
                    return responseInvalid(['Type must set to edit']);
                }

                $type = $request->type;
            }


            $latestPhoneNumber = $this->getPhoneLatest();
            $latestMessenger = $this->getMessengerLatest();
            $latestEmail = $this->getEmailLatest();
            $latestAddress = $this->getDetailAddressLatest();

            if ($type == "") {

                $data = User::from('users as a')
                    ->leftJoin('jobTitle as c', function ($join) {
                        $join->on('c.id', '=', 'a.jobTitleId')
                            ->where('c.isActive', '=', 1);
                    })->leftJoin('payPeriod as e', function ($join) {
                        $join->on('e.id', '=', 'a.payPeriodId')
                            ->where('e.isActive', '=', 1);
                    })->leftJoinSub($latestPhoneNumber, 'e', function ($join) {
                        $join->on('e.usersId', '=', 'a.id');
                    })->leftJoinSub($latestMessenger, 'f', function ($join) {
                        $join->on('f.usersId', '=', 'a.id');
                    })->leftJoinSub($latestEmail, 'g', function ($join) {
                        $join->on('g.usersId', '=', 'a.id');
                    })->leftJoinSub($latestAddress, 'h', function ($join) {
                        $join->on('h.usersId', '=', 'a.id');
                    })->select(
                        'a.id',
                        'a.firstName',
                        'a.middleName',
                        'a.lastName',
                        'a.nickName',
                        'a.gender',
                        DB::raw("IF(a.jobTitleId IS NULL, '',a.jobTitleId ) as jobTitleId"),
                        DB::raw("IF(c.jobName IS NULL, '',c.jobName ) as jobName"),
                        DB::raw("IFNULL(DATE_FORMAT(a.startDate, '%m/%d/%Y'),'') as startDate"),
                        DB::raw("IFNULL(DATE_FORMAT(a.endDate, '%m/%d/%Y'),'') as endDate"),
                        'a.registrationNo',
                        'a.designation',
                        'a.annualSickAllowance',
                        'a.annualSickAllowanceRemaining',
                        'a.annualLeaveAllowance',
                        'a.annualSickAllowanceRemaining',
                        DB::raw("IF(a.payPeriodId IS NULL, '', a.payPeriodId) as payPeriodId"),
                        DB::raw("IF(e.periodName IS NULL, '', e.periodName) as periodName"),
                        'a.userName',
                        'e.phoneNumber',
                        'f.messengerNumber',
                        'g.email',
                        'h.addressName',
                    )
                    ->where([
                        ['a.id', '=', $request->id],
                        ['a.isDeleted', '=', '0'],
                    ])
                    ->first();

                $locationId = DB::table('usersLocation as a')
                    ->leftjoin('location as b', 'b.id', '=', 'a.locationId')
                    ->select(
                        'a.locationId as locationId',
                        'b.locationName as locationName',
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', '0']
                    ])
                    ->get();

                $data->locationId = $locationId;
            } else {

                $latestPhoneNumber = $this->getPhoneLatest();
                $latestMessenger = $this->getMessengerLatest();
                $latestEmail = $this->getEmailLatest();
                $latestAddress = $this->getDetailAddressLatest();


                $data = User::from('users as a')
                    ->leftJoinSub($latestPhoneNumber, 'e', function ($join) {
                        $join->on('e.usersId', '=', 'a.id');
                    })->leftJoinSub($latestMessenger, 'f', function ($join) {
                        $join->on('f.usersId', '=', 'a.id');
                    })->leftJoinSub($latestEmail, 'g', function ($join) {
                        $join->on('g.usersId', '=', 'a.id');
                    })->leftJoinSub($latestAddress, 'h', function ($join) {
                        $join->on('h.usersId', '=', 'a.id');
                    })->select(
                        'a.id',
                        'a.firstName',
                        'a.middleName',
                        'a.lastName',
                        'a.nickName',
                        'a.gender',
                        'a.userName',
                        'e.phoneNumberId as phoneNumberId',
                        'e.phoneNumber',
                        'f.messengerId as messengerNumberId',
                        'f.messengerNumber',
                        'g.emailId as emailId',
                        'g.email',
                        'h.detailAddressId as detailAddressId',
                        'h.addressName',
                    )
                    ->where([
                        ['a.id', '=', $request->id],
                        ['a.isDeleted', '=', '0'],
                    ])
                    ->first();
            }

            return response()->json($data, 200);

            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }
}
