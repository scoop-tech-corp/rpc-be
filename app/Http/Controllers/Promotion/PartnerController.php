<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\PartnerEmail;
use App\Models\PartnerMaster;
use App\Models\PartnerMessenger;
use App\Models\PartnerPhone;
use Illuminate\Http\Request;
use Validator;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PartnerController extends Controller
{
    function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.id',
                'pm.name',
                'pm.status',
                'pp.phoneNumber',
                'pe.email',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1);

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('pm.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    private function Search($request)
    {
        $temp_column = null;

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.name'
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1);

        if ($request->search) {
            $data = $data->where('pm.name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pm.name';
        }

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pp.phoneNumber'
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1);

        if ($request->search) {
            $data = $data->where('pp.phoneNumber', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pp.phoneNumber';
        }

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pe.email'
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1);

        if ($request->search) {
            $data = $data->where('pe.email', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pe.email';
        }

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'u.firstName'
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.created_at'
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1);

        if ($request->search) {
            $data = $data->where('pm.created_at', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pm.created_at';
        }

        return $temp_column;
    }

    function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:30',
            'status' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }
        $errorPhones = $this->ValidatePhones($request->phones);

        if ($errorPhones != '') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errorPhones],
            ], 422);
        }

        $resPhone = json_decode($request->phones, true);

        $errorEmails = $this->ValidateEmails($request->emails);

        if ($errorEmails != '') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errorEmails],
            ], 422);
        }

        $resEmail = json_decode($request->emails, true);

        $errorMessengers = $this->ValidateMessengers($request->messengers);

        if ($errorMessengers != '') {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errorMessengers],
            ], 422);
        }

        $resMsg = json_decode($request->messengers, true);

        DB::beginTransaction();

        try {
            $mst =  PartnerMaster::create([
                'name' => $request->name,
                'status' => $request->status,
                'userId' => $request->user()->id,
            ]);

            foreach ($resEmail as $value) {

                PartnerEmail::create([
                    'partnerMasterId' => $mst->id,
                    'email' => $value['email'],
                    'usageId' => $value['usageId'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($resPhone as $value) {

                PartnerPhone::create([
                    'partnerMasterId' => $mst->id,
                    'phoneNumber' => $value['phoneNumber'],
                    'typeId' => $value['typeId'],
                    'usageId' => $value['usageId'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($resMsg as $value) {

                PartnerMessenger::create([
                    'partnerMasterId' => $mst->id,
                    'messengerName' => $value['messengerName'],
                    'typeId' => $value['typeId'],
                    'usageId' => $value['usageId'],
                    'userId' => $request->user()->id,
                ]);
            }

            DB::commit();
            return responseCreate();
        } catch (\Throwable $e) {
            DB::rollback();
            return responseInvalid([$e->getMessage()]);
        }
    }

    private function ValidateEmails($request)
    {
        $emails = json_decode($request, true);

        if (count($emails) > 0) {
            $validateEmails = Validator::make(
                $emails,
                [
                    '*.email' => 'required|email:rfc,dns',
                    '*.usageId' => 'required|integer|min:1',

                ],
                [
                    '*.email.required' => 'Email Should be Filled',
                    '*.email.email' => 'The email must be a valid email address.',
                    '*.usageId.integer' => 'usageId Should be Filled',

                    '*.email.required' => 'Email Should be Required',
                    '*.usageId.required' => 'Usage Should be Required',
                    '*.usageId.integer' => 'Usage Should be Filled',
                ]
            );

            if ($validateEmails->fails()) {
                $errors = $validateEmails->errors()->first();
                return $errors;
            }
        }
    }

    private function ValidatePhones($request)
    {
        $phones = json_decode($request, true);

        if (count($phones) > 0) {
            $validatePhones = Validator::make(
                $phones,
                [
                    '*.phoneNumber' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
                    '*.typeId' => 'required|integer|min:1',
                    '*.usageId' => 'required|integer|min:1',

                ],
                [
                    '*.phoneNumber.required' => 'Phone Number Should be Required',

                    '*.typeId.required' => 'Type Should be Required',
                    '*.typeId.integer' => 'Type Should be Filled',

                    '*.usageId.required' => 'Usage Should be Required',
                    '*.usageId.integer' => 'Usage Should be Filled',
                ]
            );

            if ($validatePhones->fails()) {
                $errors = $validatePhones->errors()->first();
                return $errors;
            }
        }
    }

    private function ValidateMessengers($request)
    {
        $messengers = json_decode($request, true);

        if (count($messengers) > 0) {
            $validateMessengers = Validator::make(
                $messengers,
                [
                    '*.messengerName' => 'required|string',
                    '*.typeId' => 'required|integer|min:1',
                    '*.usageId' => 'required|integer|min:1',

                ],
                [
                    '*.typeId.required' => 'Type Should be Required',
                    '*.typeId.integer' => 'Type Should be Filled',

                    '*.usageId.required' => 'Usage Should be Required',
                    '*.usageId.integer' => 'Usage Should be Filled',
                ]
            );

            if ($validateMessengers->fails()) {
                $errors = $validateMessengers->errors()->first();
                return $errors;
            }
        }
    }

    function update(Request $request) {}

    function delete(Request $request)
    {

        foreach ($request->id as $va) {
            $res = PartnerMaster::find($va);
            if (!$res) {
                responseInvalid(['There is any Data not found!']);
            }
        }

        foreach ($request->id as $va) {

            $partnerMaster = PartnerMaster::find($va);

            $partnerEmail = PartnerEmail::where('partnerMasterId', '=', $partnerMaster->id)->get();

            if ($partnerEmail) {

                PartnerEmail::where('partnerMasterId', '=', $partnerMaster->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $partnerMessenger = PartnerMessenger::where('partnerMasterId', '=', $partnerMaster->id)->get();

            if ($partnerMessenger) {

                PartnerMessenger::where('partnerMasterId', '=', $partnerMaster->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $partnerPhone = PartnerPhone::where('partnerMasterId', '=', $partnerMaster->id)->get();

            if ($partnerPhone) {

                PartnerPhone::where('partnerMasterId', '=', $partnerMaster->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $partnerMaster->DeletedBy = $request->user()->id;
            $partnerMaster->isDeleted = true;
            $partnerMaster->DeletedAt = Carbon::now();
            $partnerMaster->save();
        }
    }

    function export()
    {
        $spreadsheet = IOFactory::load(public_path() . '/template/' . 'Template_Export_Partner.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $data = DB::table('partnerMasters as pm')
            ->join('partnerPhones as pp', 'pm.id', 'pp.partnerMasterId')
            ->join('partnerEmails as pe', 'pm.id', 'pe.partnerMasterId')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.name',
                DB::raw("CASE WHEN pm.status=1 then 'Active' else 'Non Active' end as status"),
                'pp.phoneNumber',
                'pe.email',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.isDeleted', '=', 0)
            ->where('pp.usageId', '=', 1)
            ->where('pe.usageId', '=', 1)
            ->orderBy('pm.updated_at', 'desc')
            ->get();

        $row = 2;
        foreach ($data as $item) {
            // Adjust according to your data structure
            $sheet->setCellValue("A{$row}", $item->name);
            $sheet->setCellValue("B{$row}", $item->status);
            $sheet->setCellValue("C{$row}", $item->phoneNumber);
            $sheet->setCellValue("D{$row}", $item->email);
            $sheet->setCellValue("E{$row}", $item->createdBy);
            $sheet->setCellValue("F{$row}", $item->createdAt);
            // Add more columns as needed
            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Template Export Partner.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Rekap Partner.xlsx"',
        ]);
    }
}
