<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameMaster;
use App\Models\StockOpnameUser;
use Illuminate\Http\Request;
use DB;
use Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('stock_opname_masters as sm')
            ->join('users as u', 'sm.userId', 'u.id')
            ->join('location as l', 'sm.locationId', 'l.id')
            ->select(
                'sm.id',
                'sm.stockOpnameNumber',
                'sm.title',
                DB::raw("DATE_FORMAT(sm.startTime, '%Y-%m-%d %H:%i:%s') as startTime"),
                'l.id as locationId',
                'l.locationName',
                'sm.status as statusId',
                DB::raw("case
                    when sm.status = 1 then 'Draft'
                    when sm.status = 2 then 'Process Input Data'
                    when sm.status = 3 then 'Approval Pending'
                    when sm.status = 4 then 'Approved'
                    when sm.status = 5 then 'Rejected'
                    else 'Unknown' end as statusName"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(sm.updated_at, '%d/%m/%Y') as createdAt")
            )
            ->where('sm.isDeleted', '=', 0);

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->locationId) {
            $data = $data->whereIn('sm.locationId', $request->locationId);
        }

        if ($request->status) {
            $data = $data->where('sm.status', '=', $request->status);
        }

        if ($request->orderValue) {
            if ($request->orderColumn == 'createdAt') {
                $data = $data->orderBy('sm.updated_at', $request->orderValue);
            } else {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }
        } else {
            $data = $data->orderBy('sm.updated_at', 'desc');
        }

        if ($itemPerPage) {

            $offset = ($page - 1) * $itemPerPage;

            $count_data = $data->count();
            $count_result = $count_data - $offset;

            if ($count_result < 0) {
                $data = $data->offset(0)->limit($itemPerPage)->get();
            } else {
                $data = $data->offset($offset)->limit($itemPerPage)->get();
            }

            $totalPaging = $count_data / $itemPerPage;

            return response()->json([
                'totalPagination' => ceil($totalPaging),
                'data' => $data
            ], 200);
        } else {
            $data = $data->get();
            return response()->json($data);
        }
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'title' => 'required|string',
            'stockOpnameNumber' => 'required|string',
            'startTime' => 'required|date',
            'locationId' => 'required|integer',
            'users' => 'required|array|min:1',
            'users.*' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        try {
            DB::beginTransaction();

            $stockOpname = StockOpnameMaster::create([
                'title' => $request->title,
                'stockOpnameNumber' => $request->stockOpnameNumber,
                'startTime' => $request->startTime,
                'locationId' => $request->locationId,
                'status' => 1,
                'userId' => $request->user()->id,
            ]);

            foreach ($request->users as $userId) {
                StockOpnameUser::create([
                    'stockOpnameId' => $stockOpname->id,
                    'usersId' => $userId,
                    'userId' => $request->user()->id,
                ]);
            }

            DB::commit();

            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function inputProducts(Request $request)
    {
        $validate = Validator::make($request->all(), [
            '*.stockOpnameId' => 'required|integer',
            '*.productId' => 'required|integer',
            '*.stockSystem' => 'required|numeric',
            '*.stockPhysical' => 'required|numeric',
            '*.difference' => 'required|numeric',
            '*.status' => 'required|integer',
            '*.note' => 'nullable|string|max:255',
            '*.inputedBy' => 'required|integer',
            '*.inputedAt' => 'required|date',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        try {
            DB::beginTransaction();

            foreach ($request->all() as $item) {
                StockOpnameDetail::create([
                    'stockOpnameId' => $item['stockOpnameId'],
                    'productId' => $item['productId'],
                    'stockSystem' => $item['stockSystem'],
                    'stockPhysical' => $item['stockPhysical'],
                    'difference' => $item['difference'],
                    'status' => $item['status'],
                    'note' => $item['note'] ?? null,
                    'inputedBy' => $item['inputedBy'],
                    'inputedAt' => $item['inputedAt'],
                    'userId' => $request->user()->id,
                ]);
            }

            $stockOpname = StockOpnameMaster::where('id', $request->id)->where('isDeleted', false)->first();
            if ($stockOpname) {
                $stockOpname->update([
                    'status' => 2, // Update status to "Proses Input Data" or appropriate status
                ]);
            }
            DB::commit();

            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scanBarcode(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer',
            'sku' => 'required|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $user = $request->user();

        $data = DB::table('products as p')
            ->select(
                'p.id',
                'p.category',
                'p.sku',
                'p.fullName',
                'pl.inStock',
                DB::raw("'$user->firstName' as scannedBy"),
                DB::raw('NOW() as scannedAt')
            )
            ->join('productLocations as pl', 'p.id', '=', 'pl.productId')
            ->where('p.sku', $request->sku)
            ->where('pl.locationId', $request->locationId)
            ->where('p.isDeleted', false)
            ->get();

        return response()->json($data, 200);
    }

    public function detail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|exists:stock_opname_masters,id',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $data = DB::table('stock_opname_masters as sm')
            ->join('users as u', 'sm.userId', 'u.id')
            ->join('location as l', 'sm.locationId', 'l.id')
            ->select(
                'sm.id',
                'sm.stockOpnameNumber',
                'sm.title',
                DB::raw("DATE_FORMAT(sm.startTime, '%Y-%m-%d %H:%i:%s') as startTime"),
                'l.id as locationId',
                'l.locationName',
                'sm.status as statusId',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(sm.updated_at, '%d/%m/%Y') as createdAt")
            )
            ->where('sm.isDeleted', '=', 0)
            ->where('sm.id', $request->id)
            ->first();

        $users = DB::table('stock_opname_users as su')
            ->join('users as u', 'su.usersId', 'u.id')
            ->select(
                'u.id',
                'u.firstName as name',
            )
            ->where('su.isDeleted', '=', 0)
            ->where('su.stockOpnameId', $request->id)
            ->get();

        $data->users = $users;

        $products = DB::table('stock_opname_details as sd')
            ->join('products as p', 'sd.productId', 'p.id')
            ->select(
                'sd.id',
                'sd.productId',
                'p.sku',
                'p.fullName',
                'sd.stockSystem',
                'sd.stockPhysical',
                'sd.difference',
                'sd.status',
                'sd.note',
                'sd.inputedBy',
                DB::raw("DATE_FORMAT(sd.inputedAt, '%Y-%m-%d %H:%i:%s') as inputedAt"),
                'sd.imagePath'
            )
            ->where('sd.isDeleted', '=', 0)
            ->where('sd.stockOpnameId', $request->id)
            ->get();

        $data->products = $products;

        return response()->json($data, 200);
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|exists:stock_opname_masters,id',
            'masterData' => 'required|array',
            'masterData.title' => 'required|string|max:255',
            'masterData.stockOpnameNumber' => 'required|string|max:50',
            'masterData.startTime' => 'required|date',
            'masterData.locationId' => 'required|integer|exists:location,id',
            'masterData.users' => 'required|array',
            'masterData.users.*' => 'required|integer|exists:users,id',

            'products' => 'required|array',
            'products.*.productId' => 'required|integer|exists:products,id',
            'products.*.stockSystem' => 'required|numeric|min:0',
            'products.*.stockPhysical' => 'required|numeric|min:0',
            'products.*.difference' => 'required|numeric',
            'products.*.status' => 'required|integer|in:1,2',
            'products.*.note' => 'nullable|string|max:255',
            'products.*.inputedBy' => 'required|integer|exists:users,id',
            'products.*.inputedAt' => 'required|date',
            'products.*.dataStatus' => 'required|string|in:new,existing,delete',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        DB::beginTransaction();
        try {
            $stockOpname = StockOpnameMaster::where('id', $request->id)->where('isDeleted', false)->first();

            if (!$stockOpname) {
                return responseInvalid(['Stock Opname not found.']);
            }

            $details = $request->input('products');
            $preparedDetailsToInsert = [];

            foreach ($details as $detailData) {
                // Jika ada ID, lakukan update
                if ($detailData['dataStatus'] === 'existing') {
                    $stockOpnameDetail = StockOpnameDetail::findOrFail($detailData['id']);
                    $stockOpnameDetail->update([
                        'productId' => $detailData['productId'],
                        'stockSystem' => $detailData['stockSystem'],
                        'stockPhysical' => $detailData['stockPhysical'],
                        'difference' => $detailData['stockPhysical'] - $detailData['stockSystem'],
                        'status' => $detailData['stockPhysical'] === $detailData['stockSystem'] ? 1 : 2,
                        'note' => $detailData['note'] ?? null,
                        'inputedBy' => $detailData['inputedBy'],
                        'inputedAt' => $detailData['inputedAt'],
                    ]);
                }
                // Jika tidak ada ID, siapkan untuk insert
                else if ($detailData['dataStatus'] === 'new') {
                    $preparedDetailsToInsert[] = [
                        'stockOpnameId' => $stockOpname->id,
                        'productId' => $detailData['productId'],
                        'stockSystem' => $detailData['stockSystem'],
                        'stockPhysical' => $detailData['stockPhysical'],
                        'difference' => $detailData['stockPhysical'] - $detailData['stockSystem'],
                        'status' => $detailData['stockPhysical'] === $detailData['stockSystem'] ? 1 : 2,
                        'note' => $detailData['note'] ?? null,
                        'inputedBy' => $detailData['inputedBy'],
                        'inputedAt' => $detailData['inputedAt'],
                        'userId' => $request->user()->id,
                    ];
                } else if ($detailData['dataStatus'] === 'delete') {
                    $stockOpnameDetail = StockOpnameDetail::findOrFail($detailData['id']);
                    $stockOpnameDetail->update([
                        'deletedBy' => $request->user()->id,
                        'deletedAt' => now(),
                        'isDeleted' => 1,
                    ]);
                }
            }

            if (!empty($preparedDetailsToInsert)) {
                StockOpnameDetail::insert($preparedDetailsToInsert);
            }

            DB::commit();

            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function finalizeStockOpname(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|exists:stock_opname_masters,id',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $stockOpname = StockOpnameMaster::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$stockOpname) {
            return responseInvalid(['Stock Opname not found.']);
        }

        $stockOpname->update([
            'status' => 3, // Update status to "Validated" or appropriate status
        ]);

        return responseUpdate();
    }

    public function approvalStockOpname(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|exists:stock_opname_masters,id',
            'isApproved' => 'required|boolean',
            'reason' => 'required_if:isApproved,false|nullable|string|max:255',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $stockOpname = StockOpnameMaster::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$stockOpname) {
            return responseInvalid(['Stock Opname not found.']);
        }

        if ($request->isApproved == true) {
            $stockOpname->update([
                'status' => 4, // Revert status to "In Progress" or appropriate status
                'reason' => $request->reason,
            ]);

            return responseUpdate();
        } else {
            $stockOpname->update([
                'status' => 5, // Revert status to "In Progress" or appropriate status
                'reason' => $request->reason,
            ]);

            return responseUpdate();
        }
    }

    public function delete(Request $request)
    {
        foreach ($request->id as $va) {
            $res = StockOpnameMaster::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {

            $stock = StockOpnameMaster::find($va);
            $stockUser = StockOpnameUser::where('stockOpnameId', '=', $stock->id)->get();

            if ($stockUser) {

                StockOpnameUser::where('stockOpnameId', '=', $stock->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $stockDetail = StockOpnameDetail::where('stockOpnameId', '=', $stock->id)->get();

            if ($stockDetail) {

                StockOpnameDetail::where('stockOpnameId', '=', $stock->id)
                    ->update(
                        [
                            'deletedBy' => $request->user()->id,
                            'isDeleted' => 1,
                            'deletedAt' => Carbon::now()
                        ]
                    );
            }

            $stock->update(
                [
                    'deletedBy' => $request->user()->id,
                    'isDeleted' => 1,
                    'deletedAt' => Carbon::now()
                ]
            );

            recentActivity(
                $request->user()->id,
                'Stock Opname',
                'Delete Stock Opname',
                'Deleted Stock Opname'
            );
        }

        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }

    public function export(Request $request)
    {
        $data = DB::table('stock_opname_masters as sm')
            ->join('users as u', 'sm.userId', 'u.id')
            ->join('location as l', 'sm.locationId', 'l.id')
            ->select(
                'sm.stockOpnameNumber',
                'sm.title',
                DB::raw("DATE_FORMAT(sm.startTime, '%Y-%m-%d %H:%i:%s') as startTime"),
                'l.id as locationId',
                'l.locationName',
                'sm.status',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(sm.updated_at, '%d/%m/%Y') as createdAt")
            )
            ->where('sm.isDeleted', '=', 0);

        if ($request->locationId) {
            $data = $data->whereIn('sm.locationId', $request->locationId);
        }

        if ($request->status) {
            $data = $data->where('sm.status', '=', $request->status);
        }

        $data = $data->orderBy('sm.updated_at', 'desc')->get();

        $filename = 'Stock Opname.xlsx';

        $spreadsheet = IOFactory::load(public_path() . '/template/product/Template_Export_Stock_Opname.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $item->stockOpnameNumber);
            $sheet->setCellValue("C{$row}", $item->title);
            $sheet->setCellValue("D{$row}", $item->startTime);
            $sheet->setCellValue("E{$row}", $item->locationName);
            $sheet->setCellValue("F{$row}", $item->status);
            $sheet->setCellValue("G{$row}", $item->createdBy);
            $sheet->setCellValue("H{$row}", $item->createdAt);

            $row++;
            $no++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
