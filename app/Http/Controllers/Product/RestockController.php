<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductClinic;
use App\Models\ProductClinicLocation;
use App\Models\productRestockDetails;
use App\Models\productRestockImages;
use App\Models\productRestocks;
use App\Models\ProductSell;
use App\Models\ProductSellLocation;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use Validator;
use Carbon\Carbon;

class RestockController extends Controller
{
    public function index(Request $request)
    {
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'productId' => 'required|integer',
            'productType' => 'required|string|in:productSell,productClinic',
            'supplierId' => 'required|integer',
            'requireDate' => 'required|date',
            'reStockQuantity' => 'required|integer',
            'costPerItem' => 'required|numeric',
            'total' => 'required|numeric',
            'remark' => 'required|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $prodType = "";
        $checkAdminApproval = false;

        if ($request->productType == 'productSell') {

            $prodType = "Product Sell";

            $prod = ProductSell::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }

            $stockProd = ProductSellLocation::where('productSellId', '=', $request->productId)->first();
        } else {
            $prodType = "Product Clinic";

            $prod = ProductClinic::find($request->productId);

            if (!$prod) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Product does not exist!'],
                ], 422);
            }

            $stockProd = ProductClinicLocation::where('productClinicId', '=', $request->productId)->first();
        }

        if ($stockProd->reStockLimit < $request->reStockQuantity) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Restock Quantity cannot greater than Restock Limit!'],
            ], 422);
        }

        $findData = productRestocks::whereDate('created_at', Carbon::today())->count();

        $number = "";

        if ($findData == 0) {
            $number = Carbon::today();
            $number = 'RPC-PR-' . $number->format('Ymd') . str_pad(0 + 1, 5, 0, STR_PAD_LEFT);
        } else {
            $number = Carbon::today();
            $number = 'RPC-PR-' . $number->format('Ymd') . str_pad($findData + 1, 5, 0, STR_PAD_LEFT);
        }

        if ($stockProd->diffStock > 0) {
            $checkAdminApproval = true;
        }

        $prodRstk = productRestocks::create([
            'purchaseRequestNumber' => $number,
            'status' => 0,
            'isAdminApproval' => $checkAdminApproval,
            'userId' => $request->user()->id,
            //status 0 = waiting for approval, status 1 = approved, status 2 = reject, status 3 = product has arrive
        ]);

        productRestockDetails::create([

            'productRestockId' => $prodRstk->id,
            'productId' => $request->productId,
            'productType' => $prodType,
            'supplierId' => $request->supplierId,
            'requireDate' => $request->requireDate,
            'reStockQuantity' => $request->reStockQuantity,
            'costPerItem' => $request->costPerItem,
            'remark' => $request->remark,
            'userId' => $request->user()->id,
        ]);

        $count = 0;

        $flag = false;
        $res_data = [];
        $files[] = $request->file('images');

        $ResImageDatas = json_decode($request->imagesName, true);

        if ($flag == false) {

            if ($request->hasfile('images')) {

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $name = $fil->hashName();

                        $fil->move(public_path() . '/ProductRestock/', $name);

                        $fileName = "/ProductRestock/" . $name;

                        $file = new productRestockImages();
                        $file->productRestockId = $prodRstk->id;
                        $file->labelName = $ResImageDatas[$count]['name'];
                        $file->realImageName = $fil->getClientOriginalName();
                        $file->imagePath = $fileName;
                        $file->userId = $request->user()->id;
                        $file->save();

                        array_push($res_data, $file);

                        $count += 1;
                    }
                }

                $flag = true;
            }
        } else {

            foreach ($res_data as $res) {
                productRestockImages::create([
                    'productRestockId' => $prodRstk->id,
                    'labelName' => $res['labelName'],
                    'realImageName' => $res['realImageName'],
                    'imagePath' => $res['imagePath'],
                    'userId' => $request->user()->id,
                ]);
            }
        }

        return response()->json(
            [
                'message' => 'Add Data Successful!',
            ],
            200
        );
    }
}
