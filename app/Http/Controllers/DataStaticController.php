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

                DB::table('data_static')
                    ->where('id', '=', $val['id'],)
                    ->update([
                        'isDeleted' => 1,
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

    }

    public function getindexdatastatic()
    {

        $data = DB::table('data_static')
            ->select('data_static.id',
                'data_static.value',
                'data_static.name', )
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($data, 200);

    }

    public function getindexdatastaticsortid(Request $request)
    {

        $order = $request->input('order');

        $data = DB::table('data_static')
            ->select('data_static.id',
                'data_static.value',
                'data_static.name', )
            ->orderBy('id', $order)
            ->get();

        return response()->json($data, 200);

    }

    public function getindexdatastaticsortvalue(Request $request)
    {

        $order = $request->input('order');

        $data = DB::table('data_static')
            ->select('data_static.id',
                'data_static.value',
                'data_static.name', )
            ->orderBy('value', $order)
            ->get();

        return response()->json($data, 200);

    }

    public function getindexdatastaticsortname(Request $request)
    {
        $order = $request->input('order');
        $data = DB::table('data_static')
            ->select('data_static.id',
                'data_static.value',
                'data_static.name', )
            ->orderBy('name', $order)
            ->get();

        return response()->json($data, 200);

    }

}
