<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        function buildQuery(Request $request)
        {
            $data = DB::table('task as ds')->where('ds.isDeleted', '=', 0)->join('users', 'ds.userId', '=', 'users.id');

            if ($request->search) {
                $data = $data->where('ds.name', 'like', '%' . $request->search . '%');
            }
            $data = $data->orderBy('ds.updated_at', 'desc');
            
            return $data->select('ds.id', 'ds.name', 'ds.created_at', 'ds.updated_at', DB::raw("DATE_FORMAT(ds.updated_at, '%d/%m/%Y') as createdAt"), 'users.firstName as createdBy');
        }

        $data = buildQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }
}
