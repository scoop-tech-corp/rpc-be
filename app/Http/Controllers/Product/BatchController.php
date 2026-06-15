<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class BatchController extends Controller
{
    public function ListBatch(Request $request)
    {
        $itemPerPage = $request->rowPerPage ?? 10;
        $page = $request->goToPage ?? 1;

        $data = DB::table('productBatches as pb')
            ->join('products as p', 'pb.productId', 'p.id')
            ->leftJoin('productRestocks as pr', 'pb.productRestockId', 'pr.id')
            ->leftJoin('productRestockDetails as prd', 'pb.productRestockDetailId', 'prd.id')
            ->leftJoin('users as u', 'pb.userId', 'u.id')
            ->select(
                'pb.id',
                'pb.batchNumber',
                'p.fullName as productName',
                'pb.sku',
                'pb.expiredDate',
                'pb.purchaseOrderNumber',
                'pb.purchaseRequestNumber',
                'pr.numberId as restockNumber',
                'prd.received as quantity',
                DB::raw("CONCAT(u.firstName,' ',IFNULL(u.lastName,'')) as createdBy"),
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pb.isDeleted', 0)
            ->where('pb.productRestockId', '!=', 0);

        if ($request->productId) {
            $data = $data->where('pb.productId', $request->productId);
        }

        if ($request->search) {
            $keyword = $request->search;
            $data = $data->where(function ($q) use ($keyword) {
                $q->where('pb.batchNumber', 'like', '%' . $keyword . '%')
                    ->orWhere('p.fullName', 'like', '%' . $keyword . '%')
                    ->orWhere('pr.numberId', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('pb.created_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;
        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    public function DetailBatch(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $data = DB::table('productBatches as pb')
            ->join('products as p', 'pb.productId', 'p.id')
            ->leftJoin('productRestocks as pr', 'pb.productRestockId', 'pr.id')
            ->leftJoin('productRestockDetails as prd', 'pb.productRestockDetailId', 'prd.id')
            ->leftJoin('productSuppliers as sup', 'prd.supplierId', 'sup.id')
            ->leftJoin('users as u', 'pb.userId', 'u.id')
            ->select(
                'pb.id',
                'pb.batchNumber',
                'p.id as productId',
                'p.fullName as productName',
                'pb.sku',
                'pb.expiredDate',
                'pb.purchaseOrderNumber',
                'pb.purchaseRequestNumber',
                'pr.id as productRestockId',
                'pr.numberId as restockNumber',
                'sup.supplierName',
                'prd.received as quantity',
                'prd.canceled',
                'prd.reasonCancel',
                DB::raw("CONCAT(u.firstName,' ',IFNULL(u.lastName,'')) as createdBy"),
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pb.id', $request->id)
            ->where('pb.isDeleted', 0)
            ->first();

        if (!$data) {
            return responseInvalid(['Batch not found!']);
        }

        return response()->json($data, 200);
    }

    public function ListBatchTransfer(Request $request)
    {
        $itemPerPage = $request->rowPerPage ?? 10;
        $page = $request->goToPage ?? 1;

        $data = DB::table('productBatches as pb')
            ->join('products as p', 'pb.productId', 'p.id')
            ->leftJoin('productTransfers as pt', 'pb.productTransferId', 'pt.id')
            ->leftJoin('productTransferDetails as ptd', 'pb.productTransferDetailId', 'ptd.id')
            ->leftJoin('location as locOrig', 'pt.locationIdOrigin', 'locOrig.Id')
            ->leftJoin('location as locDest', 'pt.locationIdDestination', 'locDest.Id')
            ->leftJoin('users as u', 'pb.userId', 'u.id')
            ->select(
                'pb.id',
                'pb.batchNumber',
                'p.fullName as productName',
                'pb.sku',
                'pb.expiredDate',
                'pb.transferNumber',
                'pt.numberId as transferNumberId',
                'locOrig.locationName as originLocation',
                'locDest.locationName as destinationLocation',
                'ptd.received as quantity',
                DB::raw("CONCAT(u.firstName,' ',IFNULL(u.lastName,'')) as createdBy"),
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pb.isDeleted', 0)
            ->where('pb.productTransferId', '!=', 0);

        if ($request->productId) {
            $data = $data->where('pb.productId', $request->productId);
        }

        if ($request->search) {
            $keyword = $request->search;
            $data = $data->where(function ($q) use ($keyword) {
                $q->where('pb.batchNumber', 'like', '%' . $keyword . '%')
                    ->orWhere('p.fullName', 'like', '%' . $keyword . '%')
                    ->orWhere('pb.transferNumber', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('pb.created_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;
        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    public function DetailBatchTransfer(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid([$errors]);
        }

        $data = DB::table('productBatches as pb')
            ->join('products as p', 'pb.productId', 'p.id')
            ->leftJoin('productTransfers as pt', 'pb.productTransferId', 'pt.id')
            ->leftJoin('productTransferDetails as ptd', 'pb.productTransferDetailId', 'ptd.id')
            ->leftJoin('location as locOrig', 'pt.locationIdOrigin', 'locOrig.Id')
            ->leftJoin('location as locDest', 'pt.locationIdDestination', 'locDest.Id')
            ->leftJoin('users as u', 'pb.userId', 'u.id')
            ->select(
                'pb.id',
                'pb.batchNumber',
                'p.id as productId',
                'p.fullName as productName',
                'pb.sku',
                'pb.expiredDate',
                'pb.transferNumber',
                'pt.id as productTransferId',
                'pt.numberId as transferNumberId',
                'locOrig.locationName as originLocation',
                'locDest.locationName as destinationLocation',
                'ptd.received as quantity',
                'ptd.canceled',
                'ptd.reasonCancel',
                'ptd.reference',
                DB::raw("CONCAT(u.firstName,' ',IFNULL(u.lastName,'')) as createdBy"),
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pb.id', $request->id)
            ->where('pb.isDeleted', 0)
            ->first();

        if (!$data) {
            return responseInvalid(['Batch Transfer not found!']);
        }

        return response()->json($data, 200);
    }
}
