<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Support\Facades\Event;
use App\Models\PushNotification\PushNotification;
use App\Exports\exportValue;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Location;
use Validator;
use DB;
use File;
use PDF;
use App\Imports\Location\LocationImport;
use App\Models\Location\LocationDetailAddress;
use App\Models\Location\LocationEmail;
use App\Models\Location\LocationOperational;
use App\Models\Location\LocationTelephone;
use App\Models\Staff\UsersLocation;
use Carbon\Carbon;

class LocationController extends Controller
{

    public function exportLocation(Request $request)
    {

        return Excel::download(new exportValue, 'Location.xlsx');
    }

    private function ValidateImportSheet1($value, $count_row)
    {

        if ($value['name'] == "") {
            return 'There is any empty cell on column Nama at row ' . $count_row;
        }

        if ($value['status'] != 1 && $value['status'] != 0) {
            return 'There is any empty cell on column Status at row ' . $count_row;
        }

        if ($value['monday_senin'] == "") {
            return 'There is any empty cell on column Monday / Senin at row ' . $count_row;
        }

        if ($value['tuesday_selasa'] == "") {
            return 'There is any empty cell on column Tuesday / Selasa at row ' . $count_row;
        }

        if ($value['wednesday_rabu'] == "") {
            return 'There is any empty cell on column Wednesday / Rabu at row ' . $count_row;
        }

        if ($value['thursday_kamis'] == "") {
            return 'There is any empty cell on column Thursday / Kamis at row ' . $count_row;
        }

        if ($value['friday_jumat'] == "") {
            return 'There is any empty cell on column Friday / Jumat at row ' . $count_row;
        }

        if ($value['saturday_sabtu'] == "") {
            return 'There is any empty cell on column Saturday / Sabtu at row ' . $count_row;
        }

        if ($value['sunday_minggu'] == "") {
            return 'There is any empty cell on column Sunday / Minggu at row ' . $count_row;
        }
    }

    private function ValidateImportSheet2($value, $count_row)
    {

        if ($value['id'] == "") {
            return 'There is any empty cell on column Id at row ' . $count_row;
        }

        if ($value['street_address'] == "") {
            return 'There is any empty cell on column Street_address at row ' . $count_row;
        }

        if ($value['country'] == "") {
            return 'There is any empty cell on column Country at row ' . $count_row;
        }

        if ($value['province'] == "") {
            return 'There is any empty cell on column Province at row ' . $count_row;
        } else {
            $find = DB::table('provinsi')
                ->where('kodeProvinsi', '=', $value['province'])
                ->first();

            if (!$find) {
                return 'There is any invalid Province at row ' . $count_row;
            }
        }

        if ($value['city'] == "") {
            return 'There is any empty cell on column City at row ' . $count_row;
        } else {

            $find = DB::table('kabupaten')
                ->where('kodeKabupaten', '=', $value['city'])
                ->first();

            if (!$find) {
                return 'There is any invalid City at row ' . $count_row;
            }
        }

        if ($value['postal_code'] == "") {
            return 'There is any empty cell on column Postal Code at row ' . $count_row;
        }

        if ($value['alamat_utama'] != 1 && $value['alamat_utama'] != 0) {
            return 'There is any empty cell on column Alamat Utama at row ' . $count_row;
        }
    }

    private function ValidateImportSheet3($value, $count_row)
    {

        if ($value['id'] == "") {
            return 'There is any empty cell on column Id at row ' . $count_row;
        }

        if ($value['usage'] == "") {
            return 'There is any empty cell on column Street_address at row ' . $count_row;
        }

        if ($value['nomor'] == "") {
            return 'There is any empty cell on column Country at row ' . $count_row;
        }

        if ($value['type'] == "") {
            return 'There is any empty cell on column Postal Code at row ' . $count_row;
        }
    }

    private function ValidateImportSheet4($value, $count_row)
    {

        if ($value['id'] == "") {
            return 'There is any empty cell on column Id at row ' . $count_row;
        }

        if ($value['usage'] == "") {
            return 'There is any empty cell on column Street_address at row ' . $count_row;
        }

        if ($value['address'] == "") {
            return 'There is any empty cell on column Country at row ' . $count_row;
        }
    }

    private function processTime($time)
    {
        $array_time = explode('-', $time);

        $time1 = Carbon::createFromFormat('H:i', trim($array_time[0]));
        $timeFormatted1 = $time1->format('g:i A');

        $time2 = Carbon::createFromFormat('H:i', trim($array_time[1]));
        $timeFormatted2 = $time2->format('g:i A');

        $timeString1 = (string) $timeFormatted1;
        $timeString2 = (string) $timeFormatted2;

        $timeString1 = str_replace(' ', '', $timeString1);
        $timeString2 = str_replace(' ', '', $timeString2);

        return  [$timeString1, $timeString2];
    }

    public function import(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'errors' => 'The given data was invalid.',
                'message' => $errors,
            ], 422);
        }

        $id = $request->user()->id;

        $lastCodeLocation = DB::table('location')
            ->select(
                'codeLocation'
            )
            ->orderBy('id', 'desc')
            ->first();

        if (count($lastCodeLocation) == 0) {
            $lastCodeLocation = 'ABC1';
        } else {
            $lastCodeLocation = str_replace('ABC', '', $lastCodeLocation->codeLocation);
        }



        // return $lastCodeLocation;

        $locationObj = [];
        $locationOprObj = [];
        $addressObj = [];
        $phoneObj = [];
        $emailObj = [];

        $rows = Excel::toArray(new LocationImport($id), $request->file('file'));
        $src = $rows[0];

        $count_row = 1;

        if ($src) {

            foreach ($src as $value) {
                if ($count_row != 1) {

                    $res = $this->ValidateImportSheet1($value, $count_row);

                    if ($res != "") {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => [$res],
                        ], 422);
                    } else {

                        $newObject1 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'locationName' => $value['name'], 'status' => $value['status'], 'description' => ''];
                        $locationObj[] = $newObject1;

                        if ($value['monday_senin'][0] == 1) {

                            $array = explode(';', $value['monday_senin']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Monday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        if ($value['tuesday_selasa'][0] == 1) {

                            $array = explode(';', $value['tuesday_selasa']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Tuesday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        if ($value['wednesday_rabu'][0] == 1) {

                            $array = explode(';', $value['wednesday_rabu']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Wednesday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        if ($value['thursday_kamis'][0] == 1) {

                            $array = explode(';', $value['thursday_kamis']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Thursday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        if ($value['friday_jumat'][0] == 1) {

                            $array = explode(';', $value['friday_jumat']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Friday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        if ($value['saturday_sabtu'][0] == 1) {

                            $array = explode(';', $value['saturday_sabtu']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Saturday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        if ($value['sunday_minggu'][0] == 1) {

                            $array = explode(';', $value['sunday_minggu']);

                            if ($array[0] == 1) {

                                [$time1, $time2] = $this->processTime($array[1]);

                                $newObject2 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'dayName' => 'Sunday', 'fromTime' => trim($time1), 'toTime' => trim($time2), 'allDay' => 1];
                                $locationOprObj[] = $newObject2;
                            }
                        }

                        $lastCodeLocation++;
                    }
                }

                $count_row++;
            }
        }

        $src = $rows[1];

        $count_row = 1;

        if ($src) {

            foreach ($src as $value) {
                if ($count_row != 1) {

                    $res = $this->ValidateImportSheet2($value, $count_row);

                    if ($res != "") {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => [$res],
                        ], 422);
                    }

                    $additionalInfo = '';
                    if ($value['additional_info']) {

                        $additionalInfo = $value['additional_info'];
                    }

                    $newObject3 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'addressName' => $value['street_address'], 'additionalInfo' => $additionalInfo, 'provinceCode' => $value['province'], 'cityCode' => $value['city'], 'postalCode' => $value['postal_code'], 'country' => $value['country'], 'isPrimary' => 1];
                    $addressObj[] = $newObject3;
                }

                $count_row++;
            }
        }

        $src = $rows[2];

        $count_row = 1;

        if ($src) {

            foreach ($src as $value) {

                $res = $this->ValidateImportSheet3($value, $count_row);

                if ($res != "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => [$res],
                    ], 422);
                }

                $number = $value['nomor'];

                $number = substr_replace($number, '+62', 0, 1);

                $newObject4 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'phoneNumber' => $number, 'type' => $value['type'], 'usage' => 'Utama'];
                $phoneObj[] = $newObject4;

                $count_row++;
            }
        }

        $src = $rows[3];

        $count_row = 1;

        if ($src) {

            foreach ($src as $value) {

                $res = $this->ValidateImportSheet4($value, $count_row);

                if ($res != "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => [$res],
                    ], 422);
                }

                $newObject5 = (object)['codeLocation' => 'ABC' . $lastCodeLocation, 'username' => $value['address'], 'usage' => 'Utama'];
                $emailObj[] = $newObject5;

                $count_row++;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($locationObj as $value) {

                Location::create([
                    'codeLocation' => $value->codeLocation,
                    'locationName' => $value->locationName,
                    'status' => $value->status,
                    'description' => $value->description,
                    'isDeleted' => 0,
                ]);
            }

            foreach ($locationOprObj as $value) {

                LocationOperational::create([
                    'codeLocation' => $value->codeLocation,
                    'dayName' => $value->dayName,
                    'fromTime' => $value->fromTime,
                    'toTime' => $value->toTime,
                    'allDay' => 1,
                    'isDeleted' => 0,
                ]);
            }

            foreach ($addressObj as $value) {
                LocationDetailAddress::create([
                    'codeLocation' => $value->codeLocation,
                    'addressName' => $value->addressName,
                    'additionalInfo' => $value->additionalInfo,
                    'provinceCode' => $value->provinceCode,
                    'cityCode' => $value->cityCode,
                    'postalCode' => $value->postalCode,
                    'country' => $value->country,
                    'isPrimary' => $value->isPrimary,
                    'isDeleted' => 0,
                ]);
            }

            foreach ($phoneObj as $value) {

                LocationTelephone::create([
                    'codeLocation' => $value->codeLocation,
                    'phoneNumber' => $value->phoneNumber,
                    'type' => $value->type,
                    'usage' => $value->usage,
                    'isDeleted' => 0,
                ]);
            }

            foreach ($emailObj as $value) {

                LocationEmail::create([
                    'codeLocation' => $value->codeLocation,
                    'username' => $value->username,
                    'usage' => $value->usage,
                    'isDeleted' => 0,
                ]);
            }

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function deleteLocation(Request $request)
    {

        if (!adminAccess($request->user()->id)) {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['User Access not Authorize!'],
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'codeLocation' => 'required',
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

            // $message = 'cuman testing data saja';
            // $type = 'error';
            // Event::dispatch(new MessageCreated($message, $type));

            // $pushNotification = new PushNotification();
            // $pushNotification->usersId =  $request->user()->id;
            // $pushNotification->menuName = 'facility';
            // $pushNotification->message = $message;
            // $pushNotification->type = $type;
            // $pushNotification->save();

            // DB::commit();

            $data_item = [];
            foreach ($request->codeLocation as $val) {

                $checkIfDataExits = DB::table('location')
                    ->where([
                        ['codeLocation', '=', $val],
                        ['isDeleted', '=', '0']
                    ])
                    ->first();

                if (!$checkIfDataExits) {
                    array_push($data_item, 'code location : ' . $val . ' not found, please try different code location');
                }
            }

            if ($data_item) {
                return response()->json([
                    'message' => 'Inputed data is not valid',
                    'errors' => $data_item,
                ], 422);
            }

            foreach ($request->codeLocation as $val) {

                DB::table('location')
                    ->where('codeLocation', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('location_detail_address')
                    ->where('codeLocation', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('location_images')
                    ->where('codeLocation', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('location_email')
                    ->where('codeLocation', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('location_messenger')
                    ->where('codeLocation', '=', $val)
                    ->update(['isDeleted' => 1]);

                DB::table('location_telephone')
                    ->where('codeLocation', '=', $val)
                    ->update(['isDeleted' => 1]);



                $checkImages = DB::table('location_images')
                    ->where([
                        ['codeLocation', '=', $val]
                    ])
                    ->first();

                if ($checkImages != null) {
                    File::delete(public_path() . $checkImages->imagePath);
                }

                DB::commit();
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted location',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function uploadexceltest(Request $request) {}

    public function uploadImageLocation(Request $request)
    {

        try {

            $json_array = json_decode($request->imagesName, true);
            $files[] = $request->file('images');
            $index = 0;

            foreach ($json_array as $val) {


                if (($val['id'] == "" || $val['id'] == 0)  && ($val['status'] == "")) {

                    $name = $files[0][$index]->hashName();

                    $files[0][$index]->move(public_path() . '/LocationImages/', $name);

                    $fileName = "/LocationImages/" . $name;

                    DB::table('location_images')
                        ->insert([
                            'codeLocation' => $request->input('codeLocation'),
                            'labelName' => $val['name'],
                            'realImageName' => $files[0][$index]->getClientOriginalName(),
                            'imageName' => $name,
                            'imagePath' => $fileName,
                            'isDeleted' => 0,
                            'created_at' => now(),
                        ]);

                    $index = $index + 1;
                } elseif (($val['id'] != "" && $val['id'] != 0)  && ($val['status'] == "del")) {


                    $find_image = DB::table('location_images')
                        ->select(
                            'location_images.imageName',
                            'location_images.imagePath'
                        )
                        ->where('id', '=', $val['id'])
                        ->where('codeLocation', '=', $request->input('codeLocation'))
                        ->first();

                    if ($find_image) {

                        if (file_exists(public_path() . $find_image->imagePath)) {

                            File::delete(public_path() . $find_image->imagePath);

                            DB::table('location_images')->where([
                                ['codeLocation', '=', $request->input('codeLocation')],
                                ['id', '=', $val['id']]
                            ])->delete();
                        }
                    }
                } elseif (($val['id'] != "" || $val['id'] != 0)  && ($val['status'] == "")) {

                    $find_image = DB::table('location_images')
                        ->select(
                            'location_images.imageName',
                            'location_images.imagePath'
                        )
                        ->where('id', '=', $val['id'])
                        ->where('codeLocation', '=', $request->input('codeLocation'))
                        ->first();

                    if ($find_image) {

                        DB::table('location_images')
                            ->where([
                                ['codeLocation', '=', $request->input('codeLocation')],
                                ['id', '=', $val['id']]
                            ])
                            ->update([
                                'labelName' => $val['name'],
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'successfuly update image location',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function updateLocation(Request $request)
    {

        DB::beginTransaction();
        try {

            if (!adminAccess($request->user()->id)) {
                return response()->json([
                    'message' => 'The user role was invalid.',
                    'errors' => ['User Access not Authorize!'],
                ], 403);
            }


            $messages = [
                'locationName.required' => 'Please insert location name, location name is required',
                'locationName.max' => 'Exceeded maximum character, max character for location name is 50',
                'status.required' => 'Please insert status location, status location is required',
                'description.required' => 'Overview on Tab Description is required!',
            ];

            $validate = Validator::make(
                $request->all(),
                [
                    'locationName' => 'required|max:50',
                    'status' => 'required',
                    'description' => 'required',
                ],
                $messages
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkdataLocation = DB::table('location')
                ->select('locationName')
                ->where([
                    ['locationName', '=', $request->locationName],
                    ['codeLocation', '<>', $request->codeLocation],
                    ['isDeleted', '=', '0'],
                ])
                ->first();


            if ($checkdataLocation) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Location ' . $checkdataLocation->locationName . ' Already Exist on Location, please try different name !'],
                ], 422);
            }

            $data_error_address = [];

            if ($request->detailAddress) {

                $messageAddress = [
                    'addressName.required' => 'Address name on tab Address is required',
                    'provinceCode.required' => 'Province code on tab Address is required',
                    'cityCode.required' => 'City code on tab Address is required',
                    'country.required' => 'Country on tab Address is required',
                ];

                $primaryCount = 0;
                foreach ($request->detailAddress as $item) {
                    if (isset($item['isPrimary']) && $item['isPrimary'] == 1) {
                        $primaryCount++;
                    }
                }

                if ($primaryCount == 0) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => 'Detail address must have at least 1 primary address',
                    ], 422);
                } elseif ($primaryCount > 1) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => 'Detail address have 2 primary address, please check again',
                    ], 422);
                }

                foreach ($request->detailAddress as $key) {

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

                            if (!(in_array($checkisu, $data_error_address))) {
                                array_push($data_error_address, $checkisu);
                            }
                        }
                    }
                }

                if ($data_error_address) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_address,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }


            $data_error_telephone = [];

            if ($request->telephone) {

                $messagePhone = [
                    'phoneNumber.required' => 'Phone Number on tab telephone is required',
                    'type.required' => 'Type on tab telephone is required',
                    'usage.required' => 'Usage on tab telephone is required',
                ];

                foreach ($request->telephone as $key) {

                    $telephoneDetail = Validator::make(
                        $key,
                        [
                            'phoneNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messagePhone
                    );

                    if ($telephoneDetail->fails()) {

                        $errors = $telephoneDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_telephone))) {
                                array_push($data_error_telephone, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['phoneNumber'], 0, 2) === "62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }

                if ($data_error_telephone) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_telephone,
                    ], 422);
                }
            }


            $data_error_email = [];
            if ($request->email) {

                $messageEmail = [
                    'username.required' => 'username on tab email is required',
                    'usage.required' => 'Usage on tab email is required',
                ];


                foreach ($request->email as $key) {

                    $emailDetail = Validator::make(
                        $key,
                        [
                            'username' => 'required',
                            'usage' => 'required',
                        ],
                        $messageEmail
                    );

                    if ($emailDetail->fails()) {

                        $errors = $emailDetail->errors()->all();

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
            }



            $data_error_messenger = [];
            if ($request->messenger) {

                $messageMessenger = [
                    'messengerNumber.required' => 'messenger number on tab messenger is required',
                    'type.required' => 'Type on tab messenger is required',
                    'usage.required' => 'Usage on tab messenger is required',
                ];

                foreach ($request->messenger as $key) {

                    $messengerDetail = Validator::make(
                        $key,
                        [
                            'messengerNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messageMessenger
                    );

                    if ($messengerDetail->fails()) {

                        $errors = $messengerDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_messenger))) {
                                array_push($data_error_messenger, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['messageMessenger'], 0, 2) === "62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with 62',
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
            }

            DB::table('location')
                ->where('codeLocation', '=', $request->input('codeLocation'))
                ->update([
                    'locationName' => $request->input('locationName'),
                    'description' => $request->input('description'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);

            if ($request->detailAddress) {

                DB::table('location_detail_address')->where('codeLocation', '=', $request->input('codeLocation'))->delete();

                foreach ($request->detailAddress as $val) {

                    DB::table('location_detail_address')
                        ->insert([
                            'codeLocation' => $request->input('codeLocation'),
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


            if ($request->operationalHour > 0) {

                DB::table('location_operational')->where('codeLocation', '=', $request->input('codeLocation'))->delete();

                if (count($request->operationalHour) != 0) {
                    foreach ($request->operationalHour as $val) {
                        DB::table('location_operational')
                            ->insert([
                                'codeLocation' => $request->input('codeLocation'),
                                'dayName' => $val['dayName'],
                                'fromTime' => $val['fromTime'],
                                'toTime' => $val['toTime'],
                                'allDay' => $val['allDay'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }


            if ($request->messenger) {

                DB::table('location_messenger')->where('codeLocation', '=', $request->input('codeLocation'))->delete();

                foreach ($request->messenger as $val) {
                    DB::table('location_messenger')
                        ->insert([
                            'codeLocation' => $request->input('codeLocation'),
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

                DB::table('location_email')->where('codeLocation', '=', $request->input('codeLocation'))->delete();

                foreach ($request->email as $val) {
                    DB::table('location_email')
                        ->insert([
                            'codeLocation' => $request->input('codeLocation'),
                            'username' => $val['username'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->telephone) {

                DB::table('location_telephone')->where('codeLocation', '=', $request->input('codeLocation'))->delete();

                foreach ($request->telephone as $val) {
                    DB::table('location_telephone')
                        ->insert([
                            'codeLocation' => $request->input('codeLocation'),
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
                'message' => 'successfuly update data',
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function cetak_pdf()
    {
        $location = Location::all();

        $pdf = PDF::loadview('/pdf/location', ['location' => $location]);
        return $pdf->download('laporan-location-pdf');
    }

    public function insertLocation(Request $request)
    {
        DB::beginTransaction();

        try {

            if (!adminAccess($request->user()->id)) {
                return response()->json([
                    'message' => 'The user role was invalid.',
                    'errors' => ['User Access not Authorize!'],
                ], 403);
            }


            $getvaluesp = strval(collect(DB::select('call generate_codeLocation'))[0]->randomString);

            $messages = [
                'locationName.required' => 'Please insert location name, location name is required',
                'locationName.max' => 'Exceeded maximum character, max character for location name is 50',
                'status.required' => 'Please insert status location, status location is required',
                'description.required' => 'Overview on Tab Description is required!',
            ];

            $validate = Validator::make(
                $request->all(),
                [
                    'locationName' => 'required|max:50',
                    'status' => 'required',
                    'description' => 'required',
                ],
                $messages
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkdataLocation = DB::table('location')
                ->select('locationName')
                ->where([
                    ['locationName', '=', $request->locationName],
                    ['isDeleted', '=', '0'],
                ])
                ->first();

            if ($checkdataLocation) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Location ' . $checkdataLocation->locationName . ' Already Exist on Location, please try different name !'],
                ], 422);
            }


            $data_error_address = [];
            if ($request->detailAddress) {

                $arrayDetailAddress = json_decode($request->detailAddress, true);

                $messageAddress = [
                    'addressName.required' => 'Address name on tab Address is required',
                    'provinceCode.required' => 'Province code on tab Address is required',
                    'cityCode.required' => 'City code on tab Address is required',
                    'country.required' => 'Country on tab Address is required',
                ];

                $primaryCount = 0;
                foreach ($arrayDetailAddress as $item) {
                    if (isset($item['isPrimary']) && $item['isPrimary'] == 1) {
                        $primaryCount++;
                    }
                }

                if ($primaryCount == 0) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => 'Detail address must have at least 1 primary address',
                    ], 422);
                } elseif ($primaryCount > 1) {
                    return response()->json([
                        'message' => 'Inputed data is not valid',
                        'errors' => 'Detail address have 2 primary address, please check again',
                    ], 422);
                }


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

                            if (!(in_array($checkisu, $data_error_address))) {
                                array_push($data_error_address, $checkisu);
                            }
                        }
                    }
                }


                if ($data_error_address) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_address,
                    ], 422);
                }
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Detail address can not be empty!'],
                ], 422);
            }



            $data_error_telephone = [];

            if ($request->telephone) {

                $arraytelephone = json_decode($request->telephone, true);

                $messagePhone = [
                    'phoneNumber.required' => 'Phone Number on tab telephone is required',
                    'type.required' => 'Type on tab telephone is required',
                    'usage.required' => 'Usage on tab telephone is required',
                ];


                foreach ($arraytelephone as $key) {

                    $telephoneDetail = Validator::make(
                        $key,
                        [
                            'phoneNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messagePhone
                    );

                    if ($telephoneDetail->fails()) {

                        $errors = $telephoneDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_telephone))) {
                                array_push($data_error_telephone, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['phoneNumber'], 0, 2) === "62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }


                if ($data_error_telephone) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_telephone,
                    ], 422);
                }
            }



            $data_error_email = [];

            if ($request->email) {

                $arrayemail = json_decode($request->email, true);

                $messageEmail = [
                    'username.required' => 'username on tab email is required',
                    'usage.required' => 'Usage on tab email is required',
                ];

                foreach ($arrayemail as $key) {

                    $emailDetail = Validator::make(
                        $key,
                        [
                            'username' => 'required',
                            'usage' => 'required',
                        ],
                        $messageEmail
                    );


                    if ($emailDetail->fails()) {

                        $errors = $emailDetail->errors()->all();

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
            }



            $data_error_email = [];
            if ($request->messenger) {

                $arraymessenger = json_decode($request->messenger, true);

                $messageMessenger = [
                    'messengerNumber.required' => 'messenger number on tab messenger is required',
                    'type.required' => 'Type on tab messenger is required',
                    'usage.required' => 'Usage on tab messenger is required',
                ];

                foreach ($arraymessenger as $key) {

                    $messengerDetail = Validator::make(
                        $key,
                        [
                            'messengerNumber' => 'required',
                            'type' => 'required',
                            'usage' => 'required',
                        ],
                        $messageMessenger
                    );

                    if ($messengerDetail->fails()) {

                        $errors = $messengerDetail->errors()->all();

                        foreach ($errors as $checkisu) {

                            if (!(in_array($checkisu, $data_error_email))) {
                                array_push($data_error_email, $checkisu);
                            }
                        }
                    }

                    if (strtolower($key['type']) == "whatshapp") {

                        if (!(substr($key['messageMessenger'], 0, 2) === "62")) {
                            return response()->json([
                                'message' => 'Inputed data is not valid',
                                'errors' => 'Please check your phone number, for type whatshapp must start with 62',
                            ], 422);
                        }
                    }
                }

                if ($data_error_email) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $data_error_email,
                    ], 422);
                }
            }

            $flag = false;

            if ($request->hasfile('images')) {

                $flag = true;

                $data_item = [];

                $files[] = $request->file('images');

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $file_size = $fil->getSize();

                        $file_size = $file_size / 1024;

                        $oldname = $fil->getClientOriginalName();

                        if ($file_size >= 5000) {

                            array_push($data_item, 'Photo ' . $oldname . ' size more than 5mb! Please upload less than 5mb!');
                        }
                    }
                }

                if ($data_item) {

                    return response()->json([
                        'message' => 'Inputed photo is not valid',
                        'errors' => $data_item,
                    ], 422);
                }
            }

            if ($flag == true) {
                if ($request->imagesName) {
                    $ResultImageDatas = json_decode($request->imagesName, true);

                    foreach ($ResultImageDatas as $value) {

                        if ($value['name'] == "") {

                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Image name can not be empty!'],
                            ], 422);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Image name can not be empty!'],
                    ], 422);
                }
            }

            DB::table('location')->insert([
                'codeLocation' => $getvaluesp,
                'locationName' => $request->input('locationName'),
                'status' => $request->input('status'),
                'description' => $request->input('description'),
                'isDeleted' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->detailAddress) {

                foreach ($arrayDetailAddress as $val) {

                    DB::table('location_detail_address')
                        ->insert([
                            'codeLocation' => $getvaluesp,
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

            if ($request->hasfile('images')) {

                $json_array = json_decode($request->imagesName, true);
                $int = 0;

                if (count($files) != 0) {

                    foreach ($files as $file) {

                        foreach ($file as $fil) {

                            $name = $fil->hashName();
                            $fil->move(public_path() . '/LocationImages/', $name);

                            $fileName = "/LocationImages/" . $name;

                            DB::table('location_images')
                                ->insert([
                                    'codeLocation' => $getvaluesp,
                                    'labelName' => $json_array[$int]['name'],
                                    'realImageName' => $fil->getClientOriginalName(),
                                    'imageName' => $name,
                                    'imagePath' => $fileName,
                                    'isDeleted' => 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                            $int = $int + 1;
                        }
                    }
                }
            }

            if ($request->operationalHour) {

                $arrayoperationalHour = json_decode($request->operationalHour, true);

                if (count($arrayoperationalHour) != 0) {

                    foreach ($arrayoperationalHour as $val) {

                        DB::table('location_operational')
                            ->insert([
                                'codeLocation' => $getvaluesp,
                                'dayName' => $val['dayName'],
                                'fromTime' => $val['fromTime'],
                                'toTime' => $val['toTime'],
                                'allDay' => $val['allDay'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            if ($request->messenger) {

                foreach ($arraymessenger as $val) {

                    DB::table('location_messenger')
                        ->insert([
                            'codeLocation' => $getvaluesp,
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

                    DB::table('location_email')
                        ->insert([
                            'codeLocation' => $getvaluesp,
                            'username' => $val['username'],
                            'usage' => $val['usage'],
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($request->telephone) {

                foreach ($arraytelephone as $val) {

                    DB::table('location_telephone')
                        ->insert([
                            'codeLocation' => $getvaluesp,
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
                'message' => "Successfuly insert new location",
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function getLocationHeader(Request $request)
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('kabupaten', 'kabupaten.kodeKabupaten', '=', 'location_detail_address.cityCode')
            ->select(
                'location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location_detail_address.addressName as addressName',
                'kabupaten.namaKabupaten as cityName',
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                DB::raw("CASE WHEN location.status=1 then 'Active' else 'Non Active' end as status"),
            )
            ->where([
                ['location_detail_address.isPrimary', '=', '1'],
                ['location_telephone.usage', '=', 'utama'],
                ['location.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $res = $this->Search($request);

            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
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
                'codeLocation',
                'locationName',
                'addressName',
                'cityName',
                'phoneNumber',
                'status',
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

            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('location.updated_at', 'desc');

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
    }

    private function Search($request)
    {
        $columntable = '';

        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('kabupaten', 'kabupaten.kodeKabupaten', '=', 'location_detail_address.cityCode')
            ->select(
                'location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location_detail_address.addressName as addressName',
                'kabupaten.namaKabupaten as cityName',
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                'location.status as status',
            )
            ->where([
                ['location_detail_address.isPrimary', '=', '1'],
                ['location_telephone.usage', '=', 'utama'],
                ['location.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('location.locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location.locationName';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('kabupaten', 'kabupaten.kodeKabupaten', '=', 'location_detail_address.cityCode')
            ->select(
                'location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location_detail_address.addressName as addressName',
                'kabupaten.namaKabupaten as cityName',
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                'location.status as status',
            )
            ->where([
                ['location_detail_address.isPrimary', '=', '1'],
                ['location_telephone.usage', '=', 'utama'],
                ['location.isDeleted', '=', '0'],
            ]);

        if ($request->search) {

            $data = $data->where('location_detail_address.addressName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location_detail_address.addressName';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('kabupaten', 'kabupaten.kodeKabupaten', '=', 'location_detail_address.cityCode')
            ->select(
                'location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location_detail_address.addressName as addressName',
                'kabupaten.namaKabupaten as cityName',
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                'location.status as status',
            )
            ->where([
                ['location_detail_address.isPrimary', '=', '1'],
                ['location_telephone.usage', '=', 'utama'],
                ['location.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('kabupaten.namaKabupaten', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'kabupaten.namaKabupaten';
            return $temp_column;
        }


        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('kabupaten', 'kabupaten.kodeKabupaten', '=', 'location_detail_address.cityCode')
            ->select(
                'location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location_detail_address.addressName as addressName',
                'kabupaten.namaKabupaten as cityName',
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                'location.status as status',
            )
            ->where([
                ['location_detail_address.isPrimary', '=', '1'],
                ['location_telephone.usage', '=', 'utama'],
                ['location.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('kabupaten.namaKabupaten', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'kabupaten.namaKabupaten';
            return $temp_column;
        }

        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('kabupaten', 'kabupaten.kodeKabupaten', '=', 'location_detail_address.cityCode')
            ->select(
                'location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location_detail_address.addressName as addressName',
                'kabupaten.namaKabupaten as cityName',
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                'location.status as status',
            )
            ->where([
                ['location_detail_address.isPrimary', '=', '1'],
                ['location_telephone.usage', '=', 'utama'],
                ['location.isDeleted', '=', '0'],
            ]);

        if ($request->search) {
            $data = $data->where('location.status', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'location.status as status';
            return $temp_column;
        }
    }

    public function searchImageLocation(Request $request)
    {

        $request->validate(['codeLocation' => 'required|max:10000']);

        $checkIfValueExits = DB::table('location_images')
            ->where([
                ['location_images.codeLocation', '=', $request->input('codeLocation')],
                ['location_images.isDeleted', '=', '0']
            ])
            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists",
            ]);
        } else {

            $images = DB::table('location_images')
                ->select(
                    'location_images.id as id',
                    'location_images.labelName as labelName',
                    'location_images.imagePath as imagePath',
                )
                ->where([
                    ['location_images.codeLocation', '=', $request->input('codeLocation')],
                    ['location_images.isDeleted', '=', '0']
                ]);

            if ($request->name) {
                $res = $this->SearchImages($request);

                if ($res) {
                    $images = $images->where($res, 'like', '%' . $request->name . '%');
                } else {
                    $images = [];
                    return response()->json($images, 200);
                }
            }
            $images = $images->orderBy('location_images.created_at', 'desc');
            $images = $images->get();
            return response()->json(['images' => $images], 200);
        }
    }

    private function SearchImages($request)
    {

        $data = DB::table('location_images')
            ->select(
                'location_images.labelName as labelName',
                'location_images.realImageName as realImageName',
                'location_images.imageName as imageName',
                'location_images.imagePath as imagePath',
            )
            ->where([
                ['location_images.codeLocation', '=', $request->codeLocation],
                ['location_images.isDeleted', '=', '0']
            ]);

        if ($request->name) {
            $data = $data->where('location_images.labelName', 'like', '%' . $request->name . '%');
        }

        $data = $data->get();
    }

    public function getLocationDetail(Request $request)
    {
        $request->validate(['codeLocation' => 'required|max:10000']);
        $codeLocation = $request->input('codeLocation');

        $checkIfValueExits = DB::table('location')
            ->where([
                ['location.codeLocation', '=', $request->input('codeLocation')],
                ['location.isDeleted', '=', '0']
            ])

            ->first();

        if ($checkIfValueExits === null) {

            return response()->json([
                'result' => 'Failed',
                'message' => "Data not exists, please try another location code",
            ]);
        } else {

            $param_location = DB::table('location')
                ->select(
                    'location.codeLocation as codeLocation',
                    'location.locationName as locationName',
                    'location.status as status',
                    'location.description as description',
                )
                ->where('location.codeLocation', '=', $codeLocation)
                ->first();

            $location_images = DB::table('location_images')
                ->select(
                    'location_images.id as id',
                    'location_images.labelName as labelName',
                    'location_images.imagePath as imagePath',
                )
                ->where([
                    ['location_images.codeLocation', '=', $codeLocation],
                    ['location_images.isDeleted', '=', '0']
                ])
                ->get();

            $param_location->images = $location_images;

            $location_detail_address = DB::table('location_detail_address')
                ->select(
                    'location_detail_address.addressName as addressName',
                    'location_detail_address.additionalInfo as additionalInfo',
                    'location_detail_address.provinceCode as provinceCode',
                    'location_detail_address.cityCode as cityCode',
                    'location_detail_address.postalCode as postalCode',
                    'location_detail_address.country as country',
                    'location_detail_address.isPrimary as isPrimary',
                )
                ->where([
                    ['location_detail_address.codeLocation', '=', $codeLocation],
                    ['location_detail_address.isDeleted', '=', '0']
                ])
                ->get();

            $param_location->detailAddress = $location_detail_address;

            $operationalHour = DB::table('location_operational')
                ->select(
                    'location_operational.dayName as dayName',
                    'location_operational.fromTime as fromTime',
                    'location_operational.toTime as toTime',
                    'location_operational.allDay as allDay',
                )
                ->where([['location_operational.codeLocation', '=', $codeLocation]])
                ->get();

            $param_location->operationalHour = $operationalHour;

            $messenger_location = DB::table('location_messenger')
                ->select(
                    'location_messenger.messengerNumber as messengerNumber',
                    'location_messenger.type as type',
                    'location_messenger.usage as usage',
                )
                ->where([
                    ['location_messenger.codeLocation', '=', $codeLocation],
                    ['location_messenger.isDeleted', '=', '0']
                ])
                ->get();

            $param_location->messenger = $messenger_location;

            $email_location = DB::table('location_email')
                ->select(
                    'location_email.username as username',
                    'location_email.usage as usage',
                )
                ->where([
                    ['location_email.codeLocation', '=', $codeLocation],
                    ['location_email.isDeleted', '=', '0']
                ])
                ->get();

            $param_location->email = $email_location;

            $telepon_location = DB::table('location_telephone')
                ->select(
                    'location_telephone.phoneNumber as phoneNumber',
                    'location_telephone.type as type',
                    'location_telephone.usage as usage',
                )
                ->where([
                    ['location_telephone.codeLocation', '=', $codeLocation],
                    ['location_telephone.isDeleted', '=', '0']
                ])
                ->get();

            $param_location->telephone = $telepon_location;

            return response()->json($param_location, 200);
        }
    }

    public function getDataStaticLocation(Request $request)
    {

        try {

            $param_location = [];

            $data_static_telepon = DB::table('data_static')
                ->select(
                    'data_static.value as value',
                    'data_static.name as name',
                )
                ->where('data_static.value', '=', 'Telephone')
                ->get();

            $data_static_messenger = DB::table('data_static')
                ->select(
                    'data_static.value as value',
                    'data_static.name as name',
                )
                ->where('data_static.value', '=', 'messenger')
                ->get();

            $dataStaticUsage = DB::table('data_static')
                ->select(
                    'data_static.value as value',
                    'data_static.name as name',
                )
                ->where('data_static.value', '=', 'Usage')
                ->get();

            $param_location = array('dataStaticTelephone' => $data_static_telepon);
            $param_location['dataStaticMessenger'] = $data_static_messenger;
            $param_location['dataStaticUsage'] = $dataStaticUsage;

            return response()->json($param_location, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }

    public function getProvinsiLocation(Request $request)
    {

        try {

            $getProvinsi = DB::table('provinsi')
                ->select(
                    'provinsi.kodeProvinsi as id',
                    'provinsi.namaProvinsi as provinceName',
                )
                ->get();

            return response()->json($getProvinsi, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }

    public function getKabupatenLocation(Request $request)
    {

        try {

            $request->validate(['provinceCode' => 'required|max:10000']);
            $provinceId = $request->input('provinceCode');

            $data_kabupaten = DB::table('kabupaten')
                ->select(
                    'kabupaten.id as id',
                    'kabupaten.kodeKabupaten as cityCode',
                    'kabupaten.namaKabupaten as cityName'
                )
                ->where('kabupaten.kodeProvinsi', '=', $provinceId)
                ->get();

            return response()->json($data_kabupaten, 200);
        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }
    }

    public function insertdatastatic(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:255',
        ]);

        DB::beginTransaction();

        try {

            $checkIfValueExits = DB::table('data_static')
                ->where([
                    ['data_static.value', '=', $request->input('keyword')],
                    ['data_static.name', '=', $request->input('name')]
                ])
                ->first();

            if ($checkIfValueExits != null) {

                return response()->json([
                    'result' => 'Failed',
                    'message' => 'Data static already exists, please choose another keyword and name',
                ]);
            } else {

                DB::table('data_static')->insert([
                    'value' => $request->input('keyword'),
                    'name' => $request->input('name'),
                    'created_at' => now(),
                    'isDeleted' => 0,
                ]);

                DB::commit();

                return response()->json([
                    'result' => 'success',
                    'message' => 'Successfully inserted data static',
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' => $e,
            ]);
        }
    }

    public function locationList()
    {
        $Data = DB::table('location')
            ->select('id', 'locationName')
            ->where('isDeleted', '=', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($Data, 200);
    }

    public function locationListTransaction(Request $request)
    {
        $data = DB::table('location')
            ->select('id', 'locationName')
            ->where('isDeleted', '=', 0);

        if (!$request->user()->roleId == 1 || !$request->user()->roleId == 2) {
            $locations = UsersLocation::select('id')->where('usersId', $request->user()->id)->get()->pluck('id')->toArray();
            $data = $data->wherein('id', $locations);
        }
        $data = $data->orderBy('created_at', 'desc')
            ->get();

        return response()->json($data, 200);
    }

    public function locationTransferProduct(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer',
            'productName' => 'required|string',
            'productType' => 'required|string|in:productSell,productClinic',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $data = DB::table('location as l')
            ->join('productLocations as psl', 'l.id', 'psl.locationId')
            ->join('products as ps', 'ps.id', 'psl.productId')
            ->select('l.id', 'l.locationName')
            ->where('l.isDeleted', '=', 0)
            ->where('l.id', '<>', $request->locationId)
            ->where('ps.fullName', '=', $request->productName);

        if ($request->productType == 'productSell') {
            $data = $data->where('ps.category', '=', 'sell');
        } elseif ($request->productType == 'productClinic') {
            $data = $data->where('ps.category', '=', 'clinic');
        }

        $data = $data->get();
        return response()->json($data, 200);
    }

    public function locationDestination(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $data = DB::table('location as l')
            ->select('l.id as id', 'l.locationName')
            ->where('l.id', '!=', $request->locationId)
            ->get();

        return responseList($data);
    }
}
