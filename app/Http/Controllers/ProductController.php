<?php
namespace App\Http\Controllers;

use JWTAuth;
use App\Models\Product;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{

    public function addProductSupplier(Request $request)
    {
        try
        {
            $returnString = "";
            
            $request->validate([
                'supplierName' => 'required',
            ]);

            $checkIfValueExits = DB::table('product_supplier')
                ->where('product_supplier.supplierName', '=', $request->input('supplierName'))
                ->first();

            if ($checkIfValueExits === null) {

                DB::beginTransaction();

                DB::table('product_supplier')->insert([
                    'supplierName' => $request->input('supplierName'),
                    'isDeleted' => 0,
                ]);
                
                DB::commit();

                $returnString = 'Success input supplier';

            }else{

                $returnString ='Supplier name already exists, please try different name.. ';

            }

            return ($returnString);


        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

    }




    public function addProductBrand(Request $request)
    {
        try
        {
            $returnString = "";
            
            $request->validate([
                'brandName' => 'required',
            ]);

            $checkIfValueExits = DB::table('product_brand')
                ->where('product_brand.brandName', '=', $request->input('brandName'))
                ->first();

            if ($checkIfValueExits === null) {

                DB::beginTransaction();

                DB::table('product_brand')->insert([
                    'brandName' => $request->input('brandName'),
                    'isDeleted' => 0,
                ]);
                
                DB::commit();

                $returnString = 'Success input brand';

            }else{

                $returnString ='Brand name already exists, please try different name.. ';

            }

            return ($returnString);


        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

    }




    public function createProduct(Request $request)
    {
        DB::beginTransaction();

        try
        {

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

    // protected $user;

    // public function __construct()
    // {
    //     $this->user = JWTAuth::parseToken()->authenticate();
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function index()
    // {
    //     return $this->user
    //         ->products()
    //         ->get();
    // }

    // /**
    //  * Show the form for creating a new resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function create()
    // {
    //     //
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\Response
    //  */
    // public function store(Request $request)
    // {
    //     //Validate data
    //     $data = $request->only('name', 'sku', 'price', 'quantity');
    //     $validator = Validator::make($data, [
    //         'name' => 'required|string',
    //         'sku' => 'required',
    //         'price' => 'required',
    //         'quantity' => 'required'
    //     ]);

    //     //Send failed response if request is not valid
    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->messages()], 200);
    //     }

    //     //Request is valid, create new product
    //     $product = $this->user->products()->create([
    //         'name' => $request->name,
    //         'sku' => $request->sku,
    //         'price' => $request->price,
    //         'quantity' => $request->quantity
    //     ]);

    //     //Product created, return success response
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Product created successfully',
    //         'data' => $product
    //     ], Response::HTTP_OK);
    // }

    // /**
    //  * Display the specified resource.
    //  *
    //  * @param  \App\Models\Product  $product
    //  * @return \Illuminate\Http\Response
    //  */
    // public function show($id)
    // {
    //     $product = $this->user->products()->find($id);

    //     if (!$product) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Sorry, product not found.'
    //         ], 400);
    //     }

    //     return $product;
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  \App\Models\Product  $product
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit(Product $product)
    // {
    //     //
    // }

    // /**
    //  * Update the specified resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  \App\Models\Product  $product
    //  * @return \Illuminate\Http\Response
    //  */
    // public function update(Request $request, Product $product)
    // {
    //     //Validate data
    //     $data = $request->only('name', 'sku', 'price', 'quantity');
    //     $validator = Validator::make($data, [
    //         'name' => 'required|string',
    //         'sku' => 'required',
    //         'price' => 'required',
    //         'quantity' => 'required'
    //     ]);

    //     //Send failed response if request is not valid
    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->messages()], 200);
    //     }

    //     //Request is valid, update product
    //     $product = $product->update([
    //         'name' => $request->name,
    //         'sku' => $request->sku,
    //         'price' => $request->price,
    //         'quantity' => $request->quantity
    //     ]);

    //     //Product updated, return success response
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Product updated successfully',
    //         'data' => $product
    //     ], Response::HTTP_OK);
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  *
    //  * @param  \App\Models\Product  $product
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroy(Product $product)
    // {
    //     $product->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Product deleted successfully'
    //     ], Response::HTTP_OK);
    // }
}
