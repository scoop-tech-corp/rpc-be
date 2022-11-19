<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroups;
use DB;
use Illuminate\Http\Request;
use Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $Data = DB::table('customerGroups')
            ->select('id', 'customerGroup')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($Data, 200);
    }

    public function create(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'customerGroup' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('customerGroups')
            ->where('customerGroup', '=', $request->customerGroup)
            ->first();

        if ($checkIfValueExits === null) {

            CustomerGroups::create([
                'customerGroup' => $request->customerGroup,
                'userId' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'message' => 'Insert Data Successful!',
                ], 200
            );
        } else {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Customer Group already exists!'],
            ], 422);

        }
    }
}
