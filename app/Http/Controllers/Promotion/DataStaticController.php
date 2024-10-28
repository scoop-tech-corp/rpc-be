<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\TypeIdPromotion;
use App\Models\TypeMessengerPromotion;
use App\Models\TypePhonePromotion;
use App\Models\UsageIdPromotion;
use App\Models\UsagePromotions;
use Illuminate\Http\Request;
use DB;
use Validator;
use Illuminate\Support\Carbon;

class DataStaticController extends Controller
{
    function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $dataTypePhone = DB::table('typePhonePromotions as tp')
            ->join('users as u', 'tp.userId', 'u.id')
            ->select(
                'tp.id',
                'tp.name as typeName',
                DB::raw("'phone' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tp.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('tp.isDeleted', '=', 0);

        $dataTypeMes = DB::table('typeMessengerPromotions as tp')
            ->join('users as u', 'tp.userId', 'u.id')
            ->select(
                'tp.id',
                'tp.name as typeName',
                DB::raw("'messenger' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tp.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('tp.isDeleted', '=', 0);

        $dataUsage = DB::table('usagePromotions as tp')
            ->join('users as u', 'tp.userId', 'u.id')
            ->select(
                'tp.id',
                'tp.usage as typeName',
                DB::raw("'usage' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tp.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('tp.isDeleted', '=', 0);

        $dataTypePhone = $dataTypePhone
            ->union($dataTypeMes)
            ->union($dataUsage);

        $data = DB::query()->fromSub($dataTypePhone, 'p_pn')
            ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

        $dataTemp = DB::query()->fromSub($dataTypePhone, 'p_pn')
            ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

        $temp_column = null;

        if ($request->search) {

            $data1 = $dataTemp->where('typeName', 'like', '%' . $request->search . '%')->get();

            if (count($data1)) {
                $temp_column[] = 'typeName';
            }

            $dataTemp = DB::query()->fromSub($dataTypePhone, 'p_pn')
                ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

            $data2 = $dataTemp->where('type', 'like', '%' . $request->search . '%')->get();

            if (count($data2)) {
                $temp_column[] = 'type';
            }

            $dataTemp = DB::query()->fromSub($dataTypePhone, 'p_pn')
                ->select('id', 'typeName', 'type', 'createdBy', 'createdAt');

            $data3 = $dataTemp->where('createdBy', 'like', '%' . $request->search . '%')->get();

            if (count($data3)) {
                $temp_column[] = 'createdBy';
            }

            $res = $temp_column;

            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {
                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return responseIndex(0, $data);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('createdAt', 'desc');

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

    function listTypeMessenger()
    {
        $data = DB::table('typeMessengerPromotions')
            ->select('id', 'name')
            ->get();

        return responseList($data);
    }

    function listTypePhone()
    {
        $data = DB::table('typePhonePromotions')
            ->select('id', 'name')
            ->get();

        return responseList($data);
    }

    function listUsage()
    {
        $data = DB::table('usagePromotions')
            ->select('id', 'usage')
            ->get();

        return responseList($data);
    }

    function insertTypePhone(Request $request)
    {
        try {

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

            $checkIfValueExits = DB::table('typePhonePromotions')
                ->where('name', '=', $request->typeName)
                ->first();

            if ($checkIfValueExits === null) {

                TypePhonePromotion::create([
                    'name' => $request->typeName,
                    'userId' => $request->user()->id,
                ]);


                return responseSuccess();
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Type Name already exists, please try different name!'],
                ], 422);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json(
                [
                    'message' => $e,
                ],
                500
            );
        }
    }

    function insertTypeMessenger(Request $request)
    {
        try {

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

            $checkIfValueExits = DB::table('typeMessengerPromotions')
                ->where('name', '=', $request->typeName)
                ->first();

            if ($checkIfValueExits === null) {

                TypeMessengerPromotion::create([
                    'name' => $request->typeName,
                    'userId' => $request->user()->id,
                ]);


                return responseSuccess();
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Type Name already exists, please try different name!'],
                ], 422);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json(
                [
                    'message' => $e,
                ],
                500
            );
        }
    }

    function insertUsage(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'usage' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIfValueExits = DB::table('usagePromotions')
                ->where('usage', '=', $request->usage)
                ->first();

            if ($checkIfValueExits === null) {

                UsagePromotions::create([
                    'usage' => $request->usage,
                    'userId' => $request->user()->id,
                ]);


                return responseSuccess();
            } else {

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Usage Name already exists, please try different name!'],
                ], 422);
            }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json(
                [
                    'message' => $e,
                ],
                500
            );
        }
    }

    function delete(Request $request)
    {

        $validate = Validator::make(
            $request->datas,
            [
                '*.id' => 'required|integer',
                '*.type' => 'required|string|in:type,usage',
            ],
            [
                '*.id.required' => 'Id Should be Required!',
                '*.id.integer' => 'Id Should be Integer!',

                '*.type.required' => 'Type Should be Required!',
                '*.type.string' => 'Type Should be String!',
                '*.type.in' => 'Type Should be Type or Usage!',
            ]
        );

        if ($validate->fails()) {
            $errors = $validate->errors()->first();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        foreach ($request->datas as $value) {
            if ($value['type'] == 'type') {

                TypeIdPromotion::where('id', '=', $value['id'])
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            } elseif ($value['type'] == 'usage') {
                UsageIdPromotion::where('id', '=', $value['id'])
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }
        }

        return responseDelete();
    }
}
