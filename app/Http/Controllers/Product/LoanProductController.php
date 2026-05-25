<?php

namespace App\Http\Controllers\Product;

use App\Models\LoanProduct;
use App\Models\LoanProductDetail;
use App\Models\LoanProductLog;
use App\Models\ProductLocations;
use App\Models\ProductLog;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class LoanProductController
{
    // ─────────────────────────────────────────
    // Generate Loan Number
    // ─────────────────────────────────────────
    public function generateLoanNumber(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $locationPart = str_pad($request->locationId, 3, '0', STR_PAD_LEFT);
        $prefix = "RPC-LP-{$locationPart}-";

        $lastRecord = LoanProduct::where('locationId', $request->locationId)
            ->where('loanNumber', 'like', "{$prefix}%")
            ->orderBy('loanNumber', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->loanNumber, -5);
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }

        return response()->json([
            'loanNumber' => "{$prefix}{$newNumber}",
        ], 200);
    }

    // ─────────────────────────────────────────
    // Index – list with filters
    // ─────────────────────────────────────────
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;
        $page        = $request->goToPage;

        $data = DB::table('loanProducts as lp')
            ->join('users as u', 'lp.staffId', 'u.id')
            ->join('location as l', 'lp.locationId', 'l.id')
            ->leftJoin('users as a', 'lp.approvedBy', 'a.id')
            ->select(
                'lp.id',
                'lp.loanNumber',
                'lp.staffId',
                'u.firstName as staffName',
                'lp.locationId',
                'l.locationName',
                'lp.eventName',
                'lp.eventDate',
                'lp.eventAddress',
                'lp.loanDate',
                'lp.returnDeadline',
                'lp.returnDate',
                'lp.status',
                'lp.approvedBy',
                'a.firstName as approverName',
                'lp.approvedAt',
                'lp.rejectedReason',
                'lp.totalItems',
                'lp.totalLoanedQty',
                'lp.totalSoldQty',
                'lp.totalReturnedQty',
                'lp.totalRevenue',
                'lp.note',
                'lp.returnNote',
                DB::raw("DATE_FORMAT(lp.created_at, '%d/%m/%Y %H:%i') as createdAt")
            )
            ->where('lp.isDeleted', false);

        if ($request->search) {
            $search = $request->search;
            $data   = $data->where(function ($q) use ($search) {
                $q->where('lp.loanNumber', 'like', "%{$search}%")
                    ->orWhere('lp.eventName', 'like', "%{$search}%")
                    ->orWhere('u.firstName', 'like', "%{$search}%");
            });
        }

        if ($request->locationId) {
            $data = $data->whereIn('lp.locationId', $request->locationId);
        }

        if ($request->staffId) {
            $data = $data->whereIn('lp.staffId', $request->staffId);
        }

        if ($request->status) {
            $data = $data->where('lp.status', $request->status);
        }

        if ($request->startDate && $request->endDate) {
            $data = $data->whereBetween('lp.eventDate', [$request->startDate, $request->endDate]);
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        } else {
            $data = $data->orderBy('lp.created_at', 'desc');
        }

        if ($itemPerPage) {
            $offset       = ($page - 1) * $itemPerPage;
            $count_data   = $data->count();
            $count_result = $count_data - $offset;

            if ($count_result < 0) {
                $data = $data->offset(0)->limit($itemPerPage)->get();
            } else {
                $data = $data->offset($offset)->limit($itemPerPage)->get();
            }

            $totalPaging = $count_data / $itemPerPage;

            return response()->json([
                'totalPagination' => ceil($totalPaging),
                'data'            => $data,
            ], 200);
        }

        return response()->json($data->get(), 200);
    }

    // ─────────────────────────────────────────
    // Detail
    // ─────────────────────────────────────────
    public function detail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $data = LoanProduct::with([
            'staff:id,firstName',
            'location:id,locationName',
            'approver:id,firstName',
            'details',
            'logs.user:id,firstName',
        ])
            ->where('id', $request->id)
            ->where('isDeleted', false)
            ->first();

        if (!$data) {
            return responseInvalid(['Loan Product not found!']);
        }

        return response()->json($data, 200);
    }

    // ─────────────────────────────────────────
    // Create (draft)
    // ─────────────────────────────────────────
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'loanNumber'   => 'required|string|unique:loanProducts,loanNumber',
            'staffId'      => 'required|integer',
            'locationId'   => 'required|integer',
            'eventName'    => 'required|string',
            'eventDate'    => 'required|date',
            'eventAddress' => 'nullable|string',
            'returnDeadline' => 'nullable|date',
            'note'         => 'nullable|string',
            'details'      => 'required|array|min:1',
            'details.*.productId'   => 'required|integer',
            'details.*.loanedQty'   => 'required|integer|min:1',
            'details.*.suggestedPrice' => 'nullable|numeric|min:0',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        DB::beginTransaction();
        try {
            $totalLoanedQty = 0;
            $detailsData = [];

            foreach ($request->details as $item) {
                [$product, $stockCheck] = $this->resolveProduct($item['productId'], $request->locationId);

                if (!$product) {
                    DB::rollBack();
                    return responseInvalid(["Product ID {$item['productId']} not found!"]);
                }

                if ($stockCheck !== null && $stockCheck->inStock < $item['loanedQty']) {
                    DB::rollBack();
                    return responseInvalid([
                        "Insufficient stock for product '{$product->fullName}'. Available: {$stockCheck->inStock}, Requested: {$item['loanedQty']}"
                    ]);
                }

                $totalLoanedQty += $item['loanedQty'];

                $detailsData[] = [
                    'productType'    => $product->category,
                    'productId'      => $item['productId'],
                    'productName'    => $product->fullName,
                    'sku'            => $product->sku ?? null,
                    'loanedQty'      => $item['loanedQty'],
                    'costPrice'      => $product->costPrice ?? 0,
                    'suggestedPrice' => $item['suggestedPrice'] ?? ($product->price ?? 0),
                    'returnStatus'   => 'pending',
                ];
            }

            $loan = LoanProduct::create([
                'loanNumber'     => $request->loanNumber,
                'staffId'        => $request->staffId,
                'locationId'     => $request->locationId,
                'eventName'      => $request->eventName,
                'eventDate'      => $request->eventDate,
                'eventAddress'   => $request->eventAddress,
                'returnDeadline' => $request->returnDeadline,
                'status'         => 'draft',
                'totalItems'     => count($detailsData),
                'totalLoanedQty' => $totalLoanedQty,
                'note'           => $request->note,
                'isDeleted'      => false,
                'userId'         => $request->user()->id,
            ]);

            foreach ($detailsData as $detail) {
                $detail['loanProductId'] = $loan->id;
                LoanProductDetail::create($detail);
            }

            LoanProductLog::create([
                'loanProductId' => $loan->id,
                'action'        => 'created',
                'description'   => "Loan produk dibuat dengan nomor {$loan->loanNumber}",
                'userId'        => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan Product created successfully.',
                'data'    => $loan->load('details'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Update (only when draft)
    // ─────────────────────────────────────────
    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'           => 'required|integer',
            'eventName'    => 'nullable|string',
            'eventDate'    => 'nullable|date',
            'eventAddress' => 'nullable|string',
            'returnDeadline' => 'nullable|date',
            'note'         => 'nullable|string',
            'details'      => 'nullable|array|min:1',
            'details.*.productId'   => 'required_with:details|integer',
            'details.*.loanedQty'   => 'required_with:details|integer|min:1',
            'details.*.suggestedPrice' => 'nullable|numeric|min:0',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $loan = LoanProduct::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$loan) {
            return responseInvalid(['Loan Product not found!']);
        }

        if ($loan->status !== 'draft') {
            return responseInvalid(['Only draft Loan Products can be updated.']);
        }

        DB::beginTransaction();
        try {
            $loan->update([
                'eventName'      => $request->eventName ?? $loan->eventName,
                'eventDate'      => $request->eventDate ?? $loan->eventDate,
                'eventAddress'   => $request->eventAddress ?? $loan->eventAddress,
                'returnDeadline' => $request->returnDeadline ?? $loan->returnDeadline,
                'note'           => $request->note ?? $loan->note,
                'userUpdateId'   => $request->user()->id,
            ]);

            if ($request->filled('details')) {
                LoanProductDetail::where('loanProductId', $loan->id)->delete();

                $totalLoanedQty = 0;
                foreach ($request->details as $item) {
                    [$product, $stockCheck] = $this->resolveProduct($item['productId'], $loan->locationId);

                    if (!$product) {
                        DB::rollBack();
                        return responseInvalid(["Product ID {$item['productId']} not found!"]);
                    }

                    if ($stockCheck !== null && $stockCheck->inStock < $item['loanedQty']) {
                        DB::rollBack();
                        return responseInvalid([
                            "Insufficient stock for product '{$product->fullName}'. Available: {$stockCheck->inStock}, Requested: {$item['loanedQty']}"
                        ]);
                    }

                    $totalLoanedQty += $item['loanedQty'];

                    LoanProductDetail::create([
                        'loanProductId'  => $loan->id,
                        'productType'    => $product->category,
                        'productId'      => $item['productId'],
                        'productName'    => $product->fullName,
                        'sku'            => $product->sku ?? null,
                        'loanedQty'      => $item['loanedQty'],
                        'costPrice'      => $product->costPrice ?? 0,
                        'suggestedPrice' => $item['suggestedPrice'] ?? ($product->price ?? 0),
                        'returnStatus'   => 'pending',
                    ]);
                }

                $loan->update([
                    'totalItems'     => count($request->details),
                    'totalLoanedQty' => $totalLoanedQty,
                ]);
            }

            LoanProductLog::create([
                'loanProductId' => $loan->id,
                'action'        => 'updated',
                'description'   => 'Loan produk berhasil diperbarui.',
                'userId'        => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan Product updated successfully.',
                'data'    => $loan->fresh()->load('details'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Submit (draft → pending)
    // ─────────────────────────────────────────
    public function submit(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $loan = LoanProduct::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$loan) {
            return responseInvalid(['Loan Product not found!']);
        }

        if ($loan->status !== 'draft') {
            return responseInvalid(['Only draft Loan Products can be submitted.']);
        }

        $loan->update([
            'status'       => 'pending',
            'userUpdateId' => $request->user()->id,
        ]);

        LoanProductLog::create([
            'loanProductId' => $loan->id,
            'action'        => 'submitted',
            'description'   => 'Loan produk diajukan untuk persetujuan.',
            'userId'        => $request->user()->id,
        ]);

        return response()->json(['message' => 'Loan Product submitted for approval.'], 200);
    }

    // ─────────────────────────────────────────
    // Approval (pending → approved / cancelled)
    // ─────────────────────────────────────────
    public function approval(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'             => 'required|integer',
            'isApproved'     => 'required|boolean',
            'rejectedReason' => 'required_if:isApproved,false|nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $loan = LoanProduct::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$loan) {
            return responseInvalid(['Loan Product not found!']);
        }

        if ($loan->status !== 'pending') {
            return responseInvalid(['Only pending Loan Products can be approved or rejected.']);
        }

        if ($request->isApproved) {
            $loan->update([
                'status'       => 'approved',
                'approvedBy'   => $request->user()->id,
                'approvedAt'   => Carbon::now(),
                'userUpdateId' => $request->user()->id,
            ]);

            LoanProductLog::create([
                'loanProductId' => $loan->id,
                'action'        => 'approved',
                'description'   => 'Loan produk telah disetujui.',
                'userId'        => $request->user()->id,
            ]);

            return response()->json(['message' => 'Loan Product approved.'], 200);
        } else {
            $loan->update([
                'status'         => 'cancelled',
                'rejectedReason' => $request->rejectedReason,
                'userUpdateId'   => $request->user()->id,
            ]);

            LoanProductLog::create([
                'loanProductId' => $loan->id,
                'action'        => 'rejected',
                'description'   => "Loan produk ditolak. Alasan: {$request->rejectedReason}",
                'userId'        => $request->user()->id,
            ]);

            return response()->json(['message' => 'Loan Product rejected.'], 200);
        }
    }

    // ─────────────────────────────────────────
    // Loan Out – produk keluar, deduct stock (approved → active)
    // ─────────────────────────────────────────
    public function loanOut(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'       => 'required|integer',
            'loanDate' => 'required|date',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $loan = LoanProduct::with('details')->where('id', $request->id)->where('isDeleted', false)->first();

        if (!$loan) {
            return responseInvalid(['Loan Product not found!']);
        }

        if ($loan->status !== 'approved') {
            return responseInvalid(['Only approved Loan Products can be loaned out.']);
        }

        DB::beginTransaction();
        try {
            foreach ($loan->details as $detail) {
                [$product, $stockRecord] = $this->resolveProduct($detail->productId, $loan->locationId);

                if ($stockRecord !== null) {
                    if ($stockRecord->inStock < $detail->loanedQty) {
                        DB::rollBack();
                        return responseInvalid([
                            "Insufficient stock for '{$detail->productName}'. Available: {$stockRecord->inStock}, Required: {$detail->loanedQty}"
                        ]);
                    }

                    $newStock = $stockRecord->inStock - $detail->loanedQty;
                    $stockRecord->update(['inStock' => $newStock]);

                    $this->createProductLog($detail->productId, [
                        'transaction' => 'Loan Out',
                        'remark'      => "Loan Out - {$loan->loanNumber} | Event: {$loan->eventName}",
                        'quantity'    => -$detail->loanedQty,
                        'balance'     => $newStock,
                        'userId'      => $request->user()->id,
                    ]);
                }
            }

            $loan->update([
                'status'       => 'active',
                'loanDate'     => $request->loanDate,
                'userUpdateId' => $request->user()->id,
            ]);

            LoanProductLog::create([
                'loanProductId' => $loan->id,
                'action'        => 'loaned',
                'description'   => "Produk telah dipinjamkan pada tanggal {$request->loanDate}.",
                'userId'        => $request->user()->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Products loaned out successfully. Stock deducted.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Return – input hasil penjualan & kembalikan stok (active → returned)
    // ─────────────────────────────────────────
    public function returnLoan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'         => 'required|integer',
            'returnDate' => 'required|date',
            'returnNote' => 'nullable|string',
            'details'    => 'required|array|min:1',
            'details.*.id'                 => 'required|integer',
            'details.*.soldQty'            => 'required|integer|min:0',
            'details.*.actualSellingPrice' => 'required|numeric|min:0',
            'details.*.itemNote'           => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $loan = LoanProduct::with('details')->where('id', $request->id)->where('isDeleted', false)->first();

        if (!$loan) {
            return responseInvalid(['Loan Product not found!']);
        }

        if ($loan->status !== 'active') {
            return responseInvalid(['Only active Loan Products can be returned.']);
        }

        DB::beginTransaction();
        try {
            $totalSoldQty     = 0;
            $totalReturnedQty = 0;
            $totalRevenue     = 0;

            foreach ($request->details as $item) {
                $detail = LoanProductDetail::where('id', $item['id'])
                    ->where('loanProductId', $loan->id)
                    ->first();

                if (!$detail) {
                    DB::rollBack();
                    return responseInvalid(["Detail ID {$item['id']} not found in this Loan Product."]);
                }

                if ($item['soldQty'] > $detail->loanedQty) {
                    DB::rollBack();
                    return responseInvalid([
                        "Sold qty ({$item['soldQty']}) cannot exceed loaned qty ({$detail->loanedQty}) for '{$detail->productName}'."
                    ]);
                }

                $returnedQty = $detail->loanedQty - $item['soldQty'];
                $revenue     = $item['soldQty'] * $item['actualSellingPrice'];

                $detail->update([
                    'soldQty'            => $item['soldQty'],
                    'actualSellingPrice' => $item['actualSellingPrice'],
                    'returnedQty'        => $returnedQty,
                    'revenue'            => $revenue,
                    'itemNote'           => $item['itemNote'] ?? null,
                    'returnStatus'       => 'returned',
                ]);

                // Return unsold stock back
                if ($returnedQty > 0) {
                    [$product, $stockRecord] = $this->resolveProduct($detail->productId, $loan->locationId);

                    if ($stockRecord !== null) {
                        $newStock = $stockRecord->inStock + $returnedQty;
                        $stockRecord->update(['inStock' => $newStock]);

                        $this->createProductLog($detail->productId, [
                            'transaction' => 'Loan Return',
                            'remark'      => "Loan Return - {$loan->loanNumber} | Event: {$loan->eventName} | Sold: {$item['soldQty']}, Returned: {$returnedQty}",
                            'quantity'    => $returnedQty,
                            'balance'     => $newStock,
                            'userId'      => $request->user()->id,
                        ]);
                    }
                }

                $totalSoldQty     += $item['soldQty'];
                $totalReturnedQty += $returnedQty;
                $totalRevenue     += $revenue;
            }

            $loan->update([
                'status'           => 'returned',
                'returnDate'       => $request->returnDate,
                'returnNote'       => $request->returnNote,
                'totalSoldQty'     => $totalSoldQty,
                'totalReturnedQty' => $totalReturnedQty,
                'totalRevenue'     => $totalRevenue,
                'userUpdateId'     => $request->user()->id,
            ]);

            LoanProductLog::create([
                'loanProductId' => $loan->id,
                'action'        => 'returned',
                'description'   => "Produk dikembalikan pada tanggal {$request->returnDate}. Terjual: {$totalSoldQty}, Dikembalikan: {$totalReturnedQty}, Pendapatan: {$totalRevenue}",
                'userId'        => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan Product returned successfully.',
                'data'    => $loan->fresh()->load('details'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Delete (soft delete, only draft/cancelled)
    // ─────────────────────────────────────────
    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $loan = LoanProduct::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$loan) {
            return responseInvalid(['Loan Product not found!']);
        }

        if (!in_array($loan->status, ['draft', 'cancelled'])) {
            return responseInvalid(['Only draft or cancelled Loan Products can be deleted.']);
        }

        $loan->update([
            'isDeleted'    => true,
            'deletedBy'    => $request->user()->name ?? $request->user()->id,
            'deletedAt'    => Carbon::now(),
            'userUpdateId' => $request->user()->id,
        ]);

        LoanProductLog::create([
            'loanProductId' => $loan->id,
            'action'        => 'cancelled',
            'description'   => 'Loan produk telah dihapus.',
            'userId'        => $request->user()->id,
        ]);

        return response()->json(['message' => 'Loan Product deleted successfully.'], 200);
    }

    // ─────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────

    /**
     * Resolve product and its stock record by type and locationId.
     * Returns [product, stockRecord] — stockRecord may be null if no location stock entry exists.
     */
    private function resolveProduct(int $productId, int $locationId): array
    {
        $product     = Products::find($productId);
        $stockRecord = $product
            ? ProductLocations::where('productId', $productId)
                ->where('locationId', $locationId)
                ->first()
            : null;

        return [$product, $stockRecord];
    }

    private function createProductLog(int $productId, array $data): void
    {
        ProductLog::create(array_merge(['productId' => $productId], $data));
    }
}
