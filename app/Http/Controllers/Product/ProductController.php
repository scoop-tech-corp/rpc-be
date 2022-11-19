<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductBrand;
use App\Models\ProductCategories;
use App\Models\ProductSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class ProductController
{
    public function IndexProductSupplier(Request $request)
    {
        $Data = DB::table('productSuppliers')
            ->select('id', 'supplierName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($Data, 200);
    }

    public function IndexProductBrand(Request $request)
    {
        $Data = DB::table('productBrands')
            ->select('id', 'brandName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($Data, 200);
    }

    public function addProductSupplier(Request $request)
    {
        try {

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
                ->first();

            if ($checkIfValueExits === null) {

                // DB::beginTransaction();

                // DB::table('product_supplier')->insert([
                //     'supplierName' => $request->supplierName,
                //     'isDeleted' => 0,
                // ]);

                // DB::commit();
                ProductSupplier::create([
                    'supplierName' => $request->supplierName,
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
                    'errors' => ['Supplier name already exists, please try different name!'],
                ], 422);
            }
        } catch (Exception $e) {

            return response()->json(
                [
                    'message' => $e,
                ],
                500
            );
        }
    }

    public function addProductBrand(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'brandName' => 'required',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            $checkIfValueExits = DB::table('productBrands')
                ->where('brandName', '=', $request->brandName)
                ->first();

            if ($checkIfValueExits === null) {

                ProductBrand::create([
                    'brandName' => $request->brandName,
                    'UserId' => $request->user()->id,
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
                    'errors' => ['Brand name already exists, please try different name!'],
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

    public function CreateProductCategory(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'categoryName' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $checkIfValueExits = DB::table('productCategories')
            ->where('CategoryName', '=', $request->categoryName)
            ->first();

        if ($checkIfValueExits === null) {

            ProductCategories::create([
                'categoryName' => $request->categoryName,
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
                'errors' => ['Category Name already exists!'],
            ], 422);
        }
    }

    public function IndexProductCategory(Request $request)
    {
        $Data = DB::table('productCategories')
            ->select('id', 'categoryName')
            ->where('isDeleted', '=', 0)
            ->get();

        return response()->json($Data, 200);
    }
}
