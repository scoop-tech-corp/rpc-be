<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroups;
use DB;
use Illuminate\Http\Request;
use Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $Data = DB::table('customerGroups')
            ->select('id', 'customerGroup')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($Data, 200);
    }

    public function create(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'customerGroupId' => 'nullable|integer'

        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('customerGroups')
            ->where('customerGroup', '=', $request->customerGroup)
            ->first();

        if ($checkIfValueExits === null) {

            CustomerGroups::create([
                'customerGroup' => $request->customerGroup,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ], 200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Customer Group already exists!'],
            ], 422);

        }
    }

    public function CreateCustomer(Request $request)
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
                    'firstName' => 'required|string|max:100',
                    'middleName' => 'nullable|string|max:100',
                    'lastName' => 'nullable|string|max:100',
                    'nickName' => 'nullable|string|max:100',
                    'titleCustomerId' => 'nullable|integer',
                    'customerGroupId' => 'nullable|integer',
                    'locationId' => 'nullable|integer',
                    'notes' => 'nullable|string',
                    'joinDate' => 'nullable|date',
                    'typeId' => 'required|integer',
                    'numberId' => 'required|string|max:50',
                    'gender' => 'required|in:P,W',
                    'jobTitleId' => 'nullable|integer',
                    'birthDate' => 'nullable|date',
                    'referenceCustomerId' => 'required|integer',

                    'generalCustomerCanConfigReminderBooking' => 'integer|nullable',
                    'generalCustomerCanConfigReminderPayment' => 'integer|nullable',
                ]
            );


            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $data_item_pet = [];

            if ($request->customerPet) {

                $arrayCustomerPet = json_decode($request->customerPet, true);
            
                $messageCustomerPet = [
                    'petName.required' => 'Pet name on tab Customer Pet is required',
                    'petCategoryId.required' => 'Category Pet tab Customer Pet is required',
                    'condition.required' => 'Condition on tab Customer Pet is required',
                    'petGender.required' => 'Pet Gender on tab Cutomer Pet is required',
                    'isSteril.required' => 'Pet Steril  on tab Cutomer Pet is required',
                ];


                foreach ($arrayCustomerPet as $key) {

                    $validateDetail = Validator::make(
                        $key,
                        [
                            'petName' => 'required|string|max:100',
                            'petCategoryId' => 'required:integer',
                            'race' => 'nullable|string|max:100',
                            'condition' => 'required|string|max:100',
                            'petGender' => 'required|in:J,B',
                            'isSteril' => 'required|in:1,0',
                            'petAge' => 'nullable|integer',
                        ],
                        $messageCustomerPet
                    );

                    if ($validateDetail->fails()) {

                        $errors = $validateDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item_pet))) {
                                array_push($data_item_pet, $checkisu);
                            }
                        }
                    }
                }



                if ($data_item_pet) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item_pet,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Customer pet can not be empty!'],
                ], 422);
            }

            //// VALIDASI Reminder Booking
            $data_reminder_booking = [];

            if ($request->reminderBooking) {

            $arrayReminderBooking = json_decode($request->reminderBooking, true);

                $messageReminderBooking = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                ];


                foreach ($arrayReminderBooking as $key) {

                    $validateReminderBooking = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                        ],
                        $messageReminderBooking
                    );

                    if ($validateReminderBooking->fails()) {

                        $errors = $validateReminderBooking->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_booking))) {
                                array_push($data_reminder_booking, $checkisu);
                            }
                        }
                    }

                }

                if ($data_reminder_booking) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_telephone,
                    ], 422);
                }
            }

            //// VALIDASI Reminder Payment
            $data_reminder_payment = [];

            if ($request->reminderPayment) {

            $arrayReminderPayment = json_decode($request->reminderPayment, true);

                $messageReminderPayment = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                ];


                foreach ($arrayReminderPayment as $key) {

                    $validateReminderPayment = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                        ],
                        $messageReminderPayment
                    );

                    if ($validateReminderPayment->fails()) {

                        $errors = $validateReminderPayment->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_payment))) {
                                array_push($data_reminder_payment, $checkisu);
                            }
                        }
                    }

                }

                if ($data_reminder_payment) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_telephone,
                    ], 422);
                }
            }

            //// VALIDASI Reminder Late Payment
            $data_reminder_late_payment = [];

            if ($request->reminderLatePayment) {

            $reminderLatePayment = json_decode($request->reminderLatePayment, true);

                $messageReminderLatePayment = [
                    'sourceCustomerId.required' => 'Source on tab Reminder and on Reminder Booking is required',
                    'unit.required' => 'Unit on tab Reminder and on Reminder Booking is required',
                    'time.required' => 'Time on tab Reminder and on Reminder Booking is required',
                    'timeDate.required' => 'Time Date on tab Reminder and on Reminder Booking is required',
                ];


                foreach ($arrayReminderLatePayment as $key) {

                    $validateReminderLatePayment = Validator::make(
                        $key,
                        [
                            'sourceCustomerId' => 'required|integer',
                            'unit' => 'required|integer',
                            'time' => 'required',
                            'timeDate' => 'required',
                        ],
                        $messageReminderLatePayment
                    );

                    if ($validateReminderLatePayment->fails()) {

                        $errors = $validateReminderLatePayment->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_reminder_late_payment))) {
                                array_push($data_reminder_late_payment, $checkisu);
                            }
                        }
                    }

                }

                if ($data_reminder_late_payment) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_telephone,
                    ], 422);
                }
            }

            $data_item = [];

            if ($request->detailAddress) {

            $arrayDetailAddress = json_decode($request->detailAddress, true);

                $messageAddress = [
                    'addressName.required' => 'Address name on tab Address is required',
                    'provinceCode.required' => 'Province code on tab Address is required',
                    'cityCode.required' => 'City code on tab Address is required',
                    'country.required' => 'Country on tab Address is required',
                ];


                foreach ($arrayDetailAddress as $key) {

                    $validateDetail = Validator::make(
                        $key,
                        [
                            'addressName' => 'required',
                            'provinceCode' => 'required',
                            'cityCode' => 'required',
                            'country' => 'required',
                        ],
                        $messageAddress
                    );

                    if ($validateDetail->fails()) {

                        $errors = $validateDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_item))) {
                                array_push($data_item, $checkisu);
                            }
                        }
                    }
                }



                if ($data_item) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }



            //// VALIDASI PHONE
            $data_telephone = [];

            if ($request->telephone) {

            $arraytelephone = json_decode($request->telephone, true);

                $messagePhone = [
                    'phoneNumber.required' => 'Phone Number on tab telephone is required',
                    'type.required' => 'Type on tab telephone is required',
                    'usage.required' => 'Usage on tab telephone is required',
                ];

                foreach ($arraytelephone as $key) {

                    $validateTelephone = Validator::make(
                        $key,
                        [
                            'phoneNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messagePhone
                    );

                    if ($validateTelephone->fails()) {

                        $errors = $validateTelephone->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_telephone))) {
                                array_push($data_telephone, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['phoneNumber'], 0, 3) === "+62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with +62',
                            ], 422);
                        }
                    }

                }

                if ($data_telephone) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => $data_telephone,
                    ], 422);
                }

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

            $data_error_email = [];
            $insertEmailUsers = '';
            if ($request->email) {

            $arrayemail = json_decode($request->email, true);

                $messageEmail = [
                    'email.required' => 'Email on tab email is required',
                    'usage.required' => 'Usage on tab email is required',
                ];

                foreach ($arrayemail as $key) {

                    $validateEmail = Validator::make(
                        $key,
                        [
                            'email' => 'required',
                            'usage' => 'required',
                        ],
                        $messageEmail
                    );

                    if ($validateEmail->fails()) {

                        $errors = $validateEmail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_email))) {
                                array_push($data_error_email, $checkisu);
                            }
                        }
                    }
                }


                if ($data_error_email) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_email,
                    ], 422);
                }

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


            $data_error_messenger = [];

            if ($request->messenger) {

            $arraymessenger = json_decode($request->messenger, true);

                $messageMessenger = [
                    'messengerNumber.required' => 'messenger number on tab messenger is required',
                    'type.required' => 'Type on tab messenger is required',
                    'usage.required' => 'Usage on tab messenger is required',
                ];

                foreach ($arraymessenger as $key) {

                    $validateMessenger = Validator::make(
                        $key,
                        [
                            'messengerNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messageMessenger
                    );

                    if ($validateMessenger->fails()) {

                        $errors = $validateMessenger->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_messenger))) {
                                array_push($data_error_messenger, $checkisu);
                            }
                        }
                    }


                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['messengerNumber'], 0, 3) === "+62")) {

                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with +62',
                            ], 422);
                        }
                    }


                }

                if ($data_error_messenger) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_messenger,
                    ], 422);
                }


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

            //// INSERT CUSTOMER

            $lastInsertedID = DB::table('customer')
                ->insertGetId([
                    'firstName' => $request->firstName,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'nickName' => $request->nickName,
                    'gender' => $request->gender,
                    'titleCustomerId' => $request->titleCustomerId,
                    'customerGroupId' => $request->customerGroupId,
                    'locationId' => $request->locationId,
                    'notes' => $request->notes,
                    'joinDate' => $request->joinDate,
                    'typeId' => $request->typeId,
                    'numberId' => $request->numberId,
                    'jobTitleId' => $request->jobTitleId,
                    'birthDate' => $request->birthDate,
                    'referenceCustomerId' => $request->referenceCustomerId,

                    'generalCustomerCanConfigReminderBooking' => $request->generalCustomerCanConfigReminderBooking,
                    'generalCustomerCanConfigReminderPayment' => $request->generalCustomerCanConfigReminderPayment,
                    
                    'isDeleted' => 0,
                    'createdBy' => $request->user()->firstName,
                    'created_at' => now(),
                    'updated_at' => now(),

                ]);

            if ($request->customerPet) {

                foreach ($arrayCustomerPet as $val) {

                    DB::table('customerPet')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'petName' => $val['petName'],
                            'petCategoryId' => $val['petCategoryId'],
                            'races' => $val['races'],
                            'condition' => $val['condition'],
                            'petGender' => $val['petGender'],
                            'isSteril' => $val['isSteril'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->reminderBooking) {

                foreach ($arrayReminderBooking as $val) {

                    DB::table('reminderCustomer')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'B',
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }
    
            if ($request->reminderPayment) {

                foreach ($arrayReminderPayment as $val) {

                    DB::table('reminderCustomer')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'P',
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->reminderLatePayment) {

                foreach ($arrayReminderLatePayment as $val) {

                    DB::table('reminderCustomer')
                        ->insert([
                            'usersId' => $lastInsertedID,
                            'sourceCustomerId' => $val['sourceCustomerId'],
                            'unit' => $val['unit'],
                            'time' => $val['time'],
                            'timeDate' => $val['timeDate'],
                            'type' => 'LP',
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->detailAddress) {

                foreach ($arrayDetailAddress as $val) {

                    DB::table('customerAddress')
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
                            'updated_at' => now(),
                        ]);
                }
            }


            if ($request->hasfile('image')) {

                $files = $request->file('image');

                $name = $files->hashName();
                $files->move(public_path() . '/PetImages/', $name);

                $fileName = "/PetImages/" . $name;

                DB::table('petImages')
                    ->insert([
                        'usersId' => $lastInsertedID,
                        'imagePath' => $fileName,
                        'isDeleted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
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
                            'updated_at' => now(),
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
                            'updated_at' => now(),
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
                            'updated_at' => now(),
                        ]);
                }
            }


            DB::commit();

            return response()->json(
                [
                    'result' => 'success',
                    'message' => 'Insert Data Customer Successful!',
                ],
                200
            );

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ], 422);
        }
    }

    public function getSourceCustomer(Request $request)
    {

        try {

            $getSourceCustomer = DB::table('sourceCustomer as a')
                ->select(
                    'a.id as sourceCustomerId',
                    'a.sourceName as sourceCustomerName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getSourceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }

    public function insertSourceCustomer(Request $request)
    {

        $request->validate([
            'sourceName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('sourceCustomer as a')
                ->where([
                    ['a.sourceName', '=', $request->sourceName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Source Customer title already exists, please choose another name',
                ]);
            } else {

                DB::table('sourceCustomer')->insert([
                    'sourceName' => $request->sourceName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Source Customer',
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

    public function getReferenceCustomer(Request $request)
    {

        try {

            $getRefrenceCustomer = DB::table('referenceCustomer as a')
                ->select(
                    'a.id as referenceCustomerId',
                    'a.referenceName as referenceCustomerName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getRefrenceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }

    public function insertReferenceCustomer(Request $request)
    {

        $request->validate([
            'referenceName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('referenceCustomer as a')
                ->where([
                    ['a.referenceName', '=', $request->sourceName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Reference Customer title already exists, please choose another name',
                ]);
            } else {

                DB::table('referenceCustomer')->insert([
                    'referenceName' => $request->sourceName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Reference Customer',
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

    public function getTitleCustomer(Request $request)
    {

        try {

            $getRefrenceCustomer = DB::table('titleCustomer as a')
                ->select(
                    'a.id as titleCustomerId',
                    'a.titleName as titleCustomerName',
                )
                ->where([
                    ['isActive', '=', 1],
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            return response()->json($getRefrenceCustomer, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ], 422);
        }
    }

    public function insertTitleCustomer(Request $request)
    {

        $request->validate([
            'titleName' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('titleCustomer as a')
                ->where([
                    ['a.titleName', '=', $request->sourceName],
                    ['a.isActive', '=', 1]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Title Customer title already exists, please choose another name',
                ]);
            } else {

                DB::table('titleCustomer')->insert([
                    'titleName' => $request->sourceName,
                    'created_at' => now(),
                    'isActive' => 1,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted Title Customer',
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
}
