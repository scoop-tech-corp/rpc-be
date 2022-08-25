<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class DataStaticController extends Controller
{

    public function deletedatastatic(Request $request)
    {

        DB::beginTransaction();
        try
        {

            foreach ($request->deleted as $val) {

                // DB::table('location_telepon')
                // ->where('codeLocation', '=', $request->input('codeLocation'),
                //     'pemakaian', '=', $request->input('pemakaian'),
                //     'nomorTelepon', '=', $request->input('nomorTelepon'),
                //     'tipe', '=', $request->input('tipe'),
                // )
                // ->update([
                //     'isDeleted' => 1,
                // ]);

                // foreach ($request->operational_days as $val) {
                //     DB::table('location_operational_hours_details')
                //         ->where('codeLocation', '=', $request->input('codeLocation'))
                //         ->update([
                //             'days_name' => $val['days_name'],
                //             'from_time' => $val['from_time'],
                //             'to_time' => $val['to_time'],
                //             'all_day' => $val['all_day'],

                //         ]);
                // }

                DB::table('data_static')
                    ->where('value', '=', $val['value'],
                        'name', '=', $val['name'],
                    )
                    ->update([
                        'isDeleted' => 0,
                    ]);
            }

            DB::commit();

            return ('SUCCESS');
            //return back()->with('SUCCESS', 'Data has been successfully inserted');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');
            //return back()->with('ERROR', 'Your error message');
        }

        // $data = DB::table('data_static')
        //     ->select('data_static.value',
        //              'data_static.name',)
        //       ->orderBy('id', 'asc')
        //       ->get();

        // return response()->json($data, 200);

    }

    public function getindexdatastatic()
    {

        $data = DB::table('data_static')
            ->select('data_static.value',
                'data_static.name', )
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($data, 200);

    }

    public function getindexdatastaticsortid()
    {

        $data = DB::table('data_static')
            ->select('data_static.value',
                'data_static.name', )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($data, 200);

    }

    public function getindexdatastaticsortvalue()
    {

        $data = DB::table('data_static')
            ->select('data_static.value',
                'data_static.name', )
            ->orderBy('value', 'asc')
            ->get();

        return response()->json($data, 200);

    }

    public function getindexdatastaticsortname()
    {

        $data = DB::table('data_static')
            ->select('data_static.value',
                'data_static.name', )
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($data, 200);

    }

}
