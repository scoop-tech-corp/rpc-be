<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductSupplier;
use App\Models\productSupplierAddresses;
use App\Models\productSupplierEmails;
use App\Models\productSupplierMessengers;
use App\Models\productSupplierPhones;
use App\Models\productSupplierTypeMessenger;
use App\Models\productSupplierTypePhone;
use App\Models\productSupplierUsage;
use Illuminate\Http\Request;
use DB;
use Validator;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productSuppliers')
            ->select('id', 'supplierName', 'pic', 'address', 'telephone')
            ->where('isDeleted', '=', 0);

        if ($request->search) {
            $res = $this->search($request);
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

        $data = $data->orderBy('updated_at', 'desc');

        if ($request->goToPage == 0 && $request->rowPerPage == 0) {
            $data = $data->get();
            return response()->json($data, 200);
        }

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    public function create(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'supplierName' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('productSuppliers')
            ->where('supplierName', '=', $request->supplierName)
            ->first();

        if ($checkIfValueExits != null) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Supplier name already exists, please try different name!'],
            ], 422);
        }

        if ($request->addresses) {

            $resAddress = json_decode($request->addresses, true);

            $validate = Validator::make(
                $resAddress,
                [
                    '*.streetAddress' => 'required|string',
                    '*.additionalInfo' => 'nullable',
                    '*.country' => 'required|string',
                    '*.province' => 'required|integer',
                    '*.city' => 'required|integer',
                    '*.isPrimary' => 'required|bool',
                ],
                [
                    '*.streetAddress.required' => 'Street Address Should be Required!',
                    '*.streetAddress.string' => 'Street Address Should be Filled!',
                    '*.country.required' => 'Country Should be Required!',
                    '*.country.string' => 'Country Should be Filled!',
                    '*.province.required' => 'Province Should be Required!',
                    '*.province.integer' => 'Province Should be Filled!',
                    '*.city.required' => 'City Should be Required!',
                    '*.city.integer' => 'City Should be Filled!',
                    '*.isPrimary.required' => 'Is Primary Should be Required!',
                    '*.isPrimary.bool' => 'Is Primary Should be Filled!',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->first();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }
        }

        if ($request->phones) {
            $resPhones = json_decode($request->phones, true);

            $validate = Validator::make(
                $resPhones,
                [
                    '*.usageId' => 'required|integer',
                    '*.number' => 'required|integer',
                    '*.typePhoneId' => 'required|integer',
                ],
                [
                    '*.usageId.required' => 'Usage Should be Required!',
                    '*.usageId.integer' => 'Usage Should be Filled!',
                    '*.number.required' => 'Number Should be Required!',
                    '*.number.integer' => 'Number Should be Filled!',
                    '*.typePhoneId.required' => 'Type Phone Should be Required!',
                    '*.typePhoneId.integer' => 'Type Phone Should be Filled!',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->first();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }
        }

        if ($request->emails) {
            $resEmails = json_decode($request->emails, true);

            $validate = Validator::make(
                $resEmails,
                [
                    '*.usageId' => 'required|integer',
                    '*.address' => 'required|string',
                ],
                [
                    '*.usageId.required' => 'Usage Should be Required!',
                    '*.usageId.integer' => 'Usage Should be Filled!',
                    '*.address.required' => 'Email Address Should be Required!',
                    '*.address.string' => 'Email Address Should be Filled!',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->first();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }
        }

        if ($request->messengers) {
            $resMessenger = json_decode($request->messengers, true);

            $validate = Validator::make(
                $resMessenger,
                [
                    '*.usageId' => 'required|integer',
                    '*.usageName' => 'required|string',
                    '*.typeId' => 'required|integer',
                ],
                [
                    '*.usageId.required' => 'Usage Should be Required!',
                    '*.usageId.integer' => 'Usage Should be Filled!',
                    '*.usageName.required' => 'Usage Name Should be Required!',
                    '*.usageName.string' => 'Usage Name Should be Filled!',
                    '*.typeId.required' => 'Type Id Should be Required!',
                    '*.typeId.integer' => 'Type Id Should be Filled!',
                ]
            );

            if ($validate->fails()) {
                $errors = $validate->errors()->first();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }
        }

        $supp = ProductSupplier::create([
            'supplierName' => $request->supplierName,
            'pic' => $request->pic,
            'userId' => $request->user()->id,
        ]);

        if ($request->addresses) {

            $resAddress = json_decode($request->addresses, true);

            foreach ($resAddress as $valAdd) {
                productSupplierAddresses::create([
                    'productSupplierId' => $supp->id,
                    'streetAddress' => $valAdd['streetAddress'],
                    'additionalInfo' => $valAdd['additionalInfo'],
                    'country' => $valAdd['country'],
                    'province' => $valAdd['province'],
                    'city' => $valAdd['city'],
                    'postalCode' => $valAdd['postalCode'],
                    'isPrimary' => $valAdd['isPrimary'],
                    'userId' => $request->user()->id,
                ]);
            }
        }

        if ($request->phones) {
            $resPhones = json_decode($request->phones, true);

            foreach ($resPhones as $valPhone) {
                productSupplierPhones::create([
                    'productSupplierId' => $supp->id,
                    'usageId' => $valPhone['usageId'],
                    'number' => $valPhone['number'],
                    'typePhoneId' => $valPhone['typePhoneId'],
                    'userId' => $request->user()->id,
                ]);
            }
        }

        if ($request->emails) {
            $resEmails = json_decode($request->emails, true);

            foreach ($resEmails as $valEmail) {
                productSupplierEmails::create([
                    'productSupplierId' => $supp->id,
                    'usageId' => $valEmail['usageId'],
                    'address' => $valEmail['address'],
                    'userId' => $request->user()->id,
                ]);
            }
        }

        if ($request->messengers) {
            $resMsg = json_decode($request->messengers, true);

            foreach ($resMsg as $valMsg) {
                productSupplierMessengers::create([
                    'productSupplierId' => $supp->id,
                    'usageId' => $valMsg['usageId'],
                    'usageName' => $valMsg['usageName'],
                    'typeId' => $valMsg['typeId'],
                    'userId' => $request->user()->id,
                ]);
            }
        }

        return response()->json(
            [
                'message' => 'Insert Data Successful!',
            ],
            200
        );
    }

    public function detail(Request $request)
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

        $data = DB::table('productSuppliers as pss')
            ->join('users as u', 'pss.userId', 'u.id')
            ->select(
                'pss.id',
                'pss.supplierName',
                DB::raw("IFNULL(pss.pic,'') as pic"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pss.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pss.id', '=', $request->id)
            ->first();

            $dataAddress = DB::table('productSupplierAddresses as psa')
            ->where('psa.productSupplierId','=',$request->id)
            ->get();

            $data->addresses = $dataAddress;

        return response()->json($data, 200);
    }

    public function update(Request $request)
    {
    }

    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }


    }

    public function createSupplierUsage(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'usageName' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('productSupplierUsages')
            ->where('usageName', '=', $request->usageName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits === null) {

            productSupplierUsage::create([
                'usageName' => $request->usageName,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Usage name has already exists, please try different name!'],
            ], 422);
        }
    }

    public function createSupplierTypePhone(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'typeName' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('productSupplierTypePhones')
            ->where('typeName', '=', $request->typeName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits === null) {

            productSupplierTypePhone::create([
                'typeName' => $request->typeName,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Type Phone Name has already exists, please try different name!'],
            ], 422);
        }
    }

    public function createSupplierTypeMessenger(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'typeName' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('productSupplierTypeMessengers')
            ->where('typeName', '=', $request->typeName)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($checkIfValueExits === null) {

            productSupplierTypeMessenger::create([
                'typeName' => $request->typeName,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ],
                200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Messenger type name has already exists, please try different name!'],
            ], 422);
        }
    }

    public function listSupplierUsage()
    {
        $data = DB::table('productSupplierUsages')
            ->select('id', 'usageName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($data, 200);
    }
    public function listSupplierTypePhone()
    {
        $data = DB::table('productSupplierTypePhones')
            ->select('id', 'typeName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($data, 200);
    }

    public function listSupplierTypeMessenger()
    {
        $data = DB::table('productSupplierTypeMessengers')
            ->select('id', 'typeName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($data, 200);
    }
}
