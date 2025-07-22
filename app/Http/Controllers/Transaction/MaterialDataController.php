<?php

namespace App\Http\Controllers\Transaction;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\ListWeightTransaction;
use App\Models\ListTemperatureTransaction;
use App\Models\ListBreathTransaction;
use App\Models\ListSoundTransaction;
use App\Models\ListHeartTransaction;
use App\Models\ListVaginalTransaction;
use Illuminate\Support\Facades\Validator;
use DB;

class MaterialDataController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $databreath = DB::table('listBreathTransactions as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'breath' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $dataheart = DB::table('listHeartTransactions as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'heart' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $datasound = DB::table('listSoundTransactions as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'sound' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $datatemperature = DB::table('listTemperatureTransactions as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'temperature' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $datavaginal = DB::table('listVaginalTransactions as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'vaginal' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $dataweight = DB::table('listWeightTransactions as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'weight' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $datapaymentmethod = DB::table('paymentmethod as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.id',
                'ps.name',
                DB::raw("'paymentmethod' as type"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0);

        $data = $databreath
            ->union($dataheart)
            ->union($datasound)
            ->union($datatemperature)
            ->union($datavaginal)
            ->union($dataweight)
            ->union($datapaymentmethod);

        $data = DB::query()->fromSub($data, 'p_pn')
            ->select('id', 'name', 'type', 'createdBy', 'createdAt');

        $dataTemp = DB::query()->fromSub($data, 'p_pn')
            ->select('id', 'name', 'type', 'createdBy', 'createdAt');

        $temp_column = null;

        if ($request->search) {

            $data1 = $dataTemp->where('name', 'like', '%' . $request->search . '%')->get();

            if (count($data1)) {
                $temp_column[] = 'name';
            }

            $dataTemp = DB::query()->fromSub($data, 'p_pn')
                ->select('id', 'name', 'type', 'createdBy', 'createdAt');

            $data2 = $dataTemp->where('type', 'like', '%' . $request->search . '%')->get();

            if (count($data2)) {
                $temp_column[] = 'type';
            }

            $dataTemp = DB::query()->fromSub($data, 'p_pn')
                ->select('id', 'name', 'type', 'createdBy', 'createdAt');

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

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'category' => 'required|string|in:weight,temperature,breath,sound,heart,vaginal,paymentmethod',
            'name' => 'required|string'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        if ($request->category == 'weight') {
            ListWeightTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'temperature') {
            ListTemperatureTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'breath') {
            ListBreathTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'sound') {
            ListSoundTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'heart') {
            ListHeartTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'vaginal') {
            ListVaginalTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'paymentmethod') {
            PaymentMethod::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        }

        return responseCreate();
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'name' => 'required|string|max:255'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validate->errors()->all(),
            ], 422);
        }

        $method = PaymentMethod::find($request->id);
        if (!$method || $method->isDeleted) {
            return response()->json([
                'message' => 'Payment method not found.',
            ], 404);
        }

        $method->update([
            'name' => $request->name,
            'userUpdateId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $method,
        ], 200);
    }

    public function detail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validate->errors()->all(),
            ], 422);
        }

        $method = PaymentMethod::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$method) {
            return response()->json([
                'message' => 'Payment method not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $method,
        ], 200);
    }

    // public function delete(Request $request)
    // {
    //     $validate = Validator::make(
    //         $request->datas,
    //         [
    //             '*.id' => 'required|integer',
    //             '*.type' => 'required|string|in:weight,temperature,breath,sound,heart,vaginal,paymentmethod',
    //         ],
    //         [
    //             '*.id.required' => 'Id Should be Required!',
    //             '*.id.integer' => 'Id Should be Integer!',

    //             '*.type.required' => 'Type Should be Required!',
    //             '*.type.string' => 'Type Should be String!',
    //             '*.type.in' => 'Type Should be Phone, Messenger, or Usage!',
    //         ]
    //     );

    //     if ($validate->fails()) {
    //         $errors = $validate->errors()->first();

    //         return response()->json([
    //             'message' => 'The given data was invalid.',
    //             'errors' => [$errors],
    //         ], 422);
    //     }

    //     foreach ($request->datas as $value) {
    //         if ($value['type'] == 'weight') {

    //             ListWeightTransaction::where('id', '=', $value['id'])
    //                 ->update(
    //                     [
    //                         'deletedBy' => $request->user()->id,
    //                         'isDeleted' => 1,
    //                         'deletedAt' => Carbon::now()
    //                     ]
    //                 );

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Weight',
    //                 'Delete Weight data with ID ' . $value['id']
    //             );
    //         } elseif ($value['type'] == 'temperature') {
    //             ListTemperatureTransaction::where('id', '=', $value['id'])
    //                 ->update(
    //                     [
    //                         'deletedBy' => $request->user()->id,
    //                         'isDeleted' => 1,
    //                         'deletedAt' => Carbon::now()
    //                     ]
    //                 );

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Temperature',
    //                 'Delete Temperature data with ID ' . $value['id']
    //             );
    //         } elseif ($value['type'] == 'breath') {
    //             ListBreathTransaction::where('id', '=', $value['id'])
    //                 ->update([
    //                     'deletedBy' => $request->user()->id,
    //                     'isDeleted' => 1,
    //                     'deletedAt' => Carbon::now()
    //                 ]);

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Breath',
    //                 'Delete Breath data with ID ' . $value['id']
    //             );
    //         } elseif ($value['type'] == 'sound') {
    //             ListSoundTransaction::where('id', '=', $value['id'])
    //                 ->update([
    //                     'deletedBy' => $request->user()->id,
    //                     'isDeleted' => 1,
    //                     'deletedAt' => Carbon::now()
    //                 ]);

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Sound',
    //                 'Delete Sound data with ID ' . $value['id']
    //             );
    //         } elseif ($value['type'] == 'heart') {
    //             ListHeartTransaction::where('id', '=', $value['id'])
    //                 ->update([
    //                     'deletedBy' => $request->user()->id,
    //                     'isDeleted' => 1,
    //                     'deletedAt' => Carbon::now()
    //                 ]);

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Heart',
    //                 'Delete Heart data with ID ' . $value['id']
    //             );
    //         } elseif ($value['type'] == 'vaginal') {
    //             ListVaginalTransaction::where('id', '=', $value['id'])
    //                 ->update([
    //                     'deletedBy' => $request->user()->id,
    //                     'isDeleted' => 1,
    //                     'deletedAt' => Carbon::now()
    //                 ]);

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Vaginal',
    //                 'Delete Vaginal data with ID ' . $value['id']
    //             );
    //         } elseif ($value['type'] == 'paymentmethod') {
    //             PaymentMethod::where('id', '=', $value['id'])
    //                 ->update(
    //                     [
    //                         'deletedBy' => $request->user()->id,
    //                         'isDeleted' => 1,
    //                         'deletedAt' => Carbon::now()
    //                     ]
    //                 );

    //             recentActivity(
    //                 $request->user()->id,
    //                 'Material Data',
    //                 'Delete Paymentmethod',
    //                 'Delete Paymentmethod data with ID ' . $value['id']
    //             );
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Deleted successfully',
    //     ], 200);
    // }


    // public function delete(Request $request)
    // {
    //     $validate = Validator::make(
    //         $request->all(),
    //         [
    //             'datas' => 'required|array',
    //             'datas.*.id' => 'required|integer',
    //             'datas.*.type' => 'required|string|in:weight,temperature,breath,sound,heart,vaginal,paymentmethod',
    //         ],
    //         [
    //             'datas.*.id.required' => 'Id Should be Required!',
    //             'datas.*.id.integer' => 'Id Should be Integer!',
    //             'datas.*.type.required' => 'Type Should be Required!',
    //             'datas.*.type.string' => 'Type Should be String!',
    //             'datas.*.type.in' => 'Type Should be one of: weight, temperature, breath, sound, heart, vaginal, paymentmethod.',
    //         ]
    //     );

    //     if ($validate->fails()) {
    //         $errors = $validate->errors()->first();
    //         return response()->json([
    //             'message' => 'The given data was invalid.',
    //             'errors' => [$errors],
    //         ], 422);
    //     }

    //     $userId = $request->user()->id;

    //     foreach ($request->datas as $value) {
    //         $value = (array) $value;
    //         $id = $value['id'];
    //         $type = strtolower($value['type']);

    //         $models = [
    //             'weight' => ListWeightTransaction::class,
    //             'temperature' => ListTemperatureTransaction::class,
    //             'breath' => ListBreathTransaction::class,
    //             'sound' => ListSoundTransaction::class,
    //             'heart' => ListHeartTransaction::class,
    //             'vaginal' => ListVaginalTransaction::class,
    //             'paymentmethod' => PaymentMethod::class,
    //         ];

    //         if (isset($models[$type])) {
    //             $model = $models[$type];

    //             $model::where('id', $id)->update([
    //                 'deletedBy' => $userId,
    //                 'isDeleted' => 1,
    //                 'deletedAt' => now(),
    //             ]);

    //             recentActivity(
    //                 $userId,
    //                 'Material Data',
    //                 'Delete ' . ucfirst($type),
    //                 'Delete ' . ucfirst($type) . ' data with ID ' . $id
    //             );
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Deleted successfully',
    //     ], 200);
    // }

    public function delete(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            [
                'datas' => 'required|array',
                'datas.*.id' => 'required|integer',
                'datas.*.type' => 'required|string|in:weight,temperature,breath,sound,heart,vaginal,paymentmethod',
            ],
            [
                'datas.*.id.required' => 'Id Should be Required!',
                'datas.*.id.integer' => 'Id Should be Integer!',
                'datas.*.type.required' => 'Type Should be Required!',
                'datas.*.type.string' => 'Type Should be String!',
                'datas.*.type.in' => 'Type Should be one of: weight, temperature, breath, sound, heart, vaginal, paymentmethod.',
            ]
        );

        if ($validate->fails()) {
            $errors = $validate->errors()->first();
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [$errors],
            ], 422);
        }

        $userId = $request->user()->id;

        $models = [
            'weight' => ListWeightTransaction::class,
            'temperature' => ListTemperatureTransaction::class,
            'breath' => ListBreathTransaction::class,
            'sound' => ListSoundTransaction::class,
            'heart' => ListHeartTransaction::class,
            'vaginal' => ListVaginalTransaction::class,
            'paymentmethod' => PaymentMethod::class,
        ];

        $groupedDeletions = [];

        foreach ($request->datas as $value) {
            $value = (array) $value;
            $id = $value['id'];
            $type = strtolower($value['type']);

            if (isset($models[$type])) {
                $model = $models[$type];

                $model::where('id', $id)->update([
                    'deletedBy' => $userId,
                    'isDeleted' => 1,
                    'deletedAt' => now(),
                ]);

                $groupedDeletions[$type][] = $id;
            }
        }

        foreach ($groupedDeletions as $type => $ids) {
            recentActivity(
                $userId,
                'Material Data',
                'Delete ' . ucfirst($type),
                'Delete ' . ucfirst($type) . ' data with ID(s): ' . implode(', ', $ids)
            );
        }

        return response()->json([
            'message' => 'Deleted successfully',
        ], 200);
    }


    public function listPaymentMethod()
    {
        $data = PaymentMethod::where('isDeleted', false)->get();

        return responseList($data);
    }
}
