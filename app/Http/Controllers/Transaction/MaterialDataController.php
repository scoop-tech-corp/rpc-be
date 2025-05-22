<?php

namespace App\Http\Controllers\Transaction;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class MaterialDataController extends Controller
{
    public function index()
    {
        $data = PaymentMethod::where('isDeleted', false)->get();

        return response()->json([
            'message' => 'Success',
            'data' => $data,
        ], 200);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validate->errors()->all(),
            ], 422);
        }

        $method = PaymentMethod::create([
            'name' => $request->name,
            'userId' => $request->user()->id,
            'userUpdateId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Created successfully',
            'data' => $method,
        ], 201);
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

    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|array|min:1',
            'id.*' => 'integer'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validate->errors()->all(),
            ], 422);
        }

        foreach ($request->id as $id) {
            $method = PaymentMethod::find($id);
            if ($method && !$method->isDeleted) {
                $method->update([
                    'isDeleted' => true,
                    'deletedBy' => $request->user()->id,
                    'deletedAt' => Carbon::now(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Deleted successfully',
        ], 200);
    }
}
