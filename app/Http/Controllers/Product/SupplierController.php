<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\SupplierReport;
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
use Illuminate\Support\Carbon;
use Excel;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $idWa = productSupplierTypePhone::where('typeName', 'like', '%whatsapp%')->first();

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('productSuppliers as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->leftJoin('productSupplierAddresses as psa', 'ps.id', 'psa.productSupplierId')
            ->select(
                'ps.id',
                'ps.pic',
                'ps.supplierName',
                DB::raw("IFNULL(psa.streetAddress,'') as streetAddress"),

                DB::raw('CASE WHEN (select count(*) from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ') > 0
                THEN (select number from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ' limit 1)
                WHEN (select count(*) from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ') = 0
                THEN (select number from productSupplierPhones where productSupplierId=ps.id limit 1) END as phoneNumber'),

                DB::raw('CASE WHEN (select count(*) from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ') > 0
                THEN 1
                WHEN (select count(*) from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ') = 0
                THEN 0 END as isWhatsAppActive'),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->distinct()
            // ->where('psa.isPrimary', '=', 1)
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $res = $this->search($request, $idWa->id);
            if ($res) {
                if ($res[0] == 'psp.number') {
                    $id = DB::table('productSupplierPhones as psp')
                        ->select('psp.productSupplierId')
                        ->where('psp.number', 'like', '%' . $request->search . '%')
                        ->where('psp.isDeleted', '=', 0)
                        ->groupby('psp.productSupplierId')
                        ->distinct()
                        ->pluck('psp.productSupplierId');

                    $data = $data->whereIn('ps.id', $id);
                } else {
                    $data = $data->where($res[0], 'like', '%' . $request->search . '%');
                }

                for ($i = 1; $i < count($res); $i++) {
                    if ($res[$i] === 'psp.number') {
                        $id = DB::table('productSupplierPhones as psp')
                            ->select('psp.productSupplierId')
                            ->where('psp.number', 'like', '%' . $request->search . '%')
                            ->where('psp.isDeleted', '=', 0)
                            ->groupby('psp.productSupplierId')
                            ->distinct()
                            ->pluck('psp.productSupplierId');

                        $data = $data->orWherein('ps.id', $id);
                    } else {
                        $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                    }
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

        $data = $data->orderBy('ps.updated_at', 'desc');

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

    private function search($request, $idWa)
    {
        $temp_column = null;

        $data = DB::table('productSuppliers as ps')
            ->select(
                'ps.supplierName'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('ps.supplierName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ps.supplierName';
        }

        $data = DB::table('productSuppliers as ps')
            ->select(
                'ps.pic'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('ps.pic', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ps.pic';
        }

        $data = DB::table('productSuppliers as ps')
            ->join('productSupplierAddresses as psa', 'ps.id', 'psa.productSupplierId')
            ->select(
                'psa.streetAddress'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('psa.streetAddress', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'psa.streetAddress';
        }

        ///////////////////////

        $data = DB::table('productSuppliers as ps')
            ->join('productSupplierAddresses as psa', 'ps.id', 'psa.productSupplierId')
            ->join('productSupplierPhones as psp', 'ps.id', 'psp.productSupplierId')
            ->select(
                'psp.number'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('psp.number', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'psp.number';
        }

        return $temp_column;
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
            ->where('isDeleted', '=', 0)
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
                    '*.number' => 'required|string',
                    '*.typePhoneId' => 'required|integer',
                ],
                [
                    '*.usageId.required' => 'Usage Should be Required!',
                    '*.usageId.integer' => 'Usage Should be Integer!',
                    '*.number.required' => 'Number Should be Required!',
                    '*.number.string' => 'Number Should be String!',
                    '*.typePhoneId.required' => 'Type Phone Should be Required!',
                    '*.typePhoneId.integer' => 'Type Phone Should be Integer!',
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
            ->where('pss.isDeleted', '=', 0)
            ->first();

        $dataAddress = DB::table('productSupplierAddresses as psa')
            ->join('provinsi as p', 'p.kodeProvinsi', 'psa.province')
            ->join('kabupaten as k', 'k.kodeKabupaten', 'psa.city')
            ->select(
                'psa.id',
                'psa.productSupplierId',
                'psa.streetAddress',
                'psa.additionalInfo',
                'psa.country',
                'psa.province',
                'p.namaProvinsi as provinceName',
                'psa.city',
                'k.namaKabupaten as cityName',
                'psa.postalCode',
                'psa.isPrimary'
            )
            ->where('psa.productSupplierId', '=', $request->id)
            ->where('psa.isDeleted', '=', 0)
            ->get();

        $data->addresses = $dataAddress;

        $dataPhones = DB::table('productSupplierPhones as psp')
            ->join('productSupplierUsages as psu', 'psp.usageId', 'psu.id')
            ->join('productSupplierTypePhones as pst', 'psp.typePhoneId', 'pst.id')
            ->select(
                'psp.id',
                'psp.productSupplierId',
                'psp.usageId',
                'psu.usageName',
                'psp.number',
                'psp.typePhoneId',
                'pst.typeName',
            )
            ->where('psp.productSupplierId', '=', $request->id)
            ->where('psp.isDeleted', '=', 0)
            ->get();

        $data->phones = $dataPhones;

        $dataEmails = DB::table('productSupplierEmails as psp')
            ->join('productSupplierUsages as psu', 'psp.usageId', 'psu.id')
            ->select(
                'psp.id',
                'psp.productSupplierId',
                'psp.usageId',
                'psp.address',
                'psu.usageName as usage',
            )
            ->where('psp.productSupplierId', '=', $request->id)
            ->where('psp.isDeleted', '=', 0)
            ->get();

        $data->emails = $dataEmails;

        $dataMessengers = DB::table('productSupplierMessengers as psp')
            ->join('productSupplierUsages as psu', 'psp.usageId', 'psu.id')
            ->join('productSupplierTypeMessengers as pst', 'psp.typeId', 'pst.id')
            ->select(
                'psp.id',
                'psp.productSupplierId',
                'psp.usageId',
                'psu.usageName as usage',
                'psp.usageName',
                'psp.typeId',
                'pst.typeName',
            )
            ->where('psp.productSupplierId', '=', $request->id)
            ->where('psp.isDeleted', '=', 0)
            ->get();

        $data->messengers = $dataMessengers;

        return response()->json($data, 200);
    }

    public function update(Request $request)
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
            ->where('id', '!=', $request->id)
            ->first();

        if ($checkIfValueExits != null) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Supplier name already exists, please try different name!'],
            ], 422);
        }

        if ($request->addresses) {

            $resAddress = $request->addresses;

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
            $resPhones = $request->phones;

            $validate = Validator::make(
                $resPhones,
                [
                    '*.usageId' => 'required|integer',
                    '*.number' => 'required|integer',
                    '*.typePhoneId' => 'required|integer',
                ],
                [
                    '*.usageId.required' => 'Usage Should be Required!',
                    '*.usageId.integer' => 'Usage Should be Integer!',
                    '*.number.required' => 'Number Should be Required!',
                    '*.number.string' => 'Number Should be String!',
                    '*.typePhoneId.required' => 'Type Phone Should be Required!',
                    '*.typePhoneId.integer' => 'Type Phone Should be Integer!',
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
            $resEmails = $request->emails;

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
            $resMessenger = $request->messengers;

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

        ProductSupplier::updateOrCreate(
            ['id' => $request->id],
            [
                'supplierName' => $request->supplierName,
                'pic' => $request->pic,
                'updated_at' => Carbon::now(),
                'userUpdateId' => $request->user()->id,
                'userId' => $request->user()->id,
            ]
        );

        if ($request->addresses) {

            $resAddress = $request->addresses;

            foreach ($resAddress as $valAdd) {

                if ($valAdd['status'] == 'del') {

                    $res = productSupplierAddresses::find($valAdd['id']);

                    $res->DeletedBy = $request->user()->id;
                    $res->isDeleted = true;
                    $res->DeletedAt = Carbon::now();
                    $res->save();
                } else {
                    productSupplierAddresses::updateOrCreate(
                        ['id' => $valAdd['id']],
                        [
                            'productSupplierId' => $request->id,
                            'streetAddress' => $valAdd['streetAddress'],
                            'additionalInfo' => $valAdd['additionalInfo'],
                            'country' => $valAdd['country'],
                            'province' => $valAdd['province'],
                            'city' => $valAdd['city'],
                            'postalCode' => $valAdd['postalCode'],
                            'isPrimary' => $valAdd['isPrimary'],
                            'updated_at' => Carbon::now(),
                            'userUpdateId' => $request->user()->id,
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }

        if ($request->phones) {
            $resPhones = $request->phones;

            foreach ($resPhones as $valPhone) {

                if ($valPhone['status'] == 'del') {

                    $res = productSupplierPhones::find($valPhone['id']);

                    $res->DeletedBy = $request->user()->id;
                    $res->isDeleted = true;
                    $res->DeletedAt = Carbon::now();
                    $res->save();
                } else {
                    productSupplierPhones::updateOrCreate(
                        ['id' => $valPhone['id']],
                        [
                            'productSupplierId' => $request->id,
                            'usageId' => $valPhone['usageId'],
                            'number' => $valPhone['number'],
                            'typePhoneId' => $valPhone['typePhoneId'],
                            'updated_at' => Carbon::now(),
                            'userUpdateId' => $request->user()->id,
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }

        if ($request->emails) {
            $resEmails = $request->emails;

            foreach ($resEmails as $valEmail) {

                if ($valEmail['status'] == 'del') {

                    $res = productSupplierEmails::find($valEmail['id']);

                    $res->DeletedBy = $request->user()->id;
                    $res->isDeleted = true;
                    $res->DeletedAt = Carbon::now();
                    $res->save();
                } else {

                    productSupplierEmails::updateOrCreate(
                        ['id' => $valEmail['id']],
                        [
                            'productSupplierId' => $request->id,
                            'usageId' => $valEmail['usageId'],
                            'address' => $valEmail['address'],
                            'updated_at' => Carbon::now(),
                            'userUpdateId' => $request->user()->id,
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }

        if ($request->messengers) {
            $resMsg = $request->messengers;

            foreach ($resMsg as $valMsg) {

                if ($valMsg['status'] == 'del') {

                    $res = productSupplierMessengers::find($valMsg['id']);

                    $res->DeletedBy = $request->user()->id;
                    $res->isDeleted = true;
                    $res->DeletedAt = Carbon::now();
                    $res->save();
                } else {

                    productSupplierMessengers::updateOrCreate(
                        ['id' => $valMsg['id']],
                        [
                            'productSupplierId' => $request->id,
                            'usageId' => $valMsg['usageId'],
                            'usageName' => $valMsg['usageName'],
                            'typeId' => $valMsg['typeId'],
                            'updated_at' => Carbon::now(),
                            'userUpdateId' => $request->user()->id,
                            'userId' => $request->user()->id,
                        ]
                    );
                }
            }
        }

        return response()->json(
            [
                'message' => 'Update Data Successful!',
            ],
            200
        );
    }

    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            '.*id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        foreach ($request->id as $va) {
            $res = ProductSupplier::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {
            $res = ProductSupplier::find($va);

            $res->DeletedBy = $request->user()->id;
            $res->isDeleted = true;
            $res->DeletedAt = Carbon::now();
            $res->save();
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
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

    public function export(Request $request)
    {
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

        $fileName = "Rekap Supplier Produk " . $date . ".xlsx";

        return Excel::download(
            new SupplierReport(
                $request->orderValue,
                $request->orderColumn,
            ),
            $fileName
        );
    }
}
