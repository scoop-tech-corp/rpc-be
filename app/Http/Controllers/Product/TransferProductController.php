<?php

namespace App\Http\Controllers\Product;

use App\Models\ProductClinic;
use App\Models\ProductSell;
use App\Models\ProductTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use Validator;
use DB;

class TransferProductController
{
    public function transferProductNumber(Request $request)
    {
        $findData = ProductTransfer::whereDate('created_at', Carbon::today())->count();

        $number = "";

        if ($findData == 0) {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
        } else {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
        }

        return response()->json($number, 200);
    }

    public function transferProduct(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transferNumber' => 'required|string',
            'transferName' => 'required|string',
            'locationId' => 'required|integer',
            'totalItem' => 'required|integer',
            'userIdReceiver' => 'required|integer',
            'productId' => 'required|integer',
            'productCategory' => 'required|string|in:productSell,productClinic',
            'additionalCost' => 'numeric',
            'remark' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $prodDest = null;

        $findData = ProductTransfer::whereDate('created_at', Carbon::today())->count();

        $number = "";

        if ($findData == 0) {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
        } else {
            $number = Carbon::today();
            $number = 'RPC-TRF-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
        }

        //find product id destination
        if ($request->productCategory == 'productSell') {

            $prodOrigin = ProductSell::find($request->productId);

            $prodDest = DB::table('productSells as ps')
                ->join('productSellLocations as psl', 'ps.id', 'psl.productSellId')
                ->select('ps.*','psl.diffStock')
                ->where('psl.locationId', '=', $request->locationId)
                ->where('ps.fullName', '=', $prodOrigin->fullName)
                ->first();

        } elseif ($request->productCategory == 'productClinic') {

            $prodOrigin = ProductClinic::find($request->productId);

            $prodDest = DB::table('productClinics as pc')
                ->join('productClinicLocations as pcl', 'pc.id', 'pcl.productClinicId')
                ->select('pc.*','pcl.diffStock')
                ->where('pcl.locationId', '=', $request->locationId)
                ->where('pc.fullName', '=', $prodOrigin->fullName)
                ->first();
        }

        $checkAdminApproval = false;

        if ($prodDest) {

            if($prodDest->diffStock > 0){
                $checkAdminApproval = true;
            }

            ProductTransfer::create([
                'transferNumber' => $number,
                'transferName' => $request->transferName,
                'totalItem' => $request->totalItem,
                'userIdReceiver' => $request->userIdReceiver,
                'productIdOrigin' => $request->productId,
                'productIdDestination' => $prodDest->id,
                'productCategory' => $request->productCategory,
                'additionalCost' => $request->additionalCost,
                'remark' => $request->remark,
                'isAdminApproval' => $checkAdminApproval,
                'userId' => $request->user()->id,
            ]);
        } else {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Product Destination does not exist!'],
            ], 422);
        }

        return response()->json(
            [
                'message' => 'Add Data Successful!',
            ],
            200
        );
    }
}
