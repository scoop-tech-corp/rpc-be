<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Imports\RegionImport;
use App\Imports\KabupatenImport;
use Maatwebsite\Excel\Facades\Excel;

class GlobalVariableController extends Controller
{
    public function getDataStatic(Request $request)
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

    public function getProvinsi(Request $request)
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

    public function getKabupaten(Request $request)
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


    public function insertDataStatic(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:2555',
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

    //issue
    public function uploadRegion(Request $request)
    {

        try {

            if ($request->hasfile('provinsi') && $request->hasfile('kabupaten')); {

                set_time_limit(500);

                Excel::import(new RegionImport, $request->file('provinsi')->store('provinsi'));
                Excel::import(new KabupatenImport, $request->file('kabupaten')->store('kabupaten'));

                return response()->json([
                    'result' => 'success',
                    'message' => 'Success Reupload Region',
                ]);
            }
        } catch (Exception $e) {

            Excel::rollback();

            return response()->json([
                'result' => 'failed',
                'token' =>  $e,
            ]);
        }
    }
}
