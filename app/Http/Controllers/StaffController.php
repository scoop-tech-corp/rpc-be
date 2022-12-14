<?php

namespace App\Http\Controllers;

use App\Exports\exportStaff;
use App\Mail\SendEmail;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class StaffController extends Controller
{

    public function insertStaff(Request $request)
    {

        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'firstName' => 'required|max:20|min:3',
                    'middleName' => 'max:20|min:3|nullable',
                    'lastName' => 'max:20|min:3|nullable',
                    'nickName' => 'max:20|min:3|nullable',
                    'gender' => 'string|nullable',
                    'status' => 'required|integer',
                    'jobTitleId' => 'required|integer',
                    'startDate' => 'required|date',
                    'endDate' => 'required|date|after:startDate',
                    'registrationNo' => 'string|max:20|min:5|nullable',
                    'designation' => 'string|max:20|min:5|nullable',
                    'locationId' => 'required|integer',
                    'annualSickAllowance' => 'integer|nullable',
                    'annualLeaveAllowance' => 'integer|nullable',
                    'payPeriodId' => 'required|integer',
                    'payAmount' => 'numeric|nullable',
                    'typeId' => 'required|integer',
                    'identificationNumber' => 'string|nullable|max:30',
                    'additionalInfo' => 'string|nullable|max:100',
                    'generalCustomerCanSchedule' => 'integer|nullable',
                    'generalCustomerReceiveDailyEmail' => 'integer|nullable',
                    'generalAllowMemberToLogUsingEmail' => 'integer|nullable',
                    'reminderEmail' => 'integer|nullable',
                    'reminderWhatsapp' => 'integer|nullable',
                    'roleId' => 'integer|nullable',
                ]
            );


            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            if ($request->detailAddress) {

                $arrayDetailAddress = json_decode($request->detailAddress, true);

                $messageAddress = [
                    '*.addressName.required' => 'Address name on tab Address is required',
                    '*.provinceCode.required' => 'Province code on tab Address is required',
                    '*.cityCode.required' => 'City code on tab Address is required',
                    '*.country.required' => 'Country on tab Address is required',
                ];

                $validateDetail = Validator::make(
                    $arrayDetailAddress,
                    [
                        '*.addressName' => 'required',
                        '*.provinceCode' => 'required',
                        '*.cityCode' => 'required',
                        '*.country' => 'required',
                    ],
                    $messageAddress
                );

                if ($validateDetail->fails()) {
                    $errorsdetail = $validateDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetail,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }

            if ($request->telephone) {

                $arraytelephone = json_decode($request->telephone, true);

                $messagePhone = [
                    '*.phoneNumber.required' => 'Phone Number on tab telephone is required',
                    '*.type.required' => 'Type on tab telephone is required',
                    '*.usage.required' => 'Usage on tab telephone is required',
                ];

                $telephoneDetail = Validator::make(
                    $arraytelephone,
                    [
                        '*.phoneNumber' => 'required',
                        '*.type' => 'required',
                        '*.usage' => 'required',
                    ],
                    $messagePhone
                );

                if ($telephoneDetail->fails()) {
                    $errorsdetail = $telephoneDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetail,
                    ], 422);
                }

                //25122022 insert
                $checkTelephone = [];

                foreach ($arraytelephone as $val) {

                    $checkIfTelephoneAlreadyExists = DB::table('usersTelephones')
                        ->where([
                            ['phoneNumber', '=', $val['phoneNumber'],],
                            ['isDeleted', '=', '0']
                        ])
                        ->first();

                    if ($checkIfTelephoneAlreadyExists) {
                        array_push($checkTelephone, 'Phonenumber : ' . $val['phoneNumber'] . ' already exists, please try different number');
                    }
                }


                if ($checkTelephone) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $checkTelephone,
                    ], 422);
                }
            }

            $insertEmailUsers = '';
            if ($request->email) {

                $arrayemail = json_decode($request->email, true);

                $messageEmail = [
                    '*.email.required' => 'Email on tab email is required',
                    '*.usage.required' => 'Usage on tab email is required',
                ];

                $emailDetail = Validator::make(
                    $arrayemail,
                    [
                        '*.email' => 'required',
                        '*.usage' => 'required',
                    ],
                    $messageEmail
                );

                if ($emailDetail->fails()) {
                    $errorsdetailEmail = $emailDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetailEmail,
                    ], 422);
                }

                //25122022 insert
                $checkUsageEmail = false;
                $checkEmail = [];
                foreach ($arrayemail as $val) {

                    $checkIfEmailExists = DB::table('usersEmails')
                        ->where([
                            ['email', '=', $val['email'],],
                            ['isDeleted', '=', '0']
                        ])
                        ->first();

                    if ($checkIfEmailExists) {
                        array_push($checkEmail, 'Email : ' . $val['email'] . ' already exists, please try different email address');
                    }

                    if ($val['usage'] == 'Utama') {
                        $checkUsageEmail = true;
                        $insertEmailUsers = $val['email'];
                    }
                }

                if ($checkEmail) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $checkEmail,
                    ], 422);
                }

                if ($checkUsageEmail == false) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => 'Must have one primary email',
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Email can not be empty!'],
                ], 422);
            }

            if ($request->messenger) {

                $arraymessenger = json_decode($request->messenger, true);

                $messageMessenger = [
                    '*.messengerNumber.required' => 'messenger number on tab messenger is required',
                    '*.type.required' => 'Type on tab messenger is required',
                    '*.usage.required' => 'Usage on tab messenger is required',
                ];

                $messengerDetail = Validator::make(
                    $arraymessenger,
                    [
                        '*.messengerNumber' => 'required',
                        '*.type' => 'required',
                        '*.usage' => 'required',
                    ],
                    $messageMessenger
                );

                if ($messengerDetail->fails()) {
                    $errorsdetailMessenger = $messengerDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetailMessenger,
                    ], 422);
                }

                //25122022 insert
                $checkMessenger = [];
                foreach ($arraymessenger as $val) {

                    $checkifMessengerExists = DB::table('usersMessengers')
                        ->where([
                            ['messengerNumber', '=', $val['messengerNumber'],],
                            ['isDeleted', '=', '0']
                        ])
                        ->first();

                    if ($checkifMessengerExists) {
                        array_push($checkMessenger, 'Messenger number  : ' . $val['messengerNumber'] . ' already exists, please try different number');
                    }
                }

                if ($checkMessenger) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $checkMessenger,
                    ], 422);
                }
            }

            //INSERT STAFF/USERS

            $lastInsertedID = DB::table('users')
                ->insertGetId([
                    'firstName' => $request->firstName,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'nickName' => $request->nickName,
                    'gender' => $request->gender,
                    'status' => $request->status,
                    'jobTitleId' => $request->jobTitleId,
                    'startDate' => $request->startDate,
                    'endDate' => $request->endDate,
                    'registrationNo' => $request->registrationNo,
                    'designation' => $request->designation,
                    'locationId' => $request->locationId,
                    'annualSickAllowance' => $request->annualSickAllowance,
                    'payPeriodId' => $request->payPeriodId,
                    'payAmount' => $request->payAmount,
                    'typeId' => $request->typeId,
                    'identificationNumber' => $request->identificationNumber,
                    'additionalInfo' => $request->additionalInfo,
                    'generalCustomerCanSchedule' => $request->generalCustomerCanSchedule,
                    'generalCustomerReceiveDailyEmail' => $request->generalCustomerReceiveDailyEmail,
                    'generalAllowMemberToLogUsingEmail' => $request->generalAllowMemberToLogUsingEmail,
                    'reminderEmail' => $request->reminderEmail,
                    'reminderWhatsapp' => $request->reminderWhatsapp,
                    'roleId' => $request->roleId,
                    'isDeleted' => 0,
                    'createdBy' => $request->user()->firstName,
                    'email' => $insertEmailUsers,
                    'created_at' => now(),
                    'password' => null,
                ]);


            if ($request->detailAddress) {

                foreach ($arrayDetailAddress as $val) {

                    DB::table('usersDetailAddresses')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'addressName' => $val['addressName'],
                            'additionalInfo' => $val['additionalInfo'],
                            'provinceCode' => $val['provinceCode'],
                            'cityCode' => $val['cityCode'],
                            'postalCode' => $val['postalCode'],
                            'country' => $val['country'],
                            'isPrimary' => $val['isPrimary'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);
                }
            }


            if ($request->hasfile('images')) {

                $files[] = $request->file('images');
                $int = 0;

                if (count($files) != 0) {

                    foreach ($files as $file) {

                        foreach ($file as $fil) {

                            $name = $fil->hashName();
                            $fil->move(public_path() . '/UsersImages/', $name);

                            $fileName = "/UsersImages/" . $name;

                            DB::table('usersImages')
                                ->insert([
                                    'usersId' => $lastInsertedID,
                                    'imagePath' => $fileName,
                                    'isDeleted' => 0,
                                    'created_at' => now(),
                                ]);

                            $int = $int + 1;
                        }
                    }
                }
            }

            if ($request->messenger) {

                foreach ($arraymessenger as $val) {

                    DB::table('usersMessengers')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'messengerNumber' => $val['messengerNumber'],
                            'type' => $val['type'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);
                }
            }

            if ($request->email) {

                foreach ($arrayemail as $val) {

                    DB::table('usersEmails')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'email' => $val['email'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);
                }
            }

            if ($request->telephone) {

                foreach ($arraytelephone as $val) {

                    DB::table('usersTelephones')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'phoneNumber' => $val['phoneNumber'],
                            'type' => $val['type'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);
                }
            }

            // check kalau akun aktif maka baru send email , kalau akun tidak aktif maka email tidak terkirim
            if ($request->status == 1) {

                $sendEmailPrimary = DB::table('usersEmails')
                    ->select(
                        'usersId',
                        'email',
                        DB::raw("CONCAT(IFNULL(users.firstName,'') ,' ', IFNULL(users.middleName,'') ,' ', IFNULL(users.lastName,'') ,'(', IFNULL(users.nickName,'') ,')'  ) as name"),
                    )
                    ->where([
                        ['usersId', '=', $lastInsertedID],
                        ['isDeleted', '=', 0]
                    ])
                    ->first();

                $jobtitleName = DB::table('jobTitle')
                    ->select('jobName')
                    ->where([
                        ['id', '=', $request->jobTitleId],
                        ['isActive', '=', 1]
                    ])
                    ->first();

                $data = [
                    'subject' => 'RPC Petshop',
                    'body' => 'Please verify your account',
                    'isi' => 'This e-mail was sent from a notification-only address that cannot accept incoming e-mails. Please do not reply to this message.',
                    'name' => $sendEmailPrimary->name,
                    'email' => $sendEmailPrimary->email,
                    'jobTitle' => $jobtitleName->jobName,
                    'usersId' => $sendEmailPrimary->usersId,
                ];

                Mail::to($sendEmailPrimary->email)->send(new SendEmail($data));

                DB::commit();

                return response()->json(
                    [
                        'result' => 'success',
                        'message' => 'Insert Data Users Successful! Please check your email for verification',
                    ],
                    200
                );
            } else {

                DB::commit();

                return response()->json(
                    [
                        'result' => 'success',
                        'message' => 'Insert Data Users Successful!',
                    ],
                    200
                );
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }

    public function sendEmailVerification(Request $request)
    {
        //29122022
        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        DB::beginTransaction();
        try {

            $checkIfDataExits = DB::table('users')
                ->where([
                    ['id', '=', $request->id],
                    ['isDeleted', '=', 0],
                ])
                ->first();

            if (!$checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Data not found! try different ID'],
                ], 422);
            }


            $users = DB::table('users')
                ->select(
                    'status',
                    'password',
                )
                ->where('id', '=', $request->id)
                ->first();

            if ($users->status == 0) {
                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Please activated your account first',
                ], 406);
            } else {


                if ($users->password != null) {
                    return response()->json([
                        'result' => 'failed',
                        'message' => 'Your account password has been set and verified within email',
                    ], 406);
                } else {

                    $sendEmailPrimary = DB::table('users')
                        ->select(
                            'jobTitleId',
                            'email',
                            DB::raw("CONCAT(IFNULL(users.firstName,'') ,' ', IFNULL(users.middleName,'') ,' ', IFNULL(users.lastName,'') ,'(', IFNULL(users.nickName,'') ,')'  ) as name"),
                        )
                        ->where([
                            ['id', '=', $request->id],
                            ['users.isDeleted', '=', 0],

                        ])
                        ->first();


                    $jobtitleName = DB::table('jobTitle')
                        ->select('jobName')
                        ->where([
                            ['id', '=', $sendEmailPrimary->jobTitleId],
                            ['isActive', '=', 1]
                        ])
                        ->first();

                    $data = [
                        'subject' => 'RPC Petshop',
                        'body' => 'Please verify your account',
                        'isi' => 'This e-mail was sent from a notification-only address that cannot accept incoming e-mails. Please do not reply to this message.',
                        'name' => $sendEmailPrimary->name,
                        'email' => $sendEmailPrimary->email,
                        'jobTitle' => $jobtitleName->jobName,
                        'usersId' => $request->id,
                    ];

                    Mail::to($sendEmailPrimary->email)->send(new SendEmail($data));
                }
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully send email! Please check your email for verification',
            ], 200);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }


    public function updateStatusUsers(Request $request)
    {
        //update 29122022
        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        DB::beginTransaction();
        try {

            $checkIfDataExits = DB::table('users')
                ->where([
                    ['id', '=', $request->id],
                    ['isDeleted', '=', 0],
                ])
                ->first();

            if (!$checkIfDataExits) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Data not found! try different ID'],
                ], 422);
            }

            $users = DB::table('users')
                ->select('status')
                ->where('id', '=', $request->id)
                ->first();

            if ($users->status == 1) {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'Your account already been activated',
                ], 406);
            } else {

                $users = DB::table('users')
                    ->select('status')
                    ->where('id', '=', $request->id)
                    ->first();

                DB::table('users')
                    ->where('id', '=', $request->id)
                    ->update([
                        'status' => 1,
                    ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfuly activated your account, you can send email for setting your password',
                ], 200);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }


    public function getRoleStaff(Request $request)
    {

        try {

            $getRole = DB::table('usersRoles')
                ->select(
                    'id',
                    'roleName'
                )
                ->where([['isActive', '=', '1']])
                ->orderBy('id', 'asc')
                ->get();

            return response()->json($getRole, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }



    public function index(Request $request)
    {

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            //V1
            $subquery = DB::table('users as a')
                ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
                ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
                ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
                ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
                ->select(
                    'a.id as id',
                    DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                    'b.jobName as jobTitle',
                    'c.email as emailAddress',
                    DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                    DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                    DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                    'e.locationName as location',
                    'a.createdBy as createdBy',
                    DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
                )
                ->where([
                    ['a.isDeleted', '=', '0'],
                    ['b.isActive', '=', '1'],
                    ['c.usage', '=', 'Utama'],
                    ['c.isDeleted', '=', '0'],
                    ['d.usage', '=', 'Utama'],
                    ['e.isDeleted', '=', '0'],
                ]);

            //V2
            // $subquery = DB::table('users')
            //     ->leftjoin(
            //         DB::raw('(select * from jobTitle where isActive=1) as jobTitle'),
            //         function ($join) {
            //             $join->on('jobTitle.id', '=', 'users.jobTitleId');
            //         }
            //     )
            //     ->leftjoin(
            //         DB::raw('(select * from usersEmails) as usersEmails'),
            //         function ($join) {
            //             $join->on('usersEmails.usersId', '=', 'users.id');
            //         },
            //     )->where([
            //         ['usersEmails.isDeleted', '=', '0'],
            //         ['usersEmails.usage', '=', 'Utama'],
            //     ])
            //     ->leftjoin(
            //         DB::raw('(select * from userstelephones) as userstelephones'),
            //         function ($join) {
            //             $join->on('userstelephones.usersId', '=', 'users.id');
            //         },
            //     )->where([
            //         ['userstelephones.isDeleted', '=', '0'],
            //         ['userstelephones.usage', '=', 'Utama'],
            //     ])
            //     ->leftjoin(
            //         DB::raw('(select * from location where isDeleted=0) as location'),
            //         function ($join) {
            //             $join->on('location.id', '=', 'users.locationId');
            //         }
            //     )
            //     ->select(
            //         'users.id as id',
            //         DB::raw("CONCAT(users.firstName ,' ', users.middleName ,' ', users.lastName ,'(', users.nickName ,')'  ) as name"),
            //         'jobTitle.jobName as jobTitle',
            //         DB::raw("IFNULL(usersEmails.email,'') as emailAddress"),
            //         DB::raw("CONCAT(userstelephones.phoneNumber ,' ', userstelephones.type) as phoneNumber"),
            //         DB::raw("CASE WHEN users.status=1 then 'Active' else 'Non Active' end as status"),
            //         'location.locationName as location',
            //         'users.createdBy as createdBy',
            //         DB::raw('DATE_FORMAT(users.created_at, "%d-%m-%Y") as createdAt')
            //     )
            //     ->where([
            //         ['users.isDeleted', '=', '0'],
            //         ['jobTitle.isActive', '=', '1'],
            //     ]);

            $data = DB::table($subquery, 'a');

            if ($request->search) {

                $res = $this->Search($request);

                if ($res == "id") {

                    $data = $data->where('id', 'like', '%' . $request->search . '%');
                } else if ($res == "name") {

                    $data = $data->where('name', 'like', '%' . $request->search . '%');
                } else if ($res == "jobTitle") {

                    $data = $data->where('jobTitle', 'like', '%' . $request->search . '%');
                } else if ($res == "emailAddress") {

                    $data = $data->where('emailAddress', 'like', '%' . $request->search . '%');
                } else if ($res == "phoneNumber") {

                    $data = $data->where('phoneNumber', 'like', '%' . $request->search . '%');
                } else if ($res == "isWhatsapp") {

                    $data = $data->where('isWhatsapp', 'like', '%' . $request->search . '%');
                } else if ($res == "status") {

                    $data = $data->where('status', 'like', '%' . $request->search . '%');
                } else if ($res == "location") {

                    $data = $data->where('location', 'like', '%' . $request->search . '%');
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

            if ($request->orderValue) {

                $defaultOrderBy = $request->orderValue;
            }

            if ($request->orderColumn && $defaultOrderBy) {

                $listOrder = array(
                    'id',
                    'name',
                    'jobTitle',
                    'emailAddress',
                    'phoneNumber',
                    'isWhatsapp',
                    'status',
                    'location',
                    'createdBy',
                    'createdAt',
                );

                if (!in_array($request->orderColumn, $listOrder)) {

                    return response()->json([
                        'result' => 'failed',
                        'message' => 'Please try different order column',
                        'orderColumn' => $listOrder,
                    ]);
                }

                if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                    return response()->json([
                        'result' => 'failed',
                        'message' => 'order value must Ascending: ASC or Descending: DESC ',
                    ]);
                }

                $data = $data->orderBy($request->orderColumn, $defaultOrderBy);
            }

            $data = $data->orderBy('createdAt', $defaultOrderBy);

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
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }


    private function Search($request)
    {

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);

        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('id', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'id';
            return $temp_column;
        }

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);


        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'name';
            return $temp_column;
        }

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);


        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('jobTitle', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'jobTitle';
            return $temp_column;
        }

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);

        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('emailAddress', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'emailAddress';
            return $temp_column;
        }


        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);

        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('phoneNumber', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'phoneNumber';
            return $temp_column;
        }


        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);

        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('isWhatsapp', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'isWhatsapp';
            return $temp_column;
        }

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);


        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('status', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'status';
            return $temp_column;
        }


        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);


        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('location', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location';
            return $temp_column;
        }

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);

        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdBy';
            return $temp_column;
        }

        $subquery = DB::table('users as a')
            ->leftjoin('jobTitle as b', 'b.id', '=', 'a.jobTitleId')
            ->leftjoin('usersEmails as c', 'c.usersId', '=', 'a.id')
            ->leftjoin('usersTelephones as d', 'd.usersId', '=', 'a.id')
            ->leftjoin('location as e', 'e.id', '=', 'a.locationId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(a.firstName ,' ', a.middleName ,' ', a.lastName ,'(', a.nickName ,')'  ) as name"),
                'b.jobName as jobTitle',
                'c.email as emailAddress',
                DB::raw("CONCAT(d.phoneNumber) as phoneNumber"),
                DB::raw("CASE WHEN lower(d.type)='whatshapp' then true else false end as isWhatsapp"),
                DB::raw("CASE WHEN a.status=1 then 'Active' else 'Non Active' end as status"),
                'e.locationName as location',
                'a.createdBy as createdBy',
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt')
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.usage', '=', 'Utama'],
                ['c.isDeleted', '=', '0'],
                ['d.usage', '=', 'Utama'],
                ['e.isDeleted', '=', '0'],
            ]);

        $data = DB::table($subquery, 'a');

        if ($request->search) {
            $data = $data->where('createdAt', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdAt';
            return $temp_column;
        }
    }


    public function uploadImageStaff(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('users')
            ->where([
                ['id', '=', $request->id],
                ['isDeleted', '=', 0],
            ])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another user id",
            ], 406);
        } else {


            $checkImages = DB::table('usersImages')
                ->where([
                    ['usersId', '=', $request->id]
                ])
                ->first();


            if ($checkImages != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User images already exists, please delete first if you want to re-upload images',
                ], 406);
            } else {

                if ($request->hasfile('images')) {

                    $files[] = $request->file('images');
                    $int = 0;

                    if (count($files) != 0) {

                        foreach ($files as $file) {

                            foreach ($file as $fil) {

                                $name = $fil->hashName();
                                $fil->move(public_path() . '/UsersImages/', $name);

                                $fileName = "/UsersImages/" . $name;

                                DB::table('usersImages')
                                    ->insert([
                                        'usersId' => $request->id,
                                        'imagePath' => $fileName,
                                        'isDeleted' => 0,
                                        'created_at' => now(),
                                    ]);

                                $int = $int + 1;
                            }
                        }
                    }

                    DB::commit();
                    return response()->json(
                        [
                            'result' => 'success',
                            'message' => 'Upload image users Success!',
                        ],
                        200
                    );
                } else {

                    return response()->json(
                        [
                            'result' => 'failed',
                            'message' => 'Please attach images first!',
                        ],
                        406
                    );
                }
            }
        }
    }


    public function deleteImageStaff(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validate->fails()) {

            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('users')
            ->where([
                ['id', '=', $request->id],
                ['isDeleted', '=', 0],
            ])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another user id",
            ], 406);
        } else {

            $checkImages = DB::table('usersImages')
                ->where([
                    ['usersId', '=', $request->id]
                ])
                ->first();


            if ($checkImages == null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'User images empty, please upload images first',
                ], 406);
            } else {

                if (file_exists(public_path() . $checkImages->imagePath)) {

                    File::delete(public_path() . $checkImages->imagePath);

                    DB::table('usersImages')->where([
                        ['usersId', '=', $request->id]
                    ])->delete();
                }

                return response()->json(
                    [
                        'result' => 'success',
                        'message' => 'Delete image users Success!',
                    ],
                    200
                );
            }
        }
    }


    public function getDetailStaff(Request $request)
    {

        try {

            $validate = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validate->fails()) {

                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIfValueExits = DB::table('users')
                ->where([
                    ['id', '=', $request->id],
                    ['isDeleted', '=', 0],
                ])
                ->first();

            if ($checkIfValueExits === null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => "Data not exists, please try another user id",
                ], 406);
            } else {

                $users = DB::table('users as a')
                    ->leftjoin('location as b', 'b.id', '=', 'a.id')
                    ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
                    ->leftjoin('typeId as d', 'd.id', '=', 'a.typeId')
                    ->leftjoin('payPeriod as e', 'e.id', '=', 'a.payPeriodId')
                    ->select(
                        'a.id',
                        'a.firstName',
                        'a.middleName',
                        'a.lastName',
                        'a.nickName',
                        'a.gender',
                        'a.status',
                        'c.id as jobTitleId',
                        'c.jobName as jobName',
                        'a.startDate',
                        'a.endDate',
                        'a.registrationNo',
                        'a.designation',
                        'b.id as locationId',
                        'b.locationname as locationName',

                        'a.annualSickAllowance',
                        'a.annualLeaveAllowance',
                        'a.payPeriodId',
                        'e.periodName',
                        'a.payAmount',

                        'd.id as typeId',
                        'd.typeName as typeName',
                        'a.identificationNumber',
                        'a.additionalInfo',

                        'a.generalCustomerCanSchedule',
                        'a.generalCustomerReceiveDailyEmail',
                        'a.generalAllowMemberToLogUsingEmail',
                        'a.reminderEmail',
                        'a.reminderWhatsapp',
                        'a.roleId',

                    )
                    ->where([
                        ['a.id', '=', $request->id],
                        ['a.isDeleted', '=', '0'],
                        ['c.isActive', '=', '1'],
                        ['d.isActive', '=', '1'],
                        ['e.isActive', '=', '1']
                    ])
                    ->first();


                $usersimages = DB::table('usersImages as a')
                    ->select(
                        'a.id as id',
                        'a.usersId as usersId',
                        'a.imagePath as imagePath',
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', '0']
                    ])
                    ->get();

                $users->images = $usersimages;

                $users_detail_address = DB::table('usersDetailAddresses as a')
                    ->select(
                        'a.addressName as addressName',
                        'a.additionalInfo as additionalInfo',
                        'a.provinceCode as provinceCode',
                        'a.cityCode as cityCode',
                        'a.postalCode as postalCode',
                        'a.country as country',
                        'a.isPrimary as isPrimary',
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', '0']
                    ])
                    ->get();

                $users->detailAddress = $users_detail_address;


                $usersmessengers = DB::table('usersMessengers as a')
                    ->select(
                        'a.messengerNumber as messengerNumber',
                        'a.type as type',
                        'a.usage as usage',
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', '0']
                    ])
                    ->get();

                $users->messenger = $usersmessengers;

                $usersEmails = DB::table('usersEmails as a')
                    ->select(
                        'a.email as email',
                        'a.usage as usage',
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', '0']
                    ])
                    ->get();

                $users->email = $usersEmails;

                $userstelephone = DB::table('usersTelephones as a')
                    ->select(
                        'a.phoneNumber as phoneNumber',
                        'a.type as type',
                        'a.usage as usage',
                    )
                    ->where([
                        ['a.usersId', '=', $request->id],
                        ['a.isDeleted', '=', '0']
                    ])
                    ->get();

                $users->telephone = $userstelephone;

                return response()->json($users, 200);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }

    public function exportStaff(Request $request)
    {
        return Excel::download(new exportStaff, 'Staff.xlsx');
    }


    public function updateStaff(Request $request)
    {

        //25122022 update
        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        DB::beginTransaction();

        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required',
                    'firstName' => 'required|max:20|min:3',
                    'middleName' => 'max:20|min:3|nullable',
                    'lastName' => 'max:20|min:3|nullable',
                    'nickName' => 'max:20|min:3|nullable',
                    'gender' => 'string|nullable',
                    'status' => 'required|integer',
                    'jobTitleId' => 'required|integer',
                    'startDate' => 'required|date',
                    'endDate' => 'required|date|after:startDate',
                    'registrationNo' => 'string|max:20|min:5|nullable',
                    'designation' => 'string|max:20|min:5|nullable',
                    'locationId' => 'required|integer',
                    'annualSickAllowance' => 'integer|nullable',
                    'annualLeaveAllowance' => 'integer|nullable',
                    'payPeriodId' => 'required|integer',
                    'payAmount' => 'numeric|nullable',
                    'typeId' => 'required|integer',
                    'identificationNumber' => 'string|nullable|max:30',
                    'additionalInfo' => 'string|nullable|max:100',
                    'generalCustomerCanSchedule' => 'integer|nullable',
                    'generalCustomerReceiveDailyEmail' => 'integer|nullable',
                    'generalAllowMemberToLogUsingEmail' => 'integer|nullable',
                    'roleId' => 'integer|nullable',
                    'reminderEmail' => 'integer|nullable',
                    'reminderWhatsapp' => 'integer|nullable',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIfUsersExists = DB::table('users')
                ->where([
                    ['id', '=', $request->id],
                    ['isDeleted', '=', '0']
                ])
                ->first();

            if (!$checkIfUsersExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Spesific users not exists please try different id!'],
                ], 422);
            }


            if ($request->detailAddress) {

                $messageAddress = [
                    '*.addressName.required' => 'Address name on tab Address is required',
                    '*.provinceCode.required' => 'Province code on tab Address is required',
                    '*.cityCode.required' => 'City code on tab Address is required',
                    '*.country.required' => 'Country on tab Address is required',
                ];

                $validateDetail = Validator::make(
                    $request->detailAddress,
                    [
                        '*.addressName' => 'required',
                        '*.provinceCode' => 'required',
                        '*.cityCode' => 'required',
                        '*.country' => 'required',
                    ],
                    $messageAddress
                );

                if ($validateDetail->fails()) {
                    $errorsdetail = $validateDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetail,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }



            if ($request->telephone) {

                $messagePhone = [
                    '*.phoneNumber.required' => 'Phone Number on tab telephone is required',
                    '*.type.required' => 'Type on tab telephone is required',
                    '*.usage.required' => 'Usage on tab telephone is required',
                ];

                $telephoneDetail = Validator::make(
                    $request->telephone,
                    [
                        '*.phoneNumber' => 'required',
                        '*.type' => 'required',
                        '*.usage' => 'required',
                    ],
                    $messagePhone
                );

                if ($telephoneDetail->fails()) {
                    $errorsdetail = $telephoneDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetail,
                    ], 422);
                }


                //25122022 update
                $checkTelephone = [];

                foreach ($request->telephone as $val) {

                    $checkIfTelephoneAlreadyExists = DB::table('usersTelephones')
                        ->where([
                            ['phoneNumber', '=', $val['phoneNumber'],],
                            ['isDeleted', '=', '0'],
                            ['usersId', '!=', $request->id]
                        ])
                        ->first();

                    if ($checkIfTelephoneAlreadyExists) {
                        array_push($checkTelephone, 'Phonenumber : ' . $val['phoneNumber'] . ' already exists, please try different number');
                    }
                }

                if ($checkTelephone) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $checkTelephone,
                    ], 422);
                }
            }


            //25122022 update
            $insertEmailUsers = '';

            if ($request->email) {

                $messageEmail = [
                    '*.email.required' => 'Email on tab email is required',
                    '*.usage.required' => 'Usage on tab email is required',
                ];

                $emailDetail = Validator::make(
                    $request->email,
                    [
                        '*.email' => 'required',
                        '*.usage' => 'required',
                    ],
                    $messageEmail
                );

                if ($emailDetail->fails()) {
                    $errorsdetailEmail = $emailDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetailEmail,
                    ], 422);
                }


                $checkEmail = [];
                $checkUsageEmail = false;

                foreach ($request->email as $val) {

                    $checkIfEmailExists = DB::table('usersEmails')
                        ->where([
                            ['email', '=', $val['email'],],
                            ['isDeleted', '=', '0'],
                            ['usersId', '!=', $request->id]
                        ])
                        ->first();

                    if ($checkIfEmailExists) {
                        array_push($checkEmail, 'Email : ' . $val['email'] . ' already exists, please try different email address');
                    }


                    if ($val['usage'] == 'Utama') {
                        $checkUsageEmail = true;

                        //28122022
                        $checkEmailUtama = DB::table('usersEmails')
                            ->where([
                                ['usage', '=', 'Utama'],
                                ['isDeleted', '=', '0'],
                                ['usersId', '=', $request->id]
                            ])
                            ->first();

                        if ($checkEmailUtama->email != $val['email']) {
                            //data berubah change email primary maka data loop dimasukan
                            $insertEmailUsers = $val['email'];
                        }
                    }
                }

                if ($checkEmail) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $checkEmail,
                    ], 422);
                }

                if ($checkUsageEmail == false) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => 'Must have one primary email',
                    ], 422);
                }
            }

            if ($request->messenger) {

                $messageMessenger = [
                    '*.messengerNumber.required' => 'messenger number on tab messenger is required',
                    '*.type.required' => 'Type on tab messenger is required',
                    '*.usage.required' => 'Usage on tab messenger is required',
                ];

                $messengerDetail = Validator::make(
                    $request->messenger,
                    [
                        '*.messengerNumber' => 'required',
                        '*.type' => 'required',
                        '*.usage' => 'required',
                    ],
                    $messageMessenger
                );

                if ($messengerDetail->fails()) {
                    $errorsdetailMessenger = $messengerDetail->errors()->all();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $errorsdetailMessenger,
                    ], 422);
                }

                //25122022 update
                $checkMessenger = [];

                foreach ($request->messenger as $val) {

                    $checkifMessengerExists = DB::table('usersMessengers')
                        ->where([
                            ['messengerNumber', '=', $val['messengerNumber'],],
                            ['isDeleted', '=', '0'],
                            ['usersId', '!=', $request->id]
                        ])
                        ->first();

                    if ($checkifMessengerExists) {
                        array_push($checkMessenger, 'Messenger number  : ' . $val['messengerNumber'] . ' already exists, please try different number');
                    }
                }

                if ($checkMessenger) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $checkMessenger,
                    ], 422);
                }
            }

            //UPDATE
            if ($insertEmailUsers) {

                //kalau ada isi maka harus resend kembali untuk verifikasi ulang email ada send
                //tapi check terlebih dahulu apakah status data is active

                DB::table('users')
                    ->where('id', '=', $request->id)
                    ->update([
                        'firstName' => $request->firstName,
                        'middleName' => $request->middleName,
                        'lastName' => $request->lastName,
                        'nickName' => $request->nickName,
                        'gender' => $request->gender,
                        'status' => $request->status,
                        'jobTitleId' => $request->jobTitleId,
                        'startDate' => $request->startDate,
                        'endDate' => $request->endDate,
                        'registrationNo' => $request->registrationNo,
                        'designation' => $request->designation,
                        'locationId' => $request->locationId,
                        'annualSickAllowance' => $request->annualSickAllowance,
                        'payPeriodId' => $request->payPeriodId,
                        'payAmount' => $request->payAmount,
                        'typeId' => $request->typeId,
                        'identificationNumber' => $request->identificationNumber,
                        'additionalInfo' => $request->additionalInfo,
                        'generalCustomerCanSchedule' => $request->generalCustomerCanSchedule,
                        'generalCustomerReceiveDailyEmail' => $request->generalCustomerReceiveDailyEmail,
                        'generalAllowMemberToLogUsingEmail' => $request->generalAllowMemberToLogUsingEmail,
                        'reminderEmail' => $request->reminderEmail,
                        'reminderWhatsapp' => $request->reminderWhatsapp,
                        'roleId' => $request->roleId,
                        'createdBy' => $request->user()->firstName,
                        'updated_at' => now(),
                        'password' => null,
                        'email' => $insertEmailUsers,
                    ]);

                if ($request->detailAddress) {

                    DB::table('usersDetailAddresses')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->detailAddress as $val) {

                        DB::table('usersDetailAddresses')
                            ->insert([
                                'usersId' => $request->id,
                                'addressName' => $val['addressName'],
                                'additionalInfo' => $val['additionalInfo'],
                                'provinceCode' => $val['provinceCode'],
                                'cityCode' => $val['cityCode'],
                                'postalCode' => $val['postalCode'],
                                'country' => $val['country'],
                                'isPrimary' => $val['isPrimary'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                if ($request->messenger) {

                    DB::table('usersMessengers')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->messenger as $val) {
                        DB::table('usersMessengers')
                            ->insert([
                                'usersId' => $request->id,
                                'messengerNumber' => $val['messengerNumber'],
                                'type' => $val['type'],
                                'usage' => $val['usage'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                if ($request->email) {

                    DB::table('usersEmails')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->email as $val) {
                        DB::table('usersEmails')
                            ->insert([
                                'usersId' => $request->id,
                                'email' => $val['email'],
                                'usage' => $val['usage'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }



                if ($request->telephone) {

                    DB::table('usersTelephones')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->telephone as $val) {
                        DB::table('usersTelephones')
                            ->insert([
                                'usersId' => $request->id,
                                'phoneNumber' => $val['phoneNumber'],
                                'type' => $val['type'],
                                'usage' => $val['usage'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                //check if status nya 0 pada saat update 

                if ($request->status == 0) {

                    DB::commit();
                    return response()->json([
                        'result' => 'success',
                        'message' => 'successfuly update user ',
                    ]);
                } else {


                    $jobtitleName = DB::table('jobTitle')
                        ->select('jobName')
                        ->where([
                            ['id', '=', $request->jobTitleId],
                            ['isActive', '=', 1]
                        ])
                        ->first();


                    $data = [
                        'subject' => 'RPC Petshop',
                        'body' => 'Please verify your account',
                        'isi' => 'This e-mail was sent from a notification-only address that cannot accept incoming e-mails. Please do not reply to this message.',
                        'name' => $request->firstName,
                        'email' => $insertEmailUsers,
                        'jobTitle' => $jobtitleName->jobName,
                        'usersId' => $request->id,
                    ];

                    Mail::to($insertEmailUsers)->send(new SendEmail($data));

                    DB::commit();

                    return response()->json([
                        'result' => 'success',
                        'message' => 'successfuly update user, your primary email has updated, please check your new email to verify your password',
                    ]);
                }
            } else {


                DB::table('users')
                    ->where('id', '=', $request->id)
                    ->update([
                        'firstName' => $request->firstName,
                        'middleName' => $request->middleName,
                        'lastName' => $request->lastName,
                        'nickName' => $request->nickName,
                        'gender' => $request->gender,
                        'status' => $request->status,
                        'jobTitleId' => $request->jobTitleId,
                        'startDate' => $request->startDate,
                        'endDate' => $request->endDate,
                        'registrationNo' => $request->registrationNo,
                        'designation' => $request->designation,
                        'locationId' => $request->locationId,
                        'annualSickAllowance' => $request->annualSickAllowance,
                        'payPeriodId' => $request->payPeriodId,
                        'payAmount' => $request->payAmount,
                        'typeId' => $request->typeId,
                        'identificationNumber' => $request->identificationNumber,
                        'additionalInfo' => $request->additionalInfo,
                        'generalCustomerCanSchedule' => $request->generalCustomerCanSchedule,
                        'generalCustomerReceiveDailyEmail' => $request->generalCustomerReceiveDailyEmail,
                        'generalAllowMemberToLogUsingEmail' => $request->generalAllowMemberToLogUsingEmail,
                        'reminderEmail' => $request->reminderEmail,
                        'reminderWhatsapp' => $request->reminderWhatsapp,
                        'roleId' => $request->roleId,
                        'createdBy' => $request->user()->firstName,
                        'updated_at' => now(),

                    ]);


                if ($request->detailAddress) {

                    DB::table('usersDetailAddresses')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->detailAddress as $val) {

                        DB::table('usersDetailAddresses')
                            ->insert([
                                'usersId' => $request->id,
                                'addressName' => $val['addressName'],
                                'additionalInfo' => $val['additionalInfo'],
                                'provinceCode' => $val['provinceCode'],
                                'cityCode' => $val['cityCode'],
                                'postalCode' => $val['postalCode'],
                                'country' => $val['country'],
                                'isPrimary' => $val['isPrimary'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }


                if ($request->messenger) {

                    DB::table('usersMessengers')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->messenger as $val) {
                        DB::table('usersMessengers')
                            ->insert([
                                'usersId' => $request->id,
                                'messengerNumber' => $val['messengerNumber'],
                                'type' => $val['type'],
                                'usage' => $val['usage'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                if ($request->email) {

                    DB::table('usersEmails')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->email as $val) {
                        DB::table('usersEmails')
                            ->insert([
                                'usersId' => $request->id,
                                'email' => $val['email'],
                                'usage' => $val['usage'],
                                'email_verified_at' => now(),
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                if ($request->telephone) {

                    DB::table('usersTelephones')->where('usersId', '=', $request->id)->delete();

                    foreach ($request->telephone as $val) {
                        DB::table('usersTelephones')
                            ->insert([
                                'usersId' => $request->id,
                                'phoneNumber' => $val['phoneNumber'],
                                'type' => $val['type'],
                                'usage' => $val['usage'],
                                'isDeleted' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'successfuly update user',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }




    public function getLocationStaff(Request $request)
    {

        try {

            $getLocationStaff = DB::table('location as a')
                ->select(
                    'a.id as locationId',
                    'a.locationName as locationName',
                )
                ->where([
                    ['isDeleted', '=', 0],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getLocationStaff, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }



    public function getTypeId(Request $request)
    {

        try {

            $getTypeId = DB::table('typeId as a')
                ->select(
                    'a.id as typeId',
                    'a.typeName as typeName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getTypeId, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }


    public function getPayPeriod(Request $request)
    {

        try {

            $getPayPeriod = DB::table('payPeriod as a')
                ->select(
                    'a.id as payPeriodId',
                    'a.periodName as periodName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getPayPeriod, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }

    public function getJobTitle(Request $request)
    {

        try {

            $getjobTitle = DB::table('jobTitle as a')
                ->select(
                    'a.id as jobTitleid',
                    'a.jobName as jobName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getjobTitle, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }


    public function insertTypeId(Request $request)
    {

        $request->validate([
            'typeName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('typeId as a')
                ->where([
                    ['a.typeName', '=', $request->typeName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Type name already exists, please choose another name',
                ]);
            } else {

                DB::table('typeId')->insert([
                    'typeName' => $request->typeName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Type ID',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }



    public function insertJobTitle(Request $request)
    {

        $request->validate([
            'jobName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('jobTitle as a')
                ->where([
                    ['a.jobName', '=', $request->jobName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Job title already exists, please choose another name',
                ]);
            } else {

                DB::table('jobTitle')->insert([
                    'jobName' => $request->jobName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Job Title',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }



    public function insertPayPeriod(Request $request)
    {

        $request->validate([
            'periodName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('payPeriod as a')
                ->where([
                    ['a.periodName', '=', $request->periodName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Pay period already exists, please choose another name',
                ]);
            } else {

                DB::table('payPeriod')->insert([
                    'periodName' => $request->periodName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted pay period',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }






    public function deleteStaff(Request $request)
    {
        //25122022 delete
        if (adminAccess($request->user()->id) != 1) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        DB::beginTransaction();
        try {


            foreach ($request->id as $val) {

                $checkIfDataExits = DB::table('users')
                    ->where([
                        ['id', '=', $val],
                        ['isDeleted', '=', 0],
                    ])
                    ->first();

                if (!$checkIfDataExits) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Data not found! try different ID'],
                    ], 422);
                }
            }

            foreach ($request->id as $val) {

                DB::table('users')
                    ->where('id', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('usersDetailAddresses')
                    ->where('usersId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('usersEmails')
                    ->where('usersId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('usersMessengers')
                    ->where('usersId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('usersImages')
                    ->where('usersId', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('usersTelephones')
                    ->where('usersId', '=', $val)
                    ->update(['isDeleted' => 1]);


                $checkImages = DB::table('usersImages')
                    ->where([
                        ['usersId', '=', $val]
                    ])
                    ->first();

                if ($checkImages != null) {

                    File::delete(public_path() . $checkImages->imagePath);
                }

                DB::commit();
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted user',
            ], 200);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }
}
