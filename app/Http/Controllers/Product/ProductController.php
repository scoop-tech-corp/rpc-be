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

            $checkIfValueExits = DB::table('ProductSuppliers')
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

            $checkIfValueExits = DB::table('ProductBrands')
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

    public function createProduct(Request $request)
    {
        DB::beginTransaction();

        try {

            // $request->validate([
            //     'fasilitasName' => 'required',
            //     'locationName' => 'required',
            //     'capacity' => 'required',
            //     'status' => 'required',
            //     'introduction' => 'required',
            //     'description' => 'required',
            // ]);

            $getvaluesp = strval(collect(DB::select('call generate_productCode'))[0]->randomString);

            //  DB::table('fasilitas')->insert([
            //             'codeFasilitas' => $getvaluesp,
            //             'fasilitasName' => $request->input('fasilitasName'),
            //             'locationName' => $request->input('locationName'),
            //             'capacity' => $request->input('capacity'),
            //             'status' => $request->input('status'),
            //             'introduction' => $request->input('introduction'),
            //             'description' => $request->input('description'),
            //             'isDeleted' => 0,
            //         ]);

            //     foreach ($request->unit as $val) {
            //         $unitname = strval(array_keys($val)[0]);

            //        foreach ($val as $key=>$asd) {

            //         foreach ($asd as $columnval) {
            //             DB::table('fasilitas_unit')->insert([
            //                 'codeFasilitas' => $getvaluesp,
            //                 'unitName' => $unitname ,
            //                 'status' => $columnval['status'],
            //                 'notes' => $columnval['notes'],
            //                 'isDeleted' => 0,
            //             ]);
            //         }

            //        }
            //     }

            DB::commit();

            return ('SUCCESS');
        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');
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
