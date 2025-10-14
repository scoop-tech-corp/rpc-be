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
                    // 'phoneNumberId' => 'required',
                    'phoneNumber' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
                    'emailId' => 'required',
                    'email' => 'string|nullable',
                    // 'messengerNumberId' => 'required',
                    'messengerNumber' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
                    // 'detailAddressId' => 'required',
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

            if ($request->phoneNumberId) {
                $checkTelephone =  UsersTelephones::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->phoneNumberId],
                ])->first();


                if ($checkTelephone == null) {
                    return responseInvalid(['User id telephone not found, please try different id']);
                }
            }

            if ($request->messengerNumberId) {
                $checkMessengers =  UsersMessengers::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->messengerNumberId],
                ])->first();

                if ($checkMessengers == null) {
                    return responseInvalid(['User id messenger not found, please try different id']);
                }
            }

            if ($request->detailAddressId) {
                $checkDetailAddress =  UsersDetailAddresses::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->detailAddressId],
                ])->first();

                if ($checkDetailAddress == null) {
                    return responseInvalid(['User id detail address not found, please try different id']);
                }
            }

            $checEmail =  UsersEmails::where([
                ['usersId', '=', $request->id],
                ['id', '=', $request->emailId],
            ])->first();

            if ($checEmail == null) {
                return responseInvalid(['User id email not found, please try different id']);
            }

            // $latestPhoneNumber = $this->getPhoneLatest();
            // $latestMessenger = $this->getMessengerLatest();
            // $latestEmail = $this->getEmailLatest();
            // $latestAddress = $this->getDetailAddressLatest();

            // $checkDataUser = User::from('users as a')
            //     ->leftJoinSub($latestPhoneNumber, 'e', function ($join) {
            //         $join->on('e.usersId', '=', 'a.id');
            //     })->leftJoinSub($latestMessenger, 'f', function ($join) {
            //         $join->on('f.usersId', '=', 'a.id');
            //     })->leftJoinSub($latestEmail, 'g', function ($join) {
            //         $join->on('g.usersId', '=', 'a.id');
            //     })->leftJoinSub($latestAddress, 'h', function ($join) {
            //         $join->on('h.usersId', '=', 'a.id');
            //     })->select(
            //         'a.id',
            //         'e.phoneNumberId as phoneNumberId',
            //         'f.messengerId as messengerNumberId',
            //         'g.emailId as emailId',
            //         'h.detailAddressId as detailAddressId',
            //     )
            //     ->where([
            //         ['a.id', '=', $request->id],
            //         ['a.isDeleted', '=', '0'],
            //     ])
            //     ->first();


            // if ($checkDataUser->phoneNumberId != $request->phoneNumberId) {
            //     return responseInvalid(['Phone number id is not primary for update, please use id ' . $checkDataUser->phoneNumberId  . ' as phoneNumberId']);
            // }

            // if ($checkDataUser->messengerNumberId != $request->messengerNumberId) {
            //     return responseInvalid(['Messenger id is not primary for update, please use id ' . $checkDataUser->messengerNumberId  . ' as messengerNumberId']);
            // }

            // if ($checkDataUser->emailId != $request->emailId) {
            //     return responseInvalid(['Email id is not primary for update, please use id ' . $checkDataUser->detailAddressId  . ' as emailId']);
            // }

            // if ($checkDataUser->detailAddressId != $request->detailAddressId) {
            //     return responseInvalid(['Detail address id is not primary for update, please use id ' . $checkDataUser->detailAddressId  . ' as detailAddressId']);
            // }

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

            if ($request->phoneNumberId) {
                UsersTelephones::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->phoneNumberId],
                ])->update([
                    'phoneNumber' => $request->phoneNumber,
                    'updated_at' => now(),
                ]);
            } else {
                UsersTelephones::create([
                    'usersId' => $request->id,
                    'phoneNumber' => $request->phoneNumber,
                    'type' => 'Whatshapp',
                    'usage' => 'Utama',
                    'isDeleted' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($request->messengerNumberId) {
                UsersMessengers::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->messengerNumberId],
                ])->update([
                    'messengerNumber' => $request->messengerNumber,
                    'updated_at' => now(),
                ]);
            } else {
                UsersMessengers::create([
                    'usersId' => $request->id,
                    'messengerNumber' => $request->messengerNumber,
                    'type' => 'Utama',
                    'usage' => 'Utama',
                    'isDeleted' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($request->detailAddressId) {
                UsersDetailAddresses::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->detailAddressId],
                ])->update([
                    'addressName' => $request->addressName,
                    'updated_at' => now(),
                ]);
            } else {
                UsersDetailAddresses::create([
                    'usersId' => $request->id,
                    'addressName' => $request->addressName,
                    'isPrimary' => 1,
                    'isDeleted' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($request->emailId) {
                UsersEmails::where([
                    ['usersId', '=', $request->id],
                    ['id', '=', $request->emailId],
                ])->update([
                    'email' => $request->email,
                    'updated_at' => now(),
                ]);
            } else {
                UsersEmails::create([
                    'usersId' => $request->id,
                    'email' => $request->email,
                    'usage' => 'Utama',
                    'isDeleted' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                    ]
                ],
                [
                    'newPassword.regex' => 'The new password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character!',
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

            User::where('id', '=', $request->id)
                ->update([
                    'password' => bcrypt($request->newPassword),
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
                'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:5000',
                'status' => 'nullable',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                return responseInvalid([$errors]);
            }

            $user = User::find($request->id);

            if (!$user) {
                return responseInvalid(['Data not found!']);
            }

            $path = '';
            $realName = '';

            if ($user->imageName && $user->imagePath) {
                if (File::exists(public_path() . $user->imagePath)) {

                    File::delete(public_path() . $user->imagePath);
                }
            }

            if ($request->hasfile('image')) {

                $files[] = $request->file('image');

                foreach ($files as $file) {

                    $realName = $file->hashName();

                    $file_size = $file->getSize();

                    $file_size = $file_size / 1024;

                    $originalName = $file->getClientOriginalName();

                    $file->move(public_path() . '/UsersProfiles/', $realName);

                    $path = "/UsersProfiles/" . $realName;
                }
            }

            if ($request->status == 'del') {

                File::delete(public_path() . $user->imagePath);

                User::where([['id', '=', $request->id]])->update([
                    'imageName' => '',
                    'imagePath' => '',
                    'updated_at' => now(),
                ]);
                $path = '';
            } else {

                User::where([['id', '=', $request->id]])->update([
                    'imageName' => $originalName,
                    'imagePath' => $path,
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['imagePath' => $path], 200);
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

            $latestPhoneNumber = $this->getPhoneLatest();
            $latestMessenger = $this->getMessengerLatest();
            $latestEmail = $this->getEmailLatest();
            $latestAddress = $this->getDetailAddressLatest();

            if ($request->type === 'view') {

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
                        'a.imagePath',
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
                        'a.annualLeaveAllowanceRemaining',
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

                $data->locations = $locationId;

                $userIdentification = DB::table('usersIdentifications as a')
                    ->join('typeId as t', 'a.typeId', 't.id')
                    ->leftjoin('users as ua', 'a.approvedBy', 'ua.id')
                    ->select(
                        'a.id',
                        'a.typeId',
                        't.typeName',
                        'a.identification',
                        'a.imagePath',
                        DB::raw("
                        CASE
                        WHEN a.status = 0 or a.status = 1 THEN 'Waiting for Approval'
                        WHEN a.status = 2 THEN 'Approved'
                        WHEN a.status = 3 THEN 'Reject'
                        END as statusText"),
                        'a.status',
                        'ua.firstName as approvedBy',
                        DB::raw("DATE_FORMAT(a.approvedAt, '%d/%m/%Y %H:%i:%s') as approvedAt"),
                        DB::raw("DATE_FORMAT(a.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', 0]
                    ])
                    ->get();

                $data->userIdentifications = $userIdentification;
            } else if ($request->type === 'edit') {

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
                        'a.imagePath',
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

                $userIdentification = DB::table('usersIdentifications as a')
                    ->join('typeId as t', 'a.typeId', 't.id')
                    ->leftjoin('users as ua', 'a.approvedBy', 'ua.id')
                    ->select(
                        'a.id',
                        'a.typeId',
                        't.typeName',
                        'a.identification',
                        'a.imagePath',
                        DB::raw("
                        CASE
                        WHEN a.status = 0 or a.status = 1 THEN 'Waiting for Approval'
                        WHEN a.status = 2 THEN 'Approved'
                        WHEN a.status = 3 THEN 'Reject'
                        END as statusText"),
                        'a.status',
                        'ua.firstName as approvedBy',
                        DB::raw("DATE_FORMAT(a.approvedAt, '%d/%m/%Y %H:%i:%s') as approvedAt"),
                        DB::raw("DATE_FORMAT(a.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', 0]
                    ])
                    ->get();

                $data->userIdentifications = $userIdentification;
            }

            return response()->json($data, 200);

            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return responseInvalid([$e]);
        }
    }

    public function staffLate(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->leftJoin('presentStatuses as ps1', 'sa.statusHome', 'ps1.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->join('jobTitle as jt', 'u.jobTitleId', 'jt.id')
            ->select(
                'sa.id',
                'sa.shift',
                DB::raw("
                CONCAT(
                    CASE DAYOFWEEK(sa.presentTime)
                        WHEN 1 THEN 'Minggu'
                        WHEN 2 THEN 'Senin'
                        WHEN 3 THEN 'Selasa'
                        WHEN 4 THEN 'Rabu'
                        WHEN 5 THEN 'Kamis'
                        WHEN 6 THEN 'Jumat'
                        WHEN 7 THEN 'Sabtu'
                    END,
                    ', ',
                    DATE_FORMAT(sa.presentTime, '%e %b %Y')
                ) AS day
                "),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS presentTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H:%i') END AS homeTime"),
            )
            ->where('sa.isDeleted', '=', 0)
            ->where('sa.status', '=', 'Terlambat');

        $data = $data->where('sa.userId', '=', $request->user()->id);

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween('sa.presentTime', [$request->dateFrom, $request->dateTo]);
        }

        $data = $data->groupBy(
            'sa.id',
            'u.firstName',
            'j.jobName',
            'sa.shift',
            'sa.status',
            'sa.presentTime',
            'sa.homeTime',
            'sa.duration',
            'ps.statusName',
            'ps1.statusName',
            'sa.cityPresent',
            'sa.cityHome'
        );

        $data = $data->orderBy('sa.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $dataTemp = $data->get();

        $count_data = $dataTemp->count();

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }
}
