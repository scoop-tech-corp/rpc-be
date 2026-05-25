<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function ListBatch(Request $request)
    {
        $data = [
            'RPC-BTC-001-00001',
            'RPC-BTC-001-00002',
            'RPC-BTC-001-00003',
            'RPC-BTC-002-00001',
            'RPC-BTC-002-00002',
            'RPC-BTC-002-00003',
        ];

        return response()->json($data);
    }
}
